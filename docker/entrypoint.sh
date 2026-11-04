#!/bin/sh
# LigtasAlertWEB container entrypoint (audit §1.1, §4).
#
# Runs ONCE at container start, after the image is built:
#   1. Cache config, routes, and views from the LIVE environment so cached
#      values match the deployed env. This is deliberately done here rather
#      than in the Dockerfile: config baked at build time would be stale
#      the moment any env var changes (audit M-7).
#   2. Substitute ${PORT} in nginx config (default 8080 if unset).
#   3. Validate PHP-FPM and Nginx configurations.
#   4. Start PHP-FPM and nginx under a shell supervisor that:
#      - forwards signals to both children
#      - exits when either child exits (no silent 502s)
#      - does NOT auto-restart (orchestration handles restarts)
#
# Migrations are NOT run here. Supabase is already migrated (audit §4), and
# an automatic migrate at startup risks destructive changes if a future
# migration is miswritten. Apply schema changes manually via the Supabase
# dashboard or psql, with a backup first.

set -e

cd /var/www/html

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Substitute ${PORT} with default 8080 if unset. Only PORT is substituted
# so nginx variables like $remote_addr and $host remain intact.
PORT="${PORT:-8080}"
export PORT
envsubst '${PORT}' < /etc/nginx/nginx.conf > /etc/nginx/nginx.conf.tmp \
    && mv /etc/nginx/nginx.conf.tmp /etc/nginx/nginx.conf

# Validate configurations before starting processes.
# php-fpm -t tests the configuration and exits 0 on success.
php-fpm -t >/dev/null 2>&1 || {
    echo "ERROR: PHP-FPM configuration test failed" >&2
    php-fpm -t 2>&1 | sed 's/^/  /' >&2
    exit 1
}

# nginx -t tests the configuration and exits 0 on success.
nginx -t >/dev/null 2>&1 || {
    echo "ERROR: Nginx configuration test failed" >&2
    nginx -t 2>&1 | sed 's/^/  /' >&2
    exit 1
}

# Signal handler: forward TERM/INT to both children, then exit.
# Installed before children start so we catch signals during startup.
term_handler() {
    [ -n "$FPM_PID" ] && kill -TERM "$FPM_PID" 2>/dev/null || true
    [ -n "$NGINX_PID" ] && kill -TERM "$NGINX_PID" 2>/dev/null || true
    [ -n "$FPM_PID" ] && wait "$FPM_PID" 2>/dev/null || true
    [ -n "$NGINX_PID" ] && wait "$NGINX_PID" 2>/dev/null || true
    exit 143
}
trap term_handler TERM INT

# Start PHP-FPM in the background.
php-fpm -F &
FPM_PID=$!

# Start nginx in the background.
nginx -g 'daemon off;' &
NGINX_PID=$!

# Brief startup verification: ensure both processes are still alive
# after a short grace period. This catches immediate config/runtime failures
# that config tests might miss (e.g., permission issues on sockets).
sleep 2
kill -0 "$FPM_PID" 2>/dev/null || {
    echo "ERROR: PHP-FPM exited unexpectedly during startup" >&2
    kill -TERM "$NGINX_PID" 2>/dev/null || true
    wait "$NGINX_PID" 2>/dev/null || true
    exit 1
}
kill -0 "$NGINX_PID" 2>/dev/null || {
    echo "ERROR: Nginx exited unexpectedly during startup" >&2
    kill -TERM "$FPM_PID" 2>/dev/null || true
    wait "$FPM_PID" 2>/dev/null || true
    exit 1
}

# Supervisor: detect when either child exits.
#
# We use background waiters that write exit codes to temp files.
# This avoids kill -0 polling on zombie processes — a zombie still has a
# process table entry, so kill -0 would succeed indefinitely.
# Background wait reaps the child immediately and writes the exit code.
#
# This design is POSIX-compatible and works in BusyBox ash.
FPM_EXIT_FILE=$(mktemp)
NGINX_EXIT_FILE=$(mktemp)

# Background waiters: wait for each child, write exit code to file.
( wait "$FPM_PID"; echo $? > "$FPM_EXIT_FILE" ) &
( wait "$NGINX_PID"; echo $? > "$NGINX_EXIT_FILE" ) &

# Wait for either exit file to appear.
while [ ! -f "$FPM_EXIT_FILE" ] && [ ! -f "$NGINX_EXIT_FILE" ]; do
    sleep 1
done

# Determine which child exited first and read its exit code.
EXIT_CODE=0
if [ -f "$FPM_EXIT_FILE" ]; then
    EXIT_CODE=$(cat "$FPM_EXIT_FILE" 2>/dev/null || echo 1)
    echo "PHP-FPM exited with code $EXIT_CODE" >&2
elif [ -f "$NGINX_EXIT_FILE" ]; then
    EXIT_CODE=$(cat "$NGINX_EXIT_FILE" 2>/dev/null || echo 1)
    echo "Nginx exited with code $EXIT_CODE" >&2
fi

# Terminate the other child and reap it.
[ -n "$FPM_PID" ] && kill -TERM "$FPM_PID" 2>/dev/null || true
[ -n "$NGINX_PID" ] && kill -TERM "$NGINX_PID" 2>/dev/null || true
[ -n "$FPM_PID" ] && wait "$FPM_PID" 2>/dev/null || true
[ -n "$NGINX_PID" ] && wait "$NGINX_PID" 2>/dev/null || true

# Clean up temp files.
rm -f "$FPM_EXIT_FILE" "$NGINX_EXIT_FILE" 2>/dev/null || true

# Exit with the dead child's exit code.
exit $EXIT_CODE
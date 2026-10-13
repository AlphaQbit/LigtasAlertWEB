<?php

namespace App\Models;

use Database\Factories\AlertFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'facility', 'type', 'location', 'details', 'status'])]
class Alert extends Model
{
    /** @use HasFactory<AlertFactory> */
    use HasFactory;

    const STATUS_ACTIVE = 'active';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    const STATUS_RESPONDING = 'responding';

    const STATUS_ACKNOWLEDGED = 'acknowledged';

    const STATUS_RESOLVED = 'resolved';

    /**
     * Legal status moves. A rescuer may step forward through the response
     * timeline, and may close an alert from any stage.
     *
     * @var array<string, list<string>>
     */
    public const TRANSITIONS = [
        self::STATUS_ACTIVE => [self::STATUS_RESPONDING, self::STATUS_ACKNOWLEDGED, self::STATUS_RESOLVED],
        self::STATUS_RESPONDING => [self::STATUS_ACKNOWLEDGED, self::STATUS_RESOLVED],
        self::STATUS_ACKNOWLEDGED => [self::STATUS_RESPONDING, self::STATUS_RESOLVED],
        self::STATUS_RESOLVED => [],
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Alert>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_RESPONDING, self::STATUS_ACKNOWLEDGED]);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_RESPONDING, self::STATUS_ACKNOWLEDGED], true);
    }

    public function responseSeconds(): ?int
    {
        if (! $this->acknowledged_at) {
            return null;
        }

        return $this->created_at->diffInSeconds($this->acknowledged_at);
    }

    /**
     * Apply a status change, stamping the matching timestamp and refusing
     * moves the timeline does not allow.
     */
    public function transitionTo(string $status): bool
    {
        if ($status === $this->status) {
            return false;
        }

        if (! in_array($status, self::TRANSITIONS[$this->status] ?? [], true)) {
            return false;
        }

        $this->status = $status;

        if ($status === self::STATUS_ACKNOWLEDGED && ! $this->acknowledged_at) {
            $this->acknowledged_at = now();
        }

        if ($status === self::STATUS_RESOLVED) {
            $this->resolved_at = now();
        }

        return true;
    }
}

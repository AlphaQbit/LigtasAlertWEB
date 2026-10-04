<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LigtasAlert - Sign in</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center;
               justify-content: center; background: #f9fafb; font-family: system-ui, sans-serif; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
                padding: 2.5rem 2rem; max-width: 22rem; text-align: center;
                box-shadow: 0 10px 24px rgba(0,0,0,.06); }
        .dot { width: 56px; height: 56px; border-radius: 50%; background: #ef44441a;
               margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; }
        .dot::after { content: ""; width: 14px; height: 14px; border-radius: 50%; background: #ef4444; }
        h1 { font-size: 1.25rem; margin: 0 0 .5rem; color: #1f2937; }
        p { color: #6b7280; font-size: .95rem; margin: 0; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="card">
        <div class="dot"></div>
        <h1>LigtasAlert</h1>
        <p>{{ $message }}</p>
    </div>
</body>
</html>
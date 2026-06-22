<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BL Tracking Demo</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: #f6f7f9;
            color: #17202a;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            display: grid;
            place-items: center;
        }

        main {
            width: min(720px, calc(100% - 32px));
            background: #fff;
            border: 1px solid #d9e0e8;
            border-radius: 8px;
            padding: 28px;
        }

        h1 {
            margin: 0 0 10px;
            font-size: clamp(30px, 5vw, 46px);
            line-height: 1.1;
            letter-spacing: 0;
        }

        p {
            color: #627084;
            font-size: 17px;
            margin: 0 0 24px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        a {
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 750;
            min-height: 42px;
            padding: 10px 14px;
            text-decoration: none;
        }

        .primary {
            background: #0f766e;
            color: #fff;
        }

        .secondary {
            background: #eef2f6;
            color: #17202a;
        }
    </style>
</head>
<body>
    <main>
        <h1>BL Tracking Demo</h1>
        <p>Use the admin dashboard to manage customers and BL records, or use the customer login to view assigned shipments with OTP access.</p>
        <div class="actions">
            <a class="primary" href="{{ url('/admin') }}">Admin Dashboard</a>
            <a class="secondary" href="{{ route('customer.login') }}">Customer Login</a>
        </div>
    </main>
</body>
</html>

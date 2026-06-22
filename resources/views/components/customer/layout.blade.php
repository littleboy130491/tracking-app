<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Customer BL Tracking' }}</title>
    <link rel="stylesheet" href="{{ asset('css/customer.css') }}">
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="{{ route('customer.dashboard') }}">BL Tracking</a>

            @auth
                <form method="POST" action="{{ route('customer.logout') }}">
                    @csrf
                    <button class="logout" type="submit">Log out</button>
                </form>
            @endauth
        </div>
    </header>

    <main class="page">
        {{ $slot }}
    </main>

    <script src="{{ asset('js/customer.js') }}" defer></script>
</body>
</html>

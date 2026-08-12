<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Pelacakan BL Pelanggan' }}</title>
    <link rel="stylesheet" href="{{ asset('css/customer.css') }}">
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="{{ route('customer.dashboard') }}">BL Tracking</a>

            @auth
                <form method="POST" action="{{ route('customer.logout') }}">
                    @csrf
                    <button class="logout" type="submit">Keluar</button>
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

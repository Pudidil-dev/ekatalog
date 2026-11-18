<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') · E-Katalog</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body">
<a class="skip-link" href="#main">Langsung ke konten</a>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="brand" href="{{ route('admin.products.index') }}"><span class="brand-mark">◇</span>E-Katalog</a>
        <p class="eyebrow">RUANG ADMIN</p>
        <nav aria-label="Menu admin"><x-admin.nav-links /></nav>
        <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="button neutral wide">Keluar</button></form>
    </aside>
    <div class="admin-workspace">
        <header class="admin-header">
            <a class="admin-header-brand" href="{{ route('admin.products.index') }}">
                <span class="brand-mark" aria-hidden="true">◇</span>
                <span>Pengelolaan katalog</span>
            </a>
            <span class="admin-user">{{ auth()->user()->name }}</span>
            {{-- <details> works without JS; Alpine only adds close-on-outside-click/Escape. --}}
            <details class="admin-mobile-nav" x-data @click.outside="$el.open = false" @keydown.escape.window="$el.open = false">
                <summary aria-label="Buka menu admin">Menu</summary>
                <div class="admin-mobile-panel">
                    <p class="admin-mobile-user">Masuk sebagai <strong>{{ auth()->user()->name }}</strong></p>
                    <nav aria-label="Menu admin mobile"><x-admin.nav-links /></nav>
                    <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="button neutral wide">Keluar</button></form>
                </div>
            </details>
        </header>
        <main id="main" class="admin-content">
            @if(session('success'))<div class="notice success" role="status">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="notice error" role="alert"><strong>Periksa kembali isian Anda.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>

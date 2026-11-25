<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#ec4899">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Beranda') · DCFinance</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>💗</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    @stack('head')
</head>
<body>
@php
    $me = auth()->user();
    $nav = [
        ['dashboard', 'Beranda', 'home', 'dashboard'],
        ['transactions.index', 'Riwayat', 'list', 'transactions.*'],
        ['goals.index', 'Target', 'target', 'goals.*'],
        ['couple', 'Kita Berdua', 'heart', 'couple'],
        ['exchange.create', 'Tukar Uang', 'swap', 'exchange.*'],
        ['rates.index', 'Kurs AUD', 'globe', 'rates.*'],
    ];
@endphp
<div class="app">
    <aside class="sidebar">
        <a href="{{ route('dashboard') }}" class="brand">
            <div class="brand-logo">DC</div>
            <div class="brand-name">DC<span>Finance</span></div>
        </a>
        <nav class="nav">
            @foreach ($nav as [$route, $label, $icon, $pattern])
                <a href="{{ route($route) }}" class="{{ request()->routeIs($pattern) ? 'active' : '' }}">
                    @include('partials.icon', ['n' => $icon]) {{ $label }}
                </a>
            @endforeach
            <div class="nav-label">Akun</div>
            <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">@include('partials.icon', ['n' => 'user']) Profil</a>
        </nav>

        <div class="sidebar-foot">
            <a href="{{ route('transactions.create') }}" class="btn btn-primary btn-block">@include('partials.icon', ['n' => 'plus']) Catat Tabungan</a>
            <a href="{{ route('rates.index') }}" class="rate-chip">
                @include('partials.cur', ['c' => 'AUD'])
                <div>
                    <div class="xs muted">1 AUD hari ini</div>
                    <div class="bold num">Rp {{ rate_fmt($globalRate['rate']) }}</div>
                </div>
                @if ($globalRate['mode'] === 'manual')<span class="badge badge-amber" style="margin-left:auto">manual</span>@endif
            </a>
            <div class="user-chip">
                <div class="avatar" style="background: {{ $me->color }}22">{{ $me->emoji }}</div>
                <div style="min-width:0">
                    <div class="bold ellipsis">{{ $me->display_name }}</div>
                    <div class="xs muted ellipsis">{{ $me->email }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="btn btn-ghost icon-btn" title="Keluar">@include('partials.icon', ['n' => 'logout'])</button>
                </form>
            </div>
        </div>
    </aside>

    <main class="main">
        <header class="mobile-top">
            <a href="{{ route('dashboard') }}" class="brand">
                <div class="brand-logo">DC</div>
                <div class="brand-name">DC<span>Finance</span></div>
            </a>
            <details class="menu">
                <summary><div class="avatar" style="background: {{ $me->color }}22">{{ $me->emoji }}</div></summary>
                <div class="menu-body">
                    <div style="padding:8px 12px 10px"><div class="bold">{{ $me->display_name }}</div><div class="xs muted">1 AUD = Rp {{ rate_fmt($globalRate['rate']) }}</div></div>
                    <a href="{{ route('exchange.create') }}">@include('partials.icon', ['n' => 'swap']) Tukar Uang</a>
                    <a href="{{ route('rates.index') }}">@include('partials.icon', ['n' => 'globe']) Kurs AUD</a>
                    <a href="{{ route('profile.edit') }}">@include('partials.icon', ['n' => 'user']) Profil</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button>@include('partials.icon', ['n' => 'logout']) Keluar</button></form>
                </div>
            </details>
        </header>

        <div class="container">
            @yield('content')
        </div>
    </main>

    <nav class="bottom-nav">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">@include('partials.icon', ['n' => 'home'])Beranda</a>
        <a href="{{ route('transactions.index') }}" class="{{ request()->routeIs('transactions.index') ? 'active' : '' }}">@include('partials.icon', ['n' => 'list'])Riwayat</a>
        <a href="{{ route('transactions.create') }}" aria-label="Catat tabungan"><span class="fab">@include('partials.icon', ['n' => 'plus'])</span></a>
        <a href="{{ route('goals.index') }}" class="{{ request()->routeIs('goals.*') ? 'active' : '' }}">@include('partials.icon', ['n' => 'target'])Target</a>
        <a href="{{ route('couple') }}" class="{{ request()->routeIs('couple') ? 'active' : '' }}">@include('partials.icon', ['n' => 'heart'])Berdua</a>
    </nav>
</div>

@include('partials.flash')

<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
@stack('scripts')
</body>
</html>

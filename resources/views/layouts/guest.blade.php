<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ec4899">
    <title>@yield('title') · DCFinance</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>💗</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body>
<div class="auth-wrap">
    <section class="auth-art">
        <div class="brand" style="color:#fff;padding:0">
            <div class="brand-logo" style="background:#fff;color:var(--acc-600)">DC</div>
            <div class="brand-name" style="color:#fff">DCFinance</div>
        </div>
        <div>
            <h2>Tabungan kita, mimpi kita. 💗</h2>
            <p>Catat tabungan masing-masing dalam Rupiah atau mata uang apa pun, lalu kejar target bareng-bareng.</p>
            <div class="floaty">
                <div>Rupiah</div>
                <div>150+ mata uang</div>
                <div>Target</div>
                <div>Berdua</div>
            </div>
        </div>
        <div class="small" style="opacity:.8">Dibuat dengan cinta · {{ date('Y') }}</div>
    </section>
    <section class="auth-form">
        <div class="auth-card">
            @yield('content')
        </div>
    </section>
</div>
@include('partials.flash')
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
</body>
</html>

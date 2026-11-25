@extends('layouts.guest')
@section('title', 'Masuk')

@section('content')
    <h1>Hai lagi! 👋</h1>
    <p class="muted mt-1">Masuk untuk lihat tabunganmu.</p>

    <form method="POST" action="{{ route('login.attempt') }}" class="form mt-4">
        @csrf
        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="input @error('email') is-invalid @enderror" required autofocus autocomplete="username">
            @error('email')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="password">Kata sandi</label>
            <input id="password" type="password" name="password" class="input" required autocomplete="current-password">
        </div>
        <label class="row small" style="cursor:pointer">
            <input type="checkbox" name="remember" value="1" checked style="accent-color:var(--pink-500);width:16px;height:16px"> Ingat saya di perangkat ini
        </label>
        <button class="btn btn-primary btn-lg btn-block">Masuk</button>
    </form>

    @if ($registrationOpen)
        <div class="alert mt-4 small">
            Belum punya akun? <a href="{{ route('register') }}" class="bold">Daftar di sini</a>
            <div class="muted xs mt-1">Pendaftaran terbuka sampai {{ config('dcfinance.max_users') }} akun (kamu & pasangan), lalu otomatis ditutup.</div>
        </div>
    @endif
@endsection

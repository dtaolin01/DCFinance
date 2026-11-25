@extends('layouts.app')
@section('title', 'Profil & Tema')

@section('content')
<div class="page-head">
    <div>
        <h1>Profil & tema</h1>
        <p>Atur nama, warna tampilan, dan target menabung bulananmu.</p>
    </div>
</div>

<div class="grid g-main">
    <form method="POST" action="{{ route('profile.update') }}" class="card form">
        @csrf @method('PUT')

        <div class="field">
            <span class="label">Tema warna web</span>
            <div class="themes">
                @foreach (config('themes') as $key => $t)
                    <label>
                        <input type="radio" name="theme" value="{{ $key }}" @checked(old('theme', $user->themeKey()) === $key) data-theme-pick='@json($t['vars'])'>
                        <span class="theme-opt">
                            <span class="sw"><i style="background: {{ $t['vars']['acc-500'] }}"></i><i style="background: {{ $t['vars']['acc-200'] }}"></i><i style="background: #fff"></i></span>
                            <b>{{ $t['label'] }}</b>
                        </span>
                    </label>
                @endforeach
            </div>
            <div class="hint">Klik untuk melihat pratinjau, lalu Simpan. Tema hanya berlaku untuk akunmu — pasanganmu bisa memilih tema sendiri.</div>
        </div>

        <div class="divider"></div>

        <div class="grid g-2" style="gap:14px">
            <div class="field">
                <label for="name">Nama lengkap</label>
                <input id="name" name="name" class="input @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                @error('name')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="nickname">Panggilan</label>
                <input id="nickname" name="nickname" class="input" value="{{ old('nickname', $user->nickname) }}">
            </div>
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" class="input @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
            @error('email')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <span class="label">Emoji kamu</span>
            <div class="pick">
                @foreach (['🌸', '🌷', '🐰', '🐻', '🦊', '🐱', '🐶', '🐨', '🦋', '⭐', '🍓', '🧸'] as $e)
                    <label><input type="radio" name="emoji" value="{{ $e }}" @checked(old('emoji', $user->emoji) === $e)><span>{{ $e }}</span></label>
                @endforeach
            </div>
        </div>
        <div class="field">
            <span class="label">Warna penanda kamu</span>
            <div class="pick swatch">
                @foreach (config('dcfinance.colors') as $c)
                    <label><input type="radio" name="color" value="{{ $c }}" @checked(old('color', $user->color) === $c)><span style="background: {{ $c }}"></span></label>
                @endforeach
            </div>
            <div class="hint">Membedakan kamu dan pasangan di halaman Kita Berdua dan target bersama.</div>
        </div>
        <div class="field">
            <label for="monthly_target">Target menabung per bulan</label>
            <div class="input-money sm"><span class="prefix">Rp</span>
                <input id="monthly_target" name="monthly_target" class="input num" inputmode="numeric" data-money data-currency="IDR" value="{{ old('monthly_target', $user->monthly_target ? number_format($user->monthly_target, 0, ',', '.') : '') }}" placeholder="0">
            </div>
            <div class="hint">Kosongkan / 0 jika tidak ingin memakai target bulanan.</div>
        </div>
        <button class="btn btn-primary btn-lg btn-block">Simpan profil & tema</button>
    </form>

    <form method="POST" action="{{ route('profile.password') }}" class="card form">
        @csrf @method('PUT')
        <div class="card-head" style="margin-bottom:0"><h3>@include('partials.tile', ['n' => 'lock']) Ganti kata sandi</h3></div>
        <div class="field">
            <label for="current_password">Kata sandi lama</label>
            <input id="current_password" type="password" name="current_password" class="input @error('current_password') is-invalid @enderror" required autocomplete="current-password">
            @error('current_password')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="password">Kata sandi baru</label>
            <input id="password" type="password" name="password" class="input @error('password') is-invalid @enderror" required autocomplete="new-password">
            @error('password')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="password_confirmation">Ulangi kata sandi baru</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password">
        </div>
        <button class="btn btn-soft btn-block">Ganti kata sandi</button>
    </form>
</div>
@endsection

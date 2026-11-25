@extends('layouts.guest')
@section('title', 'Daftar')

@section('content')
    <h1>{{ $isFirst ? 'Buat akun pertama ✨' : 'Daftar akun 💞' }}</h1>
    <p class="muted mt-1">{{ $isFirst ? 'Akun pertama otomatis menjadi admin dan langsung aktif.' : 'Setelah mendaftar, akunmu perlu disetujui pemilik akun utama dulu.' }}</p>

    <form method="POST" action="{{ route('register.store') }}" class="form mt-4">
        @csrf
        <div class="grid g-2" style="gap:12px">
            <div class="field">
                <label for="name">Nama lengkap</label>
                <input id="name" name="name" value="{{ old('name') }}" class="input @error('name') is-invalid @enderror" required>
                @error('name')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="nickname">Panggilan</label>
                <input id="nickname" name="nickname" value="{{ old('nickname') }}" class="input" placeholder="opsional">
            </div>
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="input @error('email') is-invalid @enderror" required autocomplete="username">
            @error('email')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="password">Kata sandi</label>
            <input id="password" type="password" name="password" class="input @error('password') is-invalid @enderror" required autocomplete="new-password">
            @error('password')<div class="error">{{ $message }}</div>@else<div class="hint">Minimal 8 karakter.</div>@enderror
        </div>
        <div class="field">
            <label for="password_confirmation">Ulangi kata sandi</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password">
        </div>
        <button class="btn btn-primary btn-lg btn-block">{{ $isFirst ? 'Daftar & Masuk' : 'Kirim Pendaftaran' }}</button>
    </form>
    <p class="small center mt-3">Sudah punya akun? <a href="{{ route('login') }}" class="bold">Masuk</a></p>
@endsection

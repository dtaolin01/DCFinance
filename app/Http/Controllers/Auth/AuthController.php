<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login', ['registrationOpen' => User::registrationOpen()]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $key = Str::lower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }

        if (! Auth::attempt($data, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi salah.']);
        }
        RateLimiter::clear($key);

        if (! Auth::user()->isApproved()) {
            Auth::logout();

            return back()->withInput($request->only('email'))
                ->with('error', 'Akunmu belum disetujui. Minta pemilik akun utama menyetujuinya dulu ya.');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister()
    {
        if (! User::registrationOpen()) {
            return redirect()->route('login')->with('error', 'Pendaftaran sedang ditutup — akun sudah lengkap.');
        }

        return view('auth.register', ['isFirst' => User::count() === 0]);
    }

    public function register(Request $request)
    {
        abort_unless(User::registrationOpen(), 403, 'Pendaftaran sudah ditutup.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'nickname' => ['nullable', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $isFirst = User::count() === 0;
        $user = User::create($data + [
            'color' => $isFirst ? '#ec4899' : '#a855f7',
            'emoji' => $isFirst ? '🌸' : '🌷',
            'theme' => 'pink',
        ]);
        // Akun pertama otomatis jadi admin & aktif; akun berikutnya menunggu persetujuan.
        $user->forceFill(['is_admin' => $isFirst, 'approved_at' => $isFirst ? now() : null])->save();

        if (! $isFirst) {
            return redirect()->route('login')->with('success', 'Pendaftaran terkirim! Tunggu disetujui pemilik akun utama, lalu masuk di sini.');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Selamat datang, '.$user->display_name.'! Kamu adalah admin DCFinance.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

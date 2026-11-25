<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Akun yang belum disetujui admin tidak boleh masuk (termasuk lewat "ingat saya"). */
class EnsureApproved
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && ! $user->isApproved()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Akunmu belum disetujui oleh pemilik akun utama.');
        }

        return $next($request);
    }
}

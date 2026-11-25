<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

/** Persetujuan akun baru — hanya untuk admin (akun pertama). */
class AccountController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        return view('accounts.index', [
            'pending' => User::pending()->orderBy('created_at')->get(),
            'active' => User::approved()->orderBy('id')->get(),
            'max' => config('dcfinance.max_users', 2),
        ]);
    }

    public function approve(Request $request, User $user)
    {
        $this->authorizeAdmin($request);
        abort_if($user->isApproved(), 422);

        if (User::approved()->count() >= config('dcfinance.max_users', 2)) {
            return back()->with('error', 'Akun aktif sudah penuh ('.config('dcfinance.max_users', 2).'). Naikkan DC_MAX_USERS di .env jika perlu.');
        }

        $user->forceFill(['approved_at' => now()])->save();

        return back()->with('success', $user->display_name.' disetujui dan sekarang bisa masuk.');
    }

    public function reject(Request $request, User $user)
    {
        $this->authorizeAdmin($request);
        abort_if($user->isApproved(), 422, 'Akun aktif tidak bisa ditolak.');

        $name = $user->display_name;
        $user->delete();

        return back()->with('success', 'Pendaftaran '.$name.' ditolak dan dihapus.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->is_admin, 403, 'Hanya pemilik akun utama yang bisa mengatur persetujuan.');
    }
}

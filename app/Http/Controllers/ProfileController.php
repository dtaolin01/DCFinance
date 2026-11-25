<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $request->merge(['monthly_target' => parse_amount($request->input('monthly_target')) ?? 0]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'nickname' => ['nullable', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'emoji' => ['required', 'string', 'max:16'],
            'monthly_target' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
        ]);
        $data['monthly_target'] = (int) round($data['monthly_target'] ?? 0);

        $user->update($data);

        return back()->with('success', 'Profil disimpan 🌸');
    }

    public function password(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], ['current_password.current_password' => 'Kata sandi lama salah.']);

        $request->user()->update(['password' => Hash::make($request->input('password'))]);

        return back()->with('success', 'Kata sandi diganti 🔒');
    }
}

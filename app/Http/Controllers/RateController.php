<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;

class RateController extends Controller
{
    public function index(ExchangeRateService $rates)
    {
        return view('rates.index', [
            'rate' => $rates->current(),
            'manual' => Setting::get('rate_manual', ['enabled' => false, 'rate' => null]),
            'last' => Setting::get('rate_last'),
        ]);
    }

    public function update(Request $request, ExchangeRateService $rates)
    {
        $request->merge(['manual_rate' => parse_amount($request->input('manual_rate'))]);
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'manual_rate' => ['nullable', 'numeric', 'between:1000,100000', 'required_if:enabled,1'],
        ], ['manual_rate.required_if' => 'Isi kurs manual dulu.', 'manual_rate.between' => 'Kurs harus antara 1.000 dan 100.000.']);

        Setting::put('rate_manual', [
            'enabled' => $request->boolean('enabled'),
            'rate' => $data['manual_rate'] ?? null,
            'updated_at' => now()->toIso8601String(),
        ]);
        $rates->refresh();

        return back()->with('success', $request->boolean('enabled') ? 'Kurs manual diaktifkan.' : 'Kembali memakai kurs online.');
    }

    public function refresh(ExchangeRateService $rates)
    {
        $r = $rates->refresh();

        return back()->with($r['mode'] === 'live' ? 'success' : 'error', $r['mode'] === 'live'
            ? 'Kurs diperbarui: 1 AUD = Rp '.rate_fmt($r['rate'])
            : 'Gagal mengambil kurs online, memakai '.$r['source'].'.');
    }
}

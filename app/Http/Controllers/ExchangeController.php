<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\ExchangeRateService;
use App\Services\SavingsCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Tukar mata uang di dalam tabungan sendiri (mis. Rupiah di bank -> AUD cash).
 * Tidak dihitung sebagai menabung/menarik, hanya memindahkan bentuk simpanan.
 */
class ExchangeController extends Controller
{
    public function create(Request $request, ExchangeRateService $rates, SavingsCalculator $calc)
    {
        return view('exchange.create', [
            'rate' => $rates->current(),
            'balances' => $calc->balances($calc->transactions($request->user())),
        ]);
    }

    public function store(Request $request, SavingsCalculator $calc)
    {
        $request->merge([
            'amount_from' => parse_amount($request->input('amount_from')),
            'amount_to' => parse_amount($request->input('amount_to')),
        ]);
        $methods = array_keys(config('dcfinance.methods'));

        $data = $request->validate([
            'direction' => ['required', Rule::in(['IDR_AUD', 'AUD_IDR'])],
            'amount_from' => ['required', 'numeric', 'gt:0'],
            'amount_to' => ['required', 'numeric', 'gt:0'],
            'method_from' => ['required', Rule::in($methods)],
            'method_to' => ['required', Rule::in($methods)],
            'date' => ['required', 'date', 'before_or_equal:'.today()->addDay()->toDateString()],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'amount_from.required' => 'Isi jumlah yang ditukar.',
            'amount_to.required' => 'Isi jumlah yang diterima.',
        ]);

        [$from, $to] = $data['direction'] === 'IDR_AUD' ? ['IDR', 'AUD'] : ['AUD', 'IDR'];
        $idr = $from === 'IDR' ? $data['amount_from'] : $data['amount_to'];
        $audAmt = $from === 'AUD' ? $data['amount_from'] : $data['amount_to'];
        $rate = $idr / $audAmt;

        if ($rate < 1000 || $rate > 100000) {
            throw ValidationException::withMessages(['amount_to' => 'Kurs hasil tukar (Rp '.rate_fmt($rate).') terlihat tidak wajar. Cek lagi angkanya.']);
        }

        $available = $calc->balances($calc->transactions($request->user()))[$from];
        if ($data['amount_from'] > $available + 0.001) {
            throw ValidationException::withMessages(['amount_from' => 'Saldo '.$from.' tidak cukup. Tersedia: '.money($available, $from).'.']);
        }

        $group = (string) Str::uuid();
        $note = ($data['note'] ?? null) ?: "Tukar {$from} → {$to} @ Rp ".rate_fmt($rate);

        DB::transaction(function () use ($request, $data, $from, $to, $idr, $rate, $group, $note) {
            $base = ['date' => $data['date'], 'note' => $note, 'exchange_group' => $group, 'amount_idr' => round($idr, 2)];
            $request->user()->transactions()->create($base + [
                'type' => Transaction::EXCHANGE_OUT,
                'currency' => $from,
                'amount' => $data['amount_from'],
                'rate' => $from === 'AUD' ? $rate : 1,
                'method' => $data['method_from'],
            ]);
            $request->user()->transactions()->create($base + [
                'type' => Transaction::EXCHANGE_IN,
                'currency' => $to,
                'amount' => $data['amount_to'],
                'rate' => $to === 'AUD' ? $rate : 1,
                'method' => $data['method_to'],
            ]);
        });

        return redirect()->route('dashboard')->with('success', 'Tukar '.money($data['amount_from'], $from).' → '.money($data['amount_to'], $to).' tercatat 💱');
    }
}

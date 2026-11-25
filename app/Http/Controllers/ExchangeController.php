<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\ExchangeRateService;
use App\Services\SavingsCalculator;
use App\Support\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Tukar mata uang di dalam tabungan sendiri (mis. Rupiah di bank -> USD cash, atau AUD -> SGD).
 * Tidak dihitung sebagai menabung/menarik, hanya memindahkan bentuk simpanan.
 */
class ExchangeController extends Controller
{
    public function __construct(private ExchangeRateService $rates, private SavingsCalculator $calc) {}

    public function create(Request $request)
    {
        $user = $request->user();
        $balances = $this->calc->balances($this->calc->transactions($user));
        $used = $this->calc->usedCurrencies($user);

        return view('exchange.create', [
            'balances' => $balances,
            'currencies' => Currency::grouped($used),
            'rates' => $this->rates->all(),
            'curMeta' => Currency::forJs(),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'from' => strtoupper((string) $request->input('from')),
            'to' => strtoupper((string) $request->input('to')),
            'amount_from' => parse_amount($request->input('amount_from')),
            'amount_to' => parse_amount($request->input('amount_to')),
        ]);
        $methods = array_keys(config('dcfinance.methods'));

        $data = $request->validate([
            'from' => ['required', Rule::in(Currency::codes())],
            'to' => ['required', Rule::in(Currency::codes()), 'different:from'],
            'amount_from' => ['required', 'numeric', 'gt:0'],
            'amount_to' => ['required', 'numeric', 'gt:0'],
            'method_from' => ['required', Rule::in($methods)],
            'method_to' => ['required', Rule::in($methods)],
            'date' => ['required', 'date', 'before_or_equal:'.today()->addDay()->toDateString()],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'amount_from.required' => 'Isi jumlah yang ditukar.',
            'amount_to.required' => 'Isi jumlah yang diterima.',
            'to.different' => 'Pilih mata uang tujuan yang berbeda.',
        ]);

        [$from, $to] = [$data['from'], $data['to']];
        $amtFrom = round($data['amount_from'], Currency::decimals($from));
        $amtTo = round($data['amount_to'], Currency::decimals($to));

        // Nilai Rupiah transaksi: pasti bila salah satu sisi Rupiah, selain itu pakai kurs saat ini mata uang asal.
        $idr = match (true) {
            $from === 'IDR' => $amtFrom,
            $to === 'IDR' => $amtTo,
            default => $amtFrom * $this->rates->rate($from),
        };
        if ($idr <= 0) {
            throw ValidationException::withMessages(['amount_from' => 'Kurs '.$from.' belum tersedia. Isi kurs manual di halaman Kurs dulu.']);
        }

        // Cek kewajaran: hasil tukar tidak boleh menyimpang jauh dari kurs pasar.
        $market = $this->rates->convert($amtFrom, $from, $to);
        if ($market > 0 && ($amtTo / $market > 3 || $amtTo / $market < 0.33)) {
            throw ValidationException::withMessages(['amount_to' => 'Jumlah yang diterima terlihat tidak wajar dibanding kurs pasar (≈ '.money($market, $to).'). Cek lagi angkanya.']);
        }

        $available = $this->calc->balances($this->calc->transactions($request->user()))[$from] ?? 0;
        if ($amtFrom > $available + 0.001) {
            throw ValidationException::withMessages(['amount_from' => 'Saldo '.$from.' tidak cukup. Tersedia: '.money($available, $from).'.']);
        }

        $group = (string) Str::uuid();
        $note = ($data['note'] ?? null) ?: "Tukar {$from} → {$to}";

        DB::transaction(function () use ($request, $data, $from, $to, $amtFrom, $amtTo, $idr, $group, $note) {
            $base = ['date' => $data['date'], 'note' => $note, 'exchange_group' => $group, 'amount_idr' => round($idr, 2)];
            $request->user()->transactions()->create($base + [
                'type' => Transaction::EXCHANGE_OUT, 'currency' => $from, 'amount' => $amtFrom,
                'rate' => $from === 'IDR' ? 1 : round($idr / $amtFrom, 8), 'method' => $data['method_from'],
            ]);
            $request->user()->transactions()->create($base + [
                'type' => Transaction::EXCHANGE_IN, 'currency' => $to, 'amount' => $amtTo,
                'rate' => $to === 'IDR' ? 1 : round($idr / $amtTo, 8), 'method' => $data['method_to'],
            ]);
        });

        return redirect()->route('dashboard')->with('success', 'Tukar '.money($amtFrom, $from).' → '.money($amtTo, $to).' tercatat.');
    }
}

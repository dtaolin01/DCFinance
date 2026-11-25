<?php

namespace App\Http\Controllers;

use App\Services\ExchangeRateService;
use App\Support\Currency;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RateController extends Controller
{
    public function __construct(private ExchangeRateService $rates) {}

    public function index()
    {
        $table = $this->rates->table();
        $rows = [];
        foreach (Currency::codes() as $code) {
            if ($code === 'IDR') {
                continue;
            }
            $info = $this->rates->info($code);
            $rows[] = ['code' => $code, 'name' => Currency::name($code), 'rate' => $info['rate'], 'mode' => $info['mode']];
        }
        $popular = array_flip(Currency::popular());

        return view('rates.index', [
            'table' => $table,
            'rows' => $rows,
            'popularRows' => array_values(array_filter($rows, fn ($r) => isset($popular[$r['code']]))),
            'manual' => $this->rates->manual(),
            'currencies' => Currency::grouped([], false),
            'rates' => $this->rates->all(),
            'curMeta' => Currency::forJs(),
        ]);
    }

    public function update(Request $request)
    {
        $request->merge([
            'currency' => strtoupper((string) $request->input('currency')),
            'manual_rate' => parse_amount($request->input('manual_rate')),
        ]);
        $data = $request->validate([
            'currency' => ['required', Rule::in(array_values(array_diff(Currency::codes(), ['IDR'])))],
            'manual_rate' => ['required', 'numeric', 'gt:0', 'max:100000000'],
        ], ['manual_rate.required' => 'Isi kursnya dulu.']);

        $this->rates->setManual($data['currency'], (float) $data['manual_rate']);

        return back()->with('success', 'Kurs manual '.$data['currency'].' disimpan: Rp '.rate_fmt($data['manual_rate']).'.');
    }

    public function clear(string $code)
    {
        $this->rates->setManual(strtoupper($code), null);

        return back()->with('success', 'Kurs manual '.strtoupper($code).' dihapus, kembali ke kurs online.');
    }

    public function refresh()
    {
        $t = $this->rates->refresh();

        return back()->with($t['mode'] === 'live' ? 'success' : 'error', $t['mode'] === 'live'
            ? 'Kurs diperbarui dari '.$t['source'].'.'
            : 'Gagal mengambil kurs online, memakai kurs terakhir yang tersimpan.');
    }
}

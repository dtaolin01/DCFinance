<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Transaction;
use App\Support\Currency;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Kurs semua mata uang terhadap Rupiah (Rupiah per 1 unit).
 * Urutan sumber per mata uang:
 *   kurs manual -> kurs online (cache) -> kurs online terakhir tersimpan -> kurs transaksi terakhir -> perkiraan offline.
 */
class ExchangeRateService
{
    private const CACHE_KEY = 'dc_rates_idr_v2';

    private ?array $table = null;

    private ?array $manual = null;

    private array $txRates = [];

    /** Tabel kurs online: ['rates' => [kode => rupiah], 'source', 'updated_at', 'mode']. */
    public function table(): array
    {
        if ($this->table) {
            return $this->table;
        }

        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached) && ! empty($cached['rates'])) {
            return $this->table = $cached;
        }

        $live = $this->fetchOnline();
        if ($live) {
            Setting::put('rates_last', $live);
            Cache::put(self::CACHE_KEY, $live, now()->addMinutes(config('dcfinance.rate_cache_minutes', 180)));

            return $this->table = $live;
        }

        $last = Setting::get('rates_last');
        $fallback = is_array($last) && ! empty($last['rates'])
            ? array_merge($last, ['mode' => 'last'])
            : ['rates' => [], 'mode' => 'offline', 'source' => 'Perkiraan offline', 'updated_at' => null];

        Cache::put(self::CACHE_KEY, $fallback, now()->addMinutes(10)); // coba online lagi 10 menit lagi

        return $this->table = $fallback;
    }

    /** Kurs manual: [kode => ['rate' => float, 'updated_at' => iso]]. */
    public function manual(): array
    {
        if ($this->manual !== null) {
            return $this->manual;
        }
        $m = Setting::get('rate_manual', []);
        // format lama (hanya AUD): ['enabled' => bool, 'rate' => float]
        if (is_array($m) && array_key_exists('enabled', $m)) {
            $m = ! empty($m['enabled']) && ($m['rate'] ?? 0) > 0
                ? ['AUD' => ['rate' => (float) $m['rate'], 'updated_at' => $m['updated_at'] ?? null]]
                : [];
        }

        return $this->manual = is_array($m) ? $m : [];
    }

    public function setManual(string $code, ?float $rate): void
    {
        $m = $this->manual();
        if ($rate && $rate > 0) {
            $m[$code] = ['rate' => $rate, 'updated_at' => now()->toIso8601String()];
        } else {
            unset($m[$code]);
        }
        Setting::put('rate_manual', $m);
        $this->manual = $m;
    }

    /** Rupiah per 1 unit mata uang. 0 bila benar-benar tidak diketahui. */
    public function rate(string $code): float
    {
        return $this->info($code)['rate'];
    }

    /** Kurs + asal kursnya. */
    public function info(string $code): array
    {
        $code = strtoupper($code);
        if ($code === 'IDR') {
            return ['code' => 'IDR', 'rate' => 1.0, 'mode' => 'base', 'source' => 'Rupiah'];
        }

        $manual = $this->manual()[$code]['rate'] ?? null;
        if ($manual > 0) {
            return ['code' => $code, 'rate' => (float) $manual, 'mode' => 'manual', 'source' => 'Kurs manual', 'updated_at' => $this->manual()[$code]['updated_at'] ?? null];
        }

        $t = $this->table();
        if (($t['rates'][$code] ?? 0) > 0) {
            return ['code' => $code, 'rate' => (float) $t['rates'][$code], 'mode' => $t['mode'], 'source' => $t['source'] ?? 'Online', 'updated_at' => $t['updated_at'] ?? null];
        }

        if (! array_key_exists($code, $this->txRates)) {
            $this->txRates[$code] = (float) Transaction::where('currency', $code)->orderByDesc('date')->orderByDesc('id')->value('rate');
        }
        if ($this->txRates[$code] > 0) {
            return ['code' => $code, 'rate' => $this->txRates[$code], 'mode' => 'tx', 'source' => 'Kurs transaksi terakhir', 'updated_at' => null];
        }

        $fb = (float) (config("currencies.fallback.$code") ?? 0);

        return ['code' => $code, 'rate' => $fb, 'mode' => $fb > 0 ? 'offline' : 'unknown', 'source' => $fb > 0 ? 'Perkiraan offline' : 'Kurs belum tersedia', 'updated_at' => null];
    }

    /** Kurs untuk semua mata uang yang dikenal (untuk JavaScript & halaman kurs). */
    public function all(): array
    {
        $out = [];
        foreach (Currency::codes() as $code) {
            $out[$code] = $this->rate($code);
        }

        return $out;
    }

    /** Ubah nilai antar mata uang lewat Rupiah. */
    public function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }
        $toRate = $this->rate($to);

        return $toRate > 0 ? $amount * $this->rate($from) / $toRate : 0.0;
    }

    public function refresh(): array
    {
        Cache::forget(self::CACHE_KEY);
        $this->table = null;
        $this->txRates = [];

        return $this->table();
    }

    private function fetchOnline(): ?array
    {
        $sources = [
            // Semua sumber memberi "1 IDR = x mata uang", kita balik menjadi Rupiah per 1 unit.
            'ExchangeRate-API' => function () {
                $r = Http::timeout(8)->acceptJson()->get('https://open.er-api.com/v6/latest/IDR');
                $ts = $r->json('time_last_update_unix');

                return [$r->json('rates'), $ts ? Carbon::createFromTimestamp($ts) : now()];
            },
            'Currency-API' => function () {
                $r = Http::timeout(8)->acceptJson()->get('https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/idr.json');
                $rates = collect($r->json('idr') ?? [])->mapWithKeys(fn ($v, $k) => [strtoupper($k) => $v])->all();

                return [$rates, $r->json('date') ? Carbon::parse($r->json('date')) : now()];
            },
            'Frankfurter (ECB)' => function () {
                $r = Http::timeout(8)->acceptJson()->get('https://api.frankfurter.app/latest', ['from' => 'IDR']);

                return [$r->json('rates'), $r->json('date') ? Carbon::parse($r->json('date')) : now()];
            },
        ];

        $known = array_flip(Currency::codes());
        foreach ($sources as $name => $fn) {
            try {
                [$perIdr, $at] = $fn();
                if (! is_array($perIdr) || count($perIdr) < 5) {
                    continue;
                }
                $rates = [];
                foreach ($perIdr as $code => $v) {
                    $code = strtoupper((string) $code);
                    if (isset($known[$code]) && is_numeric($v) && $v > 0) {
                        $rates[$code] = round(1 / (float) $v, 8);
                    }
                }
                // sanity check: USD harus masuk akal
                $usd = $rates['USD'] ?? null;
                if ($usd && ($usd < 5000 || $usd > 50000)) {
                    continue;
                }
                if (count($rates) >= 5) {
                    unset($rates['IDR']);

                    return [
                        'rates' => $rates,
                        'mode' => 'live',
                        'source' => $name,
                        'updated_at' => $at->toIso8601String(),
                        'fetched_at' => now()->toIso8601String(),
                    ];
                }
            } catch (Throwable) {
                // lanjut ke sumber berikutnya
            }
        }

        return null;
    }
}

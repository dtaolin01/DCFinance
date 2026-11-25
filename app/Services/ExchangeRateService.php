<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Kurs AUD -> IDR.
 * Urutan: kurs manual (bila diaktifkan) -> cache -> API online -> kurs terakhir tersimpan -> default config.
 */
class ExchangeRateService
{
    private const CACHE_KEY = 'dc_aud_idr_rate';

    private ?array $memo = null;

    public function current(): array
    {
        if ($this->memo) {
            return $this->memo;
        }

        $manual = Setting::get('rate_manual');
        if (is_array($manual) && ! empty($manual['enabled']) && (float) ($manual['rate'] ?? 0) > 0) {
            return $this->memo = [
                'rate' => (float) $manual['rate'],
                'mode' => 'manual',
                'source' => 'Kurs manual',
                'updated_at' => $manual['updated_at'] ?? null,
            ];
        }

        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached) && ($cached['rate'] ?? 0) > 0) {
            return $this->memo = $cached;
        }

        $live = $this->fetchOnline();
        if ($live) {
            Setting::put('rate_last', $live);
            Cache::put(self::CACHE_KEY, $live, now()->addMinutes(config('dcfinance.rate_cache_minutes', 180)));

            return $this->memo = $live;
        }

        $last = Setting::get('rate_last');
        $fallback = is_array($last) && ($last['rate'] ?? 0) > 0
            ? array_merge($last, ['mode' => 'last', 'source' => ($last['source'] ?? 'Online').' (terakhir tersimpan)'])
            : [
                'rate' => (float) config('dcfinance.default_aud_rate', 10500),
                'mode' => 'default',
                'source' => 'Kurs default (offline)',
                'updated_at' => null,
            ];

        // Coba lagi 10 menit kemudian
        Cache::put(self::CACHE_KEY, $fallback, now()->addMinutes(10));

        return $this->memo = $fallback;
    }

    public function rate(): float
    {
        return (float) $this->current()['rate'];
    }

    public function refresh(): array
    {
        Cache::forget(self::CACHE_KEY);
        $this->memo = null;

        return $this->current();
    }

    private function fetchOnline(): ?array
    {
        $sources = [
            'ExchangeRate-API' => function () {
                $r = Http::timeout(6)->acceptJson()->get('https://open.er-api.com/v6/latest/AUD');
                $rate = $r->json('rates.IDR');
                $ts = $r->json('time_last_update_unix');

                return [$rate, $ts ? Carbon::createFromTimestamp($ts) : now()];
            },
            'Frankfurter (ECB)' => function () {
                $r = Http::timeout(6)->acceptJson()->get('https://api.frankfurter.app/latest', ['from' => 'AUD', 'to' => 'IDR']);

                return [$r->json('rates.IDR'), $r->json('date') ? Carbon::parse($r->json('date')) : now()];
            },
            'Currency-API' => function () {
                $r = Http::timeout(6)->acceptJson()->get('https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/aud.json');

                return [$r->json('aud.idr'), $r->json('date') ? Carbon::parse($r->json('date')) : now()];
            },
        ];

        foreach ($sources as $name => $fn) {
            try {
                [$rate, $at] = $fn();
                if (is_numeric($rate) && $rate > 1000 && $rate < 100000) {
                    return [
                        'rate' => round((float) $rate, 2),
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

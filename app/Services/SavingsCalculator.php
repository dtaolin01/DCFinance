<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Support\Currency;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Semua perhitungan tabungan per orang, untuk semua mata uang.
 */
class SavingsCalculator
{
    public function __construct(private ExchangeRateService $rates) {}

    /** Semua transaksi user, urut kronologis. */
    public function transactions(User $user): Collection
    {
        return $user->transactions()->orderBy('date')->orderBy('id')->get();
    }

    /** Saldo per mata uang (hanya yang tidak nol, IDR selalu ada). */
    public function balances(Collection $txs): array
    {
        $b = ['IDR' => 0.0];
        foreach ($txs as $t) {
            $b[$t->currency] = ($b[$t->currency] ?? 0) + $t->signedAmount();
        }
        foreach ($b as $code => $v) {
            if ($code !== 'IDR' && abs($v) < 0.005) {
                unset($b[$code]);
            }
        }

        return $b;
    }

    /** Mata uang yang pernah dipakai user (urut paling sering). */
    public function usedCurrencies(User $user): array
    {
        return $user->transactions()->selectRaw('currency, count(*) as n')->groupBy('currency')
            ->orderByDesc('n')->pluck('currency')->all();
    }

    /**
     * Modal (harga beli dalam Rupiah) per mata uang asing, metode rata-rata tertimbang.
     * Uang masuk menambah modal sesuai kurs saat itu; uang keluar mengurangi modal proporsional.
     */
    public function costBasis(Collection $txs): array
    {
        $acc = [];
        foreach ($txs as $t) {
            if ($t->currency === 'IDR') {
                continue;
            }
            $a = $acc[$t->currency] ?? ['qty' => 0.0, 'cost' => 0.0];
            if ($t->isInflow()) {
                $a['qty'] += $t->amount;
                $a['cost'] += $t->amount * $t->rate;
            } elseif ($a['qty'] > 0) {
                $portion = min($t->amount / $a['qty'], 1);
                $a['cost'] -= $a['cost'] * $portion;
                $a['qty'] -= $t->amount;
            }
            $acc[$t->currency] = $a;
        }

        return $acc;
    }

    /** Rincian setiap mata uang asing yang dipegang. */
    public function holdings(Collection $txs): array
    {
        $bal = $this->balances($txs);
        $basis = $this->costBasis($txs);
        $out = [];
        foreach ($bal as $code => $qty) {
            if ($code === 'IDR' || $qty <= 0.005) {
                continue;
            }
            $rate = $this->rates->rate($code);
            $cost = $basis[$code]['cost'] ?? 0;
            $value = $qty * $rate;
            $out[$code] = [
                'code' => $code,
                'name' => Currency::name($code),
                'qty' => $qty,
                'rate' => $rate,
                'cost_idr' => $cost,
                'avg_rate' => $qty > 0 ? $cost / $qty : 0,
                'value_idr' => $value,
                'gain' => $value - $cost,
                'gain_pct' => $cost > 0 ? ($value - $cost) / $cost * 100 : 0,
            ];
        }
        uasort($out, fn ($a, $b) => $b['value_idr'] <=> $a['value_idr']);

        return $out;
    }

    public function summary(User $user, ?Collection $txs = null): array
    {
        $txs ??= $this->transactions($user);
        $bal = $this->balances($txs);
        $holdings = $this->holdings($txs);

        $fxValue = array_sum(array_column($holdings, 'value_idr'));
        $fxCost = array_sum(array_column($holdings, 'cost_idr'));
        $total = $bal['IDR'] + $fxValue;

        $months = $this->monthly($txs, 12);
        $current = end($months);
        $previous = $months[count($months) - 2] ?? ['net' => 0];

        $lastSix = array_slice($months, -6);
        $activeSix = array_filter($lastSix, fn ($m) => $m['in'] > 0 || $m['out'] > 0);
        $avgMonthly = count($activeSix) ? array_sum(array_column($activeSix, 'net')) / count($activeSix) : 0;

        // Streak: bulan berturut-turut dengan tabungan bersih positif
        $streak = 0;
        $list = array_reverse($months);
        if (($list[0]['net'] ?? 0) <= 0) {
            array_shift($list);
        }
        foreach ($list as $m) {
            if ($m['net'] > 0) {
                $streak++;
            } else {
                break;
            }
        }

        $savings = $txs->whereIn('type', [Transaction::DEPOSIT, Transaction::WITHDRAW]);
        $deposits = $savings->where('type', Transaction::DEPOSIT);
        $monthlyTarget = (float) $user->monthly_target;
        $usd = $this->rates->rate('USD');

        return [
            'idr' => $bal['IDR'],
            'balances' => $bal,
            'holdings' => $holdings,
            'fx_value_idr' => $fxValue,
            'fx_cost_idr' => $fxCost,
            'fx_gain' => $fxValue - $fxCost,
            'fx_gain_pct' => $fxCost > 0 ? ($fxValue - $fxCost) / $fxCost * 100 : 0,
            'fx_share' => $total > 0 ? $fxValue / $total * 100 : 0,
            'total_idr' => $total,
            'total_usd' => $usd > 0 ? $total / $usd : 0,
            'month_in' => $current['in'],
            'month_out' => $current['out'],
            'month_net' => $current['net'],
            'prev_net' => $previous['net'],
            'month_change_pct' => $previous['net'] != 0 ? ($current['net'] - $previous['net']) / abs($previous['net']) * 100 : null,
            'avg_monthly' => $avgMonthly,
            'streak' => $streak,
            'monthly_target' => $monthlyTarget,
            'monthly_target_pct' => $monthlyTarget > 0 ? min(max($current['net'], 0) / $monthlyTarget * 100, 100) : null,
            'deposit_count' => $deposits->count(),
            'first_date' => $txs->first()?->date,
            'months' => $months,
            'composition' => $this->composition($txs),
        ];
    }

    /** Ringkasan per bulan (nilai Rupiah saat transaksi). Tukar uang tidak dihitung sebagai menabung. */
    public function monthly(Collection $txs, int $count = 12): array
    {
        $start = Carbon::today()->startOfMonth()->subMonths($count - 1);
        $months = [];
        for ($i = 0; $i < $count; $i++) {
            $m = $start->copy()->addMonths($i);
            $months[$m->format('Y-m')] = [
                'key' => $m->format('Y-m'),
                'label' => $m->translatedFormat('M y'),
                'in' => 0.0, 'out' => 0.0, 'net' => 0.0, 'balance' => 0.0,
            ];
        }

        $running = 0.0;
        foreach ($txs as $t) {
            $key = $t->date->format('Y-m');
            if ($t->date->lt($start)) {
                $running += $t->signedIdr();

                continue;
            }
            if (! isset($months[$key])) {
                continue;
            }
            if ($t->type === Transaction::DEPOSIT) {
                $months[$key]['in'] += $t->amount_idr;
            } elseif ($t->type === Transaction::WITHDRAW) {
                $months[$key]['out'] += $t->amount_idr;
            }
        }

        $byMonth = $txs->filter(fn ($t) => $t->date->gte($start))->groupBy(fn ($t) => $t->date->format('Y-m'));
        foreach ($months as $key => &$m) {
            $m['net'] = $m['in'] - $m['out'];
            $running += ($byMonth[$key] ?? collect())->sum(fn ($t) => $t->signedIdr());
            $m['balance'] = $running;
        }
        unset($m);

        return array_values($months);
    }

    /** Komposisi saldo per mata uang & tempat simpan (nilai Rupiah hari ini). */
    public function composition(Collection $txs): array
    {
        $parts = [];
        foreach ($txs as $t) {
            $key = $t->currency.'|'.$t->method;
            $parts[$key] = ($parts[$key] ?? 0) + $t->signedAmount();
        }
        $out = [];
        foreach ($parts as $key => $amount) {
            [$cur, $method] = explode('|', $key);
            $idr = $amount * $this->rates->rate($cur);
            if ($idr <= 0.5) {
                continue;
            }
            $out[] = [
                'label' => $cur.' · '.config("dcfinance.methods.$method.label", 'Lainnya'),
                'currency' => $cur,
                'amount' => $amount,
                'idr' => $idr,
            ];
        }
        usort($out, fn ($a, $b) => $b['idr'] <=> $a['idr']);

        return $out;
    }

    /** Kalimat insight singkat untuk dashboard. */
    public function insights(array $s, Collection $goals): array
    {
        $out = [];

        if ($s['month_net'] > 0 && $s['prev_net'] > 0 && $s['month_change_pct'] !== null) {
            $pct = round($s['month_change_pct']);
            $out[] = $pct >= 0
                ? ['icon' => 'trend', 'tone' => 'green', 'text' => "Tabungan bersih bulan ini naik {$pct}% dibanding bulan lalu. Mantap!"]
                : ['icon' => 'leaf', 'tone' => 'amber', 'text' => 'Bulan ini baru '.rupiah($s['month_net']).', bulan lalu '.rupiah($s['prev_net']).'. Masih ada waktu buat ngejar!'];
        } elseif ($s['month_net'] <= 0 && $s['deposit_count'] > 0) {
            $out[] = ['icon' => 'bell', 'tone' => 'accent', 'text' => 'Belum ada tabungan bersih bulan ini. Yuk setor walau sedikit!'];
        }

        if ($s['monthly_target'] > 0) {
            $left = $s['monthly_target'] - max($s['month_net'], 0);
            $out[] = $left > 0
                ? ['icon' => 'target', 'tone' => 'accent', 'text' => 'Kurang '.rupiah($left).' lagi untuk target bulanan '.rupiah($s['monthly_target']).'.']
                : ['icon' => 'trophy', 'tone' => 'green', 'text' => 'Target bulanan '.rupiah($s['monthly_target']).' sudah tercapai!'];
        }

        if (! empty($s['holdings']) && $s['fx_cost_idr'] > 0) {
            $codes = implode(', ', array_slice(array_keys($s['holdings']), 0, 3));
            $out[] = $s['fx_gain'] >= 0
                ? ['icon' => 'trend', 'tone' => 'green', 'text' => "Nilai valasmu ({$codes}) naik ".rupiah($s['fx_gain']).' dibanding saat kamu menyimpannya.']
                : ['icon' => 'trend-down', 'tone' => 'amber', 'text' => "Nilai valasmu ({$codes}) turun ".rupiah(abs($s['fx_gain'])).' dari modal. Belum rugi selama belum ditukar.'];
        }

        foreach ($goals as $g) {
            $p = $g->progress_cache ?? null;
            if (! $p || $p['done'] || ! $p['per_month']) {
                continue;
            }
            $out[] = ['icon' => 'calendar', 'tone' => 'violet', 'text' => "Untuk \"{$g->name}\" tepat waktu, sisihkan ".money($p['per_month'], $g->target_currency).' per bulan.'];
            break;
        }

        if ($s['streak'] >= 2) {
            $out[] = ['icon' => 'flame', 'tone' => 'amber', 'text' => "{$s['streak']} bulan berturut-turut tabunganmu bertambah. Pertahankan!"];
        }

        return array_slice($out, 0, 4);
    }
}

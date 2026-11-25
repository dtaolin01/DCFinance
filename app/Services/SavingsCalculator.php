<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Semua perhitungan tabungan per orang.
 */
class SavingsCalculator
{
    public function __construct(private ExchangeRateService $rates) {}

    /** Semua transaksi user, urut kronologis. */
    public function transactions(User $user): Collection
    {
        return $user->transactions()->orderBy('date')->orderBy('id')->get();
    }

    /** Saldo per mata uang. */
    public function balances(Collection $txs): array
    {
        $b = ['IDR' => 0.0, 'AUD' => 0.0];
        foreach ($txs as $t) {
            $b[$t->currency] = ($b[$t->currency] ?? 0) + $t->signedAmount();
        }

        return $b;
    }

    /**
     * Modal (harga beli) AUD yang masih dipegang, metode rata-rata tertimbang.
     * Setiap AUD masuk menambah modal sesuai kurs saat itu; AUD keluar mengurangi modal secara proporsional.
     */
    public function audCostBasis(Collection $txs): array
    {
        $qty = 0.0;
        $cost = 0.0;
        foreach ($txs->where('currency', 'AUD') as $t) {
            if ($t->isInflow()) {
                $qty += $t->amount;
                $cost += $t->amount * $t->rate;
            } elseif ($qty > 0) {
                $portion = min($t->amount / $qty, 1);
                $cost -= $cost * $portion;
                $qty -= $t->amount;
            }
        }
        $qty = max($qty, 0);

        return [
            'qty' => $qty,
            'cost_idr' => $qty > 0 ? $cost : 0,
            'avg_rate' => $qty > 0 ? $cost / $qty : 0,
        ];
    }

    public function summary(User $user, ?Collection $txs = null): array
    {
        $txs ??= $this->transactions($user);
        $rate = $this->rates->rate();
        $bal = $this->balances($txs);
        $basis = $this->audCostBasis($txs);

        $audValue = $bal['AUD'] * $rate;
        $audGain = $audValue - $basis['cost_idr'];
        $total = $bal['IDR'] + $audValue;

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
            array_shift($list); // bulan berjalan belum ada setoran -> mulai hitung dari bulan lalu
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

        return [
            'rate' => $rate,
            'idr' => $bal['IDR'],
            'aud' => $bal['AUD'],
            'aud_value_idr' => $audValue,
            'aud_cost_idr' => $basis['cost_idr'],
            'aud_avg_rate' => $basis['avg_rate'],
            'aud_gain' => $audGain,
            'aud_gain_pct' => $basis['cost_idr'] > 0 ? $audGain / $basis['cost_idr'] * 100 : 0,
            'total_idr' => $total,
            'total_aud' => $rate > 0 ? $total / $rate : 0,
            'aud_share' => $total > 0 ? $audValue / $total * 100 : 0,
            'month_in' => $current['in'],
            'month_out' => $current['out'],
            'month_net' => $current['net'],
            'prev_net' => $previous['net'],
            'month_change_pct' => $previous['net'] != 0 ? ($current['net'] - $previous['net']) / abs($previous['net']) * 100 : null,
            'avg_monthly' => $avgMonthly,
            'streak' => $streak,
            'monthly_target' => $monthlyTarget,
            'monthly_target_pct' => $monthlyTarget > 0 ? min(max($current['net'], 0) / $monthlyTarget * 100, 100) : null,
            'total_deposited' => $deposits->sum('amount_idr'),
            'total_withdrawn' => $savings->where('type', Transaction::WITHDRAW)->sum('amount_idr'),
            'deposit_count' => $deposits->count(),
            'biggest_deposit' => $deposits->sortByDesc('amount_idr')->first(),
            'first_date' => $txs->first()?->date,
            'months' => $months,
            'composition' => $this->composition($txs, $rate),
        ];
    }

    /**
     * Ringkasan per bulan (nilai IDR saat transaksi). Tukar mata uang tidak dihitung sebagai menabung.
     */
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
                continue; // transaksi bertanggal di masa depan
            }
            if ($t->type === Transaction::DEPOSIT) {
                $months[$key]['in'] += $t->amount_idr;
            } elseif ($t->type === Transaction::WITHDRAW) {
                $months[$key]['out'] += $t->amount_idr;
            }
        }

        // Saldo kumulatif (nilai buku) akhir tiap bulan
        $byMonth = $txs->filter(fn ($t) => $t->date->gte($start))->groupBy(fn ($t) => $t->date->format('Y-m'));
        foreach ($months as $key => &$m) {
            $m['net'] = $m['in'] - $m['out'];
            $running += ($byMonth[$key] ?? collect())->sum(fn ($t) => $t->signedIdr());
            $m['balance'] = $running;
        }
        unset($m);

        return array_values($months);
    }

    /** Komposisi saldo per mata uang & tempat simpan (nilai IDR hari ini). */
    public function composition(Collection $txs, float $rate): array
    {
        $parts = [];
        foreach ($txs as $t) {
            $key = $t->currency.'|'.$t->method;
            $parts[$key] = ($parts[$key] ?? 0) + $t->signedAmount();
        }
        $out = [];
        foreach ($parts as $key => $amount) {
            [$cur, $method] = explode('|', $key);
            $idr = $cur === 'AUD' ? $amount * $rate : $amount;
            if ($idr <= 0.5) {
                continue;
            }
            $out[] = [
                'label' => ($cur === 'AUD' ? 'AUD' : 'Rupiah').' · '.config("dcfinance.methods.$method.label", 'Lainnya'),
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
                ? ['icon' => '📈', 'text' => "Tabungan bersih bulan ini naik {$pct}% dibanding bulan lalu. Mantap!"]
                : ['icon' => '🌱', 'text' => 'Bulan ini baru '.rupiah($s['month_net']).', bulan lalu '.rupiah($s['prev_net']).'. Masih ada waktu buat ngejar!'];
        } elseif ($s['month_net'] <= 0 && $s['deposit_count'] > 0) {
            $out[] = ['icon' => '💌', 'text' => 'Belum ada tabungan bersih bulan ini. Yuk setor walau sedikit!'];
        }

        if ($s['monthly_target'] > 0) {
            $left = $s['monthly_target'] - max($s['month_net'], 0);
            $out[] = $left > 0
                ? ['icon' => '🎯', 'text' => 'Kurang '.rupiah($left).' lagi untuk target bulanan '.rupiah($s['monthly_target']).'.']
                : ['icon' => '🏆', 'text' => 'Target bulanan '.rupiah($s['monthly_target']).' sudah tercapai!'];
        }

        if ($s['aud'] > 0.009 && $s['aud_cost_idr'] > 0) {
            $out[] = $s['aud_gain'] >= 0
                ? ['icon' => '🐨', 'text' => 'Kurs AUD sekarang Rp '.rate_fmt($s['rate']).', di atas rata-rata belimu (Rp '.rate_fmt($s['aud_avg_rate']).'). Nilai AUD-mu untung '.rupiah($s['aud_gain']).'.']
                : ['icon' => '🐨', 'text' => 'Kurs AUD sekarang Rp '.rate_fmt($s['rate']).', di bawah rata-rata belimu (Rp '.rate_fmt($s['aud_avg_rate']).'). Selisihnya '.rupiah($s['aud_gain']).' — belum rugi selama belum ditukar.'];
        }

        foreach ($goals as $g) {
            $p = $g->progress_cache ?? null;
            if (! $p || $p['done'] || ! $p['per_month']) {
                continue;
            }
            $out[] = ['icon' => $g->emoji, 'text' => "Untuk \"{$g->name}\" tepat waktu, sisihkan ".money($p['per_month'], $g->target_currency).' per bulan.'];
            break;
        }

        if ($s['streak'] >= 2) {
            $out[] = ['icon' => '🔥', 'text' => "{$s['streak']} bulan berturut-turut tabunganmu bertambah. Pertahankan!"];
        }

        return array_slice($out, 0, 4);
    }
}

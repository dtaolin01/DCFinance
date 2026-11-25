<?php

namespace Database\Seeders;

use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Data contoh untuk mencoba aplikasi secara lokal.
 *   php artisan db:seed --class=DemoSeeder
 * Login: demo1@dcfinance.test / demo2@dcfinance.test — sandi: password
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $a = User::updateOrCreate(['email' => 'demo1@dcfinance.test'], [
            'name' => 'Dimas Pratama', 'nickname' => 'Dimas', 'password' => 'password',
            'color' => '#ec4899', 'emoji' => '🐻', 'monthly_target' => 3000000,
        ]);
        $b = User::updateOrCreate(['email' => 'demo2@dcfinance.test'], [
            'name' => 'Clara Anindya', 'nickname' => 'Clara', 'password' => 'password',
            'color' => '#a855f7', 'emoji' => '🌸', 'monthly_target' => 2500000,
        ]);

        $trip = Goal::create([
            'user_id' => $a->id, 'is_shared' => true, 'name' => 'Liburan ke Sydney', 'emoji' => '🐨', 'color' => '#ec4899',
            'target_amount' => 6000, 'target_currency' => 'AUD', 'deadline' => today()->addMonths(10), 'priority' => 1,
            'notes' => 'Lihat Opera House bareng!',
        ]);
        $laptop = Goal::create([
            'user_id' => $a->id, 'name' => 'Laptop baru', 'emoji' => '💻', 'color' => '#a855f7',
            'target_amount' => 18000000, 'target_currency' => 'IDR', 'deadline' => today()->addMonths(5), 'priority' => 2,
        ]);
        $dana = Goal::create([
            'user_id' => $b->id, 'name' => 'Dana darurat', 'emoji' => '🛡️', 'color' => '#14b8a6',
            'target_amount' => 15000000, 'target_currency' => 'IDR', 'priority' => 1,
        ]);

        $rate = 10350;
        for ($m = 7; $m >= 0; $m--) {
            $d = Carbon::today()->startOfMonth()->subMonths($m)->addDays(2);
            if ($d->isFuture()) {
                $d = today();
            }
            $r = $rate + ($m % 3) * 60 - 80;
            $this->tx($a, 'deposit', 'IDR', 2500000 + ($m % 3) * 400000, 1, $d, 'bank', 'Gaji bulanan', $m < 5 ? $laptop->id : null);
            $this->tx($b, 'deposit', 'IDR', 2000000 + ($m % 2) * 500000, 1, $d->copy()->addDays(1), 'bank', 'Sisihkan gaji', $dana->id);
            $this->tx($a, 'deposit', 'AUD', 100 + ($m % 2) * 50, $r, $d->copy()->addDays(5), 'cash', 'Celengan AUD', $trip->id);
            $this->tx($b, 'deposit', 'AUD', 80, $r, $d->copy()->addDays(6), 'cash', 'AUD dari kantor', $trip->id);
            if ($m % 3 === 1) {
                $this->tx($a, 'withdraw', 'IDR', 600000, 1, $d->copy()->addDays(12), 'ewallet', 'Darurat servis motor');
            }
        }

        // Tukar Rupiah -> AUD cash
        $g = (string) Str::uuid();
        $d = today()->subDays(20);
        $this->tx($b, 'exchange_out', 'IDR', 2070000, 1, $d, 'bank', 'Tukar di money changer', null, $g, 2070000);
        $this->tx($b, 'exchange_in', 'AUD', 200, 10350, $d, 'cash', 'Tukar di money changer', null, $g, 2070000);
    }

    private function tx(User $u, string $type, string $cur, float $amount, float $rate, Carbon $date, string $method, string $note, ?int $goal = null, ?string $group = null, ?float $idr = null): void
    {
        Transaction::create([
            'user_id' => $u->id, 'goal_id' => $goal, 'type' => $type, 'currency' => $cur,
            'amount' => $amount, 'rate' => $rate, 'amount_idr' => $idr ?? $amount * $rate,
            'date' => $date->isFuture() ? today() : $date, 'method' => $method, 'note' => $note, 'exchange_group' => $group,
        ]);
    }
}

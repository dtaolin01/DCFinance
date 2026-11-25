<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\User;
use App\Services\SavingsCalculator;
use Illuminate\Http\Request;

/** Halaman "Kita Berdua": ringkasan tabungan masing-masing + target bersama. */
class CoupleController extends Controller
{
    public function __invoke(Request $request, SavingsCalculator $calc)
    {
        $people = User::orderBy('id')->get()->map(function ($u) use ($calc) {
            $u->summary = $calc->summary($u);

            return $u;
        });

        $rate = $people->first()?->summary['rate'] ?? app(\App\Services\ExchangeRateService::class)->rate();
        $shared = Goal::where('is_shared', true)->with('transactions')->orderByRaw('achieved_at is not null')->orderBy('priority')->get()
            ->each(fn ($g) => $g->progress_cache = $g->progress($rate));

        $combined = [
            'total' => $people->sum(fn ($u) => $u->summary['total_idr']),
            'idr' => $people->sum(fn ($u) => $u->summary['idr']),
            'aud' => $people->sum(fn ($u) => $u->summary['aud']),
            'month_net' => $people->sum(fn ($u) => $u->summary['month_net']),
        ];

        return view('couple.index', compact('people', 'shared', 'combined', 'rate'));
    }
}

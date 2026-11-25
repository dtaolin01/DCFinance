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
        $people = User::approved()->orderBy('id')->get()->map(function ($u) use ($calc) {
            $u->summary = $calc->summary($u);

            return $u;
        });

        $shared = Goal::where('is_shared', true)->with('transactions')->orderByRaw('achieved_at is not null')->orderBy('priority')->get()
            ->each(fn ($g) => $g->progress_cache = $g->progress());

        $combined = [
            'total' => $people->sum(fn ($u) => $u->summary['total_idr']),
            'idr' => $people->sum(fn ($u) => $u->summary['idr']),
            'fx' => $people->sum(fn ($u) => $u->summary['fx_value_idr']),
            'month_net' => $people->sum(fn ($u) => $u->summary['month_net']),
        ];

        return view('couple.index', compact('people', 'shared', 'combined'));
    }
}

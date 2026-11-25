<?php

namespace App\Http\Controllers;

use App\Services\SavingsCalculator;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SavingsCalculator $calc)
    {
        $user = $request->user();
        $txs = $calc->transactions($user);
        $summary = $calc->summary($user, $txs);

        $goals = $user->visibleGoals()->with('transactions')
            ->orderBy('priority')->orderByRaw('deadline is null')->orderBy('deadline')->get()
            ->each(fn ($g) => $g->progress_cache = $g->progress());
        \App\Models\Goal::syncAchieved($goals);
        $goals = $goals->whereNull('achieved_at')->values();

        return view('dashboard.index', [
            'user' => $user,
            's' => $summary,
            'goals' => $goals->take(4),
            'goalCount' => $goals->count(),
            'recent' => $txs->sortByDesc(fn ($t) => $t->date->format('Ymd').str_pad($t->id, 10, '0', STR_PAD_LEFT))->take(6)->load('goal'),
            'insights' => $calc->insights($summary, $goals),
        ]);
    }
}

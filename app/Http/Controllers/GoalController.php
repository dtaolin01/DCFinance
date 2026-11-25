<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\User;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GoalController extends Controller
{
    public function __construct(private ExchangeRateService $rates) {}

    public function index(Request $request)
    {
        $rate = $this->rates->rate();
        $goals = $request->user()->visibleGoals()->with(['transactions', 'user'])
            ->orderBy('priority')->orderByRaw('deadline is null')->orderBy('deadline')->get()
            ->each(fn ($g) => $g->progress_cache = $g->progress($rate));

        $this->syncAchieved($goals);

        $active = $goals->whereNull('achieved_at');

        return view('goals.index', [
            'active' => $active,
            'achieved' => $goals->whereNotNull('achieved_at'),
            'totalTarget' => $active->sum(fn ($g) => $g->target_currency === 'AUD' ? $g->target_amount * $rate : $g->target_amount),
            'totalSaved' => $active->sum(fn ($g) => $g->progress_cache['saved_idr']),
            'rate' => $rate,
        ]);
    }

    public function create()
    {
        return view('goals.form', ['goal' => new Goal(['emoji' => '🎯', 'color' => '#ec4899', 'target_currency' => 'IDR', 'priority' => 2]), 'rate' => $this->rates->rate()]);
    }

    public function store(Request $request)
    {
        $goal = $request->user()->goals()->create($this->validated($request));

        return redirect()->route('goals.show', $goal)->with('success', 'Target "'.$goal->name.'" dibuat. Semangat! 🎯');
    }

    public function show(Request $request, Goal $goal)
    {
        $this->authorizeView($request, $goal);
        $rate = $this->rates->current();
        $goal->load(['transactions.user', 'user']);
        $progress = $goal->progress($rate['rate']);
        $goal->progress_cache = $progress;
        $this->syncAchieved(collect([$goal]));

        $people = User::orderBy('id')->get()->keyBy('id');

        return view('goals.show', [
            'goal' => $goal,
            'p' => $progress,
            'rate' => $rate,
            'people' => $people,
            'history' => $goal->transactions->sortByDesc(fn ($t) => $t->date->format('Ymd').str_pad($t->id, 10, '0', STR_PAD_LEFT)),
            'canEdit' => $goal->canBeEditedBy($request->user()),
        ]);
    }

    public function edit(Request $request, Goal $goal)
    {
        abort_unless($goal->canBeEditedBy($request->user()), 403);

        return view('goals.form', ['goal' => $goal, 'rate' => $this->rates->rate()]);
    }

    public function update(Request $request, Goal $goal)
    {
        abort_unless($goal->canBeEditedBy($request->user()), 403);
        $data = $this->validated($request);
        // Jika target dinaikkan sehingga belum tercapai lagi, buka kembali.
        $data['achieved_at'] = null;
        $goal->update($data);

        return redirect()->route('goals.show', $goal)->with('success', 'Target diperbarui ✨');
    }

    public function destroy(Request $request, Goal $goal)
    {
        abort_unless($goal->canBeEditedBy($request->user()), 403);
        $name = $goal->name;
        $goal->delete(); // transaksi tetap ada, hanya dilepas dari target

        return redirect()->route('goals.index')->with('success', 'Target "'.$name.'" dihapus. Transaksinya tetap tersimpan.');
    }

    private function validated(Request $request): array
    {
        $request->merge(['target_amount' => parse_amount($request->input('target_amount'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'emoji' => ['required', 'string', 'max:16'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'target_amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'target_currency' => ['required', Rule::in(['IDR', 'AUD'])],
            'deadline' => ['nullable', 'date'],
            'priority' => ['required', 'integer', 'between:1,3'],
            'is_shared' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], ['target_amount.required' => 'Nominal target wajib diisi.', 'name.required' => 'Beri nama targetmu.']);

        $data['is_shared'] = $request->boolean('is_shared');

        return $data;
    }

    private function authorizeView(Request $request, Goal $goal): void
    {
        abort_unless($goal->user_id === $request->user()->id || $goal->is_shared, 403);
    }

    /** Tandai otomatis target yang sudah 100%. */
    private function syncAchieved($goals): void
    {
        foreach ($goals as $g) {
            $done = $g->progress_cache['done'] ?? false;
            if ($done && ! $g->achieved_at) {
                $g->achieved_at = now();
                $g->saveQuietly();
            } elseif (! $done && $g->achieved_at) {
                $g->achieved_at = null;
                $g->saveQuietly();
            }
        }
    }
}

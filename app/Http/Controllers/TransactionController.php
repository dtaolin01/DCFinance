<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\ExchangeRateService;
use App\Services\SavingsCalculator;
use App\Support\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function __construct(private ExchangeRateService $rates, private SavingsCalculator $calc) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $month = $request->input('month');
        $type = $request->input('type');
        $currency = strtoupper((string) $request->input('currency'));
        $search = trim((string) $request->input('q'));

        $query = $user->transactions()->with('goal')
            ->when($month && preg_match('/^\d{4}-\d{2}$/', $month), function ($q) use ($month) {
                $d = Carbon::createFromFormat('Y-m-d', $month.'-01');
                $q->whereBetween('date', [$d->copy()->startOfMonth()->toDateString(), $d->copy()->endOfMonth()->toDateString()]);
            })
            ->when($type === 'in', fn ($q) => $q->where('type', Transaction::DEPOSIT))
            ->when($type === 'out', fn ($q) => $q->where('type', Transaction::WITHDRAW))
            ->when($type === 'exchange', fn ($q) => $q->whereIn('type', [Transaction::EXCHANGE_IN, Transaction::EXCHANGE_OUT]))
            ->when(Currency::exists($currency), fn ($q) => $q->where('currency', $currency))
            ->when($search !== '', fn ($q) => $q->where('note', 'like', "%{$search}%"));

        $totals = (clone $query)->get();
        $transactions = $query->orderByDesc('date')->orderByDesc('id')->paginate(25)->withQueryString();

        $months = $user->transactions()->orderByDesc('date')->pluck('date')
            ->map(fn ($d) => $d->format('Y-m'))->unique()->values();

        return view('transactions.index', [
            'transactions' => $transactions,
            'grouped' => $transactions->getCollection()->groupBy(fn ($t) => $t->date->format('Y-m-d')),
            'totalIn' => $totals->where('type', Transaction::DEPOSIT)->sum('amount_idr'),
            'totalOut' => $totals->where('type', Transaction::WITHDRAW)->sum('amount_idr'),
            'months' => $months,
            'usedCurrencies' => $this->calc->usedCurrencies($user),
            'filters' => ['month' => $month, 'type' => $type, 'currency' => Currency::exists($currency) ? $currency : null, 'search' => $search],
        ]);
    }

    public function create(Request $request)
    {
        $cur = strtoupper((string) $request->input('currency', 'IDR'));
        $tx = new Transaction([
            'type' => $request->input('type') === 'withdraw' ? Transaction::WITHDRAW : Transaction::DEPOSIT,
            'currency' => Currency::exists($cur) ? $cur : 'IDR',
            'goal_id' => $request->input('goal'),
            'date' => today(),
            'method' => $cur !== 'IDR' && Currency::exists($cur) ? 'cash' : 'bank',
        ]);

        return $this->form($request, $tx);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $tx = $request->user()->transactions()->create($data);

        $toGoal = $request->input('redirect') === 'goal' && $tx->goal_id;

        return redirect()->route($toGoal ? 'goals.show' : 'dashboard', $toGoal ? ['goal' => $tx->goal_id] : [])
            ->with('success', ($tx->type === Transaction::DEPOSIT ? 'Setoran ' : 'Penarikan ').money($tx->amount, $tx->currency).' tersimpan.');
    }

    public function edit(Request $request, Transaction $transaction)
    {
        $this->authorizeOwner($request, $transaction);
        if ($transaction->isExchange()) {
            return redirect()->route('transactions.index')->with('error', 'Transaksi tukar uang tidak bisa diedit. Hapus lalu buat ulang ya.');
        }

        return $this->form($request, $transaction);
    }

    public function update(Request $request, Transaction $transaction)
    {
        $this->authorizeOwner($request, $transaction);
        abort_if($transaction->isExchange(), 422);

        $transaction->update($this->validated($request, $transaction));

        return redirect()->route('transactions.index')->with('success', 'Transaksi diperbarui.');
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        $this->authorizeOwner($request, $transaction);

        if ($transaction->exchange_group) {
            Transaction::where('user_id', $request->user()->id)->where('exchange_group', $transaction->exchange_group)->delete();
        } else {
            $transaction->delete();
        }

        return back()->with('success', 'Transaksi dihapus.');
    }

    private function form(Request $request, Transaction $tx)
    {
        $user = $request->user();
        $used = $this->calc->usedCurrencies($user);

        return view('transactions.form', [
            'tx' => $tx,
            'currencies' => Currency::grouped($used),
            'quick' => array_slice(array_values(array_unique(array_merge(['IDR'], $used, ['AUD', 'USD', 'SGD']))), 0, 6),
            'rates' => $this->rates->all(),
            'curMeta' => Currency::forJs(),
            'goals' => $user->visibleGoals()->where(fn ($q) => $q->whereNull('achieved_at')->orWhere('id', $tx->goal_id))->orderBy('name')->get(),
            'balances' => $this->calc->balances($this->calc->transactions($user)),
        ]);
    }

    private function validated(Request $request, ?Transaction $existing = null): array
    {
        $user = $request->user();
        $request->merge([
            'currency' => strtoupper((string) $request->input('currency')),
            'amount' => parse_amount($request->input('amount')),
            'rate' => parse_amount($request->input('rate')),
        ]);

        $data = $request->validate([
            'type' => ['required', Rule::in([Transaction::DEPOSIT, Transaction::WITHDRAW])],
            'currency' => ['required', Rule::in(Currency::codes())],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'rate' => ['nullable', 'numeric', 'gt:0', 'max:100000000', Rule::requiredIf($request->input('currency') !== 'IDR')],
            'date' => ['required', 'date', 'before_or_equal:'.today()->addDay()->toDateString()],
            'method' => ['required', Rule::in(array_keys(config('dcfinance.methods')))],
            'goal_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'amount.required' => 'Nominal wajib diisi.',
            'amount.gt' => 'Nominal harus lebih dari 0.',
            'rate.required' => 'Kurs wajib diisi untuk mata uang selain Rupiah.',
            'date.before_or_equal' => 'Tanggal tidak boleh di masa depan.',
        ]);

        if (! empty($data['goal_id']) && ! $user->visibleGoals()->whereKey($data['goal_id'])->exists()) {
            throw ValidationException::withMessages(['goal_id' => 'Target tidak ditemukan.']);
        }

        $cur = $data['currency'];
        $data['rate'] = $cur === 'IDR' ? 1 : round((float) $data['rate'], 8);
        $data['amount'] = round($data['amount'], Currency::decimals($cur));
        if ($data['amount'] <= 0) {
            throw ValidationException::withMessages(['amount' => 'Nominal terlalu kecil.']);
        }
        $data['amount_idr'] = round($data['amount'] * $data['rate'], 2);
        $data['goal_id'] = ($data['goal_id'] ?? null) ?: null;

        if ($data['type'] === Transaction::WITHDRAW) {
            $txs = $this->calc->transactions($user)->when($existing, fn ($c) => $c->where('id', '!=', $existing->id));
            $available = $this->calc->balances($txs)[$cur] ?? 0;
            if ($data['amount'] > $available + 0.001) {
                throw ValidationException::withMessages([
                    'amount' => 'Saldo '.$cur.' tidak cukup. Saldo tersedia: '.money($available, $cur).'.',
                ]);
            }
        }

        return $data;
    }

    private function authorizeOwner(Request $request, Transaction $tx): void
    {
        abort_unless($tx->user_id === $request->user()->id, 403, 'Ini bukan transaksimu.');
    }
}

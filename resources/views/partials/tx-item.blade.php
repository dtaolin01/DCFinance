@php
    $cls = $t->isExchange() ? 'ex' : ($t->isInflow() ? 'in' : 'out');
    $title = $t->note ?: ($t->isExchange() ? 'Tukar mata uang' : ($t->isInflow() ? 'Setor tabungan' : 'Tarik tabungan'));
@endphp
<div class="tx">
    <div class="tx-icon {{ $cls }}">@include('partials.icon', ['n' => $t->icon()])</div>
    <div class="tx-main">
        <div class="tx-title"><span class="t">{{ $title }}</span></div>
        <div class="tx-meta">
            @if (! empty($showDate))<span>{{ $t->date->translatedFormat('d M Y') }}</span><span class="sep"></span>@endif
            <span>{{ $t->typeLabel() }}</span><span class="sep"></span><span>{{ $t->methodLabel() }}</span>
            @if ($t->goal && empty($hideGoal))<span class="badge badge-sm">{{ $t->goal->emoji }} {{ \Illuminate\Support\Str::limit($t->goal->name, 20) }}</span>@endif
            @if (! empty($showUser) && $t->relationLoaded('user'))<span class="badge badge-sm badge-gray">{{ $t->user->emoji }} {{ $t->user->display_name }}</span>@endif
        </div>
    </div>
    <div class="tx-amt num {{ $cls === 'in' ? 'text-green' : ($cls === 'out' ? 'text-red' : '') }}">
        {{ $t->isInflow() ? '+' : '−' }}{{ ltrim(money($t->amount, $t->currency), '-') }}
        @if ($t->currency !== 'IDR')<span class="s">≈ {{ rupiah($t->amount_idr) }} · kurs {{ rate_fmt($t->rate) }}</span>@endif
    </div>
    @if (! empty($actions) && $t->user_id === auth()->id())
        <div class="tx-actions">
            @unless ($t->isExchange())
                <a href="{{ route('transactions.edit', $t) }}" class="btn btn-ghost icon-btn btn-sm" title="Edit">@include('partials.icon', ['n' => 'edit'])</a>
            @endunless
            <form method="POST" action="{{ route('transactions.destroy', $t) }}" data-confirm="{{ $t->isExchange() ? 'Hapus transaksi tukar ini (kedua sisinya)?' : 'Hapus transaksi ini?' }}">
                @csrf @method('DELETE')
                <button class="btn btn-ghost icon-btn btn-sm" title="Hapus">@include('partials.icon', ['n' => 'trash'])</button>
            </form>
        </div>
    @endif
</div>

@php $p = $g->progress_cache; @endphp
<a href="{{ route('goals.show', $g) }}" class="card goal-card">
    <div class="row" style="align-items:flex-start">
        <div class="goal-emoji" style="background: {{ $g->color }}1f">{{ $g->emoji }}</div>
        <div style="flex:1;min-width:0">
            <div class="row-between">
                <h3 class="ellipsis" style="font-size:16px">{{ $g->name }}</h3>
                @if ($g->achieved_at)
                    <span class="badge badge-green">Tercapai 🎉</span>
                @elseif ($p['overdue'])
                    <span class="badge badge-red">Lewat tenggat</span>
                @elseif ($p['on_track'] === true)
                    <span class="badge badge-green">Sesuai jalur</span>
                @elseif ($p['on_track'] === false)
                    <span class="badge badge-amber">Perlu dikejar</span>
                @endif
            </div>
            <div class="tx-meta mt-1">
                @if ($g->is_shared)<span class="badge badge-violet" style="padding:1px 8px">💑 Bersama</span>@else<span>{{ $g->user->emoji ?? '' }} {{ $g->user->display_name ?? '' }}</span>@endif
                @if ($g->deadline)<span>· 📅 {{ $g->deadline->translatedFormat('d M Y') }}</span>@endif
            </div>
        </div>
    </div>
    <div class="row-between mt-3">
        <div class="bold num">{{ money($p['saved'], $g->target_currency) }}</div>
        <div class="small muted num">dari {{ money($g->target_amount, $g->target_currency) }}</div>
    </div>
    <div class="bar mt-1"><span data-w="{{ round($p['percent'], 1) }}" style="width:0;background:linear-gradient(90deg, {{ $g->color }}99, {{ $g->color }})"></span></div>
    <div class="row-between mt-2 small">
        <span class="bold" style="color: {{ $g->color }}">{{ number_format($p['percent'], 1, ',', '.') }}%</span>
        @if (! $p['done'])
            <span class="muted">
                @if ($p['per_month']) {{ money($p['per_month'], $g->target_currency) }}/bln
                @elseif ($p['days_left'] !== null && $p['days_left'] < 0) Kurang {{ money($p['remaining'], $g->target_currency) }}
                @else Kurang {{ money($p['remaining'], $g->target_currency) }} @endif
            </span>
        @endif
    </div>
</a>

@php $p = $g->progress_cache; @endphp
<a href="{{ route('goals.show', $g) }}" class="card goal-card">
    <div class="goal-top">
        <div class="goal-emoji" style="background: {{ $g->color }}1f">{{ $g->emoji }}</div>
        <div style="flex:1;min-width:0">
            <div class="goal-name">{{ $g->name }}</div>
            <div class="goal-meta">
                @if ($g->is_shared)
                    <span class="badge badge-sm badge-violet">@include('partials.icon', ['n' => 'users']) Bersama</span>
                @else
                    <span>{{ $g->user->emoji ?? '' }} {{ $g->user->display_name ?? '' }}</span>
                @endif
                @if ($g->deadline)<span class="row" style="gap:4px">@include('partials.icon', ['n' => 'calendar']) {{ $g->deadline->translatedFormat('d M Y') }}</span>@endif
            </div>
        </div>
    </div>
    <div class="goal-foot">
        <div class="row-between">
            <div class="bold num">{{ money($p['saved'], $g->target_currency) }}</div>
            <div class="small muted num">dari {{ money($g->target_amount, $g->target_currency) }}</div>
        </div>
        <div class="bar mt-1"><span data-w="{{ round($p['percent'], 1) }}" style="width:0;background:linear-gradient(90deg, {{ $g->color }}99, {{ $g->color }})"></span></div>
        <div class="row-between mt-2 small">
            <span class="bold" style="color: {{ $g->color }}">{{ number_format($p['percent'], 1, ',', '.') }}%</span>
            @if ($g->achieved_at)
                <span class="badge badge-green">Tercapai</span>
            @elseif ($p['overdue'])
                <span class="badge badge-red">Lewat tenggat</span>
            @elseif ($p['on_track'] === true)
                <span class="badge badge-green">Sesuai jalur</span>
            @elseif ($p['on_track'] === false)
                <span class="badge badge-amber">Perlu dikejar</span>
            @else
                <span class="muted">Kurang {{ money($p['remaining'], $g->target_currency) }}</span>
            @endif
        </div>
        @if (! $p['done'] && $p['per_month'])
            <div class="xs muted mt-1">Butuh {{ money($p['per_month'], $g->target_currency) }} / bulan</div>
        @endif
    </div>
</a>

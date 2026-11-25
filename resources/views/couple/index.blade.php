@extends('layouts.app')
@section('title', 'Kita Berdua')

@section('content')
<div class="page-head">
    <div>
        <h1>Kita berdua</h1>
        <p>Tabungan masing-masing tetap terpisah — di sini kalian bisa lihat progres bareng.</p>
    </div>
</div>

<div class="card hero mb-3">
    <div class="label">@include('partials.tile', ['n' => 'heart', 'tone' => 'white']) Gabungan tabungan kalian</div>
    <div class="big num">{{ rupiah($combined['total']) }}</div>
    <div class="sub num">Rupiah {{ rupiah($combined['idr']) }} · Valas {{ rupiah($combined['fx']) }}</div>
    @if ($combined['total'] > 0 && $people->count() > 1)
        <div class="split-bar mt-3">
            @foreach ($people as $u)
                <span style="width: {{ $u->summary['total_idr'] / $combined['total'] * 100 }}%; background: {{ $loop->first ? '#fff' : 'rgba(255,255,255,.55)' }}"></span>
            @endforeach
        </div>
        <div class="row-between mt-1 small">
            @foreach ($people as $u)
                <span>{{ $u->emoji }} {{ $u->display_name }} · {{ number_format($u->summary['total_idr'] / $combined['total'] * 100, 0) }}%</span>
            @endforeach
        </div>
    @endif
</div>

@if ($people->count() < 2)
    <div class="alert mb-3">@include('partials.tile', ['n' => 'user']) <span>Pasanganmu belum punya akun aktif. Minta dia daftar di <b>{{ route('register') }}</b> lalu setujui di menu <b>Persetujuan</b>.</span></div>
@endif

<div class="people mb-3">
    @foreach ($people as $u)
        @php $s = $u->summary; @endphp
        <div class="card person-card" style="--c: {{ $u->color }}">
            <div class="row mb-3">
                <div class="avatar avatar-lg" style="background: {{ $u->color }}22">{{ $u->emoji }}</div>
                <div style="min-width:0">
                    <h3 class="row" style="gap:8px;font-size:17px">{{ $u->display_name }} @if ($u->id === auth()->id())<span class="badge badge-sm">kamu</span>@endif</h3>
                    <div class="small muted">Menabung sejak {{ $s['first_date'] ? $s['first_date']->translatedFormat('M Y') : '—' }}</div>
                </div>
            </div>
            <div class="stat-k">Total (dirupiahkan)</div>
            <div class="stat-v num" style="font-size:26px">{{ rupiah($s['total_idr']) }}</div>
            <div class="mt-2">
                <div class="kv"><span class="k">@include('partials.cur', ['c' => 'IDR']) Rupiah</span><span class="v num">{{ rupiah($s['idr']) }}</span></div>
                <div class="kv"><span class="k">@include('partials.icon', ['n' => 'globe']) Valas{{ count($s['holdings']) ? ' ('.implode(', ', array_slice(array_keys($s['holdings']), 0, 3)).')' : '' }}</span><span class="v num">{{ rupiah($s['fx_value_idr']) }}</span></div>
                <div class="kv"><span class="k">Bersih bulan ini</span><span class="v num {{ $s['month_net'] >= 0 ? 'text-green' : 'text-red' }}">{{ rupiah($s['month_net'], true) }}</span></div>
                <div class="kv"><span class="k">Rata-rata / bulan</span><span class="v num">{{ rupiah($s['avg_monthly']) }}</span></div>
                <div class="kv"><span class="k">Streak</span><span class="v">{{ $s['streak'] }} bulan</span></div>
            </div>
            @if ($s['monthly_target'] > 0)
                <div class="mt-2 small row-between"><span class="muted">Target bulanan {{ rupiah_short($s['monthly_target']) }}</span><span class="bold">{{ round($s['monthly_target_pct']) }}%</span></div>
                <div class="bar mt-1"><span data-w="{{ round($s['monthly_target_pct']) }}" style="width:0;background: {{ $u->color }}"></span></div>
            @endif
        </div>
    @endforeach
</div>

<div class="grid g-main">
    <div class="card">
        <div class="card-head"><h3>@include('partials.tile', ['n' => 'chart']) Tabungan bersih per bulan</h3></div>
        <div class="chart-box"><canvas id="coupleChart"></canvas></div>
    </div>
    <div class="card">
        <div class="card-head">
            <h3>@include('partials.tile', ['n' => 'users', 'tone' => 'violet']) Target bersama</h3>
            <a href="{{ route('goals.create') }}" class="card-link">@include('partials.icon', ['n' => 'plus']) Baru</a>
        </div>
        @forelse ($shared as $g)
            @php $p = $g->progress_cache; @endphp
            <a href="{{ route('goals.show', $g) }}" class="goal-mini">
                <div class="ring" style="--p: {{ round($p['percent']) }}; --c: {{ $g->color }}"><div>{{ round($p['percent']) }}%</div></div>
                <div class="grow">
                    <div class="bold ellipsis">{{ $g->emoji }} {{ $g->name }}</div>
                    <div class="small muted num">{{ money($p['saved'], $g->target_currency) }} / {{ money($g->target_amount, $g->target_currency) }}</div>
                    <div class="row wrap mt-1" style="gap:4px">
                        @foreach ($people as $u)
                            <span class="badge badge-sm badge-gray">{{ $u->emoji }} {{ rupiah_short($p['by_user'][$u->id] ?? 0) }}</span>
                        @endforeach
                    </div>
                </div>
            </a>
        @empty
            @include('partials.empty', ['icon' => 'users', 'title' => 'Belum ada target bersama', 'text' => 'Centang “Target bersama” saat membuat target.', 'pad' => '16px 8px'])
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    if (!window.Chart) return;
    Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";
    Chart.defaults.color = DC.cssVar('--ink-3');
    const people = @json($people->map(fn ($u) => ['name' => $u->display_name, 'color' => $u->color, 'months' => $u->summary['months']])->values());
    if (!people.length) return;
    new Chart(document.getElementById('coupleChart'), {
        type: 'bar',
        data: {
            labels: people[0].months.map(m => m.label),
            datasets: people.map(p => ({ label: p.name, data: p.months.map(m => m.net), backgroundColor: p.color, borderRadius: 6, maxBarThickness: 16 })),
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } }, tooltip: { callbacks: { label: (c) => ' ' + c.dataset.label + ': ' + DC.idr(c.parsed.y) } } },
            scales: { x: { grid: { display: false } }, y: { grid: { color: DC.cssVar('--line') }, border: { display: false }, ticks: { callback: (v) => Math.abs(v) >= 1e6 ? (v / 1e6).toFixed(1).replace('.', ',') + ' jt' : (Math.abs(v) >= 1e3 ? (v / 1e3) + ' rb' : v) } } },
        },
    });
})();
</script>
@endpush

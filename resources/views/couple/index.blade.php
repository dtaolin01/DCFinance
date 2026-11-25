@extends('layouts.app')
@section('title', 'Kita Berdua')

@section('content')
<div class="page-head">
    <div>
        <h1>Kita berdua 💑</h1>
        <p>Tabungan masing-masing tetap terpisah — ini cuma intip progres bareng.</p>
    </div>
</div>

<div class="card hero mb-3">
    <div class="label">Gabungan tabungan kalian</div>
    <div class="big num">{{ rupiah($combined['total']) }}</div>
    <div class="sub">Rupiah {{ rupiah($combined['idr']) }} · AUD {{ aud($combined['aud']) }} · kurs Rp {{ rate_fmt($rate) }}</div>
    <div class="hero-split" style="grid-template-columns: repeat({{ max($people->count(), 1) }}, minmax(0,1fr))">
        @foreach ($people as $u)
            @php $share = $combined['total'] > 0 ? $u->summary['total_idr'] / $combined['total'] * 100 : 0; @endphp
            <div class="hero-box">
                <div class="k">{{ $u->emoji }} {{ $u->display_name }}</div>
                <div class="v num">{{ number_format($share, 0) }}%</div>
            </div>
        @endforeach
    </div>
</div>

@if ($people->count() < 2)
    <div class="alert mb-3">💌 Pasanganmu belum punya akun. Minta dia daftar di <b>{{ route('register') }}</b> (pendaftaran masih terbuka).</div>
@endif

<div class="vs mb-3">
    @foreach ($people as $i => $u)
        @php $s = $u->summary; @endphp
        @if ($i === 1)<div class="heart center">💗</div>@endif
        <div class="card" style="border-top: 4px solid {{ $u->color }}">
            <div class="row mb-3">
                <div class="avatar avatar-lg" style="background: {{ $u->color }}22">{{ $u->emoji }}</div>
                <div>
                    <h3>{{ $u->display_name }} @if ($u->id === auth()->id())<span class="badge">kamu</span>@endif</h3>
                    <div class="small muted">Menabung sejak {{ $s['first_date'] ? $s['first_date']->translatedFormat('M Y') : '—' }}</div>
                </div>
            </div>
            <div class="stat-k">Total (dirupiahkan)</div>
            <div class="stat-v num" style="font-size:26px">{{ rupiah($s['total_idr']) }}</div>
            <div class="kv mt-2"><span class="k">🇮🇩 Rupiah</span><span class="v num">{{ rupiah($s['idr']) }}</span></div>
            <div class="kv"><span class="k">🇦🇺 AUD cash</span><span class="v num">{{ aud($s['aud']) }}</span></div>
            <div class="kv"><span class="k">📆 Bulan ini</span><span class="v num {{ $s['month_net'] >= 0 ? 'text-green' : 'text-red' }}">{{ rupiah($s['month_net'], true) }}</span></div>
            <div class="kv"><span class="k">📊 Rata-rata/bulan</span><span class="v num">{{ rupiah($s['avg_monthly']) }}</span></div>
            <div class="kv"><span class="k">🔥 Streak</span><span class="v">{{ $s['streak'] }} bulan</span></div>
            @if ($s['monthly_target'] > 0)
                <div class="mt-2 small row-between"><span class="muted">Target bulanan {{ rupiah_short($s['monthly_target']) }}</span><span class="bold">{{ round($s['monthly_target_pct']) }}%</span></div>
                <div class="bar mt-1"><span data-w="{{ round($s['monthly_target_pct']) }}" style="width:0;background: {{ $u->color }}"></span></div>
            @endif
        </div>
    @endforeach
</div>

<div class="grid g-main">
    <div class="card">
        <div class="card-head"><h3>📈 Tabungan bersih per bulan</h3></div>
        <div class="chart-box"><canvas id="coupleChart"></canvas></div>
    </div>
    <div class="card">
        <div class="card-head"><h3>💑 Target bersama</h3><a href="{{ route('goals.create') }}">+ Baru</a></div>
        @forelse ($shared as $g)
            @php $p = $g->progress_cache; @endphp
            <a href="{{ route('goals.show', $g) }}" class="goal-mini">
                <div class="ring" style="--p: {{ round($p['percent']) }}; --c: {{ $g->color }}"><div>{{ round($p['percent']) }}%</div></div>
                <div class="grow">
                    <div class="bold ellipsis">{{ $g->emoji }} {{ $g->name }}</div>
                    <div class="small muted num">{{ money($p['saved'], $g->target_currency) }} / {{ money($g->target_amount, $g->target_currency) }}</div>
                    <div class="row mt-1" style="gap:4px">
                        @foreach ($people as $u)
                            @php $v = $p['by_user'][$u->id] ?? 0; @endphp
                            <span class="badge badge-gray" style="padding:1px 8px">{{ $u->emoji }} {{ rupiah_short($v) }}</span>
                        @endforeach
                    </div>
                </div>
            </a>
        @empty
            <div class="empty" style="padding:20px">
                <div class="ill">🏝️</div>
                <h4>Belum ada target bersama</h4>
                <p class="small">Centang “Target bersama” saat membuat target.</p>
            </div>
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
    Chart.defaults.color = '#a08a9b';
    const people = @json($people->map(fn ($u) => ['name' => $u->display_name, 'color' => $u->color, 'months' => $u->summary['months']])->values());
    if (!people.length) return;
    new Chart(document.getElementById('coupleChart'), {
        type: 'bar',
        data: {
            labels: people[0].months.map(m => m.label),
            datasets: people.map(p => ({ label: p.name, data: p.months.map(m => m.net), backgroundColor: p.color, borderRadius: 8, maxBarThickness: 18 })),
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } }, tooltip: { callbacks: { label: (c) => ' ' + c.dataset.label + ': ' + DC.idr(c.parsed.y) } } },
            scales: { x: { grid: { display: false } }, y: { grid: { color: '#fbe7f0' }, ticks: { callback: (v) => Math.abs(v) >= 1e6 ? (v / 1e6).toFixed(1).replace('.', ',') + ' jt' : (Math.abs(v) >= 1e3 ? (v / 1e3) + ' rb' : v) } } },
        },
    });
})();
</script>
@endpush

@extends('layouts.app')
@section('title', 'Beranda')

@section('content')
@php
    $hour = now()->hour;
    $greet = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam'));
@endphp

<div class="page-head">
    <div>
        <h1>{{ $greet }}, {{ $user->display_name }} {{ $user->emoji }}</h1>
        <p>{{ now()->translatedFormat('l, d F Y') }} · ini ringkasan tabunganmu.</p>
    </div>
    <div class="page-actions hide-m">
        <a href="{{ route('exchange.create') }}" class="btn btn-ghost">@include('partials.icon', ['n' => 'swap']) Tukar Uang</a>
        <a href="{{ route('transactions.create') }}" class="btn btn-primary">@include('partials.icon', ['n' => 'plus']) Catat Tabungan</a>
    </div>
</div>

<div class="grid g-main">
    <div class="stack">
        {{-- HERO --}}
        <div class="card hero">
            <div class="label">Total tabungan (dirupiahkan)</div>
            <div class="big num">{{ rupiah($s['total_idr']) }}</div>
            <div class="sub num">≈ {{ aud($s['total_aud']) }} · kurs 1 AUD = Rp {{ rate_fmt($s['rate']) }}</div>
            <div class="hero-split">
                <div class="hero-box">
                    <div class="k">🇮🇩 Rupiah</div>
                    <div class="v num">{{ rupiah($s['idr']) }}</div>
                </div>
                <div class="hero-box">
                    <div class="k">🇦🇺 AUD cash</div>
                    <div class="v num">{{ aud($s['aud']) }}</div>
                    <div class="xs" style="opacity:.85">≈ {{ rupiah($s['aud_value_idr']) }}</div>
                </div>
                <div class="hero-box">
                    <div class="k">📆 Bulan ini (bersih)</div>
                    <div class="v num">{{ rupiah($s['month_net'], true) }}</div>
                    @if ($s['monthly_target_pct'] !== null)
                        <div class="bar bar-white mt-1" style="height:6px"><span data-w="{{ round($s['monthly_target_pct']) }}" style="width:0"></span></div>
                    @endif
                </div>
            </div>
            <div class="hero-actions">
                <a href="{{ route('transactions.create') }}" class="btn btn-white">@include('partials.icon', ['n' => 'arrow-down']) Setor</a>
                <a href="{{ route('transactions.create', ['currency' => 'AUD']) }}" class="btn btn-glass">🇦🇺 Setor AUD</a>
                <a href="{{ route('transactions.create', ['type' => 'withdraw']) }}" class="btn btn-glass">@include('partials.icon', ['n' => 'arrow-up']) Tarik</a>
            </div>
        </div>

        {{-- STATS --}}
        <div class="grid g-4 stats">
            <div class="card card-tight">
                <div class="stat">
                    <div class="stat-icon">💰</div>
                    <div><div class="stat-k">Masuk bulan ini</div><div class="stat-v num text-green">{{ rupiah_short($s['month_in']) }}</div><div class="stat-s">Keluar {{ rupiah_short($s['month_out']) }}</div></div>
                </div>
            </div>
            <div class="card card-tight">
                <div class="stat">
                    <div class="stat-icon">📊</div>
                    <div><div class="stat-k">Rata-rata/bulan</div><div class="stat-v num">{{ rupiah_short($s['avg_monthly']) }}</div><div class="stat-s">6 bulan terakhir</div></div>
                </div>
            </div>
            <div class="card card-tight">
                <div class="stat">
                    <div class="stat-icon">🐨</div>
                    <div>
                        <div class="stat-k">Untung/rugi kurs AUD</div>
                        <div class="stat-v num {{ $s['aud_gain'] >= 0 ? 'text-green' : 'text-red' }}">{{ $s['aud'] > 0 ? rupiah_short($s['aud_gain']) : '—' }}</div>
                        <div class="stat-s">{{ $s['aud'] > 0 ? 'Rata-rata beli Rp '.rate_fmt($s['aud_avg_rate']) : 'Belum ada AUD' }}</div>
                    </div>
                </div>
            </div>
            <div class="card card-tight">
                <div class="stat">
                    <div class="stat-icon">🔥</div>
                    <div><div class="stat-k">Streak nabung</div><div class="stat-v num">{{ $s['streak'] }} bulan</div><div class="stat-s">{{ $s['deposit_count'] }}× setoran total</div></div>
                </div>
            </div>
        </div>

        {{-- CHART --}}
        <div class="card">
            <div class="card-head">
                <h3>Perjalanan tabungan</h3>
                <div class="tabs" data-chart-tabs>
                    <a href="#" class="active" data-mode="flow">Arus bulanan</a>
                    <a href="#" data-mode="balance">Saldo</a>
                </div>
            </div>
            <div class="chart-box"><canvas id="flowChart"></canvas></div>
        </div>

        {{-- RECENT --}}
        <div class="card">
            <div class="card-head">
                <h3>Transaksi terakhir</h3>
                <a href="{{ route('transactions.index') }}">Lihat semua →</a>
            </div>
            @if ($recent->isEmpty())
                <div class="empty">
                    <div class="ill">🐷</div>
                    <h4>Belum ada catatan</h4>
                    <p>Setoran pertamamu akan muncul di sini.</p>
                    <a href="{{ route('transactions.create') }}" class="btn btn-primary mt-3">Catat setoran pertama</a>
                </div>
            @else
                <div class="tx-list">
                    @foreach ($recent as $t)
                        @include('partials.tx-item', ['t' => $t, 'showDate' => true])
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="stack">
        {{-- INSIGHTS --}}
        @if (count($insights))
            <div class="card card-pink">
                <div class="card-head"><h3>💡 Catatan untukmu</h3></div>
                <div class="stack" style="gap:10px">
                    @foreach ($insights as $i)
                        <div class="insight"><span class="ic">{{ $i['icon'] }}</span><span>{{ $i['text'] }}</span></div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- GOALS --}}
        <div class="card">
            <div class="card-head">
                <h3>🎯 Target aktif <span class="muted small">({{ $goalCount }})</span></h3>
                <a href="{{ route('goals.index') }}">Semua →</a>
            </div>
            @forelse ($goals as $g)
                @php $p = $g->progress_cache; @endphp
                <a href="{{ route('goals.show', $g) }}" class="goal-mini">
                    <div class="ring" style="--p: {{ round($p['percent']) }}; --c: {{ $g->color }}"><div>{{ round($p['percent']) }}%</div></div>
                    <div class="grow">
                        <div class="bold ellipsis">{{ $g->emoji }} {{ $g->name }}</div>
                        <div class="small muted num">{{ money($p['saved'], $g->target_currency) }} / {{ money($g->target_amount, $g->target_currency) }}</div>
                        <div class="xs muted">
                            @if ($g->is_shared)💑 Bersama · @endif
                            @if ($p['days_left'] !== null) {{ $p['days_left'] >= 0 ? $p['days_left'].' hari lagi' : 'lewat '.abs($p['days_left']).' hari' }} @else tanpa tenggat @endif
                        </div>
                    </div>
                    <span class="muted" style="width:18px">@include('partials.icon', ['n' => 'chev'])</span>
                </a>
            @empty
                <div class="empty" style="padding:20px 10px">
                    <div class="ill">🎯</div>
                    <h4>Belum ada target</h4>
                    <p class="small">Mau nabung buat apa? Liburan, nikah, rumah?</p>
                    <a href="{{ route('goals.create') }}" class="btn btn-soft mt-3">+ Buat target</a>
                </div>
            @endforelse
            @if ($goals->isNotEmpty())
                <a href="{{ route('goals.create') }}" class="btn btn-soft btn-block mt-3">+ Target baru</a>
            @endif
        </div>

        {{-- COMPOSITION --}}
        <div class="card">
            <div class="card-head"><h3>🧺 Disimpan di mana?</h3></div>
            @if (empty($s['composition']))
                <p class="muted small">Belum ada saldo.</p>
            @else
                <div class="chart-box sm"><canvas id="compChart"></canvas></div>
                <div class="legend mt-3">
                    @php $palette = ['#ec4899', '#f9a8d4', '#a855f7', '#fb7185', '#f59e0b', '#14b8a6', '#db2777', '#c084fc']; @endphp
                    @foreach ($s['composition'] as $idx => $c)
                        <div class="row-between">
                            <div class="row" style="gap:8px"><span class="dot" style="background: {{ $palette[$idx % 8] }}"></span>{{ $c['label'] }}</div>
                            <div class="bold num">{{ money($c['amount'], $c['currency']) }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- AUD DETAIL --}}
        <div class="card">
            <div class="card-head"><h3>🇦🇺 Celengan AUD</h3><a href="{{ route('rates.index') }}">Kurs →</a></div>
            <div class="kv"><span class="k">Jumlah AUD dipegang</span><span class="v num">{{ aud($s['aud']) }}</span></div>
            <div class="kv"><span class="k">Modal (saat beli/setor)</span><span class="v num">{{ rupiah($s['aud_cost_idr']) }}</span></div>
            <div class="kv"><span class="k">Nilai hari ini</span><span class="v num">{{ rupiah($s['aud_value_idr']) }}</span></div>
            <div class="kv"><span class="k">Selisih kurs</span><span class="v num {{ $s['aud_gain'] >= 0 ? 'text-green' : 'text-red' }}">{{ rupiah($s['aud_gain'], true) }} ({{ number_format($s['aud_gain_pct'], 2, ',', '.') }}%)</span></div>
            <div class="kv"><span class="k">Porsi dari total</span><span class="v num">{{ number_format($s['aud_share'], 1, ',', '.') }}%</span></div>
        </div>
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
    const months = @json($s['months']);
    const short = (v) => {
        const a = Math.abs(v);
        if (a >= 1e9) return (v / 1e9).toFixed(1).replace('.', ',') + ' M';
        if (a >= 1e6) return (v / 1e6).toFixed(1).replace('.', ',') + ' jt';
        if (a >= 1e3) return (v / 1e3).toFixed(0) + ' rb';
        return v;
    };
    const ctx = document.getElementById('flowChart');
    const grad = ctx.getContext('2d').createLinearGradient(0, 0, 0, 260);
    grad.addColorStop(0, 'rgba(236,72,153,.35)'); grad.addColorStop(1, 'rgba(236,72,153,0)');
    const tooltip = { callbacks: { label: (c) => ' ' + c.dataset.label + ': ' + DC.idr(c.parsed.y) } };
    const flow = {
        type: 'bar',
        data: { labels: months.map(m => m.label), datasets: [
            { label: 'Masuk', data: months.map(m => m.in), backgroundColor: '#ec4899', borderRadius: 8, maxBarThickness: 22 },
            { label: 'Keluar', data: months.map(m => m.out), backgroundColor: '#fbcfe8', borderRadius: 8, maxBarThickness: 22 },
        ]},
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } }, tooltip },
            scales: { x: { grid: { display: false } }, y: { grid: { color: '#fbe7f0' }, ticks: { callback: short } } } }
    };
    const balance = {
        type: 'line',
        data: { labels: months.map(m => m.label), datasets: [
            { label: 'Saldo', data: months.map(m => m.balance), borderColor: '#ec4899', backgroundColor: grad, fill: true, tension: .35, pointRadius: 3, pointBackgroundColor: '#fff', pointBorderWidth: 2 },
        ]},
        options: { maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip },
            scales: { x: { grid: { display: false } }, y: { grid: { color: '#fbe7f0' }, ticks: { callback: short } } } }
    };
    let chart = new Chart(ctx, flow);
    document.querySelectorAll('[data-chart-tabs] a').forEach(a => a.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelectorAll('[data-chart-tabs] a').forEach(x => x.classList.remove('active'));
        a.classList.add('active');
        chart.destroy();
        chart = new Chart(ctx, a.dataset.mode === 'balance' ? balance : flow);
    }));

    const comp = @json($s['composition']);
    const cc = document.getElementById('compChart');
    if (cc && comp.length) {
        new Chart(cc, {
            type: 'doughnut',
            data: { labels: comp.map(c => c.label), datasets: [{ data: comp.map(c => Math.round(c.idr)), backgroundColor: ['#ec4899', '#f9a8d4', '#a855f7', '#fb7185', '#f59e0b', '#14b8a6', '#db2777', '#c084fc'], borderWidth: 3, borderColor: '#fff', hoverOffset: 6 }] },
            options: { maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => ' ' + DC.idr(c.parsed) } } } }
        });
    }
})();
</script>
@endpush

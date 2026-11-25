@extends('layouts.app')
@section('title', 'Beranda')

@section('content')
@php
    $hour = now()->hour;
    $greet = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam'));
    $fxCodes = array_keys($s['holdings']);
@endphp

<div class="page-head">
    <div>
        <h1>{{ $greet }}, {{ $user->display_name }}</h1>
        <p>{{ now()->translatedFormat('l, d F Y') }}</p>
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
            <div class="label">@include('partials.tile', ['n' => 'wallet', 'tone' => 'white']) Total tabungan (dirupiahkan)</div>
            <div class="big num">{{ rupiah($s['total_idr']) }}</div>
            <div class="sub num">≈ {{ money($s['total_usd'], 'USD') }} dengan kurs hari ini</div>
            <div class="hero-split">
                <div class="hero-box">
                    <div class="k">@include('partials.cur', ['c' => 'IDR']) Rupiah</div>
                    <div class="v num">{{ rupiah($s['idr']) }}</div>
                </div>
                <div class="hero-box">
                    <div class="k">@include('partials.icon', ['n' => 'globe', 'w' => 2]) Valas</div>
                    <div class="v num">{{ rupiah($s['fx_value_idr']) }}</div>
                    <div class="s ellipsis">{{ $fxCodes ? implode(' · ', array_slice($fxCodes, 0, 4)).(count($fxCodes) > 4 ? ' +'.(count($fxCodes) - 4) : '') : 'Belum ada valas' }}</div>
                </div>
                <div class="hero-box">
                    <div class="k">Bersih bulan ini</div>
                    <div class="v num">{{ rupiah($s['month_net'], true) }}</div>
                    @if ($s['monthly_target_pct'] !== null)
                        <div class="bar bar-white mt-1" style="height:5px"><span data-w="{{ round($s['monthly_target_pct']) }}" style="width:0"></span></div>
                    @endif
                </div>
            </div>
            <div class="hero-actions">
                <a href="{{ route('transactions.create') }}" class="btn btn-white">@include('partials.icon', ['n' => 'arrow-down']) Setor</a>
                <a href="{{ route('exchange.create') }}" class="btn btn-glass">@include('partials.icon', ['n' => 'swap']) Tukar</a>
                <a href="{{ route('transactions.create', ['type' => 'withdraw']) }}" class="btn btn-glass">@include('partials.icon', ['n' => 'arrow-up']) Tarik</a>
            </div>
        </div>

        {{-- STATS --}}
        <div class="grid g-4 stats">
            <div class="card">
                @include('partials.tile', ['n' => 'arrow-down', 'tone' => 'green', 'class' => 'tile-lg'])
                <div><div class="stat-k">Masuk bulan ini</div><div class="stat-v num">{{ rupiah_short($s['month_in']) }}</div><div class="stat-s">Keluar {{ rupiah_short($s['month_out']) }}</div></div>
            </div>
            <div class="card">
                @include('partials.tile', ['n' => 'chart', 'class' => 'tile-lg'])
                <div><div class="stat-k">Rata-rata/bulan</div><div class="stat-v num">{{ rupiah_short($s['avg_monthly']) }}</div><div class="stat-s">6 bulan terakhir</div></div>
            </div>
            <div class="card">
                @include('partials.tile', ['n' => $s['fx_gain'] >= 0 ? 'trend' : 'trend-down', 'tone' => $s['fx_gain'] >= 0 ? 'violet' : 'amber', 'class' => 'tile-lg'])
                <div>
                    <div class="stat-k">Selisih kurs</div>
                    <div class="stat-v num {{ $fxCodes ? ($s['fx_gain'] >= 0 ? 'text-green' : 'text-red') : '' }}">{{ $fxCodes ? ($s['fx_gain'] >= 0 ? '+' : '').rupiah_short($s['fx_gain']) : '—' }}</div>
                    <div class="stat-s">{{ $fxCodes ? number_format($s['fx_gain_pct'], 2, ',', '.').'% dari modal' : 'Belum ada valas' }}</div>
                </div>
            </div>
            <div class="card">
                @include('partials.tile', ['n' => 'flame', 'tone' => 'amber', 'class' => 'tile-lg'])
                <div><div class="stat-k">Streak</div><div class="stat-v num">{{ $s['streak'] }} bulan</div><div class="stat-s">{{ $s['deposit_count'] }}× setoran</div></div>
            </div>
        </div>

        {{-- CHART --}}
        <div class="card">
            <div class="card-head">
                <h3>@include('partials.tile', ['n' => 'chart']) Perjalanan tabungan</h3>
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
                <h3>@include('partials.tile', ['n' => 'list']) Transaksi terakhir</h3>
                <a href="{{ route('transactions.index') }}" class="card-link">Lihat semua @include('partials.icon', ['n' => 'chev'])</a>
            </div>
            @if ($recent->isEmpty())
                @include('partials.empty', ['icon' => 'wallet', 'title' => 'Belum ada catatan', 'text' => 'Setoran pertamamu akan muncul di sini.', 'action' => route('transactions.create'), 'actionText' => 'Catat setoran pertama'])
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
            <div class="card card-soft">
                <div class="card-head"><h3>@include('partials.tile', ['n' => 'bulb']) Catatan untukmu</h3></div>
                <div class="stack" style="gap:10px">
                    @foreach ($insights as $i)
                        <div class="insight">@include('partials.tile', ['n' => $i['icon'], 'tone' => $i['tone'] ?? null])<span>{{ $i['text'] }}</span></div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- GOALS --}}
        <div class="card">
            <div class="card-head">
                <h3>@include('partials.tile', ['n' => 'target']) Target aktif <span class="count">{{ $goalCount }}</span></h3>
                <a href="{{ route('goals.index') }}" class="card-link">Semua @include('partials.icon', ['n' => 'chev'])</a>
            </div>
            @forelse ($goals as $g)
                @php $p = $g->progress_cache; @endphp
                <a href="{{ route('goals.show', $g) }}" class="goal-mini">
                    <div class="ring" style="--p: {{ round($p['percent']) }}; --c: {{ $g->color }}"><div>{{ round($p['percent']) }}%</div></div>
                    <div class="grow">
                        <div class="bold ellipsis goal-mini-name">{{ $g->emoji }} {{ $g->name }}</div>
                        <div class="small muted num ellipsis">{{ money($p['saved'], $g->target_currency) }} / {{ money($g->target_amount, $g->target_currency) }}</div>
                        <div class="xs muted">
                            @if ($g->is_shared)Bersama · @endif
                            @if ($p['days_left'] !== null) {{ $p['days_left'] >= 0 ? $p['days_left'].' hari lagi' : 'lewat '.abs($p['days_left']).' hari' }} @else tanpa tenggat @endif
                        </div>
                    </div>
                    <span class="chev">@include('partials.icon', ['n' => 'chev'])</span>
                </a>
            @empty
                @include('partials.empty', ['icon' => 'target', 'title' => 'Belum ada target', 'text' => 'Mau nabung buat apa? Liburan, nikah, rumah?', 'action' => route('goals.create'), 'actionText' => 'Buat target', 'pad' => '12px 8px'])
            @endforelse
            @if ($goals->isNotEmpty())
                <a href="{{ route('goals.create') }}" class="btn btn-soft btn-block mt-3">@include('partials.icon', ['n' => 'plus']) Target baru</a>
            @endif
        </div>

        {{-- FX --}}
        <div class="card">
            <div class="card-head">
                <h3>@include('partials.tile', ['n' => 'globe', 'tone' => 'violet']) Celengan valas</h3>
                <a href="{{ route('rates.index') }}" class="card-link">Kurs @include('partials.icon', ['n' => 'chev'])</a>
            </div>
            @forelse ($s['holdings'] as $h)
                <div class="kv">
                    <span class="k">@include('partials.cur', ['c' => $h['code']]) <span class="num" style="color:var(--ink);font-weight:700">{{ money($h['qty'], $h['code']) }}</span></span>
                    <span class="v num">{{ rupiah($h['value_idr']) }}
                        <span class="xs {{ $h['gain'] >= 0 ? 'text-green' : 'text-red' }}" style="display:block;font-weight:600">{{ rupiah($h['gain'], true) }}</span>
                    </span>
                </div>
            @empty
                @include('partials.empty', ['icon' => 'globe', 'title' => 'Belum ada valas', 'text' => 'Setor dalam USD, AUD, SGD, atau mata uang lain.', 'action' => route('transactions.create', ['currency' => 'USD']), 'actionText' => 'Setor valas', 'pad' => '12px 8px'])
            @endforelse
            @if ($fxCodes)
                <div class="hint mt-2">Nilai dihitung dengan kurs hari ini. Selisih = nilai sekarang − modal saat disimpan.</div>
            @endif
        </div>

        {{-- COMPOSITION --}}
        @if (! empty($s['composition']))
            <div class="card">
                <div class="card-head"><h3>@include('partials.tile', ['n' => 'pie']) Disimpan di mana</h3></div>
                <div class="chart-box sm"><canvas id="compChart"></canvas></div>
                <div class="legend mt-3" data-legend>
                    @foreach ($s['composition'] as $idx => $c)
                        <div class="row-between">
                            <div class="row" style="gap:8px;min-width:0"><span class="dot" data-dot="{{ $idx }}"></span><span class="ellipsis">{{ $c['label'] }}</span></div>
                            <div class="bold num">{{ money($c['amount'], $c['currency']) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    const acc = DC.cssVar('--acc-500'), acc2 = DC.cssVar('--acc-200'), rgb = DC.cssVar('--acc-rgb'), line = DC.cssVar('--line'), muted = DC.cssVar('--ink-3');
    const palette = [acc, DC.cssVar('--acc-300'), '#a855f7', '#f59e0b', '#14b8a6', '#3b82f6', DC.cssVar('--acc-700'), '#94a3b8'];
    document.querySelectorAll('[data-dot]').forEach(d => d.style.background = palette[d.dataset.dot % palette.length]);
    if (!window.Chart) return;
    Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";
    Chart.defaults.color = muted;
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
    grad.addColorStop(0, `rgba(${rgb},.28)`); grad.addColorStop(1, `rgba(${rgb},0)`);
    const tooltip = { callbacks: { label: (c) => ' ' + c.dataset.label + ': ' + DC.idr(c.parsed.y) } };
    const scales = { x: { grid: { display: false } }, y: { grid: { color: line }, border: { display: false }, ticks: { callback: short } } };
    const flow = {
        type: 'bar',
        data: { labels: months.map(m => m.label), datasets: [
            { label: 'Masuk', data: months.map(m => m.in), backgroundColor: acc, borderRadius: 6, maxBarThickness: 20 },
            { label: 'Keluar', data: months.map(m => m.out), backgroundColor: acc2, borderRadius: 6, maxBarThickness: 20 },
        ]},
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } }, tooltip }, scales }
    };
    const balance = {
        type: 'line',
        data: { labels: months.map(m => m.label), datasets: [
            { label: 'Saldo', data: months.map(m => m.balance), borderColor: acc, backgroundColor: grad, fill: true, tension: .35, pointRadius: 3, pointBackgroundColor: '#fff', pointBorderColor: acc, pointBorderWidth: 2 },
        ]},
        options: { maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip }, scales }
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
            data: { labels: comp.map(c => c.label), datasets: [{ data: comp.map(c => Math.round(c.idr)), backgroundColor: palette, borderWidth: 3, borderColor: '#fff', hoverOffset: 6 }] },
            options: { maintainAspectRatio: false, cutout: '70%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => ' ' + DC.idr(c.parsed) } } } }
        });
    }
})();
</script>
@endpush

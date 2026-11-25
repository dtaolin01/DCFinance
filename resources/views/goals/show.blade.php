@extends('layouts.app')
@section('title', $goal->name)

@section('content')
@php $cur = $goal->target_currency; @endphp
<div class="page-head">
    <div>
        <a href="{{ route('goals.index') }}" class="small bold row" style="gap:4px"><span style="width:16px;display:inline-flex">@include('partials.icon', ['n' => 'back'])</span>Semua target</a>
        <h1 class="mt-1">{{ $goal->emoji }} {{ $goal->name }}</h1>
        <p>
            @if ($goal->is_shared) 💑 Target bersama @else {{ $goal->user->emoji }} Target {{ $goal->user->display_name }} @endif
            · Prioritas {{ strtolower($goal->priorityLabel()) }}
            @if ($goal->deadline) · 📅 {{ $goal->deadline->translatedFormat('d F Y') }} @endif
        </p>
    </div>
    @if ($canEdit)
        <div class="page-actions">
            <a href="{{ route('goals.edit', $goal) }}" class="btn btn-ghost">@include('partials.icon', ['n' => 'edit']) Edit</a>
            <a href="{{ route('transactions.create', ['goal' => $goal->id, 'currency' => $cur, 'redirect' => 'goal']) }}" class="btn btn-primary">@include('partials.icon', ['n' => 'plus']) Tabung ke sini</a>
        </div>
    @endif
</div>

<div class="grid g-main">
    <div class="stack">
        <div class="card">
            <div class="row wrap" style="gap:26px">
                <div class="ring ring-lg" style="--p: {{ round($p['percent'], 1) }}; --c: {{ $goal->color }}">
                    <div>{{ floor($p['percent']) }}%<span class="xs muted" style="font-weight:600">terkumpul</span></div>
                </div>
                <div style="flex:1;min-width:220px">
                    @if ($p['done'])
                        <span class="badge badge-green">🎉 Target tercapai!</span>
                    @elseif ($p['overdue'])
                        <span class="badge badge-red">⏰ Lewat tenggat</span>
                    @elseif ($p['on_track'] === true)
                        <span class="badge badge-green">✅ Sesuai jalur</span>
                    @elseif ($p['on_track'] === false)
                        <span class="badge badge-amber">🏃 Perlu dikejar</span>
                    @endif
                    <div class="big bold num mt-2" style="font-size:30px;letter-spacing:-.02em">{{ money($p['saved'], $cur) }}</div>
                    <div class="muted num">dari {{ money($p['target'], $cur) }}
                        @if ($cur === 'AUD') <span class="small">(≈ {{ rupiah($p['target'] * $rate['rate']) }})</span>@endif
                    </div>
                    <div class="bar bar-lg mt-2"><span data-w="{{ round($p['percent'], 1) }}" style="width:0;background:linear-gradient(90deg, {{ $goal->color }}99, {{ $goal->color }})"></span></div>
                    <div class="row-between small mt-1"><span class="muted">Sisa</span><span class="bold num">{{ money($p['remaining'], $cur) }}</span></div>
                </div>
            </div>
            @if ($goal->notes)
                <div class="insight mt-3"><span class="ic">📝</span><span>{{ $goal->notes }}</span></div>
            @endif
        </div>

        @unless ($p['done'])
            <div class="grid g-3">
                <div class="card card-tight">
                    <div class="stat-k">Per bulan</div>
                    <div class="stat-v num">{{ $p['per_month'] ? money($p['per_month'], $cur) : '—' }}</div>
                    <div class="stat-s">agar tepat waktu</div>
                </div>
                <div class="card card-tight">
                    <div class="stat-k">Per minggu</div>
                    <div class="stat-v num">{{ $p['per_week'] ? money($p['per_week'], $cur) : '—' }}</div>
                    <div class="stat-s">{{ $p['days_left'] !== null && $p['days_left'] >= 0 ? $p['days_left'].' hari lagi' : 'atur tenggat untuk rencana' }}</div>
                </div>
                <div class="card card-tight">
                    <div class="stat-k">Per hari</div>
                    <div class="stat-v num">{{ $p['per_day'] ? money($p['per_day'], $cur) : '—' }}</div>
                    <div class="stat-s">kalau rutin harian</div>
                </div>
            </div>
        @endunless

        <div class="card">
            <div class="card-head"><h3>Riwayat tabungan ke target ini</h3><span class="muted small">{{ $p['count'] }} transaksi</span></div>
            @forelse ($history as $t)
                @include('partials.tx-item', ['t' => $t, 'showDate' => true, 'showUser' => $goal->is_shared, 'actions' => true])
            @empty
                <div class="empty">
                    <div class="ill">🪙</div>
                    <h4>Belum ada tabungan untuk target ini</h4>
                    <p>Saat mencatat setoran, pilih target ini di kolom “Untuk target”.</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="stack">
        <div class="card card-pink">
            <div class="card-head"><h3>🔮 Perkiraan</h3></div>
            <div class="kv"><span class="k">Kecepatan 3 bulan terakhir</span><span class="v num">{{ money(max($p['pace_per_month'], 0), $cur) }}/bln</span></div>
            <div class="kv"><span class="k">Perkiraan tercapai</span><span class="v">
                @if ($p['done']) Sudah 🎉
                @elseif ($p['eta']) {{ $p['eta']->translatedFormat('M Y') }}
                @else <span class="muted">belum bisa diperkirakan</span> @endif
            </span></div>
            @if ($goal->deadline)
                <div class="kv"><span class="k">Tenggat</span><span class="v">{{ $goal->deadline->translatedFormat('d M Y') }}</span></div>
            @endif
            @if (! $p['done'] && $p['eta'] && $goal->deadline && $p['eta']->gt($goal->deadline))
                <div class="alert alert-amber small mt-2">Dengan kecepatan sekarang, target ini telat ±{{ (int) round($goal->deadline->diffInDays($p['eta']) / 30.4375) ?: 1 }} bulan. Naikkan setoran ke {{ money($p['per_month'], $cur) }}/bulan ya!</div>
            @endif
        </div>

        <div class="card">
            <div class="card-head"><h3>💼 Isi celengan</h3></div>
            <div class="kv"><span class="k">🇮🇩 Rupiah</span><span class="v num">{{ rupiah($p['idr_part']) }}</span></div>
            <div class="kv"><span class="k">🇦🇺 AUD</span><span class="v num">{{ aud($p['aud_part']) }}</span></div>
            <div class="kv"><span class="k">Total dirupiahkan</span><span class="v num">{{ rupiah($p['saved_idr']) }}</span></div>
            <div class="xs muted mt-1">AUD dinilai dengan kurs hari ini (Rp {{ rate_fmt($rate['rate']) }}).</div>
        </div>

        @if ($goal->is_shared && count($p['by_user']))
            <div class="card">
                <div class="card-head"><h3>💑 Kontribusi</h3></div>
                @php $sum = max(array_sum(array_map(fn ($v) => max($v, 0), $p['by_user'])), 1); @endphp
                @foreach ($people as $person)
                    @php $v = $p['by_user'][$person->id] ?? 0; @endphp
                    <div class="mt-2">
                        <div class="row-between small"><span class="bold">{{ $person->emoji }} {{ $person->display_name }}</span><span class="num">{{ rupiah($v) }}</span></div>
                        <div class="bar mt-1"><span data-w="{{ round(max($v, 0) / $sum * 100) }}" style="width:0;background: {{ $person->color }}"></span></div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

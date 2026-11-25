@extends('layouts.app')
@section('title', 'Riwayat')

@section('content')
<div class="page-head">
    <div>
        <h1>Riwayat tabungan</h1>
        <p>Semua setoran, penarikan, dan tukar uangmu.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('exchange.create') }}" class="btn btn-ghost">@include('partials.icon', ['n' => 'swap']) Tukar</a>
        <a href="{{ route('transactions.create') }}" class="btn btn-primary">@include('partials.icon', ['n' => 'plus']) Catat</a>
    </div>
</div>

<div class="card mb-3">
    <form method="GET" class="filters">
        <select name="month" class="input" onchange="this.form.submit()">
            <option value="">Semua bulan</option>
            @foreach ($months as $m)
                <option value="{{ $m }}" @selected($filters['month'] === $m)>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $m.'-01')->translatedFormat('F Y') }}</option>
            @endforeach
        </select>
        <select name="type" class="input" onchange="this.form.submit()">
            <option value="">Semua jenis</option>
            <option value="in" @selected($filters['type'] === 'in')>Setor</option>
            <option value="out" @selected($filters['type'] === 'out')>Tarik</option>
            <option value="exchange" @selected($filters['type'] === 'exchange')>Tukar uang</option>
        </select>
        <select name="currency" class="input" onchange="this.form.submit()">
            <option value="">IDR & AUD</option>
            <option value="IDR" @selected($filters['currency'] === 'IDR')>🇮🇩 IDR</option>
            <option value="AUD" @selected($filters['currency'] === 'AUD')>🇦🇺 AUD</option>
        </select>
        <input type="search" name="q" value="{{ $filters['search'] }}" class="input grow" placeholder="Cari catatan…">
        <button class="btn btn-soft">@include('partials.icon', ['n' => 'search']) Cari</button>
        @if (array_filter($filters))
            <a href="{{ route('transactions.index') }}" class="btn btn-ghost">Reset</a>
        @endif
    </form>
</div>

<div class="grid g-3 mb-3">
    <div class="card card-tight"><div class="stat"><div class="stat-icon">⬇️</div><div><div class="stat-k">Total setor</div><div class="stat-v num text-green">{{ rupiah($totalIn) }}</div></div></div></div>
    <div class="card card-tight"><div class="stat"><div class="stat-icon">⬆️</div><div><div class="stat-k">Total tarik</div><div class="stat-v num text-red">{{ rupiah($totalOut) }}</div></div></div></div>
    <div class="card card-tight"><div class="stat"><div class="stat-icon">🧮</div><div><div class="stat-k">Bersih</div><div class="stat-v num">{{ rupiah($totalIn - $totalOut, true) }}</div><div class="stat-s">nilai rupiah saat transaksi</div></div></div></div>
</div>

<div class="card">
    @if ($transactions->isEmpty())
        <div class="empty">
            <div class="ill">🔍</div>
            <h4>Tidak ada transaksi</h4>
            <p>Coba ubah filter, atau catat transaksi baru.</p>
        </div>
    @else
        @foreach ($grouped as $day => $items)
            @php
                $d = \Illuminate\Support\Carbon::parse($day);
                $net = $items->where('type', 'deposit')->sum('amount_idr') - $items->where('type', 'withdraw')->sum('amount_idr');
            @endphp
            <div class="day-head">
                <span>{{ $d->isToday() ? 'Hari ini' : ($d->isYesterday() ? 'Kemarin' : $d->translatedFormat('l, d M Y')) }}</span>
                @if ($net != 0)<span class="num {{ $net > 0 ? 'text-green' : 'text-red' }}">{{ rupiah($net, true) }}</span>@endif
            </div>
            <div class="tx-list">
                @foreach ($items as $t)
                    @include('partials.tx-item', ['t' => $t, 'actions' => true])
                @endforeach
            </div>
        @endforeach

        @if ($transactions->hasPages())
            <div class="pager">
                @if ($transactions->onFirstPage())<span class="disabled">‹</span>@else<a href="{{ $transactions->previousPageUrl() }}">‹</a>@endif
                @foreach ($transactions->getUrlRange(max(1, $transactions->currentPage() - 2), min($transactions->lastPage(), $transactions->currentPage() + 2)) as $page => $url)
                    @if ($page == $transactions->currentPage())<span class="active">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                @endforeach
                @if ($transactions->hasMorePages())<a href="{{ $transactions->nextPageUrl() }}">›</a>@else<span class="disabled">›</span>@endif
            </div>
        @endif
    @endif
</div>
@endsection

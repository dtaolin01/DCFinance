@extends('layouts.app')
@php $editing = $tx->exists; @endphp
@section('title', $editing ? 'Edit transaksi' : 'Catat tabungan')

@section('content')
@php
    $amountVal = old('amount', $tx->amount !== null ? ($tx->currency === 'AUD' ? number_format($tx->amount, 2, ',', '.') : number_format($tx->amount, 0, ',', '.')) : '');
    $rateVal = old('rate', $editing && $tx->currency === 'AUD' ? rate_fmt($tx->rate) : rate_fmt($rate['rate']));
    $type = old('type', $tx->type);
    $cur = old('currency', $tx->currency);
@endphp

<div class="page-head">
    <div>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" class="small bold row" style="gap:4px"><span style="width:16px;display:inline-flex">@include('partials.icon', ['n' => 'back'])</span>Kembali</a>
        <h1 class="mt-1">{{ $editing ? 'Edit transaksi ✏️' : 'Catat tabungan 💖' }}</h1>
        <p>Rupiah atau AUD cash — semua otomatis dirupiahkan.</p>
    </div>
</div>

<div class="grid g-main">
    <form method="POST" action="{{ $editing ? route('transactions.update', $tx) : route('transactions.store') }}" class="card form" data-tx-form data-balances='@json($balances)' @if($editing) data-editing="1" @endif>
        @csrf
        @if ($editing) @method('PUT') @endif
        @if (request('redirect') === 'goal')<input type="hidden" name="redirect" value="goal">@endif

        <div class="field">
            <span class="label">Jenis</span>
            <div class="seg">
                <label><input type="radio" name="type" value="deposit" @checked($type === 'deposit')><span class="in">⬇️ Setor</span></label>
                <label><input type="radio" name="type" value="withdraw" @checked($type === 'withdraw')><span class="out">⬆️ Tarik</span></label>
            </div>
            <div class="hint" data-type-hint></div>
        </div>

        <div class="field">
            <span class="label">Mata uang</span>
            <div class="seg">
                <label><input type="radio" name="currency" value="IDR" @checked($cur === 'IDR')><span>🇮🇩 Rupiah</span></label>
                <label><input type="radio" name="currency" value="AUD" @checked($cur === 'AUD')><span>🇦🇺 AUD</span></label>
            </div>
        </div>

        <div class="field">
            <label for="amount">Nominal</label>
            <div class="input-money">
                <span class="prefix" data-prefix>Rp</span>
                <input id="amount" name="amount" class="input num @error('amount') is-invalid @enderror" inputmode="decimal" autocomplete="off" placeholder="0" value="{{ $amountVal }}" data-money data-currency-from="currency" required @unless($editing) autofocus @endunless>
            </div>
            @error('amount')<div class="error">{{ $message }}</div>@else<div class="hint" data-balance></div>@enderror
            <div class="row wrap mt-2" style="gap:6px">
                <button type="button" class="btn btn-soft btn-sm" data-quick data-idr="50000" data-aud="5">+50rb / +A$5</button>
                <button type="button" class="btn btn-soft btn-sm" data-quick data-idr="100000" data-aud="10">+100rb / +A$10</button>
                <button type="button" class="btn btn-soft btn-sm" data-quick data-idr="500000" data-aud="50">+500rb / +A$50</button>
                <button type="button" class="btn btn-soft btn-sm" data-quick data-idr="1000000" data-aud="100">+1jt / +A$100</button>
            </div>
        </div>

        <div class="field" data-rate-wrap>
            <label for="rate">Kurs 1 AUD ke Rupiah</label>
            <div class="input-money sm">
                <span class="prefix">Rp</span>
                <input id="rate" name="rate" class="input num @error('rate') is-invalid @enderror" inputmode="decimal" value="{{ $rateVal }}" data-money data-decimal="always">
            </div>
            @error('rate')<div class="error">{{ $message }}</div>@else
            <div class="hint">Terisi otomatis dari kurs hari ini ({{ $rate['source'] }}). Ganti kalau kurs tukarmu berbeda.</div>@enderror
        </div>

        <div class="convert-box" data-preview-wrap>
            <span style="font-size:26px">🐨</span>
            <div><div class="small muted">Kalau dirupiahkan</div><div class="big num" data-preview>Rp 0</div></div>
        </div>

        <div class="grid g-2" style="gap:14px">
            <div class="field">
                <label for="date">Tanggal</label>
                <input id="date" type="date" name="date" class="input @error('date') is-invalid @enderror" value="{{ old('date', optional($tx->date)->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}" required>
                @error('date')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="method">Disimpan di</label>
                <select id="method" name="method" class="input">
                    @foreach (config('dcfinance.methods') as $key => $m)
                        <option value="{{ $key }}" @selected(old('method', $tx->method) === $key)>{{ $m['icon'] }} {{ $m['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="field">
            <label for="goal_id">Untuk target <span class="muted">(opsional)</span></label>
            <select id="goal_id" name="goal_id" class="input @error('goal_id') is-invalid @enderror">
                <option value="">— Tabungan umum —</option>
                @foreach ($goals as $g)
                    <option value="{{ $g->id }}" @selected((string) old('goal_id', $tx->goal_id) === (string) $g->id)>{{ $g->emoji }} {{ $g->name }}{{ $g->is_shared ? ' (bersama)' : '' }}</option>
                @endforeach
            </select>
            @error('goal_id')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="note">Catatan <span class="muted">(opsional)</span></label>
            <input id="note" name="note" class="input" maxlength="255" value="{{ old('note', $tx->note) }}" placeholder="mis. Gaji November, sisa jajan, angpau…">
        </div>

        <button class="btn btn-primary btn-lg btn-block">{{ $editing ? 'Simpan perubahan' : 'Simpan' }}</button>
    </form>

    <div class="stack">
        <div class="card card-pink">
            <div class="card-head"><h3>🇦🇺 Kurs hari ini</h3></div>
            <div class="big bold num" style="font-size:26px;color:var(--pink-600)">Rp {{ rate_fmt($rate['rate']) }}</div>
            <div class="small muted">per 1 AUD · {{ $rate['source'] }}</div>
            <div class="divider mt-2"></div>
            <div class="kv"><span class="k">Saldo Rupiah</span><span class="v num">{{ rupiah($balances['IDR']) }}</span></div>
            <div class="kv"><span class="k">Saldo AUD</span><span class="v num">{{ aud($balances['AUD']) }}</span></div>
        </div>
        <div class="card">
            <h3 style="font-size:15px">Tips cepat</h3>
            <ul class="small muted" style="padding-left:18px;margin:10px 0 0;display:flex;flex-direction:column;gap:6px">
                <li>Simpan uang <b>AUD cash</b> dengan memilih mata uang AUD — nilai rupiahnya mengikuti kurs.</li>
                <li>Beli AUD pakai rupiahmu? Gunakan menu <a href="{{ route('exchange.create') }}">Tukar Uang</a> supaya tidak terhitung dobel.</li>
                <li>Hubungkan ke <b>target</b> agar progresnya ikut naik.</li>
            </ul>
        </div>
    </div>
</div>
@endsection

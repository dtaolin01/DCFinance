@extends('layouts.app')
@section('title', 'Tukar Uang')

@push('data')
    @include('partials.dc-data')
@endpush

@section('content')
@php
    $from = old('from', 'IDR');
    $to = old('to', collect(array_keys($balances))->first(fn ($c) => $c !== 'IDR') ?? 'USD');
@endphp
<div class="page-head">
    <div>
        <h1>Tukar uang</h1>
        <p>Pindahkan tabungan antar mata uang, mis. Rupiah → USD cash. Tidak dihitung sebagai setor/tarik.</p>
    </div>
</div>

<div class="grid g-main">
    <form method="POST" action="{{ route('exchange.store') }}" class="card form" data-exchange-form data-balances='@json($balances)'>
        @csrf
        <div class="grid g-2" style="gap:14px;align-items:end">
            <div class="field">
                <label for="from">Dari</label>
                @include('partials.cur-select', ['name' => 'from', 'selected' => $from, 'groups' => $currencies])
            </div>
            <div class="field">
                <label for="to" class="row-between" style="display:flex">Ke
                    <button type="button" class="btn btn-ghost btn-sm" data-swap style="padding:2px 8px">@include('partials.icon', ['n' => 'swap']) Balik</button>
                </label>
                @include('partials.cur-select', ['name' => 'to', 'selected' => $to, 'groups' => $currencies])
                @error('to')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="field">
            <label for="amount_from">Jumlah yang ditukar</label>
            <div class="input-money">
                <span class="prefix" data-prefix-from>{{ \App\Support\Currency::symbol($from) }}</span>
                <input id="amount_from" name="amount_from" class="input num @error('amount_from') is-invalid @enderror" inputmode="decimal" placeholder="0" value="{{ old('amount_from') }}" data-money data-currency-from="from" required>
            </div>
            @error('amount_from')<div class="error">{{ $message }}</div>@else<div class="hint" data-balance></div>@enderror
        </div>

        <div class="field">
            <label for="amount_to">Jumlah yang diterima</label>
            <div class="input-money">
                <span class="prefix" data-prefix-to>{{ \App\Support\Currency::symbol($to) }}</span>
                <input id="amount_to" name="amount_to" class="input num @error('amount_to') is-invalid @enderror" inputmode="decimal" placeholder="0" value="{{ old('amount_to') }}" data-money data-currency-from="to" required>
            </div>
            @error('amount_to')<div class="error">{{ $message }}</div>@else
            <div class="hint">Terisi otomatis dengan kurs hari ini. Ubah sesuai uang yang benar-benar kamu terima. <a href="#" data-reset-rate>Hitung ulang</a></div>@enderror
        </div>

        <div class="convert-box">
            @include('partials.tile', ['n' => 'calc', 'tone' => 'violet'])
            <div><div class="small muted">Kurs transaksi ini</div><div class="big num" data-effective>—</div></div>
        </div>

        <div class="grid g-2" style="gap:14px">
            <div class="field">
                <label for="method_from">Diambil dari</label>
                <select id="method_from" name="method_from" class="input">
                    @foreach (config('dcfinance.methods') as $key => $m)
                        <option value="{{ $key }}" @selected(old('method_from', 'bank') === $key)>{{ $m['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="method_to">Disimpan ke</label>
                <select id="method_to" name="method_to" class="input">
                    @foreach (config('dcfinance.methods') as $key => $m)
                        <option value="{{ $key }}" @selected(old('method_to', 'cash') === $key)>{{ $m['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="date">Tanggal</label>
                <input id="date" type="date" name="date" class="input" value="{{ old('date', today()->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}" required>
            </div>
            <div class="field">
                <label for="note">Catatan <span class="opt">(opsional)</span></label>
                <input id="note" name="note" class="input" value="{{ old('note') }}" placeholder="mis. Money changer bandara">
            </div>
        </div>
        <button class="btn btn-primary btn-lg btn-block">Simpan tukar uang</button>
    </form>

    <div class="stack">
        <div class="card">
            <div class="card-head"><h3>@include('partials.tile', ['n' => 'wallet']) Saldo kamu</h3></div>
            @foreach ($balances as $code => $amt)
                <div class="kv"><span class="k">@include('partials.cur', ['c' => $code]) {{ \App\Support\Currency::name($code) }}</span><span class="v num">{{ money($amt, $code) }}</span></div>
            @endforeach
        </div>
        <div class="card">
            <div class="card-head"><h3>@include('partials.tile', ['n' => 'info']) Cara kerjanya</h3></div>
            <ul class="list-tips">
                <li>@include('partials.tile', ['n' => 'arrow-up', 'tone' => 'red'])<span>Saldo mata uang asal berkurang sejumlah yang ditukar.</span></li>
                <li>@include('partials.tile', ['n' => 'arrow-down', 'tone' => 'green'])<span>Saldo mata uang tujuan bertambah sejumlah yang diterima.</span></li>
                <li>@include('partials.tile', ['n' => 'lock'])<span>Kurs transaksi ini disimpan sebagai modal, untuk menghitung untung/rugi kurs nanti.</span></li>
            </ul>
        </div>
    </div>
</div>
@endsection

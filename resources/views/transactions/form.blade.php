@extends('layouts.app')
@php $editing = $tx->exists; @endphp
@section('title', $editing ? 'Edit transaksi' : 'Catat tabungan')

@push('data')
    @include('partials.dc-data')
@endpush

@section('content')
@php
    $cur = old('currency', $tx->currency);
    $dec = \App\Support\Currency::decimals($cur);
    $amountVal = old('amount', $tx->amount !== null ? number_format($tx->amount, $dec, ',', '.') : '');
    $rateVal = old('rate', $editing && $tx->currency !== 'IDR' ? rate_fmt($tx->rate) : '');
    $type = old('type', $tx->type);
@endphp

<div class="page-head">
    <div>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" class="back-link">@include('partials.icon', ['n' => 'back'])Kembali</a>
        <h1>{{ $editing ? 'Edit transaksi' : 'Catat tabungan' }}</h1>
        <p>Rupiah atau mata uang asing apa pun — nilainya otomatis dirupiahkan.</p>
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
                <label><input type="radio" name="type" value="deposit" @checked($type === 'deposit')><span class="in">@include('partials.icon', ['n' => 'arrow-down']) Setor</span></label>
                <label><input type="radio" name="type" value="withdraw" @checked($type === 'withdraw')><span class="out">@include('partials.icon', ['n' => 'arrow-up']) Tarik</span></label>
            </div>
            <div class="hint" data-type-hint></div>
        </div>

        <div class="field">
            <label for="currency">Mata uang</label>
            <div class="chips mb-2">
                @foreach ($quick as $q)
                    <button type="button" class="chip" data-pick-cur="{{ $q }}">{{ $q }}</button>
                @endforeach
            </div>
            @include('partials.cur-select', ['name' => 'currency', 'selected' => $cur, 'groups' => $currencies, 'required' => true])
            @error('currency')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="amount">Nominal <span class="opt">· <span data-cur-name>{{ \App\Support\Currency::name($cur) }}</span></span></label>
            <div class="input-money">
                <span class="prefix" data-prefix>{{ \App\Support\Currency::symbol($cur) }}</span>
                <input id="amount" name="amount" class="input num @error('amount') is-invalid @enderror" inputmode="decimal" autocomplete="off" placeholder="0" value="{{ $amountVal }}" data-money data-currency-from="currency" required @unless($editing) autofocus @endunless>
            </div>
            @error('amount')<div class="error">{{ $message }}</div>@else<div class="hint" data-balance></div>@enderror
            <div class="chips mt-2">
                @for ($i = 0; $i < 4; $i++)
                    <button type="button" class="chip" data-quick="{{ $i }}">+</button>
                @endfor
            </div>
        </div>

        <div class="field" data-rate-wrap>
            <label for="rate" data-rate-label>Kurs 1 {{ $cur }} dalam Rupiah</label>
            <div class="input-money sm">
                <span class="prefix">Rp</span>
                <input id="rate" name="rate" class="input num @error('rate') is-invalid @enderror" inputmode="decimal" value="{{ $rateVal }}" data-money data-decimal="rate">
            </div>
            @error('rate')<div class="error">{{ $message }}</div>@else<div class="hint" data-rate-hint></div>@enderror
        </div>

        <div class="convert-box" data-preview-wrap>
            @include('partials.tile', ['n' => 'swap', 'tone' => 'violet'])
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
                        <option value="{{ $key }}" @selected(old('method', $tx->method) === $key)>{{ $m['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="field">
            <label for="goal_id">Untuk target <span class="opt">(opsional)</span></label>
            <select id="goal_id" name="goal_id" class="input @error('goal_id') is-invalid @enderror">
                <option value="">Tabungan umum</option>
                @foreach ($goals as $g)
                    <option value="{{ $g->id }}" @selected((string) old('goal_id', $tx->goal_id) === (string) $g->id)>{{ $g->emoji }} {{ $g->name }}{{ $g->is_shared ? ' (bersama)' : '' }}</option>
                @endforeach
            </select>
            @error('goal_id')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label for="note">Catatan <span class="opt">(opsional)</span></label>
            <input id="note" name="note" class="input" maxlength="255" value="{{ old('note', $tx->note) }}" placeholder="mis. Gaji November, sisa uang jajan, angpau">
        </div>

        <button class="btn btn-primary btn-lg btn-block">{{ $editing ? 'Simpan perubahan' : 'Simpan' }}</button>
    </form>

    <div class="stack">
        <div class="card">
            <div class="card-head"><h3>@include('partials.tile', ['n' => 'wallet']) Saldo kamu</h3></div>
            @foreach ($balances as $code => $amt)
                <div class="kv"><span class="k">@include('partials.cur', ['c' => $code]) {{ \App\Support\Currency::name($code) }}</span><span class="v num">{{ money($amt, $code) }}</span></div>
            @endforeach
        </div>
        <div class="card">
            <div class="card-head"><h3>@include('partials.tile', ['n' => 'bulb']) Tips</h3></div>
            <ul class="list-tips">
                <li>@include('partials.tile', ['n' => 'globe'])<span>Simpan <b>valas cash</b> (USD, AUD, SGD, dan lainnya) dengan memilih mata uangnya — nilai rupiahnya mengikuti kurs.</span></li>
                <li>@include('partials.tile', ['n' => 'swap'])<span>Membeli valas pakai rupiahmu? Pakai menu <a href="{{ route('exchange.create') }}">Tukar Uang</a> supaya tidak terhitung dobel.</span></li>
                <li>@include('partials.tile', ['n' => 'target'])<span>Pilih <b>target</b> agar progresnya ikut naik.</span></li>
            </ul>
        </div>
    </div>
</div>
@endsection

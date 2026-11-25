@extends('layouts.app')
@section('title', 'Tukar Uang')

@section('content')
@php $dir = old('direction', 'IDR_AUD'); @endphp
<div class="page-head">
    <div>
        <h1>Tukar uang 💱</h1>
        <p>Pindahkan tabungan Rupiah ke AUD cash (atau sebaliknya). Tidak dihitung sebagai setor/tarik.</p>
    </div>
</div>

<div class="grid g-main">
    <form method="POST" action="{{ route('exchange.store') }}" class="card form" data-exchange-form data-rate="{{ $rate['rate'] }}" data-balances='@json($balances)'>
        @csrf
        <div class="field">
            <span class="label">Arah tukar</span>
            <div class="seg">
                <label><input type="radio" name="direction" value="IDR_AUD" @checked($dir === 'IDR_AUD')><span>🇮🇩 → 🇦🇺 Beli AUD</span></label>
                <label><input type="radio" name="direction" value="AUD_IDR" @checked($dir === 'AUD_IDR')><span>🇦🇺 → 🇮🇩 Jual AUD</span></label>
            </div>
        </div>

        <div class="field">
            <label for="amount_from">Yang ditukar</label>
            <div class="input-money">
                <span class="prefix" data-prefix-from>Rp</span>
                <input id="amount_from" name="amount_from" class="input num @error('amount_from') is-invalid @enderror" inputmode="decimal" placeholder="0" value="{{ old('amount_from') }}" data-money data-currency="{{ $dir === 'IDR_AUD' ? 'IDR' : 'AUD' }}" required>
            </div>
            @error('amount_from')<div class="error">{{ $message }}</div>@else<div class="hint" data-balance></div>@enderror
        </div>

        <div class="field">
            <label for="amount_to">Yang diterima</label>
            <div class="input-money">
                <span class="prefix" data-prefix-to>A$</span>
                <input id="amount_to" name="amount_to" class="input num @error('amount_to') is-invalid @enderror" inputmode="decimal" placeholder="0" value="{{ old('amount_to') }}" data-money data-currency="{{ $dir === 'IDR_AUD' ? 'AUD' : 'IDR' }}" required>
            </div>
            @error('amount_to')<div class="error">{{ $message }}</div>@else
            <div class="hint">Terisi otomatis pakai kurs hari ini. Ubah sesuai uang yang benar-benar kamu terima dari money changer. <a href="#" data-reset-rate>Hitung ulang</a></div>@enderror
        </div>

        <div class="convert-box">
            <span style="font-size:26px">🧾</span>
            <div><div class="small muted">Kurs efektif transaksi ini (per 1 AUD)</div><div class="big num" data-effective>—</div></div>
        </div>

        <div class="grid g-2" style="gap:14px">
            <div class="field">
                <label for="method_from">Diambil dari</label>
                <select id="method_from" name="method_from" class="input">
                    @foreach (config('dcfinance.methods') as $key => $m)
                        <option value="{{ $key }}" @selected(old('method_from', 'bank') === $key)>{{ $m['icon'] }} {{ $m['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="method_to">Disimpan ke</label>
                <select id="method_to" name="method_to" class="input">
                    @foreach (config('dcfinance.methods') as $key => $m)
                        <option value="{{ $key }}" @selected(old('method_to', 'cash') === $key)>{{ $m['icon'] }} {{ $m['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid g-2" style="gap:14px">
            <div class="field">
                <label for="date">Tanggal</label>
                <input id="date" type="date" name="date" class="input" value="{{ old('date', today()->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}" required>
            </div>
            <div class="field">
                <label for="note">Catatan</label>
                <input id="note" name="note" class="input" value="{{ old('note') }}" placeholder="mis. Money changer Valas">
            </div>
        </div>
        <button class="btn btn-primary btn-lg btn-block">Simpan tukar uang</button>
    </form>

    <div class="stack">
        <div class="card card-pink" data-converter data-rate="{{ $rate['rate'] }}">
            <div class="card-head"><h3>🧮 Kalkulator cepat</h3></div>
            <div class="field">
                <label>AUD</label>
                <div class="input-money sm"><span class="prefix">A$</span><input name="conv_aud" class="input num" inputmode="decimal" data-money data-currency="AUD" placeholder="0"></div>
            </div>
            <div class="center muted mt-1 mb-2">⇅</div>
            <div class="field">
                <label>Rupiah</label>
                <div class="input-money sm"><span class="prefix">Rp</span><input name="conv_idr" class="input num" inputmode="numeric" data-money data-currency="IDR" placeholder="0"></div>
            </div>
            <div class="hint">Kurs: Rp {{ rate_fmt($rate['rate']) }} ({{ $rate['source'] }})</div>
        </div>
        <div class="card">
            <div class="kv"><span class="k">Saldo Rupiah</span><span class="v num">{{ rupiah($balances['IDR']) }}</span></div>
            <div class="kv"><span class="k">Saldo AUD</span><span class="v num">{{ aud($balances['AUD']) }}</span></div>
        </div>
    </div>
</div>
@endsection

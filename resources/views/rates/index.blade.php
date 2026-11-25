@extends('layouts.app')
@section('title', 'Kurs AUD')

@section('content')
@php
    $modeBadge = ['live' => ['badge-green', 'Online'], 'manual' => ['badge-amber', 'Manual'], 'last' => ['badge-gray', 'Tersimpan'], 'default' => ['badge-red', 'Default']][$rate['mode']] ?? ['badge-gray', $rate['mode']];
@endphp
<div class="page-head">
    <div>
        <h1>Kurs AUD</h1>
        <p>Dipakai untuk merupiahkan tabungan AUD-mu.</p>
    </div>
    <form method="POST" action="{{ route('rates.refresh') }}" class="page-actions">@csrf
        <button class="btn btn-soft">@include('partials.icon', ['n' => 'refresh']) Perbarui kurs</button>
    </form>
</div>

<div class="grid g-main">
    <div class="stack">
        <div class="card hero">
            <div class="row-between">
                <div class="label">1 Dolar Australia =</div>
                <span class="badge" style="background:#fff">{{ $modeBadge[1] }}</span>
            </div>
            <div class="big num">Rp {{ rate_fmt($rate['rate']) }}</div>
            <div class="sub">
                Sumber: {{ $rate['source'] }}
                @if (! empty($rate['updated_at'])) · diperbarui {{ \Illuminate\Support\Carbon::parse($rate['updated_at'])->timezone(config('app.timezone'))->translatedFormat('d M Y H:i') }} @endif
            </div>
            <div class="hero-split">
                <div class="hero-box"><div class="k">A$ 10</div><div class="v num">{{ rupiah(10 * $rate['rate']) }}</div></div>
                <div class="hero-box"><div class="k">A$ 100</div><div class="v num">{{ rupiah(100 * $rate['rate']) }}</div></div>
                <div class="hero-box"><div class="k">Rp 1 juta</div><div class="v num">{{ aud(1000000 / $rate['rate']) }}</div></div>
            </div>
        </div>

        <div class="card" data-converter data-rate="{{ $rate['rate'] }}">
            <div class="card-head"><h3>@include('partials.tile', ['n' => 'calc']) Konverter</h3></div>
            <div class="grid g-2" style="gap:14px">
                <div class="field">
                    <label>AUD</label>
                    <div class="input-money"><span class="prefix">A$</span><input name="conv_aud" class="input num" inputmode="decimal" data-money data-currency="AUD" placeholder="0"></div>
                </div>
                <div class="field">
                    <label>Rupiah</label>
                    <div class="input-money"><span class="prefix">Rp</span><input name="conv_idr" class="input num" inputmode="numeric" data-money data-currency="IDR" placeholder="0"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="stack">
        <form method="POST" action="{{ route('rates.update') }}" class="card form">
            @csrf @method('PUT')
            <div class="card-head" style="margin-bottom:0"><h3>@include('partials.tile', ['n' => 'edit']) Kurs manual</h3></div>
            <p class="small muted">Pakai kurs sendiri (mis. kurs money changer langgananmu) untuk semua perhitungan. Berlaku untuk kalian berdua.</p>
            <label class="check">
                <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $manual['enabled'] ?? false))>
                <div><div class="bold">Aktifkan kurs manual</div><div class="small muted">Matikan untuk kembali ke kurs online otomatis.</div></div>
            </label>
            <div class="field">
                <label for="manual_rate">Kurs 1 AUD</label>
                <div class="input-money sm"><span class="prefix">Rp</span>
                    <input id="manual_rate" name="manual_rate" class="input num @error('manual_rate') is-invalid @enderror" inputmode="decimal" data-money data-decimal="always" value="{{ old('manual_rate', ! empty($manual['rate']) ? rate_fmt($manual['rate']) : '') }}" placeholder="{{ rate_fmt($rate['rate']) }}">
                </div>
                @error('manual_rate')<div class="error">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary btn-block">Simpan</button>
        </form>

        <div class="card">
            <div class="card-head"><h3>@include('partials.tile', ['n' => 'info']) Cara kerja kurs</h3></div>
            <ul class="list-tips">
                <li>@include('partials.tile', ['n' => 'refresh'])<span>Kurs online diambil otomatis & disimpan {{ config('dcfinance.rate_cache_minutes') }} menit.</span></li>
                <li>@include('partials.tile', ['n' => 'lock'])<span>Setiap setoran AUD menyimpan <b>kurs saat itu</b>, jadi modal AUD-mu tetap tercatat.</span></li>
                <li>@include('partials.tile', ['n' => 'trend'])<span><b>Total tabungan</b> menilai AUD dengan kurs hari ini, sehingga terlihat untung/rugi kursnya.</span></li>
                @if ($last)<li>@include('partials.tile', ['n' => 'clock'])<span>Kurs online terakhir: Rp {{ rate_fmt($last['rate']) }} ({{ $last['source'] ?? '-' }}).</span></li>@endif
            </ul>
        </div>
    </div>
</div>
@endsection

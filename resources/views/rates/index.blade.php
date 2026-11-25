@extends('layouts.app')
@section('title', 'Kurs')

@push('data')
    @include('partials.dc-data')
@endpush

@section('content')
@php
    $modeBadge = ['live' => ['badge-green', 'Online'], 'last' => ['badge-amber', 'Tersimpan'], 'offline' => ['badge-red', 'Offline']][$table['mode']] ?? ['badge-gray', $table['mode']];
    $allCurrencies = ['Populer' => \App\Support\Currency::popular(), 'Semua mata uang' => array_values(array_diff(\App\Support\Currency::codes(), \App\Support\Currency::popular()))];
@endphp
<div class="page-head">
    <div>
        <h1>Kurs mata uang</h1>
        <p>Nilai 1 unit mata uang dalam Rupiah. Dipakai untuk merupiahkan tabungan valas.</p>
    </div>
    <form method="POST" action="{{ route('rates.refresh') }}" class="page-actions">@csrf
        <button class="btn btn-soft">@include('partials.icon', ['n' => 'refresh']) Perbarui kurs</button>
    </form>
</div>

<div class="grid g-main">
    <div class="stack">
        <div class="card hero">
            <div class="label">@include('partials.tile', ['n' => 'calc', 'tone' => 'white']) Konverter</div>
            <div data-converter class="mt-2">
                <div class="grid" style="grid-template-columns: minmax(0,1.2fr) minmax(0,1fr) auto minmax(0,1fr); gap:10px; align-items:center">
                    <input name="conv_amount" class="input num" inputmode="decimal" data-money data-currency-from="conv_from" value="100" aria-label="Jumlah">
                    @include('partials.cur-select', ['name' => 'conv_from', 'selected' => 'USD', 'groups' => $allCurrencies])
                    <button type="button" class="btn btn-glass icon-btn" data-swap title="Tukar arah">@include('partials.icon', ['n' => 'swap'])</button>
                    @include('partials.cur-select', ['name' => 'conv_to', 'selected' => 'IDR', 'groups' => $allCurrencies])
                </div>
                <div class="big num mt-3" data-conv-out>—</div>
                <div class="sub">
                    Sumber: {{ $table['source'] ?? '—' }} <span class="badge badge-sm {{ $modeBadge[0] }}">{{ $modeBadge[1] }}</span>
                    @if (! empty($table['updated_at'])) · diperbarui {{ \Illuminate\Support\Carbon::parse($table['updated_at'])->timezone(config('app.timezone'))->translatedFormat('d M Y H:i') }} @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3>@include('partials.tile', ['n' => 'globe']) Semua kurs <span class="count">{{ count($rows) }}</span></h3>
                <input type="search" class="input" style="max-width:220px;min-height:38px;padding:7px 12px" placeholder="Cari mata uang…" data-filter-table="#rate-table">
            </div>
            <div class="table-wrap">
                <table class="table" id="rate-table">
                    <thead><tr><th>Mata uang</th><th class="r">1 unit = Rupiah</th><th class="r">Rp 1 juta =</th></tr></thead>
                    <tbody>
                        @foreach ($rows as $r)
                            @php $isTop = in_array($r['code'], \App\Support\Currency::popular(), true) || $r['mode'] === 'manual'; @endphp
                            <tr class="{{ $isTop ? '' : 'more hide' }}">
                                <td><div class="row" style="gap:10px">@include('partials.cur', ['c' => $r['code']]) <span class="ellipsis" style="max-width:220px">{{ $r['name'] }}</span>
                                    @if ($r['mode'] === 'manual')<span class="badge badge-sm badge-amber">manual</span>@endif</div></td>
                                <td class="r num bold">{{ $r['rate'] > 0 ? 'Rp '.rate_fmt($r['rate']) : '—' }}</td>
                                <td class="r num muted">{{ $r['rate'] > 0 ? money(1000000 / $r['rate'], $r['code']) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-soft btn-block mt-3" data-show-all>Tampilkan semua {{ count($rows) }} mata uang</button>
        </div>
    </div>

    <div class="stack">
        <form method="POST" action="{{ route('rates.update') }}" class="card form">
            @csrf @method('PUT')
            <div class="card-head" style="margin-bottom:0"><h3>@include('partials.tile', ['n' => 'edit']) Kurs manual</h3></div>
            <p class="small muted">Pakai kurs sendiri (mis. kurs money changer langgananmu). Berlaku untuk kalian berdua dan menggantikan kurs online.</p>
            <div class="field">
                <label for="currency">Mata uang</label>
                @include('partials.cur-select', ['name' => 'currency', 'selected' => old('currency', 'USD'), 'groups' => $currencies])
            </div>
            <div class="field">
                <label for="manual_rate">Kurs 1 unit dalam Rupiah</label>
                <div class="input-money sm"><span class="prefix">Rp</span>
                    <input id="manual_rate" name="manual_rate" class="input num @error('manual_rate') is-invalid @enderror" inputmode="decimal" data-money data-decimal="rate" value="{{ old('manual_rate') }}" placeholder="0">
                </div>
                @error('manual_rate')<div class="error">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary btn-block">Simpan kurs manual</button>

            @if (count($manual))
                <div class="divider"></div>
                <div class="label" style="margin:0">Kurs manual aktif</div>
                @foreach ($manual as $code => $m)
                    <div class="kv">
                        <span class="k">@include('partials.cur', ['c' => $code]) <span class="num" style="color:var(--ink);font-weight:700">Rp {{ rate_fmt($m['rate']) }}</span></span>
                        <button form="clear-{{ $code }}" class="btn btn-ghost btn-sm">Hapus</button>
                    </div>
                @endforeach
            @endif
        </form>
        @foreach ($manual as $code => $m)
            <form id="clear-{{ $code }}" method="POST" action="{{ route('rates.clear', $code) }}">@csrf @method('DELETE')</form>
        @endforeach

        <div class="card">
            <div class="card-head"><h3>@include('partials.tile', ['n' => 'info']) Cara kerja kurs</h3></div>
            <ul class="list-tips">
                <li>@include('partials.tile', ['n' => 'refresh'])<span>Kurs online semua mata uang diambil otomatis & disimpan {{ config('dcfinance.rate_cache_minutes') }} menit.</span></li>
                <li>@include('partials.tile', ['n' => 'lock'])<span>Setiap setoran valas menyimpan <b>kurs saat itu</b> sebagai modal.</span></li>
                <li>@include('partials.tile', ['n' => 'trend'])<span><b>Total tabungan</b> menilai valas dengan kurs hari ini, sehingga terlihat untung/rugi kursnya.</span></li>
            </ul>
        </div>
    </div>
</div>
@endsection

@push('head')
<style>
    .hero .input { background: rgba(255,255,255,.95); border-color: transparent; }
    .hero .big[data-conv-out] { font-size: 30px; }
    @media (max-width: 560px) { [data-converter] .grid { grid-template-columns: 1fr 1fr !important; } [data-converter] [data-swap] { display: none; } }
</style>
@endpush

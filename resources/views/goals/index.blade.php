@extends('layouts.app')
@section('title', 'Target')

@section('content')
<div class="page-head">
    <div>
        <h1>Target tabungan</h1>
        <p>Mimpi yang dicicil sedikit-sedikit.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('goals.create') }}" class="btn btn-primary">@include('partials.icon', ['n' => 'plus']) Target baru</a>
    </div>
</div>

@if ($active->isNotEmpty())
    @php $pct = $totalTarget > 0 ? min($totalSaved / $totalTarget * 100, 100) : 0; @endphp
    <div class="card hero mb-3">
        <div class="label">@include('partials.tile', ['n' => 'target', 'tone' => 'white']) Progres semua target aktif</div>
        <div class="row-between wrap" style="align-items:flex-end">
            <div>
                <div class="big num">{{ number_format($pct, 1, ',', '.') }}%</div>
                <div class="sub num">{{ rupiah($totalSaved) }} terkumpul dari {{ rupiah($totalTarget) }}</div>
            </div>
            <div class="hero-box" style="min-width:200px">
                <div class="k">Sisa yang perlu dikumpulkan</div>
                <div class="v num">{{ rupiah(max($totalTarget - $totalSaved, 0)) }}</div>
            </div>
        </div>
        <div class="bar bar-white bar-lg mt-3"><span data-w="{{ round($pct, 1) }}" style="width:0"></span></div>
    </div>

    <div class="grid g-3">
        @foreach ($active as $g)
            @include('partials.goal-card', ['g' => $g])
        @endforeach
    </div>
@else
    <div class="card">
        @include('partials.empty', ['icon' => 'target', 'title' => 'Belum ada target aktif', 'text' => 'Buat target pertama — misalnya liburan, dana nikah, atau DP rumah.', 'action' => route('goals.create'), 'actionText' => 'Buat target'])
    </div>
@endif

@if ($achieved->isNotEmpty())
    <h3 class="section-title">@include('partials.tile', ['n' => 'trophy', 'tone' => 'green']) Sudah tercapai</h3>
    <div class="grid g-3">
        @foreach ($achieved as $g)
            @include('partials.goal-card', ['g' => $g])
        @endforeach
    </div>
@endif
@endsection

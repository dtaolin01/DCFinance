@extends('layouts.app')
@section('title', 'Persetujuan akun')

@section('content')
<div class="page-head">
    <div>
        <h1>Persetujuan akun</h1>
        <p>Akun baru baru bisa masuk setelah kamu setujui. Akun aktif maksimal {{ $max }}.</p>
    </div>
</div>

<div class="grid g-main">
    <div class="card">
        <div class="card-head"><h3>@include('partials.tile', ['n' => 'bell', 'tone' => 'amber']) Menunggu persetujuan <span class="count">{{ $pending->count() }}</span></h3></div>
        @forelse ($pending as $u)
            <div class="tx">
                <div class="avatar" style="background: {{ $u->color }}22">{{ $u->emoji }}</div>
                <div class="tx-main">
                    <div class="tx-title">{{ $u->name }} @if ($u->nickname)<span class="muted" style="font-weight:500">({{ $u->nickname }})</span>@endif</div>
                    <div class="tx-meta"><span>{{ $u->email }}</span><span class="sep"></span><span>daftar {{ $u->created_at->diffForHumans() }}</span></div>
                </div>
                <form method="POST" action="{{ route('accounts.reject', $u) }}" data-confirm="Tolak dan hapus pendaftaran {{ $u->name }}?">@csrf @method('DELETE')
                    <button class="btn btn-ghost btn-sm">Tolak</button>
                </form>
                <form method="POST" action="{{ route('accounts.approve', $u) }}">@csrf
                    <button class="btn btn-success btn-sm">@include('partials.icon', ['n' => 'check']) Setujui</button>
                </form>
            </div>
        @empty
            @include('partials.empty', ['icon' => 'check', 'title' => 'Tidak ada pendaftaran baru', 'text' => 'Kalau pasanganmu mendaftar, namanya akan muncul di sini.'])
        @endforelse
    </div>

    <div class="card">
        <div class="card-head"><h3>@include('partials.tile', ['n' => 'users']) Akun aktif <span class="count">{{ $active->count() }}/{{ $max }}</span></h3></div>
        @foreach ($active as $u)
            <div class="tx">
                <div class="avatar" style="background: {{ $u->color }}22">{{ $u->emoji }}</div>
                <div class="tx-main">
                    <div class="tx-title">{{ $u->display_name }}</div>
                    <div class="tx-meta"><span>{{ $u->email }}</span></div>
                </div>
                <span class="badge {{ $u->is_admin ? '' : 'badge-gray' }}">{{ $u->is_admin ? 'Admin' : 'Anggota' }}</span>
            </div>
        @endforeach
        <div class="hint mt-3">Link pendaftaran untuk pasanganmu: <b>{{ route('register') }}</b></div>
    </div>
</div>
@endsection

@extends('layouts.app')
@php $editing = $goal->exists; @endphp
@section('title', $editing ? 'Edit target' : 'Target baru')

@push('data')
    @include('partials.dc-data')
@endpush

@section('content')
@php
    $cur = old('target_currency', $goal->target_currency);
    $amt = old('target_amount', $goal->target_amount ? number_format($goal->target_amount, \App\Support\Currency::decimals($cur), ',', '.') : '');
@endphp
<div class="page-head">
    <div>
        <a href="{{ $editing ? route('goals.show', $goal) : route('goals.index') }}" class="back-link">@include('partials.icon', ['n' => 'back'])Kembali</a>
        <h1>{{ $editing ? 'Edit target' : 'Target baru' }}</h1>
        <p>Tentukan mimpinya, nominalnya, dan kapan mau tercapai.</p>
    </div>
</div>

<form method="POST" action="{{ $editing ? route('goals.update', $goal) : route('goals.store') }}" class="grid g-main" data-goal-form>
    @csrf
    @if ($editing) @method('PUT') @endif
    <div class="card form">
        <div class="row" style="gap:14px;align-items:flex-end">
            <div class="goal-emoji" data-emoji-preview style="width:58px;height:58px;font-size:30px;background: {{ $goal->color }}22">{{ old('emoji', $goal->emoji) }}</div>
            <div class="field" style="flex:1;min-width:0">
                <label for="name">Nama target</label>
                <input id="name" name="name" class="input @error('name') is-invalid @enderror" value="{{ old('name', $goal->name) }}" placeholder="mis. Liburan ke Jepang" required maxlength="100">
                @error('name')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="field">
            <span class="label">Ikon</span>
            <div class="pick">
                @foreach (config('dcfinance.goal_emojis') as $e)
                    <label><input type="radio" name="emoji" value="{{ $e }}" @checked(old('emoji', $goal->emoji) === $e)><span>{{ $e }}</span></label>
                @endforeach
            </div>
        </div>

        <div class="field">
            <span class="label">Warna</span>
            <div class="pick swatch">
                @foreach (config('dcfinance.colors') as $c)
                    <label><input type="radio" name="color" value="{{ $c }}" @checked(old('color', $goal->color) === $c)><span style="background: {{ $c }}"></span></label>
                @endforeach
            </div>
        </div>

        <div class="grid g-2" style="gap:14px">
            <div class="field">
                <label for="target_currency">Mata uang target</label>
                @include('partials.cur-select', ['name' => 'target_currency', 'selected' => $cur, 'groups' => $currencies])
            </div>
            <div class="field">
                <label for="target_amount">Nominal target</label>
                <div class="input-money sm">
                    <span class="prefix" data-prefix>{{ \App\Support\Currency::symbol($cur) }}</span>
                    <input id="target_amount" name="target_amount" class="input num @error('target_amount') is-invalid @enderror" inputmode="decimal" value="{{ $amt }}" data-money data-currency-from="target_currency" placeholder="0" required>
                </div>
                @error('target_amount')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="deadline">Tercapai sebelum <span class="opt">(opsional)</span></label>
                <input id="deadline" type="date" name="deadline" class="input" value="{{ old('deadline', optional($goal->deadline)->format('Y-m-d')) }}">
            </div>
            <div class="field">
                <label for="priority">Prioritas</label>
                <select id="priority" name="priority" class="input">
                    <option value="1" @selected(old('priority', $goal->priority) == 1)>Tinggi</option>
                    <option value="2" @selected(old('priority', $goal->priority) == 2)>Sedang</option>
                    <option value="3" @selected(old('priority', $goal->priority) == 3)>Rendah</option>
                </select>
            </div>
        </div>

        <label class="check">
            <input type="checkbox" name="is_shared" value="1" @checked(old('is_shared', $goal->is_shared))>
            <div><div class="bold">Target bersama</div><div class="small muted">Kalian berdua bisa melihat & menabung ke target ini. Kontribusi masing-masing tetap tercatat terpisah.</div></div>
        </label>

        <div class="field">
            <label for="notes">Catatan <span class="opt">(opsional)</span></label>
            <textarea id="notes" name="notes" class="input" maxlength="1000" placeholder="Kenapa target ini penting buat kamu?">{{ old('notes', $goal->notes) }}</textarea>
        </div>

        <button class="btn btn-primary btn-lg btn-block">{{ $editing ? 'Simpan perubahan' : 'Buat target' }}</button>
    </div>

    <div class="stack">
        <div class="card card-soft">
            <div class="card-head"><h3>@include('partials.tile', ['n' => 'calendar']) Rencana menabung</h3></div>
            <div data-plan></div>
        </div>
        @if ($editing)
            <div class="card">
                <div class="card-head"><h3>@include('partials.tile', ['n' => 'trash', 'tone' => 'red']) Hapus target</h3></div>
                <p class="small muted">Menghapus target tidak menghapus transaksinya — uangnya tetap ada di tabungan.</p>
                <button form="delete-goal" class="btn btn-danger btn-block mt-3">Hapus target ini</button>
            </div>
        @endif
    </div>
</form>
@if ($editing)
    <form id="delete-goal" method="POST" action="{{ route('goals.destroy', $goal) }}" data-confirm="Hapus target ini? Transaksinya tetap tersimpan.">@csrf @method('DELETE')</form>
@endif
@endsection

<div class="empty" @isset($pad) style="padding: {{ $pad }}" @endisset>
    <div class="ill">@include('partials.icon', ['n' => $icon ?? 'sparkle'])</div>
    <h4>{{ $title }}</h4>
    @isset($text)<p class="small">{{ $text }}</p>@endisset
    @isset($action)<a href="{{ $action }}" class="btn btn-soft btn-sm mt-3">@include('partials.icon', ['n' => 'plus']) {{ $actionText }}</a>@endisset
</div>

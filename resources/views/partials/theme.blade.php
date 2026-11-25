@if (! empty($theme['vars']))
<style>:root{@foreach ($theme['vars'] as $k => $v)--{{ $k }}:{{ $v }};@endforeach}</style>
@endif

<select name="{{ $name }}" id="{{ $id ?? $name }}" class="input {{ $class ?? '' }}" @if(!empty($required)) required @endif>
    @foreach ($groups as $label => $codes)
        <optgroup label="{{ $label }}">
            @foreach ($codes as $code)
                <option value="{{ $code }}" @selected($selected === $code)>{{ $code }} — {{ \App\Support\Currency::name($code) }}</option>
            @endforeach
        </optgroup>
    @endforeach
</select>

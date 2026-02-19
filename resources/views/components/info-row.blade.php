@props(['label', 'value', 'color' => null, 'mono' => false])
<div class="info-row">
    <span class="label">{{ $label }}</span>
    <span class="value" @if($color) style="color:{{ $color }}" @endif @if($mono) style="font-family:monospace;font-size:11px" @endif>{{ $value }}</span>
</div>

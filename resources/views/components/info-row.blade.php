{{-- resources/views/components/info-row.blade.php --}}
@props(['label', 'value'])
<div class="info-row">
    <span class="label">{{ $label }}</span>
    <span class="value">{{ $value }}</span>
</div>

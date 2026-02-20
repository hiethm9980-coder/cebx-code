{{-- resources/views/components/stat-card.blade.php --}}
@props(['icon' => '', 'label', 'value', 'trend' => null, 'up' => true])
<div class="stat-card">
    <div class="label">{{ $icon }} {{ $label }}</div>
    <div class="value">{{ $value }}</div>
    @if($trend)
        <div class="trend {{ $up ? 'up' : 'down' }}">{{ $up ? '↑' : '↓' }} {{ $trend }}</div>
    @endif
</div>

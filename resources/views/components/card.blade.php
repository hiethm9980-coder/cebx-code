@props(['title' => null])
<div class="card">
    @if($title)
        <div class="card-title">{{ $title }}</div>
    @endif
    {{ $slot }}
</div>

{{-- resources/views/components/card.blade.php --}}
@props(['title' => null])
<div class="card">
    @if($title)
        <div class="card-title" style="display:flex;justify-content:space-between;align-items:center">
            <span>{{ $title }}</span>
            @if(isset($action))
                <span>{{ $action }}</span>
            @endif
        </div>
    @endif
    {{ $slot }}
</div>

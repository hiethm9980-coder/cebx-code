@props(['id', 'title'])
<div class="modal-bg" id="{{ $id }}" style="display:none">
    <div class="modal" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2>{{ $title }}</h2>
            <button class="btn btn-ghost" data-modal-close>✕</button>
        </div>
        {{ $slot }}
    </div>
</div>

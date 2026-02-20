{{-- resources/views/components/modal.blade.php --}}
@props(['id', 'title', 'wide' => false])
<div class="modal-bg" id="{{ $id }}" style="display:none">
    <div class="modal" style="{{ $wide ? 'max-width:640px' : '' }}">
        <div class="modal-header">
            <h2>{{ $title }}</h2>
            <button data-modal-close style="background:none;border:none;color:var(--td);font-size:20px;cursor:pointer">✕</button>
        </div>
        {{ $slot }}
    </div>
</div>

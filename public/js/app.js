/**
 * Shipping Gateway — App JavaScript
 * Handles modals, toasts, and interactive UI
 */

// ═══ MODALS ═══
document.addEventListener('DOMContentLoaded', function () {
    // Open modal
    document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = this.getAttribute('data-modal-open');
            var modal = document.getElementById(id);
            if (modal) modal.style.display = 'flex';
        });
    });

    // Close modal (button)
    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var modal = this.closest('.modal-bg');
            if (modal) modal.style.display = 'none';
        });
    });

    // Close modal (background click)
    document.querySelectorAll('.modal-bg').forEach(function (bg) {
        bg.addEventListener('click', function (e) {
            if (e.target === this) this.style.display = 'none';
        });
    });

    // Close modal (Escape key)
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-bg').forEach(function (m) {
                m.style.display = 'none';
            });
        }
    });

    // ═══ TOASTS ═══
    var toasts = document.querySelectorAll('.toast');
    toasts.forEach(function (toast) {
        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(function () {
                toast.parentElement && toast.parentElement.removeChild(toast);
            }, 300);
        }, 4000);
    });

    // ═══ SELECT ALL CHECKBOX ═══
    var selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            var checkboxes = document.querySelectorAll('input[name="selected[]"]');
            checkboxes.forEach(function (cb) { cb.checked = selectAll.checked; });
        });
    }

    // ═══ AMOUNT BUTTONS (Wallet) ═══
    document.querySelectorAll('.amount-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.amount-btn').forEach(function (b) {
                b.style.border = '1px solid var(--bd)';
                b.style.background = 'var(--sf)';
            });
            this.style.border = '2px solid var(--pr)';
            this.style.background = 'rgba(59,130,246,0.13)';
        });
    });
});

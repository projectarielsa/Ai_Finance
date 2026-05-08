import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;
Alpine.start();

// Toast notifications
window.showToast = function(message, type = 'success', duration = 4000) {
    const icons = {
        success: `<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`,
        error:   `<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`,
        info:    `<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
        warning: `<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`,
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `${icons[type]}<span class="text-sm font-medium">${message}</span>
        <button onclick="this.parentElement.remove()" class="ml-2 opacity-60 hover:opacity-100">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>`;

    // Stack toasts
    const existing = document.querySelectorAll('.toast');
    const offset   = existing.length * 70;
    toast.style.bottom = `${24 + offset}px`;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
};

// Auto-show flash messages as toasts
document.addEventListener('DOMContentLoaded', () => {
    const flashSuccess = document.querySelector('[data-flash-success]');
    const flashError   = document.querySelector('[data-flash-error]');
    if (flashSuccess) showToast(flashSuccess.dataset.flashSuccess, 'success');
    if (flashError)   showToast(flashError.dataset.flashError, 'error');
});

// Confirm delete dialogs
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-confirm]');
    if (btn) {
        e.preventDefault();
        if (confirm(btn.dataset.confirm || 'Yakin ingin menghapus?')) {
            const form = btn.closest('form') || document.getElementById(btn.dataset.formId);
            if (form) form.submit();
        }
    }
});

// Format currency inputs
document.addEventListener('input', (e) => {
    if (e.target.classList.contains('currency-input')) {
        let val = e.target.value.replace(/\D/g, '');
        e.target.value = val;
    }
});

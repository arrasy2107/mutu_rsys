import Swal from 'sweetalert2';

// ─── Toast Configuration ───────────────────────────────────────────────────────
const ToastMixin = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    background: '#ffffff',
    color: '#1e293b',
    didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
    },
    customClass: {
        popup: 'rounded-2xl shadow-xl border border-slate-100 text-xs font-sans',
        title: 'font-semibold text-slate-800 text-xs',
    }
});

export const toast = {
    success: (title, text = '') => ToastMixin.fire({ icon: 'success', title, text }),
    error: (title, text = '') => ToastMixin.fire({ icon: 'error', title, text }),
    warning: (title, text = '') => ToastMixin.fire({ icon: 'warning', title, text }),
    info: (title, text = '') => ToastMixin.fire({ icon: 'info', title, text }),
};

// ─── Modal Alert Configuration ─────────────────────────────────────────────────
export const alert = {
    success: (title, text = '', html = null) => Swal.fire({
        icon: 'success',
        title,
        text,
        html,
        confirmButtonColor: '#059669',
        confirmButtonText: 'Tutup',
        customClass: {
            popup: 'rounded-2xl font-sans',
            title: 'text-base font-bold text-slate-900',
            htmlContainer: 'text-sm text-slate-600',
        }
    }),
    error: (title, text = '', html = null) => Swal.fire({
        icon: 'error',
        title,
        text,
        html,
        confirmButtonColor: '#e11d48',
        confirmButtonText: 'Mengerti',
        customClass: {
            popup: 'rounded-2xl font-sans',
            title: 'text-base font-bold text-slate-900',
            htmlContainer: 'text-sm text-slate-600',
        }
    }),
    warning: (title, text = '', html = null) => Swal.fire({
        icon: 'warning',
        title,
        text,
        html,
        confirmButtonColor: '#d97706',
        confirmButtonText: 'Tutup',
        customClass: {
            popup: 'rounded-2xl font-sans',
            title: 'text-base font-bold text-slate-900',
            htmlContainer: 'text-sm text-slate-600',
        }
    }),
    info: (title, text = '', html = null) => Swal.fire({
        icon: 'info',
        title,
        text,
        html,
        confirmButtonColor: '#2563eb',
        confirmButtonText: 'Tutup',
        customClass: {
            popup: 'rounded-2xl font-sans',
            title: 'text-base font-bold text-slate-900',
            htmlContainer: 'text-sm text-slate-600',
        }
    }),
};

// ─── Confirmation Dialogs ──────────────────────────────────────────────────────
export const confirm = async ({
    title = 'Konfirmasi Tindakan',
    text = '',
    html = null,
    icon = 'question',
    confirmButtonText = 'Ya, Lanjutkan!',
    cancelButtonText = 'Batal',
    confirmButtonColor = '#2563eb',
    cancelButtonColor = '#64748b',
} = {}) => {
    const result = await Swal.fire({
        title,
        text,
        html,
        icon,
        showCancelButton: true,
        confirmButtonColor,
        cancelButtonColor,
        confirmButtonText,
        cancelButtonText,
        reverseButtons: true,
        customClass: {
            popup: 'rounded-2xl font-sans p-6 shadow-2xl',
            title: 'text-base font-bold text-slate-900',
            htmlContainer: 'text-sm text-slate-600 leading-relaxed',
            confirmButton: 'rounded-xl font-semibold px-5 py-2.5 text-xs',
            cancelButton: 'rounded-xl font-semibold px-5 py-2.5 text-xs',
        }
    });

    return result.isConfirmed;
};

// ─── Destructive / Danger Confirmations (Delete, Reject, etc.) ─────────────────
export const dangerConfirm = async ({
    title = 'Apakah Anda Yakin?',
    text = 'Tindakan ini tidak dapat dibatalkan.',
    html = null,
    confirmButtonText = 'Ya, Lanjutkan!',
    cancelButtonText = 'Batal',
} = {}) => {
    return confirm({
        title,
        text,
        html,
        icon: 'warning',
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText,
        cancelButtonText,
    });
};

// ─── Prompt / Textarea Input Modal ─────────────────────────────────────────────
export const promptInput = async ({
    title = 'Masukkan Keterangan',
    placeholder = 'Ketik keterangan di sini...',
    confirmButtonText = 'Kirim',
    cancelButtonText = 'Batal',
    confirmButtonColor = '#2563eb',
    required = true,
    requiredMessage = 'Keterangan wajib diisi.',
    inputValidator = null,
} = {}) => {
    return Swal.fire({
        title,
        input: 'textarea',
        inputPlaceholder: placeholder,
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText,
        confirmButtonColor,
        cancelButtonColor: '#64748b',
        reverseButtons: true,
        inputValidator: inputValidator || ((val) => {
            if (required && (!val || !val.trim())) {
                return requiredMessage;
            }
            return null;
        }),
        customClass: {
            popup: 'rounded-2xl font-sans p-6 shadow-2xl',
            title: 'text-base font-bold text-slate-900',
            input: 'text-sm rounded-xl border-slate-200 focus:ring-indigo-500 focus:border-indigo-500',
            confirmButton: 'rounded-xl font-semibold px-5 py-2.5 text-xs',
            cancelButton: 'rounded-xl font-semibold px-5 py-2.5 text-xs',
        }
    });
};

// Global export helper
const notify = {
    toast,
    alert,
    confirm,
    dangerConfirm,
    promptInput,
};

export default notify;

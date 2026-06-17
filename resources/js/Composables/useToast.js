import { reactive } from 'vue';

const toasts = reactive([]);
let nextId = 0;

/**
 * Show a toast notification.
 * @param {string} message
 * @param {'success'|'error'|'warning'|'info'} type
 * @param {number} duration  milliseconds before auto-dismiss (default 4000)
 */
export function useToast() {
    const show = (message, type = 'success', duration = 4000) => {
        const id = ++nextId;
        toasts.push({ id, message, type, duration });
        return id;
    };

    const dismiss = (id) => {
        const idx = toasts.findIndex((t) => t.id === id);
        if (idx !== -1) toasts.splice(idx, 1);
    };

    return { toasts, show, dismiss };
}

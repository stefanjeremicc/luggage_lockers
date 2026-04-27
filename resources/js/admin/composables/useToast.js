import { ref } from 'vue';

const toasts = ref([]);
let counter = 0;

const push = (type, message, timeout = 4000) => {
    const id = ++counter;
    toasts.value.push({ id, type, message });
    if (timeout) setTimeout(() => remove(id), timeout);
};

const remove = (id) => {
    toasts.value = toasts.value.filter(t => t.id !== id);
};

export function useToast() {
    return {
        toasts,
        success: (msg, t) => push('success', msg, t),
        error: (msg, t) => push('error', msg, t ?? 6000),
        info: (msg, t) => push('info', msg, t),
        warning: (msg, t) => push('warning', msg, t),
        remove,
    };
}

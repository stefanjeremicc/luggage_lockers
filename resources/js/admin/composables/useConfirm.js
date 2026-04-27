import { ref } from 'vue';

/*
 * Global confirm dialog. Resolves to true when user confirms, false on cancel.
 * Mounted once in AdminLayout.vue; call `useConfirm().ask(...)` anywhere to prompt.
 *
 * Usage:
 *   const confirm = useConfirm();
 *   if (await confirm.ask({ title: 'Delete post?', message: 'Cannot be undone.', variant: 'danger' })) { ... }
 */
const open = ref(false);
const state = ref({
    title: 'Confirm',
    message: '',
    variant: 'default',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
});
let resolver = null;

export function useConfirm() {
    const ask = (opts = {}) => {
        state.value = {
            title: opts.title || 'Are you sure?',
            message: opts.message || '',
            variant: opts.variant || 'default',
            confirmText: opts.confirmText || (opts.variant === 'danger' ? 'Delete' : 'Confirm'),
            cancelText: opts.cancelText || 'Cancel',
        };
        open.value = true;
        return new Promise(resolve => { resolver = resolve; });
    };
    const _confirm = () => { open.value = false; resolver?.(true); resolver = null; };
    const _cancel = () => { open.value = false; resolver?.(false); resolver = null; };
    return { ask, open, state, _confirm, _cancel };
}

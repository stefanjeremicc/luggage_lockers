<template>
    <Teleport to="body">
        <div class="fixed top-4 right-4 z-[60] flex flex-col gap-2 pointer-events-none">
            <TransitionGroup name="toast">
                <div v-for="t in toasts" :key="t.id"
                    class="pointer-events-auto min-w-[280px] max-w-md rounded-lg border p-3 shadow-xl flex items-start gap-3 bg-[#1A1A1A]"
                    :class="borderFor(t.type)">
                    <div class="shrink-0 mt-0.5" :class="iconColor(t.type)">
                        <svg v-if="t.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        <svg v-else-if="t.type === 'error'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        <svg v-else-if="t.type === 'warning'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <p class="text-sm text-white flex-1 leading-snug">{{ t.message }}</p>
                    <button @click="remove(t.id)" class="text-[#A0A0A0] hover:text-white shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import { useToast } from '../composables/useToast';
const { toasts, remove } = useToast();
const borderFor = (t) => ({ success: 'border-green-500/40', error: 'border-red-500/40', warning: 'border-amber-500/40', info: 'border-blue-500/40' })[t];
const iconColor = (t) => ({ success: 'text-green-400', error: 'text-red-400', warning: 'text-amber-400', info: 'text-blue-400' })[t];
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.2s ease; }
.toast-enter-from { opacity: 0; transform: translateX(20px); }
.toast-leave-to { opacity: 0; transform: translateX(20px); }
</style>

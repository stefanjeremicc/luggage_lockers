<template>
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="cancel">
                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-2xl shadow-2xl max-w-md w-full p-6">
                    <div class="flex items-start gap-3 mb-4">
                        <div class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center" :class="iconBg">
                            <svg class="w-5 h-5" :class="iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath" /></svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-white text-lg mb-1">{{ title }}</h3>
                            <p class="text-sm text-[#A0A0A0]">{{ message }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <button @click="cancel" class="px-4 py-2 text-sm rounded-md border border-[#2A2A2A] text-[#A0A0A0] hover:text-white hover:border-[#3A3A3A] transition">{{ cancelText }}</button>
                        <button @click="confirm" class="px-4 py-2 text-sm rounded-md font-medium transition" :class="confirmClass">{{ confirmText }}</button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, default: 'Confirm' },
    message: { type: String, default: 'Are you sure?' },
    variant: { type: String, default: 'default' },
    confirmText: { type: String, default: 'Confirm' },
    cancelText: { type: String, default: 'Cancel' },
});
const emit = defineEmits(['update:modelValue', 'confirm', 'cancel']);

const cancel = () => { emit('update:modelValue', false); emit('cancel'); };
const confirm = () => { emit('update:modelValue', false); emit('confirm'); };

const iconBg = computed(() => ({
    danger: 'bg-red-500/15',
    warning: 'bg-amber-500/15',
    default: 'bg-blue-500/15',
})[props.variant] || 'bg-blue-500/15');

const iconColor = computed(() => ({
    danger: 'text-red-400',
    warning: 'text-amber-400',
    default: 'text-blue-400',
})[props.variant] || 'text-blue-400');

const iconPath = computed(() => props.variant === 'danger'
    ? 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
    : 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z');

const confirmClass = computed(() => ({
    danger: 'bg-red-600 hover:bg-red-700 text-white',
    warning: 'bg-amber-500 hover:bg-amber-600 text-black',
    default: 'bg-[#F59E0B] hover:bg-[#D97706] text-black',
})[props.variant] || 'bg-[#F59E0B] hover:bg-[#D97706] text-black');
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>

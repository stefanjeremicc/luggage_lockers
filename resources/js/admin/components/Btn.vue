<template>
    <component :is="resolvedTag" v-bind="attrs" :disabled="disabled || loading" :class="classes" @click="onClick">
        <svg v-if="loading" class="animate-spin shrink-0" :class="iconSize" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
        </svg>
        <slot name="icon" v-else />
        <slot />
    </component>
</template>
<script setup>
import { computed, useAttrs } from 'vue';
import { RouterLink } from 'vue-router';

const props = defineProps({
    variant: { type: String, default: 'primary' }, // primary | secondary | danger | ghost | outline
    size: { type: String, default: 'md' },          // sm | md | lg | icon
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    as: { type: String, default: 'button' },        // 'button' | 'a' | 'router-link'
    block: { type: Boolean, default: false },
});
const emit = defineEmits(['click']);
const attrs = useAttrs();

const resolvedTag = computed(() =>
    props.as === 'router-link' ? RouterLink : props.as
);

const sizeClasses = {
    sm: 'h-8 px-3 text-xs gap-1.5 rounded-md',
    md: 'h-10 px-4 text-sm gap-2 rounded-lg',
    lg: 'h-12 px-6 text-sm gap-2 rounded-lg',
    icon: 'h-9 w-9 rounded-lg',
};

const iconSize = computed(() => ({
    sm: 'w-3.5 h-3.5',
    md: 'w-4 h-4',
    lg: 'w-4 h-4',
    icon: 'w-4 h-4',
}[props.size]));

const variantClasses = {
    primary: 'bg-[#F59E0B] hover:bg-[#D97706] active:bg-[#B45309] text-black font-semibold disabled:opacity-50 disabled:cursor-not-allowed focus-visible:ring-2 focus-visible:ring-[#F59E0B]/50',
    secondary: 'bg-[#1F1F1F] hover:bg-[#2A2A2A] border border-[#2A2A2A] hover:border-[#3A3A3A] text-white font-medium disabled:opacity-50 disabled:cursor-not-allowed',
    danger: 'bg-[#EF4444]/15 hover:bg-[#EF4444]/25 text-[#EF4444] font-semibold border border-transparent hover:border-[#EF4444]/30 disabled:opacity-50',
    'danger-solid': 'bg-[#EF4444] hover:bg-red-600 text-white font-semibold disabled:opacity-50',
    ghost: 'text-[#A0A0A0] hover:text-white hover:bg-[#1A1A1A] font-medium',
    outline: 'border border-[#2A2A2A] hover:border-[#F59E0B] text-white font-medium bg-transparent',
};

const classes = computed(() => [
    'inline-flex items-center justify-center whitespace-nowrap transition select-none focus:outline-none',
    sizeClasses[props.size] || sizeClasses.md,
    variantClasses[props.variant] || variantClasses.primary,
    props.block ? 'w-full' : '',
]);

const onClick = (e) => emit('click', e);
</script>

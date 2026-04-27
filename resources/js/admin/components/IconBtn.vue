<template>
    <component :is="resolvedTag" v-bind="attrs" :title="title" :aria-label="title" :class="classes" :type="props.as === 'button' ? 'button' : undefined">
        <slot />
    </component>
</template>
<script setup>
import { computed, useAttrs } from 'vue';
import { RouterLink } from 'vue-router';

const props = defineProps({
    variant: { type: String, default: 'default' }, // default | danger | primary
    title: { type: String, default: '' },
    as: { type: String, default: 'button' },
});

const attrs = useAttrs();
const resolvedTag = computed(() => props.as === 'router-link' ? RouterLink : props.as);

const variants = {
    default: 'text-[#A0A0A0] hover:text-white hover:bg-[#2A2A2A]',
    primary: 'text-[#F59E0B] hover:bg-[#F59E0B]/10',
    danger: 'text-[#6B7280] hover:text-[#EF4444] hover:bg-[#EF4444]/10',
};

const classes = computed(() => [
    'w-9 h-9 rounded-lg flex items-center justify-center transition shrink-0',
    variants[props.variant] || variants.default,
]);
</script>

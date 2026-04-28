<template>
    <component :is="tag" :class="classes">
        <header v-if="$slots.header || title" class="flex items-center justify-between gap-3"
            :class="[headerPadding, $slots.default || $slots.body ? bodyDivider : '']">
            <slot name="header">
                <div class="min-w-0">
                    <h2 v-if="title" :class="titleClass">{{ title }}</h2>
                    <p v-if="subtitle" class="text-xs text-[#A0A0A0] mt-0.5">{{ subtitle }}</p>
                </div>
            </slot>
            <div v-if="$slots.actions" class="shrink-0">
                <slot name="actions" />
            </div>
        </header>

        <div v-if="$slots.default || $slots.body" :class="bodyPaddingClass">
            <slot name="body">
                <slot />
            </slot>
        </div>

        <footer v-if="$slots.footer" class="border-t border-[#2A2A2A]" :class="footerPadding">
            <slot name="footer" />
        </footer>
    </component>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    tag: { type: String, default: 'section' },
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    titleVariant: { type: String, default: 'default' }, // default | section (uppercase amber)
    padding: { type: String, default: 'md' }, // none | sm | md | lg
    interactive: { type: Boolean, default: false }, // hover state for clickable cards
    flat: { type: Boolean, default: false }, // no border
});

const paddingMap = { none: '', sm: 'p-3', md: 'p-5', lg: 'p-6' };
const bodyPaddingClass = computed(() => paddingMap[props.padding] ?? paddingMap.md);
const headerPadding = computed(() => paddingMap[props.padding] ?? paddingMap.md);
const footerPadding = computed(() => paddingMap[props.padding] ?? paddingMap.md);
const bodyDivider = computed(() => 'border-b border-[#2A2A2A]');

const titleClass = computed(() => props.titleVariant === 'section'
    ? 'text-sm font-semibold text-[#F59E0B] uppercase tracking-wide'
    : 'text-base font-semibold text-white');

const classes = computed(() => [
    'bg-[#1A1A1A] rounded-xl',
    props.flat ? '' : 'border border-[#2A2A2A]',
    props.interactive ? 'transition hover:border-[#3A3A3A] cursor-pointer' : '',
]);
</script>

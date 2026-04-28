<template>
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="modelValue"
                class="fixed inset-0 z-50 flex justify-center bg-black/70 backdrop-blur-sm"
                :class="positionClass"
                @click.self="closeOnBackdrop && close()">
                <div class="bg-[#1A1A1A] border border-[#2A2A2A] shadow-2xl w-full overflow-hidden flex flex-col"
                    :class="[sizeClass, shapeClass, maxHeightClass]">
                    <header v-if="$slots.header || title" class="flex items-start justify-between gap-3 p-4 border-b border-[#2A2A2A] sticky top-0 bg-[#1A1A1A] z-10">
                        <div class="min-w-0 flex-1">
                            <slot name="header">
                                <h2 class="text-lg font-semibold truncate">{{ title }}</h2>
                                <p v-if="subtitle" class="text-[10px] text-[#6B7280] font-mono mt-0.5 truncate">{{ subtitle }}</p>
                            </slot>
                        </div>
                        <button v-if="dismissible" @click="close"
                            class="text-[#A0A0A0] hover:text-white text-2xl leading-none shrink-0 -mt-1"
                            aria-label="Close">×</button>
                    </header>

                    <div class="overflow-y-auto flex-1" :class="bodyPadding">
                        <slot />
                    </div>

                    <footer v-if="$slots.footer" class="border-t border-[#2A2A2A] p-4 bg-[#1A1A1A] sticky bottom-0">
                        <slot name="footer" />
                    </footer>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, watch } from 'vue';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    size: { type: String, default: 'md' }, // sm | md | lg | xl | full
    position: { type: String, default: 'auto' }, // auto = bottom-sheet on mobile / center on desktop; center; bottom
    dismissible: { type: Boolean, default: true },
    closeOnBackdrop: { type: Boolean, default: true },
    closeOnEscape: { type: Boolean, default: true },
    bodyClass: { type: String, default: '' },
    noPadding: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'close']);

const close = () => { emit('update:modelValue', false); emit('close'); };

const sizeClass = computed(() => ({
    sm: 'max-w-sm',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
    full: 'max-w-6xl',
}[props.size] || 'max-w-lg'));

const positionClass = computed(() => ({
    auto: 'items-end sm:items-center sm:p-4',
    center: 'items-center p-4',
    bottom: 'items-end',
}[props.position] || 'items-end sm:items-center sm:p-4'));

const shapeClass = computed(() => props.position === 'center'
    ? 'rounded-xl'
    : 'sm:rounded-xl rounded-t-2xl');

const maxHeightClass = computed(() => 'max-h-[92vh] sm:max-h-[90vh]');

const bodyPadding = computed(() => props.noPadding ? props.bodyClass : ['p-4', props.bodyClass].filter(Boolean).join(' '));

watch(() => props.modelValue, (open) => {
    if (open && props.closeOnEscape) {
        const onKey = (e) => { if (e.key === 'Escape') close(); };
        document.addEventListener('keydown', onKey, { once: false });
        const stop = watch(() => props.modelValue, (v) => {
            if (!v) { document.removeEventListener('keydown', onKey); stop(); }
        });
    }
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>

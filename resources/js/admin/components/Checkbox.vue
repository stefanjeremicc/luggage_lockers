<template>
    <label class="flex items-start gap-3 cursor-pointer select-none" :class="{ 'opacity-50 cursor-not-allowed': disabled }">
        <span class="relative flex items-center justify-center mt-0.5 shrink-0">
            <input
                type="checkbox"
                class="peer sr-only"
                :checked="modelValue"
                :disabled="disabled"
                @change="$emit('update:modelValue', $event.target.checked)"
            >
            <span
                class="w-5 h-5 rounded-md border transition flex items-center justify-center
                       bg-[#111] border-[#3A3A3A]
                       peer-hover:border-[#F59E0B]/60
                       peer-focus-visible:ring-2 peer-focus-visible:ring-[#F59E0B]/50
                       peer-checked:bg-[#F59E0B] peer-checked:border-[#F59E0B]"
            >
                <svg
                    v-if="modelValue"
                    class="w-3.5 h-3.5 text-black"
                    fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </span>
        </span>
        <span v-if="label || description || $slots.default" class="min-w-0">
            <span v-if="label" class="text-sm font-semibold text-white block leading-tight">{{ label }}</span>
            <span v-if="description" class="text-xs text-[#A0A0A0] block mt-0.5">{{ description }}</span>
            <slot />
        </span>
    </label>
</template>
<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    label: { type: String, default: '' },
    description: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
});
defineEmits(['update:modelValue']);
</script>

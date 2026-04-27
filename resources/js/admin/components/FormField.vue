<template>
    <label class="block">
        <span class="text-sm font-medium text-white flex items-center gap-1">
            {{ label }}
            <span v-if="required" class="text-[#EF4444]">*</span>
        </span>
        <p v-if="hint" class="text-xs text-[#6B7280] mt-0.5 mb-1.5">{{ hint }}</p>
        <div :class="hint ? '' : 'mt-1.5'">
            <slot />
        </div>
        <p v-if="error" class="text-xs text-[#EF4444] mt-1">{{ error }}</p>
    </label>
</template>
<script setup>
defineProps({
    label: { type: String, required: true },
    hint: { type: String, default: '' },
    required: { type: Boolean, default: false },
    error: { type: String, default: '' },
});
</script>

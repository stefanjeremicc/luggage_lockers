<template>
    <div class="flex items-start justify-between mb-6 gap-3 flex-wrap min-h-[44px]">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold leading-tight">{{ title }}</h1>
            <p v-if="subtitle" class="text-sm text-[#A0A0A0] mt-1">{{ subtitle }}</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <slot name="actions" />
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
});
</script>

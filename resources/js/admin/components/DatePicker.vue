<template>
    <!-- Native date input: value is already YYYY-MM-DD (our model format), never
         shows a time, and color-scheme:dark themes the picker + makes the
         calendar icon visible. Reliable where vue-datepicker v12 ignored props. -->
    <input
        type="date"
        class="ll-date"
        :value="modelValue"
        :min="min || undefined"
        :max="max || undefined"
        @input="emit('update:modelValue', $event.target.value)"
    />
</template>

<script setup>
defineProps({
    modelValue: { type: String, default: null },
    min: { type: String, default: null },
    max: { type: String, default: null },
});
const emit = defineEmits(['update:modelValue']);
</script>

<style scoped>
.ll-date {
    width: 100%;
    height: 42px;
    background: #111;
    border: 1px solid #2A2A2A;
    border-radius: 8px;
    padding: 0 14px;          /* left-aligned like the Select inputs */
    color: #fff;
    font-size: 0.875rem;
    color-scheme: dark;        /* dark native picker + visible icon */
}
.ll-date:focus { outline: none; border-color: #F59E0B; }
.ll-date::-webkit-calendar-picker-indicator { cursor: pointer; opacity: 0.7; }
.ll-date:hover::-webkit-calendar-picker-indicator { opacity: 1; }
</style>

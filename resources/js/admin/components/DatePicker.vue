<template>
    <VueDatePicker
        :model-value="modelValue"
        model-type="yyyy-MM-dd"
        :enable-time-picker="false"
        format="dd.MM.yyyy"
        :dark="true"
        :teleport="true"
        :auto-apply="true"
        :clearable="false"
        :month-change-on-scroll="false"
        :min-date="minDate"
        :max-date="maxDate"
        class="bll-datepicker"
        @update:model-value="(v) => emit('update:modelValue', v)"
    />
</template>

<script setup>
import { computed } from 'vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

// Date-only picker bound to a 'YYYY-MM-DD' string (model-type handles the
// conversion, so no time component and the value stays a plain date). Themed
// + whole-field clickable like the rest of the admin.
const props = defineProps({
    modelValue: { type: String, default: null },
    min: { type: String, default: null },
    max: { type: String, default: null },
});
const emit = defineEmits(['update:modelValue']);

const minDate = computed(() => props.min ? new Date(props.min + 'T00:00:00') : null);
const maxDate = computed(() => props.max ? new Date(props.max + 'T00:00:00') : null);
</script>

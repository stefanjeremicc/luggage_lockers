<template>
    <VueDatePicker
        v-model="internal"
        :enable-time-picker="true"
        :is-24="true"
        format="dd.MM.yyyy HH:mm"
        dark
        :teleport="true"
        :auto-apply="false"
        :month-change-on-scroll="false"
        time-picker-inline
        :clearable="true"
        :min-date="minDate"
        :max-date="maxDate"
        :start-date="minDate || undefined"
        class="bll-datepicker"
        @update:model-value="onChange"
    />
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
    modelValue: { type: [String, Date, null], default: null },
    min: { type: [String, Date, null], default: null },
    max: { type: [String, Date, null], default: null },
});
const emit = defineEmits(['update:modelValue']);

const toDate = (v) => v ? (v instanceof Date ? v : new Date(v)) : null;
const internal = ref(toDate(props.modelValue));
const minDate = computed(() => toDate(props.min));
const maxDate = computed(() => toDate(props.max));

watch(() => props.modelValue, (v) => { internal.value = toDate(v); });

const onChange = (val) => {
    emit('update:modelValue', val ? new Date(val).toISOString() : null);
};
</script>

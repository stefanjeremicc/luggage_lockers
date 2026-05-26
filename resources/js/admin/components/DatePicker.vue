<template>
    <VueDatePicker
        v-model="internal"
        :enable-time-picker="false"
        format="dd.MM.yyyy"
        dark
        :teleport="true"
        :auto-apply="true"
        :month-change-on-scroll="false"
        :clearable="false"
        :min-date="minDate"
        :max-date="maxDate"
        class="bll-datepicker"
        @update:model-value="onChange"
    />
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

// Date-only picker (no time) bound to a 'YYYY-MM-DD' string. Matches the rest
// of the admin via the shared .bll-datepicker theme + dark mode; the whole
// field is clickable to open the calendar (native input quirks avoided).
const props = defineProps({
    modelValue: { type: String, default: null },
    min: { type: String, default: null },
    max: { type: String, default: null },
});
const emit = defineEmits(['update:modelValue']);

const pad = (n) => String(n).padStart(2, '0');
const toDate = (v) => v ? new Date(v + 'T00:00:00') : null;
const toYmd = (d) => d ? `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}` : null;

const internal = ref(toDate(props.modelValue));
const minDate = computed(() => toDate(props.min));
const maxDate = computed(() => toDate(props.max));

watch(() => props.modelValue, (v) => { internal.value = toDate(v); });

const onChange = (val) => {
    emit('update:modelValue', val ? toYmd(new Date(val)) : null);
};
</script>

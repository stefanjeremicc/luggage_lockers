<template>
    <div class="flex gap-2 w-full">
        <!-- Country selector -->
        <div class="relative shrink-0" ref="dropdownRef">
            <button type="button" @click="open = !open"
                class="flex items-center gap-2 bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2.5 hover:border-[#3A3A3A] focus:border-[#F59E0B] focus:outline-none">
                <span class="text-lg leading-none">{{ selected.flag }}</span>
                <span class="text-sm text-[#A0A0A0]">{{ selected.dial }}</span>
                <svg class="w-4 h-4 text-[#A0A0A0]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div v-if="open"
                class="absolute top-full left-0 mt-1 w-72 max-w-[80vw] bg-[#111] border border-[#2A2A2A] rounded-lg shadow-2xl z-50 max-h-80 overflow-hidden flex flex-col">
                <div class="p-2 border-b border-[#2A2A2A]">
                    <input v-model="search" placeholder="Search country…" ref="searchInput"
                        class="w-full bg-[#0A0A0A] border border-[#2A2A2A] rounded px-3 py-1.5 text-sm text-white focus:border-[#F59E0B] focus:outline-none">
                </div>
                <div class="overflow-y-auto flex-1">
                    <button v-for="c in filtered" :key="c.code" type="button"
                        @click="pick(c)"
                        class="w-full flex items-center gap-3 px-3 py-2 text-sm hover:bg-[#1A1A1A] text-left"
                        :class="c.code === selected.code ? 'bg-[#F59E0B]/10 text-[#F59E0B]' : 'text-white'">
                        <span class="text-lg leading-none shrink-0">{{ c.flag }}</span>
                        <span class="flex-1 truncate">{{ c.name }}</span>
                        <span class="text-xs text-[#A0A0A0]">{{ c.dial }}</span>
                    </button>
                    <div v-if="!filtered.length" class="p-4 text-center text-sm text-[#A0A0A0]">No countries match.</div>
                </div>
            </div>
        </div>

        <!-- National number input -->
        <input
            :value="nationalDisplay"
            @input="onInput"
            @blur="onBlur"
            type="tel"
            :placeholder="placeholder"
            inputmode="tel"
            autocomplete="tel"
            class="flex-1 min-w-0 bg-[#111] border rounded-lg px-3 py-2.5 text-white focus:outline-none"
            :class="hasError ? 'border-[#EF4444]' : 'border-[#2A2A2A] focus:border-[#F59E0B]'">
    </div>
    <p v-if="hasError && errorText" class="text-xs text-[#EF4444] mt-1">{{ errorText }}</p>
</template>
<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import countries from '../../public/countries.js';

const props = defineProps({
    modelValue: { type: String, default: '' },
    defaultCountry: { type: String, default: 'RS' },
    placeholder: { type: String, default: '64 294 1503' },
    required: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'valid']);

const open = ref(false);
const search = ref('');
const dropdownRef = ref(null);
const searchInput = ref(null);
const nationalRaw = ref('');

// Parse initial E.164 value into country + national
const parseE164 = (value) => {
    if (!value) return { country: countries.find(c => c.code === props.defaultCountry), national: '' };
    const v = value.trim().replace(/\s+/g, '');
    if (!v.startsWith('+')) {
        return { country: countries.find(c => c.code === props.defaultCountry), national: v };
    }
    // Find longest matching dial code
    const match = [...countries]
        .sort((a, b) => b.dial.length - a.dial.length)
        .find(c => v.startsWith(c.dial.replace(/[^+\d]/g, '')));
    if (match) {
        const dial = match.dial.replace(/[^+\d]/g, '');
        return { country: match, national: v.slice(dial.length) };
    }
    return { country: countries.find(c => c.code === props.defaultCountry), national: v };
};

const initial = parseE164(props.modelValue);
const selected = ref(initial.country || countries[0]);
nationalRaw.value = initial.national;

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return countries;
    return countries.filter(c =>
        c.name.toLowerCase().includes(q) ||
        c.code.toLowerCase().includes(q) ||
        c.dial.includes(q)
    );
});

const nationalDisplay = computed(() => formatNational(nationalRaw.value));

const formatNational = (digits) => {
    const d = digits.replace(/\D/g, '');
    if (!d) return '';
    // Simple formatting: groups of 3-3-4 or 3-3-3
    if (d.length <= 3) return d;
    if (d.length <= 6) return `${d.slice(0, 3)} ${d.slice(3)}`;
    if (d.length <= 9) return `${d.slice(0, 3)} ${d.slice(3, 6)} ${d.slice(6)}`;
    return `${d.slice(0, 3)} ${d.slice(3, 6)} ${d.slice(6, 10)} ${d.slice(10)}`;
};

const isValid = computed(() => {
    const d = nationalRaw.value.replace(/\D/g, '');
    if (!props.required && !d) return true;
    return d.length >= 6 && d.length <= 14;
});

const hasError = ref(false);
const errorText = computed(() => {
    const d = nationalRaw.value.replace(/\D/g, '');
    if (props.required && !d) return 'Phone number is required';
    if (d && (d.length < 6 || d.length > 14)) return 'Enter a valid phone number';
    return '';
});

const onInput = (e) => {
    nationalRaw.value = e.target.value.replace(/\D/g, '');
    emitValue();
};

const onBlur = () => {
    hasError.value = !isValid.value;
};

const emitValue = () => {
    const d = nationalRaw.value.replace(/\D/g, '');
    if (!d) {
        emit('update:modelValue', '');
        emit('valid', !props.required);
        return;
    }
    const dial = selected.value.dial.replace(/[^+\d]/g, '');
    emit('update:modelValue', dial + d);
    emit('valid', isValid.value);
};

const pick = (country) => {
    selected.value = country;
    open.value = false;
    search.value = '';
    emitValue();
};

watch(open, async (v) => {
    if (v) {
        await nextTick();
        searchInput.value?.focus();
    }
});

watch(() => props.modelValue, (newVal) => {
    const parsed = parseE164(newVal);
    if (parsed.country) selected.value = parsed.country;
    nationalRaw.value = parsed.national.replace(/\D/g, '');
});

const closeOnOutside = (e) => {
    if (open.value && dropdownRef.value && !dropdownRef.value.contains(e.target)) open.value = false;
};
onMounted(() => document.addEventListener('click', closeOnOutside));
onUnmounted(() => document.removeEventListener('click', closeOnOutside));
</script>

<template>
    <div class="relative">
        <button type="button" @click="open = !open"
            class="w-full bg-[#111] border rounded-lg px-4 py-2.5 text-left text-white focus:outline-none transition flex items-center justify-between cursor-pointer"
            :class="open ? 'border-[#F59E0B]' : 'border-[#2A2A2A] hover:border-[#3A3A3A]'">
            <span class="font-mono">{{ display }}</span>
            <svg class="w-4 h-4 text-[#A0A0A0]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </button>
        <Transition name="dropdown">
            <div v-if="open" class="absolute z-30 mt-1 left-0 right-0 bg-[#111] border border-[#2A2A2A] rounded-lg shadow-2xl p-3 flex gap-2">
                <div class="flex-1">
                    <div class="text-[10px] uppercase tracking-wide text-[#6B7280] mb-1 text-center">Hour</div>
                    <div class="max-h-44 overflow-y-auto rounded border border-[#2A2A2A] py-1">
                        <button v-for="h in hours" :key="h" type="button"
                            @click="setHour(h)"
                            class="w-full text-center py-1 text-sm font-mono cursor-pointer transition"
                            :class="h === selectedHour ? 'bg-[#F59E0B] text-black' : 'text-white hover:bg-[#1A1A1A]'">
                            {{ h.toString().padStart(2, '0') }}
                        </button>
                    </div>
                </div>
                <div class="flex-1">
                    <div class="text-[10px] uppercase tracking-wide text-[#6B7280] mb-1 text-center">Minute</div>
                    <div class="max-h-44 overflow-y-auto rounded border border-[#2A2A2A] py-1">
                        <button v-for="m in minutes" :key="m" type="button"
                            @click="setMinute(m)"
                            class="w-full text-center py-1 text-sm font-mono cursor-pointer transition"
                            :class="m === selectedMinute ? 'bg-[#F59E0B] text-black' : 'text-white hover:bg-[#1A1A1A]'">
                            {{ m.toString().padStart(2, '0') }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, getCurrentInstance } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    minuteStep: { type: Number, default: 15 },
});
const emit = defineEmits(['update:modelValue']);

const open = ref(false);

const hours = Array.from({ length: 24 }, (_, i) => i);
const minutes = computed(() => {
    const out = [];
    for (let m = 0; m < 60; m += props.minuteStep) out.push(m);
    return out;
});

const parsed = computed(() => {
    const [h = 9, m = 0] = (props.modelValue || '').split(':').map(Number);
    return { h: isNaN(h) ? 9 : h, m: isNaN(m) ? 0 : m };
});

const selectedHour = computed(() => parsed.value.h);
const selectedMinute = computed(() => {
    const m = parsed.value.m;
    return minutes.value.reduce((a, b) => Math.abs(b - m) < Math.abs(a - m) ? b : a, minutes.value[0]);
});

const display = computed(() => {
    if (!props.modelValue) return '—';
    return `${parsed.value.h.toString().padStart(2, '0')}:${parsed.value.m.toString().padStart(2, '0')}`;
});

const setHour = (h) => {
    const m = parsed.value.m;
    emit('update:modelValue', `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`);
};
const setMinute = (m) => {
    const h = parsed.value.h;
    emit('update:modelValue', `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`);
    open.value = false;
};

const root = ref(null);
const onClickOutside = (e) => {
    const inst = getCurrentInstance();
    const el = inst?.proxy?.$el;
    if (el && !el.contains(e.target)) open.value = false;
};
onMounted(() => document.addEventListener('click', onClickOutside));
onUnmounted(() => document.removeEventListener('click', onClickOutside));
</script>

<style scoped>
.dropdown-enter-active, .dropdown-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-4px); }
</style>

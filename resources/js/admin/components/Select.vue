<template>
    <div class="relative" ref="rootRef">
        <button type="button"
            @click="toggle"
            :disabled="disabled"
            class="w-full bg-[#111] border rounded-lg px-4 pr-10 py-2.5 text-left text-white text-sm flex items-center justify-between gap-2 focus:outline-none transition h-[42px]"
            :class="[open ? 'border-[#F59E0B]' : (error ? 'border-[#EF4444]' : 'border-[#2A2A2A] hover:border-[#3A3A3A]'), disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer']">
            <span class="flex items-center gap-2 min-w-0 flex-1">
                <span v-if="selected?.icon" class="shrink-0" v-html="selected.icon"></span>
                <span class="truncate" :class="selected ? 'text-white' : 'text-[#6B7280]'">{{ selected ? selected.label : placeholder }}</span>
            </span>
            <svg class="w-4 h-4 text-[#A0A0A0] shrink-0 transition" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <Transition name="dropdown">
            <div v-if="open"
                class="absolute left-0 right-0 mt-1 bg-[#111] border border-[#2A2A2A] rounded-lg shadow-2xl z-40 overflow-hidden">
                <div v-if="searchable" class="p-2 border-b border-[#2A2A2A]">
                    <input v-model="query" ref="searchInput" :placeholder="searchPlaceholder"
                        class="w-full bg-[#0A0A0A] border border-[#2A2A2A] rounded px-3 py-1.5 text-sm text-white focus:border-[#F59E0B] focus:outline-none">
                </div>
                <div class="max-h-64 overflow-y-auto">
                    <button v-for="opt in filteredOptions" :key="opt.value" type="button"
                        @click="pick(opt)"
                        class="w-full text-left px-4 py-2.5 text-sm flex items-center gap-2 transition"
                        :class="opt.value === modelValue ? 'bg-[#F59E0B]/15 text-[#F59E0B]' : 'text-white hover:bg-[#1A1A1A]'">
                        <span v-if="opt.icon" v-html="opt.icon" class="shrink-0"></span>
                        <span class="flex-1 truncate">{{ opt.label }}</span>
                        <svg v-if="opt.value === modelValue" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </button>
                    <div v-if="!filteredOptions.length" class="px-4 py-3 text-sm text-[#A0A0A0] text-center">No matches</div>
                </div>
            </div>
        </Transition>
    </div>
</template>
<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number, null], default: null },
    options: { type: Array, required: true }, // [{ value, label, icon? }]
    placeholder: { type: String, default: 'Select…' },
    searchable: { type: Boolean, default: false },
    searchPlaceholder: { type: String, default: 'Search…' },
    disabled: { type: Boolean, default: false },
    error: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const query = ref('');
const rootRef = ref(null);
const searchInput = ref(null);

const selected = computed(() => props.options.find(o => o.value === props.modelValue));

const filteredOptions = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return props.options;
    return props.options.filter(o =>
        o.label.toLowerCase().includes(q) ||
        String(o.value).toLowerCase().includes(q)
    );
});

const toggle = () => {
    if (props.disabled) return;
    open.value = !open.value;
};

const pick = (opt) => {
    emit('update:modelValue', opt.value);
    open.value = false;
    query.value = '';
};

watch(open, async (v) => {
    if (v && props.searchable) {
        await nextTick();
        searchInput.value?.focus();
    }
});

const closeOnOutside = (e) => {
    if (open.value && rootRef.value && !rootRef.value.contains(e.target)) open.value = false;
};
const onKey = (e) => { if (e.key === 'Escape') open.value = false; };

onMounted(() => {
    document.addEventListener('click', closeOnOutside);
    document.addEventListener('keydown', onKey);
});
onUnmounted(() => {
    document.removeEventListener('click', closeOnOutside);
    document.removeEventListener('keydown', onKey);
});
</script>
<style scoped>
.dropdown-enter-active, .dropdown-leave-active { transition: all 0.15s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-4px); }
</style>

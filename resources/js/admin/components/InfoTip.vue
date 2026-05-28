<template>
    <span ref="anchor" class="inline-flex align-middle">
        <button type="button"
            @click.stop="toggle" @mouseenter="openHover" @mouseleave="hover = false"
            class="text-[#6B7280] hover:text-[#F59E0B] transition leading-none" aria-label="Info">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </button>
        <Teleport to="body">
            <div v-if="clicked" class="fixed inset-0 z-40" @click="clicked = false"></div>
            <div v-if="hover || clicked"
                class="fixed z-50 px-2.5 py-1.5 rounded-lg bg-[#0A0A0A] border border-[#3A3A3A] shadow-xl text-[11px] text-[#D0D0D0] leading-snug pointer-events-none"
                :style="style">{{ text }}</div>
        </Teleport>
    </span>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({ text: { type: String, required: true } });
const anchor = ref(null);
const hover = ref(false);
const clicked = ref(false);
const style = ref({});
const M = 12, W = 240;

const place = () => {
    const el = anchor.value;
    if (!el) return;
    const r = el.getBoundingClientRect();
    const left = Math.min(Math.max(M, r.left + r.width / 2 - W / 2), window.innerWidth - W - M);
    style.value = { left: left + 'px', top: (r.top - 6) + 'px', transform: 'translateY(-100%)', maxWidth: W + 'px' };
};
const openHover = () => { hover.value = true; place(); };
const toggle = () => { clicked.value = !clicked.value; if (clicked.value) place(); };
const onReposition = () => { if (hover.value || clicked.value) place(); };

onMounted(() => {
    window.addEventListener('scroll', onReposition, true);
    window.addEventListener('resize', onReposition);
});
onUnmounted(() => {
    window.removeEventListener('scroll', onReposition, true);
    window.removeEventListener('resize', onReposition);
});
</script>

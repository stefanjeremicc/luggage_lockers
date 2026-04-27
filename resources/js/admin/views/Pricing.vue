<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold">Pricing</h1>
                <p class="text-sm text-[#A0A0A0] mt-1">Prices in EUR per locker for the full duration of the booking.</p>
            </div>
        </div>

        <div v-if="loading" class="text-sm text-[#A0A0A0]">Loading…</div>

        <div v-for="loc in groupedByLocation" :key="loc.id ?? 'global'" class="mb-8">
            <h2 class="text-lg font-semibold mb-3">{{ loc.name }}</h2>

            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="border-b border-[#2A2A2A] bg-[#141414]">
                        <tr class="text-[#A0A0A0] text-left">
                            <th class="px-2 sm:px-5 py-3 font-medium">Duration</th>
                            <th class="px-2 sm:px-5 py-3 font-medium">
                                <span class="inline-flex items-center gap-2 text-cyan-400">
                                    <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                                    Standard
                                </span>
                            </th>
                            <th class="px-2 sm:px-5 py-3 font-medium">
                                <span class="inline-flex items-center gap-2 text-fuchsia-400">
                                    <span class="w-2 h-2 rounded-full bg-fuchsia-400"></span>
                                    Large
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="duration in durationOrder" :key="duration" class="border-t border-[#2A2A2A]/60">
                            <td class="px-2 sm:px-5 py-3 text-[#A0A0A0] text-xs sm:text-sm">{{ durationLabel(duration) }}</td>
                            <td class="px-2 sm:px-5 py-3">
                                <PriceInput :rule="findRule(loc.id, 'standard', duration)" @update="onUpdate" />
                            </td>
                            <td class="px-2 sm:px-5 py-3">
                                <PriceInput :rule="findRule(loc.id, 'large', duration)" @update="onUpdate" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, h } from 'vue';
import { useAuth } from '../composables/useAuth';
import { useToast } from '../composables/useToast';

const { apiFetch } = useAuth();
const toast = useToast();
const rules = ref([]);
const loading = ref(true);

const durationOrder = ['6h', '24h', '2_days', '3_days', '4_days', '5_days', '1_week', '2_weeks', '1_month'];

const durationLabels = {
    '6h': 'Up to 6 hours',
    '24h': '24 hours',
    '2_days': '2 days',
    '3_days': '3 days',
    '4_days': '4 days',
    '5_days': '5 days',
    '1_week': '1 week',
    '2_weeks': '2 weeks',
    '1_month': '1 month',
};
const durationLabel = (k) => durationLabels[k] || k;

const groupedByLocation = computed(() => {
    const map = new Map();
    rules.value.forEach(r => {
        const id = r.location?.id ?? null;
        const name = r.location?.name ?? 'Global (fallback)';
        if (!map.has(id)) map.set(id, { id, name, rules: [] });
        map.get(id).rules.push(r);
    });
    return Array.from(map.values());
});

const findRule = (locationId, size, duration) => {
    return rules.value.find(r =>
        (r.location?.id ?? null) === locationId &&
        r.locker_size === size &&
        r.duration_key === duration
    );
};

const onUpdate = async ({ rule, price }) => {
    try {
        await apiFetch(`/api/admin/pricing-rules/${rule.id}`, {
            method: 'PUT',
            body: JSON.stringify({ price_eur: price }),
        });
        rule.price_eur = price;
        toast.success(`Updated ${durationLabel(rule.duration_key)} · ${rule.locker_size}`);
    } catch (e) {
        toast.error('Failed to update price: ' + e.message);
    }
};

const PriceInput = {
    props: { rule: { type: Object, default: null } },
    emits: ['update'],
    setup(props, { emit }) {
        const draft = ref('');
        const focused = ref(false);

        const formatted = (v) => {
            const n = Number(v);
            if (isNaN(n)) return '';
            return n.toFixed(2);
        };

        const display = computed(() =>
            props.rule ? (focused.value ? draft.value : formatted(props.rule.price_eur)) : ''
        );

        const onFocus = (e) => {
            if (!props.rule) return;
            focused.value = true;
            draft.value = String(Number(props.rule.price_eur));
            setTimeout(() => e.target.select(), 0);
        };

        const onBlur = () => {
            focused.value = false;
            if (!props.rule) return;
            const raw = draft.value.replace(',', '.').trim();
            if (raw === '') return;
            const n = Number(raw);
            if (isNaN(n) || n < 0) {
                toast.error('Invalid price');
                return;
            }
            const rounded = Math.round(n * 100) / 100;
            if (rounded === Number(props.rule.price_eur)) return;
            emit('update', { rule: props.rule, price: rounded });
        };

        return () => props.rule
            ? h('div', { class: 'flex items-center gap-1.5' }, [
                h('span', { class: 'text-[#6B7280] text-sm' }, '€'),
                h('input', {
                    type: 'text',
                    inputmode: 'decimal',
                    value: display.value,
                    onInput: (e) => (draft.value = e.target.value),
                    onFocus,
                    onBlur,
                    onKeydown: (e) => { if (e.key === 'Enter') e.target.blur(); },
                    class: 'bg-[#111] border border-[#2A2A2A] rounded-lg px-2 sm:px-3 py-1.5 w-16 sm:w-24 text-white text-right text-sm focus:border-[#F59E0B] focus:outline-none tabular-nums',
                }),
            ])
            : h('span', { class: 'text-[#6B7280] text-xs italic' }, 'not set');
    },
};

onMounted(async () => {
    try {
        const res = await apiFetch('/api/admin/pricing-rules');
        rules.value = await res.json();
    } catch (e) {
        toast.error('Failed to load pricing rules');
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold mb-6">Dashboard</h1>

        <div v-if="loading" class="text-sm text-[#A0A0A0]">Loading…</div>
        <div v-else-if="error" class="bg-[#EF4444]/10 border border-[#EF4444]/30 rounded-xl p-4 text-sm text-[#EF4444]">
            Failed to load dashboard: {{ error }}
        </div>

        <template v-else>
            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <StatCard v-for="s in statCards" :key="s.label" v-bind="s" />
            </div>

            <!-- Revenue breakdown -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div v-for="r in revenueCards" :key="r.label"
                    class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                    <p class="text-xs uppercase tracking-wide text-[#A0A0A0]">{{ r.label }}</p>
                    <p class="text-2xl font-bold mt-1 text-[#F59E0B]">€{{ Number(r.value).toFixed(2) }}</p>
                </div>
            </div>

            <!-- Alerts -->
            <div v-if="hasAlerts" class="mb-8">
                <h2 class="text-sm font-semibold text-[#F59E0B] uppercase tracking-wide mb-3">Alerts</h2>
                <div class="space-y-2">
                    <div v-for="l in data.alerts.low_battery" :key="'bat-'+l.id"
                        class="bg-[#1A1A1A] border border-[#F59E0B]/30 rounded-lg px-4 py-3 text-sm flex items-center justify-between">
                        <span>
                            <span class="font-mono font-semibold mr-2">{{ l.number }}</span>
                            <span class="text-[#A0A0A0]">low battery · {{ l.location?.name }}</span>
                        </span>
                        <span class="text-[#F59E0B] font-mono">{{ l.battery_level }}%</span>
                    </div>
                    <div v-for="l in data.alerts.offline" :key="'off-'+l.id"
                        class="bg-[#1A1A1A] border border-[#EF4444]/30 rounded-lg px-4 py-3 text-sm flex items-center justify-between">
                        <span>
                            <span class="font-mono font-semibold mr-2">{{ l.number }}</span>
                            <span class="text-[#A0A0A0]">offline · {{ l.location?.name }}</span>
                        </span>
                        <span class="text-[#EF4444] text-xs uppercase">Offline</span>
                    </div>
                </div>
            </div>

            <!-- Locker grid per location -->
            <div v-if="!data.locker_grid?.length" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-8 text-center text-[#A0A0A0]">
                No active locations. <router-link to="/admin/locations/new" class="text-[#F59E0B] hover:underline">Create one →</router-link>
            </div>

            <div v-for="loc in data.locker_grid" :key="loc.id" class="mb-8">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-semibold">{{ loc.name }}</h2>
                    <router-link :to="`/admin/locations/${loc.id}/edit`" class="text-xs text-[#A0A0A0] hover:text-[#F59E0B]">Manage →</router-link>
                </div>
                <div v-if="!loc.lockers?.length" class="text-sm text-[#A0A0A0] italic">No lockers assigned.</div>
                <div v-else class="grid grid-cols-4 sm:grid-cols-6 lg:grid-cols-12 gap-2">
                    <router-link v-for="l in loc.lockers" :key="l.id" :to="`/admin/lockers/${l.id}`"
                        class="aspect-square rounded-lg flex flex-col items-center justify-center text-xs font-mono border transition hover:scale-105"
                        :class="{
                            'bg-[#10B981]/15 border-[#10B981]/40 text-[#10B981]': l.status === 'available',
                            'bg-[#EF4444]/15 border-[#EF4444]/40 text-[#EF4444]': l.status === 'occupied',
                            'bg-[#F59E0B]/15 border-[#F59E0B]/40 text-[#F59E0B]': l.status === 'maintenance',
                            'bg-[#6B7280]/15 border-[#6B7280]/40 text-[#6B7280]': l.status === 'offline',
                        }"
                        :title="l.current_booking
                            ? `${l.number} · BOOKED by ${l.current_booking.customer_name} until ${new Date(l.current_booking.check_out).toLocaleString()}`
                            : `${l.number} · ${l.status} · ${l.battery_level ?? '—'}%`">
                        <span class="font-bold">{{ l.number }}</span>
                        <span class="text-[10px] mt-0.5 uppercase opacity-70">{{ l.size[0] }}</span>
                        <span v-if="l.current_booking" class="text-[8px] uppercase font-bold mt-0.5">●</span>
                    </router-link>
                </div>
            </div>
        </template>
    </div>
</template>
<script setup>
import { ref, computed, onMounted, h } from 'vue';
import { useAuth } from '../composables/useAuth';

const { apiFetch } = useAuth();
const data = ref(null);
const loading = ref(true);
const error = ref(null);

const statCards = computed(() => {
    if (!data.value) return [];
    const s = data.value.stats;
    return [
        { label: "Today's bookings", value: s.today_bookings, color: 'text-white' },
        { label: 'Active now', value: s.active_bookings, color: 'text-[#10B981]' },
        { label: 'Overdue', value: s.overdue_bookings, color: s.overdue_bookings > 0 ? 'text-[#EF4444]' : 'text-white' },
        { label: 'Revenue (month)', value: '€' + Number(s.revenue_month).toFixed(2), color: 'text-[#F59E0B]' },
    ];
});

const revenueCards = computed(() => {
    if (!data.value) return [];
    const s = data.value.stats;
    return [
        { label: 'Today', value: s.revenue_today ?? 0 },
        { label: 'This week', value: s.revenue_week ?? 0 },
        { label: 'This month', value: s.revenue_month ?? 0 },
    ];
});

const hasAlerts = computed(() =>
    (data.value?.alerts?.low_battery?.length || 0) > 0 ||
    (data.value?.alerts?.offline?.length || 0) > 0
);

const StatCard = {
    props: ['label', 'value', 'color'],
    setup: (p) => () =>
        h('div', { class: 'bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5' }, [
            h('p', { class: 'text-xs uppercase tracking-wide text-[#A0A0A0]' }, p.label),
            h('p', { class: 'text-2xl font-bold mt-1 ' + (p.color || 'text-white') }, p.value),
        ]),
};

onMounted(async () => {
    try {
        const res = await apiFetch('/api/admin/dashboard');
        if (!res.ok) throw new Error('HTTP ' + res.status);
        data.value = await res.json();
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
});
</script>

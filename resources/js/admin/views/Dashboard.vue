<template>
    <div>
        <h1 class="text-2xl font-bold mb-6">Dashboard</h1>

        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div v-for="stat in stats" :key="stat.label" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                <p class="text-sm text-[#A0A0A0]">{{ stat.label }}</p>
                <p class="text-2xl font-bold mt-1" :class="stat.color || 'text-white'">{{ stat.value }}</p>
            </div>
        </div>

        <!-- Alerts -->
        <div v-if="data?.alerts?.low_battery?.length || data?.alerts?.offline?.length" class="mb-8">
            <h2 class="text-lg font-semibold mb-3 text-[#EF4444]">Alerts</h2>
            <div class="space-y-2">
                <div v-for="l in data?.alerts?.low_battery" :key="'bat-'+l.id" class="bg-[#1A1A1A] border border-[#F59E0B]/30 rounded-lg px-4 py-3 text-sm flex justify-between">
                    <span>Low battery: {{ l.number }} ({{ l.location?.name }})</span>
                    <span class="text-[#F59E0B]">{{ l.battery_level }}%</span>
                </div>
                <div v-for="l in data?.alerts?.offline" :key="'off-'+l.id" class="bg-[#1A1A1A] border border-[#EF4444]/30 rounded-lg px-4 py-3 text-sm flex justify-between">
                    <span>Offline: {{ l.number }} ({{ l.location?.name }})</span>
                    <span class="text-[#EF4444]">Offline</span>
                </div>
            </div>
        </div>

        <!-- Locker Grid -->
        <div v-for="loc in data?.locker_grid" :key="loc.id" class="mb-8">
            <h2 class="text-lg font-semibold mb-3">{{ loc.name }}</h2>
            <div class="grid grid-cols-4 sm:grid-cols-6 lg:grid-cols-12 gap-2">
                <div v-for="l in loc.lockers" :key="l.id"
                     class="aspect-square rounded-lg flex flex-col items-center justify-center text-xs font-mono border"
                     :class="{
                         'bg-[#10B981]/20 border-[#10B981]/40 text-[#10B981]': l.status === 'available',
                         'bg-[#EF4444]/20 border-[#EF4444]/40 text-[#EF4444]': l.status === 'occupied',
                         'bg-[#F59E0B]/20 border-[#F59E0B]/40 text-[#F59E0B]': l.status === 'maintenance',
                         'bg-[#6B7280]/20 border-[#6B7280]/40 text-[#6B7280]': l.status === 'offline',
                     }">
                    <span class="font-bold">{{ l.number }}</span>
                    <span class="text-[10px] mt-0.5 uppercase">{{ l.size[0] }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAuth } from '../composables/useAuth';

const { apiFetch } = useAuth();
const data = ref(null);

const stats = computed(() => {
    if (!data.value) return [];
    const s = data.value.stats;
    return [
        { label: 'Today\'s Bookings', value: s.today_bookings, color: 'text-white' },
        { label: 'Active Bookings', value: s.active_bookings, color: 'text-[#10B981]' },
        { label: 'Overdue', value: s.overdue_bookings, color: s.overdue_bookings > 0 ? 'text-[#EF4444]' : 'text-white' },
        { label: 'Revenue (Month)', value: '\u20AC' + Number(s.revenue_month).toFixed(2), color: 'text-[#F59E0B]' },
    ];
});

onMounted(async () => {
    const res = await apiFetch('/api/admin/dashboard');
    data.value = await res.json();
});
</script>

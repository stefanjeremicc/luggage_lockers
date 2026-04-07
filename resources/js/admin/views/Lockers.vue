<template>
    <div>
        <h1 class="text-2xl font-bold mb-6">Lockers</h1>
        <div v-for="loc in groupedLockers" :key="loc.name" class="mb-8">
            <h2 class="text-lg font-semibold mb-3">{{ loc.name }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div v-for="l in loc.lockers" :key="l.id" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4 flex items-center justify-between">
                    <div>
                        <span class="font-mono font-bold text-lg">{{ l.number }}</span>
                        <span class="text-xs ml-2 px-2 py-0.5 rounded-full" :class="l.size === 'large' ? 'bg-purple-500/20 text-purple-400' : 'bg-blue-500/20 text-blue-400'">{{ l.size }}</span>
                        <div class="flex items-center gap-3 mt-1 text-xs text-[#A0A0A0]">
                            <span :class="l.is_online ? 'text-[#10B981]' : 'text-[#EF4444]'">{{ l.is_online ? 'Online' : 'Offline' }}</span>
                            <span v-if="l.battery_level !== null" :class="l.battery_level < 20 ? 'text-[#EF4444]' : 'text-[#A0A0A0]'">{{ l.battery_level }}%</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full" :class="{
                            'bg-[#10B981]': l.status === 'available',
                            'bg-[#EF4444]': l.status === 'occupied',
                            'bg-[#F59E0B]': l.status === 'maintenance',
                            'bg-[#6B7280]': l.status === 'offline',
                        }"></span>
                        <select v-model="l.status" @change="updateStatus(l)" class="bg-[#111] border border-[#2A2A2A] rounded px-2 py-1 text-xs text-white">
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="offline">Offline</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';

const { apiFetch } = useAuth();
const lockers = ref([]);

const groupedLockers = computed(() => {
    const groups = {};
    lockers.value.forEach(l => {
        const name = l.location?.name || 'Unknown';
        if (!groups[name]) groups[name] = { name, lockers: [] };
        groups[name].lockers.push(l);
    });
    return Object.values(groups);
});

const updateStatus = async (l) => {
    await apiFetch(`/api/admin/lockers/${l.id}`, { method: 'PUT', body: JSON.stringify({ status: l.status }) });
};

onMounted(async () => {
    const res = await apiFetch('/api/admin/lockers');
    lockers.value = await res.json();
});
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Locations</h1>
        </div>
        <div class="space-y-4">
            <div v-for="loc in locations" :key="loc.id" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">{{ loc.name }}</h2>
                        <p class="text-sm text-[#A0A0A0]">{{ loc.address }}, {{ loc.city }}</p>
                        <div class="flex gap-4 mt-2 text-xs text-[#A0A0A0]">
                            <span>Slug: {{ loc.slug }}</span>
                            <span>{{ loc.is_24h ? '24/7' : loc.opening_time + ' - ' + loc.closing_time }}</span>
                            <span :class="loc.is_active ? 'text-[#10B981]' : 'text-[#EF4444]'">{{ loc.is_active ? 'Active' : 'Inactive' }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <span class="text-sm text-[#A0A0A0]">{{ loc.phone }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';

const { apiFetch } = useAuth();
const locations = ref([]);

onMounted(async () => {
    const res = await apiFetch('/api/admin/locations');
    locations.value = await res.json();
});
</script>

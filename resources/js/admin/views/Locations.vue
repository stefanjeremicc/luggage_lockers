<template>
    <div>
        <PageHeader title="Locations" :subtitle="`${locations.length} locations`">
            <template #actions>
                <Btn as="router-link" to="/admin/locations/new" variant="primary">
                    <template #icon>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </template>
                    New Location
                </Btn>
            </template>
        </PageHeader>

        <div v-if="loading" class="text-sm text-[#A0A0A0]">Loading…</div>
        <div v-else-if="!locations.length" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-8 text-center text-[#A0A0A0]">
            No locations yet. Create one to start assigning lockers.
        </div>

        <div v-else class="grid gap-4 md:grid-cols-2">
            <router-link v-for="loc in locations" :key="loc.id"
                :to="`/admin/locations/${loc.id}/edit`"
                class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 hover:border-[#F59E0B] transition block">
                <div class="flex items-start justify-between mb-2">
                    <h2 class="text-lg font-semibold">{{ loc.name }}</h2>
                    <span class="text-xs px-2 py-0.5 rounded-full"
                        :class="loc.is_active ? 'bg-[#10B981]/15 text-[#10B981]' : 'bg-[#EF4444]/15 text-[#EF4444]'">
                        {{ loc.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <p class="text-sm text-[#A0A0A0]">{{ loc.address }}, {{ loc.city }}</p>
                <div class="flex items-center gap-3 mt-3 text-xs text-[#A0A0A0]">
                    <span class="font-mono">/{{ loc.slug }}</span>
                    <span>·</span>
                    <span>{{ loc.is_24h ? '24/7' : (loc.opening_time || '—') + ' – ' + (loc.closing_time || '—') }}</span>
                    <span v-if="loc.phone">·</span>
                    <span v-if="loc.phone">{{ loc.phone }}</span>
                </div>
            </router-link>
        </div>
    </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';
import Btn from '../components/Btn.vue';
import PageHeader from '../components/PageHeader.vue';

const { apiFetch } = useAuth();
const locations = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const res = await apiFetch('/api/admin/locations');
        locations.value = await res.json();
    } finally {
        loading.value = false;
    }
});
</script>

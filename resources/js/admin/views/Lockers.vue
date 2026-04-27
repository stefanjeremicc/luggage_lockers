<template>
    <div>
        <PageHeader title="Lockers" :subtitle="`${lockers.length} lockers`">
            <template #actions>
                <Btn variant="primary" :loading="syncing" @click="syncAll">
                    <template #icon>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4 9a8 8 0 0114-3m-1 13a8 8 0 01-13-3"/></svg>
                    </template>
                    {{ syncing ? 'Syncing…' : 'Sync from TTLock' }}
                </Btn>
            </template>
        </PageHeader>
        <p v-if="syncMsg" class="text-xs text-[#10B981] mb-3 -mt-3">{{ syncMsg }}</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <router-link v-for="l in sortedLockers" :key="l.id"
                :to="`/admin/lockers/${l.id}`"
                class="bg-[#1A1A1A] border border-[#2A2A2A] hover:border-[#3A3A3A] rounded-xl p-4 flex items-center justify-between transition">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-mono font-bold text-lg">{{ l.number }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full" :class="l.size === 'large' ? 'bg-fuchsia-500/20 text-fuchsia-400' : 'bg-cyan-500/20 text-cyan-400'">{{ l.size }}</span>
                    </div>
                    <div class="text-xs text-[#A0A0A0] mt-1 truncate">{{ l.location?.name || 'Unassigned' }}</div>
                    <div class="flex items-center gap-3 mt-1 text-xs">
                        <span :class="l.is_online ? 'text-[#10B981]' : 'text-[#EF4444]'">{{ l.is_online ? 'Online' : 'Offline' }}</span>
                        <span v-if="l.battery_level !== null" :class="l.battery_level < 20 ? 'text-[#EF4444]' : 'text-[#A0A0A0]'">{{ l.battery_level }}%</span>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-1 shrink-0">
                    <span class="text-[9px] uppercase tracking-wide text-[#6B7280]">Site visibility</span>
                    <div @click.stop.prevent class="w-36">
                        <Select :model-value="l.is_published_on_site ? 'visible' : 'hidden'"
                            :options="visibilityOptions"
                            @update:model-value="v => updateVisibility(l, v)" />
                    </div>
                    <span class="text-[9px] mt-1 uppercase tracking-wide text-[#6B7280]">App status (TTLock)</span>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full" :class="{
                            'bg-[#10B981]': l.status === 'available',
                            'bg-[#EF4444]': l.status === 'occupied',
                            'bg-[#F59E0B]': l.status === 'maintenance',
                            'bg-[#6B7280]': l.status === 'offline',
                        }"></span>
                        <span class="text-xs capitalize text-[#A0A0A0]">{{ l.status }}</span>
                    </div>
                </div>
            </router-link>
        </div>
    </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';
import { useToast } from '../composables/useToast';
import Select from '../components/Select.vue';
import Btn from '../components/Btn.vue';
import PageHeader from '../components/PageHeader.vue';

const visibilityOptions = [
    { value: 'visible', label: 'Visible on site' },
    { value: 'hidden', label: 'Hidden from site' },
];

const { apiFetch } = useAuth();
const toast = useToast();
const lockers = ref([]);
const syncing = ref(false);
const syncMsg = ref('');

// Natural sort: "B-01" < "B-02" < "B-10" (not lexicographic)
const naturalCmp = (a, b) => a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });

const sortedLockers = computed(() =>
    [...lockers.value].sort((a, b) => {
        const locCmp = naturalCmp(a.location?.name || 'zzz', b.location?.name || 'zzz');
        if (locCmp !== 0) return locCmp;
        return naturalCmp(a.number, b.number);
    })
);

const updateVisibility = async (l, v) => {
    const next = v === 'visible';
    try {
        const res = await apiFetch(`/api/admin/lockers/${l.id}`, {
            method: 'PUT',
            body: JSON.stringify({ is_published_on_site: next }),
        });
        if (!res.ok) throw new Error();
        l.is_published_on_site = next;
        toast.success(next ? 'Locker shown on site' : 'Locker hidden from site');
    } catch {
        toast.error('Failed to update visibility');
    }
};

const load = async () => {
    const res = await apiFetch('/api/admin/lockers');
    lockers.value = await res.json();
};

const syncAll = async () => {
    syncing.value = true; syncMsg.value = '';
    try {
        const res = await apiFetch('/api/admin/lockers/sync-all', { method: 'POST' });
        const data = await res.json();
        syncMsg.value = `Updated ${data.updated} of ${data.total_api} locks.`;
        await load();
    } catch (e) { syncMsg.value = 'Error: ' + e.message; }
    finally { syncing.value = false; setTimeout(() => syncMsg.value = '', 5000); }
};

onMounted(load);
</script>

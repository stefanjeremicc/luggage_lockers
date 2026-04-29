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

        <div v-if="unmapped.length" class="mb-6 bg-[#1A1A1A] border border-[#F59E0B]/30 rounded-xl p-4">
            <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
                <h3 class="text-sm font-semibold text-[#F59E0B]">{{ unmapped.length }} unmapped TTLock lock{{ unmapped.length === 1 ? '' : 's' }}</h3>
                <Btn variant="primary" size="sm" :loading="autoCreating" @click="autoCreate">Auto-create from TTLock</Btn>
            </div>
            <p class="text-xs text-[#A0A0A0] mb-3">Locks present in TTLock but not yet linked to a DB locker. Either assign each to an existing locker (matches by alias / number) or auto-create new rows.</p>
            <div class="space-y-2">
                <div v-for="lock in unmapped" :key="lock.lockId"
                    class="bg-[#111] border border-[#2A2A2A] rounded-lg p-3 grid grid-cols-1 sm:grid-cols-[1fr_auto_auto] gap-2 items-center">
                    <div class="min-w-0">
                        <div class="font-mono font-bold">{{ lock.lockAlias || '(no alias)' }}</div>
                        <div class="text-xs text-[#6B7280]">lockId: {{ lock.lockId }} · battery: {{ lock.electricQuantity ?? '?' }}%</div>
                    </div>
                    <Select :model-value="null" :options="unassignedOptions"
                        placeholder="Assign to existing locker…"
                        @update:model-value="id => assignTTLock(lock, id)" />
                    <Btn variant="secondary" size="sm" @click="createFromTTLock(lock)">Create new</Btn>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <router-link v-for="l in sortedLockers" :key="l.id"
                :to="`/admin/lockers/${l.id}`"
                class="bg-[#1A1A1A] border border-[#2A2A2A] hover:border-[#3A3A3A] rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 transition">
                <div class="min-w-0" :class="{ 'opacity-60': !l.is_online || !l.is_active }">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-mono font-bold text-lg">{{ l.number }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full" :class="l.size === 'large' ? 'bg-fuchsia-500/20 text-fuchsia-400' : 'bg-cyan-500/20 text-cyan-400'">{{ l.size }}</span>
                        <span v-if="!l.is_online" class="text-[10px] px-2 py-0.5 rounded-full bg-[#EF4444]/20 text-[#EF4444] font-semibold uppercase tracking-wide">Unreachable</span>
                        <span v-if="!l.is_active" class="text-[10px] px-2 py-0.5 rounded-full bg-[#6B7280]/20 text-[#A0A0A0] font-semibold uppercase tracking-wide">Deactivated</span>
                        <span v-if="l.current_bookings?.length" class="text-[10px] px-2 py-0.5 rounded-full bg-[#EF4444]/20 text-[#EF4444] font-semibold uppercase tracking-wide">Booked</span>
                        <span v-else-if="l.upcoming_bookings?.length" class="text-[10px] px-2 py-0.5 rounded-full bg-[#F59E0B]/20 text-[#F59E0B] font-semibold uppercase tracking-wide">Upcoming</span>
                    </div>
                    <div class="text-xs text-[#A0A0A0] mt-1 truncate">{{ l.location?.name || 'Unassigned' }}</div>
                    <div v-if="l.current_bookings?.length" class="text-xs text-[#EF4444] mt-1 truncate">
                        🔒 {{ l.current_bookings[0].customer?.full_name }} · do {{ formatTime(l.current_bookings[0].check_out) }}
                    </div>
                    <div v-else-if="l.upcoming_bookings?.length" class="text-xs text-[#F59E0B] mt-1 truncate">
                        ⏱ {{ l.upcoming_bookings[0].customer?.full_name }} · {{ formatTime(l.upcoming_bookings[0].check_in) }}
                    </div>
                    <div class="flex items-center gap-3 mt-1 text-xs">
                        <span :class="l.is_online ? 'text-[#10B981]' : 'text-[#EF4444]'">{{ l.is_online ? 'Online' : 'Offline' }}</span>
                        <span v-if="l.battery_level !== null" :class="l.battery_level < 20 ? 'text-[#EF4444]' : 'text-[#A0A0A0]'">{{ l.battery_level }}%</span>
                    </div>
                </div>
                <div class="flex flex-row sm:flex-col items-stretch sm:items-end gap-3 sm:gap-1 shrink-0 pt-3 sm:pt-0 border-t sm:border-t-0 border-[#2A2A2A] sm:border-0 -mx-4 sm:mx-0 px-4 sm:px-0">
                    <div class="flex-1 sm:flex-none">
                        <span class="text-[9px] uppercase tracking-wide text-[#6B7280] block mb-1">Site visibility</span>
                        <div @click.stop.prevent class="w-full sm:w-44">
                            <Select :model-value="l.is_published_on_site ? 'visible' : 'hidden'"
                                :options="visibilityOptions"
                                @update:model-value="v => updateVisibility(l, v)" />
                        </div>
                    </div>
                    <div class="flex-1 sm:flex-none">
                        <span class="text-[9px] uppercase tracking-wide text-[#6B7280] block mb-1">Booking</span>
                        <div @click.stop.prevent class="w-full sm:w-44">
                            <Select :model-value="l.is_active ? 'active' : 'inactive'"
                                :options="activeOptions"
                                @update:model-value="v => updateActive(l, v)" />
                        </div>
                    </div>
                    <div class="sm:mt-1">
                        <span class="text-[9px] uppercase tracking-wide text-[#6B7280] block mb-1">TTLock</span>
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

const activeOptions = [
    { value: 'active', label: 'Bookable' },
    { value: 'inactive', label: 'Deactivated' },
];

const { apiFetch } = useAuth();
const toast = useToast();
const lockers = ref([]);
const unmapped = ref([]); // TTLock locks not yet linked to a DB locker
const syncing = ref(false);
const autoCreating = ref(false);
const syncMsg = ref('');

const unassignedOptions = computed(() => [
    { value: null, label: 'Assign to existing…' },
    ...lockers.value
        .filter(l => !l.ttlock_lock_id)
        .map(l => ({ value: l.id, label: `${l.number} (${l.size}, ${l.location?.name || 'unassigned'})` })),
]);

// Natural sort: "B-01" < "B-02" < "B-10" (not lexicographic)
const naturalCmp = (a, b) => a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });

const sortedLockers = computed(() =>
    [...lockers.value].sort((a, b) => {
        const locCmp = naturalCmp(a.location?.name || 'zzz', b.location?.name || 'zzz');
        if (locCmp !== 0) return locCmp;
        return naturalCmp(a.number, b.number);
    })
);

const formatTime = (d) => d ? new Date(d).toLocaleString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '';

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

const updateActive = async (l, v) => {
    const next = v === 'active';
    try {
        const res = await apiFetch(`/api/admin/lockers/${l.id}`, {
            method: 'PUT',
            body: JSON.stringify({ is_active: next }),
        });
        if (!res.ok) throw new Error();
        l.is_active = next;
        toast.success(next ? 'Locker now bookable' : 'Locker deactivated — no new bookings');
    } catch {
        toast.error('Failed to update bookable status');
    }
};

const load = async () => {
    const res = await apiFetch('/api/admin/lockers');
    lockers.value = await res.json();
};

const syncAll = async (opts = {}) => {
    syncing.value = true; syncMsg.value = '';
    try {
        const url = '/api/admin/lockers/sync-all' + (opts.autoCreate ? '?auto_create=1' : '');
        const res = await apiFetch(url, { method: 'POST' });
        const data = await res.json();
        const parts = [`Synced ${data.updated}/${data.total_api}`];
        if (data.matched_by_alias) parts.push(`${data.matched_by_alias} matched by alias`);
        if (data.created) parts.push(`${data.created} created`);
        syncMsg.value = parts.join(' · ');
        unmapped.value = data.unmapped || [];
        await load();
    } catch (e) { syncMsg.value = 'Error: ' + e.message; }
    finally { syncing.value = false; setTimeout(() => syncMsg.value = '', 7000); }
};

const autoCreate = async () => {
    autoCreating.value = true;
    try { await syncAll({ autoCreate: true }); }
    finally { autoCreating.value = false; }
};

const assignTTLock = async (ttLock, lockerId) => {
    if (!lockerId) return;
    try {
        const res = await apiFetch(`/api/admin/lockers/${lockerId}`, {
            method: 'PUT',
            body: JSON.stringify({ ttlock_lock_id: ttLock.lockId }),
        });
        if (!res.ok) throw new Error();
        toast.success(`Linked TTLock ${ttLock.lockAlias} → locker`);
        unmapped.value = unmapped.value.filter(u => u.lockId !== ttLock.lockId);
        await load();
    } catch { toast.error('Failed to assign'); }
};

const createFromTTLock = async (ttLock) => {
    try {
        const res = await apiFetch('/api/admin/lockers', {
            method: 'POST',
            body: JSON.stringify({
                location_id: lockers.value[0]?.location_id || null,
                number: ttLock.lockAlias || `Lock-${ttLock.lockId}`,
                size: 'standard',
                ttlock_lock_id: ttLock.lockId,
            }),
        });
        if (!res.ok) throw new Error();
        toast.success(`Created locker ${ttLock.lockAlias || ttLock.lockId}`);
        unmapped.value = unmapped.value.filter(u => u.lockId !== ttLock.lockId);
        await load();
    } catch { toast.error('Failed to create — set a location for at least one locker first'); }
};

onMounted(load);
</script>

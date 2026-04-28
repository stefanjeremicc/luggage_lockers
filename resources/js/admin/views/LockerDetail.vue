<template>
    <div>
        <div class="flex items-center gap-3 mb-6">
            <router-link to="/admin/lockers" class="text-[#A0A0A0] hover:text-white text-sm">← Back</router-link>
            <h1 class="text-2xl font-bold">{{ locker?.number || 'Loading...' }}</h1>
            <span v-if="locker" class="text-xs px-2 py-0.5 rounded-full" :class="locker.size === 'large' ? 'bg-fuchsia-500/20 text-fuchsia-400' : 'bg-cyan-500/20 text-cyan-400'">{{ locker.size }}</span>
        </div>

        <ConfirmModal v-model="confirmOpen" :title="confirmCfg.title" :message="confirmCfg.message" :variant="confirmCfg.variant" :confirm-text="confirmCfg.confirmText" @confirm="confirmCfg.onConfirm && confirmCfg.onConfirm()" />

        <div class="border-b border-[#2A2A2A] mb-6 -mx-4 sm:mx-0">
            <div class="flex gap-1 overflow-x-auto scrollbar-thin px-4 sm:px-0" role="tablist">
                <button v-for="t in tabs" :key="t.id" @click="tab = t.id"
                    role="tab" :aria-selected="tab === t.id"
                    class="shrink-0 px-3 sm:px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition whitespace-nowrap focus:outline-none"
                    :class="tab === t.id ? 'border-[#F59E0B] text-[#F59E0B]' : 'border-transparent text-[#A0A0A0] hover:text-white'">
                    {{ t.label }}
                </button>
            </div>
        </div>

        <div v-if="tab === 'overview'">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                    <h3 class="font-semibold mb-3">Status</h3>
                    <dl class="text-sm space-y-2">
                        <div class="flex justify-between"><dt class="text-[#A0A0A0]">Alias</dt><dd>{{ detail?.lockAlias || locker?.number }}</dd></div>
                        <div class="flex justify-between"><dt class="text-[#A0A0A0]">Battery</dt><dd :class="(locker?.battery_level ?? 100) < 20 ? 'text-red-400' : ''">{{ locker?.battery_level ?? '—' }}%</dd></div>
                        <div class="flex justify-between"><dt class="text-[#A0A0A0]">Online</dt><dd :class="locker?.is_online ? 'text-green-400' : 'text-red-400'">{{ locker?.is_online ? 'Yes' : 'No' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-[#A0A0A0]">Status</dt><dd>{{ locker?.status }}</dd></div>
                        <div class="flex justify-between"><dt class="text-[#A0A0A0]">TTLock ID</dt><dd class="font-mono text-xs">{{ locker?.ttlock_lock_id ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-[#A0A0A0]">Last sync</dt><dd class="text-xs">{{ locker?.last_synced_at ? new Date(locker.last_synced_at).toLocaleString() : '—' }}</dd></div>
                    </dl>
                </div>

                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                    <h3 class="font-semibold mb-3">Remote actions</h3>
                    <div class="flex flex-col gap-2">
                        <Btn variant="secondary" size="sm" :disabled="busy" @click="doAction('sync', 'POST')">Refresh status</Btn>
                        <Btn variant="success" size="sm" :disabled="busy || unsupported.unlock"
                            :title="unsupported.unlock ? 'Not supported by this lock' : ''" @click="askRemote('unlock')">Remote unlock</Btn>
                        <Btn variant="primary" size="sm" :disabled="busy || unsupported.lock"
                            :title="unsupported.lock ? 'Not supported by this lock' : ''" @click="askRemote('lock')">Remote lock</Btn>
                    </div>
                    <p v-if="unsupported.unlock && unsupported.lock" class="mt-3 text-xs text-[#A0A0A0]">This lock model doesn't support remote commands. Use passcodes or Bluetooth app.</p>
                </div>
            </div>

            <!-- Reservation timeline for this locker -->
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 mt-4">
                <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                    <h3 class="font-semibold">Reservations</h3>
                    <button @click="loadBookings" class="text-xs text-[#A0A0A0] hover:text-white">Refresh</button>
                </div>
                <div v-if="bookingsLoading" class="text-sm text-[#A0A0A0]">Loading…</div>
                <div v-else-if="!bookings.length" class="text-sm text-[#A0A0A0]">No active or upcoming reservations.</div>
                <div v-else class="divide-y divide-[#2A2A2A]/60 -mx-2 sm:-mx-3">
                    <router-link v-for="b in bookings" :key="b.id" to="/admin/bookings"
                        class="flex items-center gap-3 px-2 sm:px-3 py-3 hover:bg-[#222] transition rounded-lg">
                        <!-- Status dot column -->
                        <span class="shrink-0 w-2.5 h-2.5 rounded-full"
                            :class="b.is_active_now ? 'bg-[#EF4444]' : 'bg-[#F59E0B]'"
                            :title="b.is_active_now ? 'In use now' : 'Upcoming'"></span>

                        <!-- Main info: name + dates -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-white truncate">{{ b.customer?.full_name || '—' }}</span>
                                <span class="text-[10px] text-[#6B7280] font-mono shrink-0">#{{ b.id }}</span>
                            </div>
                            <div class="text-xs text-[#A0A0A0] mt-0.5 font-mono">
                                {{ formatDateTime(b.check_in) }}
                                <span class="text-[#6B7280] mx-1">→</span>
                                {{ formatDateTime(b.check_out) }}
                            </div>
                        </div>

                        <!-- Chevron -->
                        <svg class="w-4 h-4 text-[#6B7280] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
                    </router-link>
                </div>
            </div>
        </div>

        <div v-if="tab === 'passcodes'">
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 mb-4">
                <h3 class="font-semibold mb-3">Create passcode</h3>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-2">
                    <select v-model.number="newPwd.type" class="bg-[#111] border border-[#2A2A2A] rounded px-3 py-2 text-sm">
                        <option :value="3">Timed (period)</option>
                        <option :value="2">Permanent</option>
                        <option :value="1">One-time</option>
                    </select>
                    <input v-model="newPwd.code" placeholder="Code (4-9 digits)" class="bg-[#111] border border-[#2A2A2A] rounded px-3 py-2 text-sm" />
                    <input v-model="newPwd.name" placeholder="Name (optional)" class="bg-[#111] border border-[#2A2A2A] rounded px-3 py-2 text-sm" />
                    <DateTimePicker v-if="newPwd.type !== 2" v-model="newPwd.start" :min="new Date().toISOString()" />
                    <DateTimePicker v-if="newPwd.type !== 2" v-model="newPwd.end" :min="newPwd.start || new Date().toISOString()" />
                </div>
                <div class="flex gap-2 mt-3">
                    <Btn variant="secondary" size="sm" @click="genCode">Generate random</Btn>
                    <Btn variant="primary" size="sm" :disabled="busy" @click="createPasscode">Create</Btn>
                </div>
            </div>

            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold">Active passcodes ({{ passcodes.length }})</h3>
                    <button @click="loadPasscodes" class="text-xs text-[#A0A0A0] hover:text-white">Refresh</button>
                </div>
                <div v-if="passcodes.length === 0" class="text-sm text-[#A0A0A0]">No passcodes.</div>
                <template v-else>
                    <!-- Mobile: cards -->
                    <div class="md:hidden space-y-2">
                        <div v-for="p in passcodes" :key="p.keyboardPwdId" class="bg-[#111] border border-[#2A2A2A] rounded-lg p-3">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div>
                                    <div class="font-mono font-bold text-base text-[#F59E0B]">{{ p.keyboardPwd }}</div>
                                    <div class="text-xs text-[#A0A0A0] mt-0.5">{{ p.keyboardPwdName || '—' }}</div>
                                </div>
                                <span class="text-[10px] uppercase tracking-wide px-2 py-0.5 rounded-full bg-[#2A2A2A] text-[#A0A0A0]">{{ pwdType(p.keyboardPwdType) }}</span>
                            </div>
                            <div class="text-xs text-[#A0A0A0] mb-2">{{ pwdRange(p) }}</div>
                            <button @click="deletePasscode(p)" class="text-red-400 hover:text-red-300 text-xs underline">Delete</button>
                        </div>
                    </div>
                    <!-- Desktop: table -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-xs text-[#A0A0A0] border-b border-[#2A2A2A]">
                                <tr><th class="text-left py-2">Code</th><th class="text-left">Name</th><th class="text-left">Type</th><th class="text-left">Valid</th><th></th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in passcodes" :key="p.keyboardPwdId" class="border-b border-[#2A2A2A]/40">
                                    <td class="py-2 font-mono">{{ p.keyboardPwd }}</td>
                                    <td>{{ p.keyboardPwdName || '—' }}</td>
                                    <td class="text-xs">{{ pwdType(p.keyboardPwdType) }}</td>
                                    <td class="text-xs">{{ pwdRange(p) }}</td>
                                    <td class="text-right"><button @click="deletePasscode(p)" class="text-red-400 hover:text-red-300 text-xs">Delete</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        </div>

        <div v-if="tab === 'history'">
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold">Unlock history (last 7 days)</h3>
                    <button @click="loadHistory" class="text-xs text-[#A0A0A0] hover:text-white">Refresh</button>
                </div>
                <div v-if="history.length === 0" class="text-sm text-[#A0A0A0]">No records.</div>
                <template v-else>
                    <!-- Mobile: cards -->
                    <div class="md:hidden space-y-2">
                        <div v-for="r in history" :key="r.lockRecordId" class="bg-[#111] border border-[#2A2A2A] rounded-lg p-3 flex items-center justify-between">
                            <div class="min-w-0">
                                <div class="text-sm">{{ recordType(r.recordType) }}</div>
                                <div class="text-xs text-[#A0A0A0] truncate">{{ r.username || r.keyboardPwd || '—' }}</div>
                                <div class="text-[10px] text-[#6B7280] mt-0.5">{{ new Date(r.lockDate).toLocaleString() }}</div>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full shrink-0" :class="r.success === 1 ? 'bg-green-500/15 text-green-400' : 'bg-red-500/15 text-red-400'">{{ r.success === 1 ? 'OK' : 'Fail' }}</span>
                        </div>
                    </div>
                    <!-- Desktop: table -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-xs text-[#A0A0A0] border-b border-[#2A2A2A]">
                                <tr><th class="text-left py-2">Time</th><th class="text-left">Type</th><th class="text-left">User / Code</th><th class="text-left">Success</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="r in history" :key="r.lockRecordId" class="border-b border-[#2A2A2A]/40">
                                    <td class="py-2 text-xs">{{ new Date(r.lockDate).toLocaleString() }}</td>
                                    <td class="text-xs">{{ recordType(r.recordType) }}</td>
                                    <td class="text-xs">{{ r.username || r.keyboardPwd || '—' }}</td>
                                    <td class="text-xs" :class="r.success === 1 ? 'text-green-400' : 'text-red-400'">{{ r.success === 1 ? 'OK' : 'Fail' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        </div>

        <div v-if="tab === 'settings'">
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 max-w-xl">
                <h3 class="font-semibold mb-3">Rename</h3>
                <p class="text-xs text-[#A0A0A0] mb-2">Changes alias on TTLock side and in local DB.</p>
                <div class="flex gap-2">
                    <input v-model="newAlias" class="flex-1 bg-[#111] border border-[#2A2A2A] rounded px-3 py-2 text-sm" />
                    <Btn variant="primary" size="sm" :disabled="busy" @click="rename">Save</Btn>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useAuth } from '../composables/useAuth';
import { useToast } from '../composables/useToast';
import DateTimePicker from '../components/DateTimePicker.vue';
import ConfirmModal from '../components/ConfirmModal.vue';
import Btn from '../components/Btn.vue';

const toast = useToast();

const confirmOpen = ref(false);
const confirmCfg = reactive({ title: '', message: '', variant: 'default', confirmText: 'Confirm', onConfirm: null });
const unsupported = reactive({ unlock: false, lock: false });

const ask = (cfg) => {
    Object.assign(confirmCfg, { variant: 'default', confirmText: 'Confirm', ...cfg });
    confirmOpen.value = true;
};

const friendlyError = (msg) => {
    if (!msg) return 'Unknown error';
    const m = String(msg).match(/\[(-?\d+)\]/);
    const map = {
        '-4043': "This lock doesn't support remote commands. Use a passcode or the Bluetooth app.",
        '-3004': 'Gateway is offline. The lock cannot be reached right now.',
        '-3003': 'Lock is offline.',
        '-1002': 'Authentication failed — check TTLock credentials.',
        '-2012': 'Passcode already exists on this lock.',
        '-2018': 'Passcode must be 4 to 9 digits.',
    };
    return (m && map[m[1]]) || msg;
};

const route = useRoute();
const { apiFetch } = useAuth();

const id = route.params.id;
const locker = ref(null);
const detail = ref(null);
const busy = ref(false);
const tab = ref('overview');
const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'passcodes', label: 'Passcodes' },
    { id: 'history', label: 'History' },
    { id: 'settings', label: 'Settings' },
];

const passcodes = ref([]);
const history = ref([]);
const bookings = ref([]);
const bookingsLoading = ref(false);
const newAlias = ref('');
const newPwd = ref({ type: 3, code: '', name: '', start: '', end: '' });

const loadBookings = async () => {
    bookingsLoading.value = true;
    try {
        const res = await apiFetch(`/api/admin/lockers/${id}/bookings`);
        bookings.value = await res.json();
    } catch (e) { toast.error(friendlyError(e.message)); }
    finally { bookingsLoading.value = false; }
};

const formatDateTime = (s) => {
    if (!s) return '';
    return new Date(s).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const load = async () => {
    try {
        const res = await apiFetch(`/api/admin/lockers/${id}`);
        const data = await res.json();
        locker.value = data.locker;
        detail.value = data.ttlock_detail;
        newAlias.value = data.locker?.number || '';
    } catch (e) { toast.error(friendlyError(e.message)); }
};

const loadPasscodes = async () => {
    try {
        const res = await apiFetch(`/api/admin/lockers/${id}/passcodes`);
        const data = await res.json();
        passcodes.value = data.list || [];
    } catch (e) { toast.error(friendlyError(e.message)); }
};

const loadHistory = async () => {
    try {
        const res = await apiFetch(`/api/admin/lockers/${id}/unlock-records`);
        const data = await res.json();
        history.value = data.list || [];
    } catch (e) { toast.error(friendlyError(e.message)); }
};

const doAction = async (endpoint, method = 'POST') => {
    busy.value = true;
    try {
        const res = await apiFetch(`/api/admin/lockers/${id}/${endpoint}`, { method });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Failed');
        toast.success(data.message || 'Done');
        if (endpoint === 'sync') await load();
    } catch (e) {
        const msg = friendlyError(e.message);
        toast.error(msg);
        if (e.message?.includes('-4043')) {
            if (endpoint === 'remote-unlock') unsupported.unlock = true;
            if (endpoint === 'remote-lock') unsupported.lock = true;
        }
    } finally { busy.value = false; }
};

const askRemote = (kind) => {
    ask({
        title: kind === 'unlock' ? 'Remote unlock?' : 'Remote lock?',
        message: kind === 'unlock'
            ? `This will send an unlock command to ${locker.value?.number}. Anyone nearby could open it.`
            : `This will send a lock command to ${locker.value?.number}.`,
        variant: kind === 'unlock' ? 'warning' : 'default',
        confirmText: kind === 'unlock' ? 'Unlock' : 'Lock',
        onConfirm: () => doAction(kind === 'unlock' ? 'remote-unlock' : 'remote-lock', 'POST'),
    });
};

const toLocalInput = (d) => {
    const pad = n => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const genCode = () => {
    newPwd.value.code = String(Math.floor(100000 + Math.random() * 900000));
    if (!newPwd.value.name) {
        const t = new Date();
        newPwd.value.name = `admin-${t.getFullYear()}${String(t.getMonth()+1).padStart(2,'0')}${String(t.getDate()).padStart(2,'0')}-${Math.floor(Math.random()*1000).toString().padStart(3,'0')}`;
    }
    if (newPwd.value.type !== 2 && !newPwd.value.start) {
        newPwd.value.start = toLocalInput(new Date());
    }
    if (newPwd.value.type !== 2 && !newPwd.value.end) {
        const defaultHours = newPwd.value.type === 1 ? 6 : 24;
        newPwd.value.end = toLocalInput(new Date(Date.now() + defaultHours * 3600 * 1000));
    }
};

const createPasscode = async () => {
    if (!newPwd.value.code) { toast.error('Code is required'); return; }
    if (!/^\d{4,9}$/.test(newPwd.value.code)) { toast.error('Code must be 4 to 9 digits'); return; }
    if (newPwd.value.type !== 2) {
        if (!newPwd.value.start || !newPwd.value.end) { toast.error('Start and end time are required'); return; }
        const s = new Date(newPwd.value.start).getTime();
        const e = new Date(newPwd.value.end).getTime();
        if (!(s < e)) { toast.error('End time must be after start time'); return; }
        if (e < Date.now()) { toast.error('End time cannot be in the past'); return; }
    }
    busy.value = true;
    try {
        const body = { code: newPwd.value.code, type: newPwd.value.type, name: newPwd.value.name || null };
        if (newPwd.value.type !== 2) { body.start = newPwd.value.start; body.end = newPwd.value.end; }
        const res = await apiFetch(`/api/admin/lockers/${id}/passcodes`, { method: 'POST', body: JSON.stringify(body) });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Create failed');
        toast.success('Passcode created');
        newPwd.value.code = ''; newPwd.value.name = ''; newPwd.value.start = ''; newPwd.value.end = '';
        await loadPasscodes();
    } catch (e) { toast.error(friendlyError(e.message)); }
    finally { busy.value = false; }
};

const deletePasscode = (p) => {
    ask({
        title: 'Delete passcode?',
        message: `Passcode ${p.keyboardPwd}${p.keyboardPwdName ? ' ("' + p.keyboardPwdName + '")' : ''} will be permanently removed from the lock.`,
        variant: 'danger',
        confirmText: 'Delete',
        onConfirm: async () => {
            busy.value = true;
            try {
                const res = await apiFetch(`/api/admin/lockers/${id}/passcodes/${p.keyboardPwdId}`, { method: 'DELETE' });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Delete failed');
                toast.success('Passcode deleted');
                await loadPasscodes();
            } catch (e) { toast.error(friendlyError(e.message)); }
            finally { busy.value = false; }
        },
    });
};

const rename = () => {
    if (!newAlias.value.trim()) return;
    ask({
        title: 'Rename locker?',
        message: `Rename from "${locker.value?.number}" to "${newAlias.value.trim()}". This changes the alias on TTLock side.`,
        variant: 'warning',
        confirmText: 'Rename',
        onConfirm: async () => {
            busy.value = true;
            try {
                const res = await apiFetch(`/api/admin/lockers/${id}/rename`, { method: 'POST', body: JSON.stringify({ alias: newAlias.value }) });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Rename failed');
                await load();
                toast.success('Locker renamed');
            } catch (e) { toast.error(friendlyError(e.message)); }
            finally { busy.value = false; }
        },
    });
};

const pwdType = (t) => ({ 1: 'One-time', 2: 'Permanent', 3: 'Timed', 4: 'Cyclic' }[t] || t);
const pwdRange = (p) => {
    if (p.keyboardPwdType === 2) return 'Permanent';
    if (!p.startDate && !p.endDate) return '—';
    const s = p.startDate ? new Date(p.startDate).toLocaleString() : '';
    const e = p.endDate ? new Date(p.endDate).toLocaleString() : '';
    return `${s} → ${e}`;
};
const recordType = (t) => ({ 1: 'App', 4: 'Passcode', 7: 'Card', 8: 'Fingerprint', 11: 'Remote' }[t] || `type=${t}`);

watch(tab, (t) => {
    if (t === 'passcodes' && passcodes.value.length === 0) loadPasscodes();
    if (t === 'history' && history.value.length === 0) loadHistory();
});

onMounted(async () => {
    await load();
    await loadBookings();
});
</script>

<style scoped>
.scrollbar-thin::-webkit-scrollbar { height: 4px; }
.scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
.scrollbar-thin::-webkit-scrollbar-thumb { background: #2A2A2A; border-radius: 2px; }
.scrollbar-thin { scrollbar-width: thin; scrollbar-color: #2A2A2A transparent; }
</style>

<template>
    <div>
        <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
            <h1 class="text-2xl font-bold">Users</h1>
            <Btn @click="openNew" size="md">+ New user</Btn>
        </div>

        <div v-if="loading" class="text-sm text-[#A0A0A0]">Loading…</div>

        <div v-else-if="!users.length" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-8 text-center text-[#A0A0A0]">
            No users yet.
        </div>

        <div v-else class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-[#2A2A2A]">
                    <tr class="text-[#A0A0A0] text-left">
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Role</th>
                        <th class="px-4 py-3 font-medium">Locations</th>
                        <th class="px-4 py-3 font-medium">Last login</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="u in users" :key="u.id" class="border-b border-[#2A2A2A]/50 hover:bg-[#111]">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ u.name }}</div>
                            <div v-if="u.username" class="text-xs text-[#6B7280] font-mono">@{{ u.username }}</div>
                        </td>
                        <td class="px-4 py-3 text-[#A0A0A0]">{{ u.email }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="u.role === 'super_admin' ? 'bg-[#F59E0B]/20 text-[#F59E0B]' : 'bg-blue-500/20 text-blue-400'">
                                {{ u.role === 'super_admin' ? 'Super admin' : 'Admin' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-[#A0A0A0]">{{ locationLabel(u) }}</td>
                        <td class="px-4 py-3 text-xs text-[#A0A0A0]">{{ formatDate(u.last_login_at) || '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 text-xs"
                                :class="u.is_active ? 'text-[#10B981]' : 'text-[#6B7280]'">
                                <span class="w-1.5 h-1.5 rounded-full" :class="u.is_active ? 'bg-[#10B981]' : 'bg-[#6B7280]'"></span>
                                {{ u.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <Btn variant="ghost" size="sm" @click="openEdit(u)">Edit</Btn>
                            <Btn variant="ghost" size="sm" @click="openReset(u)">Reset password</Btn>
                            <Btn v-if="u.is_active && u.id !== currentUserId" variant="ghost" size="sm" @click="deactivate(u)">Deactivate</Btn>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Edit / new modal -->
        <Modal v-model="formOpen" :title="form.id ? 'Edit user' : 'New user'" size="md">
            <form @submit.prevent="save" class="space-y-4">
                <FormField label="Name" required>
                    <input v-model="form.name" required
                        class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-white focus:border-[#F59E0B] focus:outline-none">
                </FormField>
                <FormField label="Email" required>
                    <input v-model="form.email" type="email" required
                        class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-white focus:border-[#F59E0B] focus:outline-none">
                </FormField>
                <FormField label="Username" hint="Optional. Used for login if email isn't.">
                    <input v-model="form.username"
                        class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-white focus:border-[#F59E0B] focus:outline-none">
                </FormField>
                <FormField v-if="!form.id" label="Password" required>
                    <input v-model="form.password" type="password" required minlength="8"
                        class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-white focus:border-[#F59E0B] focus:outline-none">
                </FormField>
                <FormField label="Role" required>
                    <select v-model="form.role" :disabled="form.id === currentUserId"
                        class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-white focus:border-[#F59E0B] focus:outline-none disabled:opacity-50">
                        <option value="admin">Admin</option>
                        <option value="super_admin">Super admin</option>
                    </select>
                    <p v-if="form.id === currentUserId" class="text-[10px] text-[#6B7280] mt-1">You cannot change your own role.</p>
                </FormField>
                <FormField v-if="form.role === 'admin'" label="Locations" hint="Restrict admin access to selected locations. Leave empty for all.">
                    <div class="space-y-1.5 max-h-48 overflow-y-auto bg-[#111] border border-[#2A2A2A] rounded-lg p-3">
                        <label v-for="l in locations" :key="l.id" class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" :value="l.id" v-model="form.location_ids" class="accent-[#F59E0B]">
                            {{ l.name }}
                        </label>
                        <p v-if="!locations.length" class="text-xs text-[#6B7280]">No locations yet.</p>
                    </div>
                </FormField>
                <FormField v-if="form.id" label="Active">
                    <Checkbox v-model="form.is_active" :disabled="form.id === currentUserId"
                        :label="form.is_active ? 'Account is active' : 'Account is deactivated'" />
                </FormField>
            </form>
            <template #footer>
                <div class="flex gap-2 justify-end">
                    <Btn variant="secondary" size="sm" @click="formOpen = false">Cancel</Btn>
                    <Btn variant="primary" size="sm" :loading="saving" @click="save">Save</Btn>
                </div>
            </template>
        </Modal>

        <!-- Reset password modal -->
        <Modal v-model="resetOpen" :title="'Reset password'" :subtitle="resetUser?.name" size="sm">
            <FormField label="New password" required hint="Min 8 characters. Communicate the new password to the user out-of-band.">
                <input v-model="resetPassword" type="text" required minlength="8"
                    class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-white font-mono focus:border-[#F59E0B] focus:outline-none">
            </FormField>
            <Btn variant="ghost" size="sm" @click="resetPassword = randomPassword()">Generate random</Btn>
            <template #footer>
                <div class="flex gap-2 justify-end">
                    <Btn variant="secondary" size="sm" @click="resetOpen = false">Cancel</Btn>
                    <Btn variant="primary" size="sm" :loading="resetting" @click="confirmReset">Set password</Btn>
                </div>
            </template>
        </Modal>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';
import { useToast } from '../composables/useToast';
import { useConfirm } from '../composables/useConfirm';
import Modal from '../components/Modal.vue';
import Btn from '../components/Btn.vue';
import FormField from '../components/FormField.vue';
import Checkbox from '../components/Checkbox.vue';

const { apiFetch, user: authUser } = useAuth();
const toast = useToast();
const confirmDialog = useConfirm();

const users = ref([]);
const locations = ref([]);
const loading = ref(true);
const saving = ref(false);
const resetting = ref(false);

const currentUserId = computed(() => authUser.value?.id);

const formOpen = ref(false);
const form = reactive(emptyForm());

const resetOpen = ref(false);
const resetUser = ref(null);
const resetPassword = ref('');

function emptyForm() {
    return { id: null, name: '', email: '', username: '', password: '', role: 'admin', location_ids: [], is_active: true };
}

const fetchUsers = async () => {
    loading.value = true;
    try {
        const res = await apiFetch('/api/admin/users');
        users.value = await res.json();
    } catch { toast.error('Failed to load users'); }
    finally { loading.value = false; }
};

const fetchLocations = async () => {
    try {
        const res = await apiFetch('/api/admin/locations');
        const data = await res.json();
        locations.value = (Array.isArray(data) ? data : data.data || []).map(l => ({ id: l.id, name: l.name }));
    } catch { /* non-fatal */ }
};

const locationLabel = (u) => {
    if (u.role === 'super_admin') return 'All';
    if (!u.location_ids?.length) return 'All';
    const names = u.location_ids
        .map(id => locations.value.find(l => l.id === id)?.name)
        .filter(Boolean);
    return names.length ? names.join(', ') : `${u.location_ids.length} selected`;
};

const openNew = () => { Object.assign(form, emptyForm()); formOpen.value = true; };

const openEdit = (u) => {
    Object.assign(form, {
        id: u.id, name: u.name, email: u.email, username: u.username || '',
        password: '', role: u.role, location_ids: u.location_ids || [], is_active: !!u.is_active,
    });
    formOpen.value = true;
};

const save = async () => {
    saving.value = true;
    try {
        const payload = {
            name: form.name,
            email: form.email,
            username: form.username || null,
            role: form.role,
            location_ids: form.role === 'super_admin' ? [] : form.location_ids,
            is_active: form.is_active,
        };
        if (!form.id) payload.password = form.password;

        const url = form.id ? `/api/admin/users/${form.id}` : '/api/admin/users';
        const method = form.id ? 'PUT' : 'POST';
        const res = await apiFetch(url, { method, body: JSON.stringify(payload) });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Save failed');
        toast.success(form.id ? 'User updated' : 'User created');
        formOpen.value = false;
        await fetchUsers();
    } catch (e) { toast.error(e.message); }
    finally { saving.value = false; }
};

const openReset = (u) => {
    resetUser.value = u;
    resetPassword.value = randomPassword();
    resetOpen.value = true;
};

const randomPassword = () => {
    const chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    let out = '';
    for (let i = 0; i < 12; i++) out += chars[Math.floor(Math.random() * chars.length)];
    return out;
};

const confirmReset = async () => {
    if (resetPassword.value.length < 8) { toast.error('Password must be at least 8 characters'); return; }
    resetting.value = true;
    try {
        const res = await apiFetch(`/api/admin/users/${resetUser.value.id}/reset-password`, {
            method: 'POST',
            body: JSON.stringify({ password: resetPassword.value }),
        });
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            throw new Error(data.message || 'Reset failed');
        }
        toast.success('Password updated. Share it with the user securely.');
        resetOpen.value = false;
    } catch (e) { toast.error(e.message); }
    finally { resetting.value = false; }
};

const deactivate = async (u) => {
    const ok = await confirmDialog.ask({
        title: 'Deactivate user?',
        message: `${u.name} will no longer be able to log in. You can reactivate them later.`,
        variant: 'warning',
        confirmText: 'Deactivate',
    });
    if (!ok) return;
    try {
        const res = await apiFetch(`/api/admin/users/${u.id}`, { method: 'DELETE' });
        if (!res.ok) throw new Error();
        toast.success('User deactivated');
        await fetchUsers();
    } catch { toast.error('Failed to deactivate'); }
};

const formatDate = (d) => {
    if (!d) return '';
    const dt = new Date(d);
    if (Number.isNaN(dt.getTime())) return '';
    const pad = n => String(n).padStart(2, '0');
    return `${pad(dt.getDate())}.${pad(dt.getMonth() + 1)}.${dt.getFullYear()} ${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
};

onMounted(async () => {
    await Promise.all([fetchUsers(), fetchLocations()]);
});
</script>

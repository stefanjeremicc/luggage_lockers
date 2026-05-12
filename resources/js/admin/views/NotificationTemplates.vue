<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold">Notification Templates</h1>
                <p class="text-sm text-[#A0A0A0] mt-1">Email message templates. Use <code v-pre class="text-[#F59E0B]">{{ variable }}</code> placeholders.</p>
            </div>
            <Btn variant="primary" @click="openNew">
                <template #icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                </template>
                New Template
            </Btn>
        </div>

        <div v-if="loading" class="text-sm text-[#A0A0A0]">Loading…</div>

        <div v-else-if="!templates.length" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-8 text-center text-[#A0A0A0]">
            No templates yet. Create one to start customizing notification content.
        </div>

        <div v-else class="space-y-3">
            <div v-for="group in groupedTemplates" :key="group.key"
                class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4 hover:border-[#3A3A3A] transition">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <div class="font-mono font-medium text-white">{{ group.key }}</div>
                        <div v-if="group.subject" class="text-xs text-[#A0A0A0] mt-1 truncate">{{ group.subject }}</div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button v-for="loc in localeOptions" :key="loc.value"
                            @click="openLocale(group, loc.value)"
                            :title="group.variants[loc.value] ? `Edit ${loc.label}` : `Add ${loc.label} variant`"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold uppercase transition flex items-center gap-1.5"
                            :class="group.variants[loc.value]
                                ? (group.variants[loc.value].is_active
                                    ? 'bg-[#F59E0B]/10 text-[#F59E0B] border border-[#F59E0B]/30 hover:bg-[#F59E0B]/20'
                                    : 'bg-[#2A2A2A] text-[#A0A0A0] border border-[#3A3A3A] hover:text-white')
                                : 'bg-transparent text-[#666] border border-dashed border-[#3A3A3A] hover:text-[#A0A0A0] hover:border-[#555]'">
                            <span>{{ loc.value }}</span>
                            <span v-if="group.variants[loc.value] && !group.variants[loc.value].is_active" class="text-[10px] opacity-70">(inactive)</span>
                            <span v-else-if="!group.variants[loc.value]" class="text-[10px] opacity-70">+</span>
                        </button>
                        <IconBtn variant="danger" @click="removeGroup(group)" title="Delete all locales for this key">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                        </IconBtn>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="editing" class="fixed inset-0 bg-black/70 backdrop-blur flex items-center justify-center p-4 z-50"
            @click.self="editing = null">
            <div class="bg-[#0F0F0F] border border-[#2A2A2A] rounded-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                <div class="p-5 border-b border-[#2A2A2A] flex items-center justify-between sticky top-0 bg-[#0F0F0F]">
                    <h2 class="text-lg font-bold">{{ editing.id ? 'Edit Template' : 'New Template' }}</h2>
                    <button @click="editing = null" class="text-[#A0A0A0] hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="save" class="p-5 space-y-4">
                    <Field label="Key" required hint="Identifier referenced in code (e.g. booking.confirmed)">
                        <input v-model="editing.key" required
                            class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white font-mono focus:border-[#F59E0B] focus:outline-none">
                    </Field>
                    <div class="grid grid-cols-2 gap-4">
                        <Field label="Channel" required>
                            <Select v-model="editing.channel" :options="channelOptions" />
                        </Field>
                        <Field label="Language" required>
                            <Select v-model="editing.locale" :options="localeOptions" />
                        </Field>
                    </div>
                    <Field v-if="editing.channel === 'email'" label="Subject">
                        <input v-model="editing.subject"
                            class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none">
                    </Field>
                    <Field label="Body" required :hint="bodyHint">
                        <RichEditor v-if="editing.channel === 'email'" v-model="editing.body" min-height="240px" />
                        <textarea v-else v-model="editing.body" rows="8" required
                            class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white font-mono text-sm focus:border-[#F59E0B] focus:outline-none"></textarea>
                    </Field>
                    <Checkbox v-model="editing.is_active" label="Active" />
                    <div class="flex justify-end gap-2 pt-2 border-t border-[#2A2A2A]">
                        <button type="button" @click="editing = null"
                            class="px-4 py-2 rounded-lg text-sm text-[#A0A0A0] hover:bg-[#1A1A1A]">Cancel</button>
                        <button type="submit" :disabled="saving"
                            class="bg-[#F59E0B] text-black px-6 py-2 rounded-lg text-sm font-semibold hover:bg-[#D97706] disabled:opacity-60">
                            {{ saving ? 'Saving…' : 'Save' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';
import { useToast } from '../composables/useToast';
import { useConfirm } from '../composables/useConfirm';
import RichEditor from '../components/RichEditor.vue';
import Field from '../components/FormField.vue';
import Btn from '../components/Btn.vue';
import IconBtn from '../components/IconBtn.vue';
import Select from '../components/Select.vue';
import Checkbox from '../components/Checkbox.vue';

const confirmDialog = useConfirm();

const { apiFetch } = useAuth();
const toast = useToast();

const templates = ref([]);
const loading = ref(true);
const editing = ref(null);
const saving = ref(false);

const bodyHint = 'Use {{ variable }} for dynamic content (e.g. customer name, locker number).';
// WhatsApp + SMS channels hidden until those providers are wired up — only
// email currently delivers. Keep the strings in code so we can re-enable
// in one line when WhatsApp Business / SMS gateway gets integrated.
const channelOptions = [
    { value: 'email', label: 'Email' },
    // { value: 'whatsapp', label: 'WhatsApp' },
    // { value: 'sms', label: 'SMS' },
];
const localeOptions = [
    { value: 'en', label: 'English' },
    { value: 'sr', label: 'Serbian' },
];

const blank = () => ({
    id: null, key: '', channel: 'email', locale: 'en',
    subject: '', body: '', variables: null, is_active: true,
});

const openNew = () => (editing.value = blank());
const openEdit = (t) => (editing.value = { ...t });

// Group templates by key, keeping only email channel (WhatsApp/SMS UI hidden globally).
// Each group exposes { key, subject, variants: { en, sr } } so the list can render
// one card per template key with locale badges instead of a 4x duplicated flat list.
const groupedTemplates = computed(() => {
    const groups = new Map();
    for (const t of templates.value) {
        if (t.channel !== 'email') continue;
        if (!groups.has(t.key)) {
            groups.set(t.key, { key: t.key, subject: '', variants: {} });
        }
        const g = groups.get(t.key);
        g.variants[t.locale] = t;
        // Prefer English subject for the card preview; fall back to whatever is first.
        if (!g.subject || t.locale === 'en') g.subject = t.subject || g.subject;
    }
    return Array.from(groups.values()).sort((a, b) => a.key.localeCompare(b.key));
});

const openLocale = (group, locale) => {
    const existing = group.variants[locale];
    if (existing) {
        openEdit(existing);
    } else {
        // Pre-fill a new variant for this key + locale so the user can fill subject/body.
        editing.value = { ...blank(), key: group.key, locale };
    }
};

const removeGroup = async (group) => {
    const variants = Object.values(group.variants);
    const ok = await confirmDialog.ask({
        title: 'Delete template?',
        message: `"${group.key}" will be permanently removed (${variants.length} locale${variants.length === 1 ? '' : 's'}).`,
        variant: 'danger',
    });
    if (!ok) return;
    await Promise.all(variants.map(v =>
        apiFetch(`/api/admin/notification-templates/${v.id}`, { method: 'DELETE' })
    ));
    templates.value = templates.value.filter(x => x.key !== group.key);
    toast.success('Deleted');
};

const fetch = async () => {
    loading.value = true;
    try {
        const res = await apiFetch('/api/admin/notification-templates');
        templates.value = await res.json();
    } finally {
        loading.value = false;
    }
};

const save = async () => {
    saving.value = true;
    try {
        const payload = { ...editing.value };
        const url = payload.id
            ? `/api/admin/notification-templates/${payload.id}`
            : '/api/admin/notification-templates';
        const res = await apiFetch(url, {
            method: payload.id ? 'PUT' : 'POST',
            body: JSON.stringify(payload),
        });
        if (!res.ok) {
            const err = await res.json();
            throw new Error(err.message || 'Save failed');
        }
        toast.success('Template saved');
        editing.value = null;
        await fetch();
    } catch (e) {
        toast.error(e.message);
    } finally {
        saving.value = false;
    }
};

const remove = async (t) => {
    const ok = await confirmDialog.ask({
        title: 'Delete template?',
        message: `"${t.key}" (${t.channel}/${t.locale}) will be permanently removed.`,
        variant: 'danger',
    });
    if (!ok) return;
    await apiFetch(`/api/admin/notification-templates/${t.id}`, { method: 'DELETE' });
    templates.value = templates.value.filter(x => x.id !== t.id);
    toast.success('Deleted');
};

onMounted(fetch);
</script>

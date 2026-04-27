<template>
    <div class="max-w-3xl">
        <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
            <h1 class="text-2xl font-bold">Settings</h1>
            <button @click="save" :disabled="saving || !isValid"
                class="bg-[#F59E0B] text-black px-6 py-2.5 rounded-lg font-semibold text-sm hover:bg-[#D97706] disabled:opacity-50 disabled:cursor-not-allowed">
                {{ saving ? 'Saving…' : 'Save changes' }}
            </button>
        </div>

        <div v-if="loading" class="text-sm text-[#A0A0A0]">Loading…</div>

        <template v-else>
            <section v-for="group in visibleGroups" :key="group.key" class="mb-6">
                <h2 class="text-sm font-semibold mb-3 text-[#F59E0B] uppercase tracking-wide">{{ group.label }}</h2>
                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl divide-y divide-[#2A2A2A]/60">
                    <div v-for="field in group.fields" :key="field.key" class="p-4 sm:p-5">
                        <label class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 md:gap-4">
                            <div class="md:flex-1 md:max-w-[40%]">
                                <div class="text-sm text-white font-medium">{{ field.label }}</div>
                                <p v-if="field.hint" class="text-xs text-[#6B7280] mt-0.5">{{ field.hint }}</p>
                            </div>
                            <div class="md:w-[60%] md:max-w-md">
                                <!-- Phone -->
                                <PhoneInput v-if="field.type === 'phone'"
                                    v-model="values[field.key]"
                                    @valid="v => validity[field.key] = v"
                                    :required="field.required" />

                                <!-- Select (custom) -->
                                <Select v-else-if="field.type === 'select'"
                                    v-model="values[field.key]"
                                    :options="field.options" />

                                <!-- Number -->
                                <input v-else-if="field.type === 'number'"
                                    v-model="values[field.key]"
                                    type="number"
                                    :min="field.min" :max="field.max" :step="field.step || 1"
                                    class="w-full bg-[#111] border rounded-lg px-3 py-2.5 text-white focus:outline-none"
                                    :class="errors[field.key] ? 'border-[#EF4444]' : 'border-[#2A2A2A] focus:border-[#F59E0B]'"
                                    @input="validateField(field)"
                                    @blur="validateField(field)">

                                <!-- Textarea -->
                                <textarea v-else-if="field.type === 'textarea'"
                                    v-model="values[field.key]"
                                    :rows="field.rows || 3"
                                    :maxlength="field.max || undefined"
                                    class="w-full bg-[#111] border rounded-lg px-3 py-2.5 text-white focus:outline-none resize-y"
                                    :class="errors[field.key] ? 'border-[#EF4444]' : 'border-[#2A2A2A] focus:border-[#F59E0B]'"
                                    @input="validateField(field)"
                                    @blur="validateField(field)"></textarea>

                                <!-- Text/Email/URL -->
                                <input v-else
                                    v-model="values[field.key]"
                                    :type="field.type === 'email' ? 'email' : (field.type === 'url' ? 'url' : 'text')"
                                    :inputmode="field.type === 'email' ? 'email' : (field.type === 'url' ? 'url' : 'text')"
                                    :maxlength="field.max || undefined"
                                    class="w-full bg-[#111] border rounded-lg px-3 py-2.5 text-white focus:outline-none"
                                    :class="errors[field.key] ? 'border-[#EF4444]' : 'border-[#2A2A2A] focus:border-[#F59E0B]'"
                                    @input="validateField(field)"
                                    @blur="validateField(field)">

                                <p v-if="errors[field.key]" class="text-xs text-[#EF4444] mt-1">{{ errors[field.key] }}</p>
                                <p v-else-if="field.type === 'textarea' && field.max" class="text-[11px] text-[#6B7280] mt-1 text-right">
                                    {{ (values[field.key] || '').length }}/{{ field.max }}
                                </p>
                            </div>
                        </label>
                    </div>
                </div>
            </section>

            <div class="flex justify-end sticky bottom-4">
                <button @click="save" :disabled="saving || !isValid"
                    class="bg-[#F59E0B] text-black px-8 py-3 rounded-lg font-semibold shadow-xl hover:bg-[#D97706] disabled:opacity-50">
                    {{ saving ? 'Saving…' : 'Save changes' }}
                </button>
            </div>
        </template>
    </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';
import { useToast } from '../composables/useToast';
import PhoneInput from '../components/PhoneInput.vue';
import Select from '../components/Select.vue';

const { apiFetch } = useAuth();
const toast = useToast();

const values = ref({});
const validity = ref({});
const errors = ref({});
const loading = ref(true);
const saving = ref(false);

/** Declarative field schema — single source of truth for labels, types, and validation. */
const groups = [
    {
        key: 'contact',
        label: 'Contact',
        fields: [
            { key: 'site_name', label: 'Site name', type: 'text', max: 100, required: true },
            { key: 'company_phone', label: 'Phone number', type: 'phone', required: true,
                hint: 'One number used for call, WhatsApp, and display across the site.' },
            { key: 'company_email', label: 'Email', type: 'email', max: 150, required: true },
        ],
    },
    {
        key: 'general',
        label: 'General',
        fields: [
            { key: 'default_locale', label: 'Default language', type: 'select',
                options: [{ value: 'en', label: 'English' }, { value: 'sr', label: 'Serbian (srpski)' }] },
            { key: 'booking_tolerance_minutes', label: 'Booking tolerance (minutes)', type: 'number', min: 0, max: 240,
                hint: 'How long a pending booking stays reserved before expiring.' },
            { key: 'expiry_reminder_minutes', label: 'Expiry reminder (minutes)', type: 'number', min: 0, max: 1440,
                hint: 'Minutes before check-out to send reminder email.' },
            { key: 'eur_rsd_rate', label: 'EUR → RSD exchange rate', type: 'number', min: 1, max: 10000, step: 0.01,
                hint: 'Used to display RSD alongside EUR prices.' },
        ],
    },
    {
        key: 'homepage',
        label: 'Homepage — Google reviews badge',
        fields: [
            { key: 'google_rating', label: 'Rating', type: 'text', max: 3,
                hint: 'Number between 0 and 5 with one decimal (e.g. 4.9).' },
            { key: 'google_review_count', label: 'Review count label', type: 'text', max: 20,
                hint: 'Free-form label shown next to rating, e.g. "70+".' },
            { key: 'google_reviews_url', label: 'Reviews URL', type: 'url', max: 500,
                hint: 'Link to your Google Maps reviews page.' },
        ],
    },
    {
        key: 'seo',
        label: 'SEO — Homepage meta',
        fields: [
            { key: 'home_meta_title', label: 'Meta title', type: 'text', max: 60,
                hint: 'Up to 60 characters for best Google display.' },
            { key: 'home_meta_description', label: 'Meta description', type: 'textarea', max: 150, rows: 3,
                hint: 'Up to 150 characters.' },
        ],
    },
];

const visibleGroups = computed(() => groups.filter(g =>
    g.fields.some(f => values.value[f.key] !== undefined)
));

// --- Validation ---
const validators = {
    text: (f, v) => (f.required && !v?.trim()) ? 'Required' : (f.max && v?.length > f.max ? `Max ${f.max} characters` : ''),
    email: (f, v) => {
        if (f.required && !v?.trim()) return 'Required';
        if (v && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return 'Invalid email';
        return '';
    },
    url: (f, v) => {
        if (!v) return f.required ? 'Required' : '';
        try { new URL(v); return ''; } catch { return 'Invalid URL'; }
    },
    number: (f, v) => {
        if (v === '' || v === null || v === undefined) return f.required ? 'Required' : '';
        const n = Number(v);
        if (isNaN(n)) return 'Must be a number';
        if (f.min !== undefined && n < f.min) return `Min ${f.min}`;
        if (f.max !== undefined && n > f.max) return `Max ${f.max}`;
        return '';
    },
    textarea: (f, v) => (f.required && !v?.trim()) ? 'Required' : (f.max && v?.length > f.max ? `Max ${f.max} characters` : ''),
    select: (f, v) => (f.required && !v) ? 'Required' : '',
};

const validateField = (field) => {
    // google_rating special case — allow empty or 0-5 with .1 precision
    if (field.key === 'google_rating') {
        const v = values.value[field.key] || '';
        if (v && !/^[0-5](\.\d)?$/.test(v)) {
            errors.value[field.key] = 'Use a number 0–5 with one decimal (e.g. 4.9)';
            return;
        }
        errors.value[field.key] = '';
        return;
    }
    const fn = validators[field.type] || validators.text;
    errors.value[field.key] = fn(field, values.value[field.key]);
};

const isValid = computed(() => {
    for (const k in errors.value) if (errors.value[k]) return false;
    for (const k in validity.value) if (validity.value[k] === false) return false;
    return true;
});

const save = async () => {
    // Run full validation first
    for (const g of visibleGroups.value) for (const f of g.fields) validateField(f);
    if (!isValid.value) {
        toast.error('Please fix the highlighted fields');
        return;
    }
    saving.value = true;
    try {
        const flat = { ...values.value };
        const res = await apiFetch('/api/admin/settings', {
            method: 'PUT',
            body: JSON.stringify({ settings: flat }),
        });
        if (!res.ok) {
            const err = await res.json();
            if (err.errors) {
                for (const [k, msgs] of Object.entries(err.errors)) errors.value[k] = msgs[0];
            }
            throw new Error(err.message || 'Validation failed');
        }
        toast.success('Settings saved');
    } catch (e) {
        toast.error(e.message);
    } finally {
        saving.value = false;
    }
};

onMounted(async () => {
    try {
        const res = await apiFetch('/api/admin/settings');
        const data = await res.json();
        const flat = {};
        Object.values(data).forEach(group => Object.assign(flat, group));
        values.value = flat;
    } catch {
        toast.error('Failed to load settings');
    } finally {
        loading.value = false;
    }
});
</script>

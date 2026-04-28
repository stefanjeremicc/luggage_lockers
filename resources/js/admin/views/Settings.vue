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
                        <label class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-4">
                            <div class="md:flex-1 md:max-w-[40%]">
                                <div class="text-sm text-white font-medium">{{ field.label }}</div>
                                <p v-if="field.hint" class="text-xs text-[#6B7280] mt-0.5 leading-relaxed">{{ field.hint }}</p>
                            </div>
                            <div class="w-full md:w-[60%] md:max-w-md">
                                <!-- Bool (toggle) -->
                                <label v-if="field.type === 'bool'" class="inline-flex items-center gap-2 cursor-pointer select-none">
                                    <input type="checkbox" :checked="['1', 1, true, 'true'].includes(values[field.key])"
                                        @change="e => values[field.key] = e.target.checked ? '1' : '0'"
                                        class="w-4 h-4 rounded border-[#2A2A2A] bg-[#111] accent-[#F59E0B]">
                                    <span class="text-xs text-[#A0A0A0]">{{ ['1', 1, true, 'true'].includes(values[field.key]) ? 'Enabled' : 'Disabled' }}</span>
                                </label>

                                <!-- Phone -->
                                <PhoneInput v-else-if="field.type === 'phone'"
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

            <div class="sticky bottom-0 -mx-4 sm:-mx-6 md:-mx-8 -mb-4 sm:-mb-6 md:-mb-8 px-4 sm:px-6 md:px-8 py-3 bg-gradient-to-t from-[#0A0A0A] via-[#0A0A0A] to-transparent flex justify-end">
                <button @click="save" :disabled="saving || !isValid"
                    class="bg-[#F59E0B] text-black w-full sm:w-auto sm:px-8 py-3 rounded-lg font-semibold shadow-xl hover:bg-[#D97706] disabled:opacity-50">
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
            { key: 'site_address', label: 'Street address', type: 'text', max: 200,
                hint: 'Used in footer, schema.org, and contact page.' },
            { key: 'site_city', label: 'City', type: 'text', max: 100 },
            { key: 'site_country', label: 'Country', type: 'text', max: 100 },
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
        label: 'Homepage — Hero & Google reviews',
        fields: [
            { key: 'hero_image', label: 'Hero image path', type: 'text', max: 500,
                hint: 'Path to hero background, e.g. /images/hero-belgrade.webp' },
            { key: 'hero_headline_en', label: 'Hero headline (EN)', type: 'text', max: 120 },
            { key: 'hero_headline_sr', label: 'Hero headline (SR)', type: 'text', max: 120 },
            { key: 'hero_subhead_en', label: 'Hero subheading (EN)', type: 'textarea', max: 300, rows: 2 },
            { key: 'hero_subhead_sr', label: 'Hero subheading (SR)', type: 'textarea', max: 300, rows: 2 },
            { key: 'google_rating', label: 'Google rating', type: 'text', max: 3,
                hint: 'Number between 0 and 5 with one decimal (e.g. 4.9).' },
            { key: 'google_review_count', label: 'Review count label', type: 'text', max: 20,
                hint: 'Free-form label shown next to rating, e.g. "70+".' },
            { key: 'google_reviews_url', label: 'Reviews URL', type: 'url', max: 500,
                hint: 'Link to your Google Maps reviews page.' },
        ],
    },
    {
        key: 'map',
        label: 'Map defaults',
        fields: [
            { key: 'map_default_lat', label: 'Default latitude', type: 'number', min: -90, max: 90, step: 0.0001,
                hint: 'Center point for locations and contact maps.' },
            { key: 'map_default_lng', label: 'Default longitude', type: 'number', min: -180, max: 180, step: 0.0001 },
            { key: 'map_default_zoom', label: 'Default zoom', type: 'number', min: 1, max: 20 },
        ],
    },
    {
        key: 'social',
        label: 'Social links',
        fields: [
            { key: 'social_facebook_url', label: 'Facebook URL', type: 'url', max: 500 },
            { key: 'social_instagram_url', label: 'Instagram URL', type: 'url', max: 500 },
            { key: 'social_tiktok_url', label: 'TikTok URL', type: 'url', max: 500 },
        ],
    },
    {
        key: 'legal',
        label: 'Legal & business',
        fields: [
            { key: 'legal_company_name', label: 'Company legal name', type: 'text', max: 200,
                hint: 'Used in footer copyright and Terms.' },
            { key: 'legal_vat', label: 'VAT / PIB', type: 'text', max: 50 },
            { key: 'legal_registration_number', label: 'Registration / matični broj', type: 'text', max: 50 },
        ],
    },
    {
        key: 'access',
        label: 'Access codes',
        fields: [
            { key: 'entry_door_code', label: 'Entry door code', type: 'text', max: 20,
                hint: 'Code customers use to enter the building (same for everyone). Include "#" if your keypad needs it, e.g. "0717#".' },
        ],
    },
    {
        key: 'lockers',
        label: 'Locker sizes — capacity & dimensions',
        fields: [
            { key: 'locker_standard_capacity_en', label: 'Standard capacity (EN)', type: 'text', max: 120,
                hint: 'e.g. "1 suitcase & 1 bag" — shown on pricing & booking pages.' },
            { key: 'locker_standard_capacity_sr', label: 'Standard capacity (SR)', type: 'text', max: 120 },
            { key: 'locker_standard_dimensions', label: 'Standard dimensions', type: 'text', max: 60,
                hint: 'e.g. "50 × 65 × 28 cm".' },
            { key: 'locker_standard_image', label: 'Standard locker image', type: 'text', max: 500,
                hint: 'Path or URL.' },
            { key: 'locker_large_capacity_en', label: 'Large capacity (EN)', type: 'text', max: 120 },
            { key: 'locker_large_capacity_sr', label: 'Large capacity (SR)', type: 'text', max: 120 },
            { key: 'locker_large_dimensions', label: 'Large dimensions', type: 'text', max: 60 },
            { key: 'locker_large_image', label: 'Large locker image', type: 'text', max: 500 },
        ],
    },
    {
        key: 'seo',
        label: 'SEO — Homepage meta',
        fields: [
            { key: 'home_meta_title', label: 'Meta title (EN)', type: 'text', max: 60,
                hint: 'Up to 60 characters for best Google display.' },
            { key: 'home_meta_description', label: 'Meta description (EN)', type: 'textarea', max: 150, rows: 3 },
            { key: 'home_meta_title_sr', label: 'Meta title (SR)', type: 'text', max: 60 },
            { key: 'home_meta_description_sr', label: 'Meta description (SR)', type: 'textarea', max: 150, rows: 3 },
        ],
    },
    {
        key: 'notifications',
        label: 'Notifications — Admin & developer routing',
        fields: [
            { key: 'notifications_admin_email', label: 'Admin email', type: 'email', max: 150,
                hint: 'Where booking copies (and dev-mode redirects) go.' },
            { key: 'notifications_admin_whatsapp', label: 'Admin WhatsApp', type: 'phone',
                hint: 'International format (e.g. +381649679212).' },
            { key: 'notifications_dev_mode', label: 'Dev mode — redirect everything to admin', type: 'bool',
                hint: 'When ON, customer gets NOTHING — all booking emails + WhatsApp go to admin only. Use during testing.' },
            { key: 'notifications_notify_admin', label: 'Always notify admin (alongside customer)', type: 'bool',
                hint: 'When ON, admin gets a copy of every booking notification (BCC for email, separate WhatsApp). Ignored if dev mode is also on.' },
            { key: 'notifications_disabled', label: 'Disable all notifications (kill switch)', type: 'bool',
                hint: 'When ON, NO emails or WhatsApp are sent — useful when running seeders or imports.' },
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

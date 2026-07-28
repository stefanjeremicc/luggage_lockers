<template>
    <div class="max-w-3xl">
        <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
            <h1 class="text-2xl font-bold">Settings</h1>
            <button @click="save" :disabled="saving || !isValid"
                class="bg-[#F59E0B] text-black px-6 py-2.5 rounded-lg font-semibold text-sm hover:bg-[#D97706] disabled:opacity-50 disabled:cursor-not-allowed">
                {{ saving ? 'Saving…' : 'Save changes' }}
            </button>
        </div>

        <!-- Hub: sections moved here from the sidebar (Notifications / Sitemap / Users) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-8">
            <router-link to="/admin/notification-templates" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4 hover:border-[#F59E0B]/50 transition flex items-start gap-3">
                <span class="shrink-0 w-9 h-9 rounded-lg bg-[#F59E0B]/10 text-[#F59E0B] flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-white">Notifications</span>
                    <span class="block text-xs text-[#6B7280] mt-0.5">Email & WhatsApp templates</span>
                </span>
            </router-link>

            <router-link to="/admin/sitemap" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4 hover:border-[#F59E0B]/50 transition flex items-start gap-3">
                <span class="shrink-0 w-9 h-9 rounded-lg bg-[#F59E0B]/10 text-[#F59E0B] flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-white">Sitemap</span>
                    <span class="block text-xs text-[#6B7280] mt-0.5">View URLs & check broken links</span>
                </span>
            </router-link>

            <router-link v-if="user?.role === 'super_admin'" to="/admin/users" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4 hover:border-[#F59E0B]/50 transition flex items-start gap-3">
                <span class="shrink-0 w-9 h-9 rounded-lg bg-[#F59E0B]/10 text-[#F59E0B] flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 01-4 4"/></svg>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-white">Users</span>
                    <span class="block text-xs text-[#6B7280] mt-0.5">Admin accounts & roles</span>
                </span>
            </router-link>
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

const { apiFetch, user } = useAuth();
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
                hint: 'Number for call and contact display across the site.' },
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
            { key: 'hero_tagline_en', label: 'Hero tagline (EN)', type: 'text', max: 120,
                hint: 'Small line under the hero buttons, e.g. "2,000+ Happy Travelers — And Growing Every Day".' },
            { key: 'hero_tagline_sr', label: 'Hero tagline (SR)', type: 'text', max: 120 },
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
            { key: 'locker_standard_capacity_en', label: 'Regular capacity (EN)', type: 'text', max: 120,
                hint: 'e.g. "1 suitcase & 1 bag" — shown on pricing & booking pages.' },
            { key: 'locker_standard_capacity_sr', label: 'Regular capacity (SR)', type: 'text', max: 120 },
            { key: 'locker_standard_dimensions', label: 'Regular dimensions', type: 'text', max: 60,
                hint: 'e.g. "50 × 65 × 28 cm".' },
            { key: 'locker_standard_image', label: 'Regular locker image', type: 'text', max: 500,
                hint: 'Path or URL.' },
            { key: 'locker_large_capacity_en', label: 'Big capacity (EN)', type: 'text', max: 120 },
            { key: 'locker_large_capacity_sr', label: 'Big capacity (SR)', type: 'text', max: 120 },
            { key: 'locker_large_dimensions', label: 'Big dimensions', type: 'text', max: 60 },
            { key: 'locker_large_image', label: 'Big locker image', type: 'text', max: 500 },
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
            { key: 'notifications_admin_email', label: 'Admin email(s)', type: 'text', max: 500,
                hint: 'Where booking copies go. Separate multiple addresses with a comma — e.g. "stefan@webby.rs, ops@example.com".' },
            // Admin WhatsApp number hidden until WhatsApp Business is wired up.
            // { key: 'notifications_admin_whatsapp', label: 'Admin WhatsApp', type: 'phone',
            //     hint: 'International format (e.g. +381649679212).' },
            { key: 'notifications_dev_mode', label: 'Dev mode — redirect everything to admin', type: 'bool',
                hint: 'When ON, customer gets NOTHING — all booking emails go to admin only. Use during testing.' },
            { key: 'notifications_notify_admin', label: 'Always notify admin (alongside customer)', type: 'bool',
                hint: 'When ON, admin gets a copy of every booking email (BCC). Ignored if dev mode is also on.' },
            { key: 'notifications_disabled', label: 'Disable all notifications (kill switch)', type: 'bool',
                hint: 'When ON, NO emails are sent — useful when running seeders or imports.' },
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

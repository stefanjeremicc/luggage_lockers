<template>
    <div class="max-w-5xl mx-auto">
        <EditPageHeader
            :title="isNew ? 'New Location' : 'Edit Location'"
            back-to="/admin/locations"
            back-label="Back to locations"
            :saving="saving"
            :show-delete="!isNew"
            delete-label="Deactivate"
            @save="save"
            @delete="confirmDelete"
        />

        <div v-if="loading" class="text-sm text-[#A0A0A0]">Loading…</div>

        <form v-else @submit.prevent="save" class="space-y-6">
            <!-- Basics -->
            <section class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 space-y-4">
                <h2 class="text-sm font-semibold text-[#F59E0B] uppercase tracking-wide">Basics</h2>

                <!-- Language tabs for translatable text fields -->
                <div class="flex border-b border-[#2A2A2A]">
                    <button type="button" v-for="t in tabs" :key="t.value"
                        @click="activeTab = t.value"
                        class="px-4 py-2 text-sm font-medium transition border-b-2 -mb-px"
                        :class="activeTab === t.value ? 'border-[#F59E0B] text-[#F59E0B]' : 'border-transparent text-[#A0A0A0] hover:text-white'">
                        {{ t.label }}
                    </button>
                </div>

                <div v-show="activeTab === 'en'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Field label="Name (English)" required>
                            <input v-model="form.name" @input="autoSlug" required
                                class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none">
                        </Field>
                        <Field label="Slug" required hint="lowercase letters, digits and hyphens only">
                            <input v-model="form.slug" required pattern="[a-z0-9-]+"
                                class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white font-mono text-sm focus:border-[#F59E0B] focus:outline-none">
                        </Field>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Field label="Address (English)" required>
                            <input v-model="form.address" required
                                class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none">
                        </Field>
                        <Field label="City (English)">
                            <input v-model="form.city" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none">
                        </Field>
                    </div>
                </div>

                <div v-show="activeTab === 'sr'" class="space-y-4">
                    <Field label="Naziv (Srpski)" hint="Ostavite prazno da koristi engleski">
                        <input v-model="form.name_sr"
                            class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none">
                    </Field>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Field label="Adresa (Srpski)" hint="Ostavite prazno da koristi engleski">
                            <input v-model="form.address_sr"
                                class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none">
                        </Field>
                        <Field label="Grad (Srpski)">
                            <input v-model="form.city_sr"
                                class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none">
                        </Field>
                    </div>
                </div>

                <Field label="Map & coordinates" hint="Search address to auto-locate, or drag/click the marker to fine-tune.">
                    <LocationMap
                        :lat="form.lat"
                        :lng="form.lng"
                        :address="locationSearchAddress"
                        @update:lat="v => form.lat = v"
                        @update:lng="v => form.lng = v"
                    />
                </Field>
            </section>

            <!-- Images -->
            <section class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 space-y-4">
                <h2 class="text-sm font-semibold text-[#F59E0B] uppercase tracking-wide">Images</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <Field label="Hero image" hint="Shown on the location detail page header.">
                        <ImageUploader v-model="form.image_url" folder="locations" />
                    </Field>
                    <Field label="Social share image (Open Graph)" hint="Used when this location is shared on Facebook, X, WhatsApp. Recommended 1200×630.">
                        <ImageUploader v-model="form.og_image" folder="locations" />
                    </Field>
                </div>
            </section>

            <!-- Hours -->
            <section class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 space-y-4">
                <h2 class="text-sm font-semibold text-[#F59E0B] uppercase tracking-wide">Opening hours</h2>
                <Checkbox v-model="form.is_24h" label="Open 24/7" description="When on, the site shows '24/7' badge and ignores opening times." />
                <div v-if="!form.is_24h" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Field label="Opens at">
                        <TimePicker v-model="form.opening_time" />
                    </Field>
                    <Field label="Closes at">
                        <TimePicker v-model="form.closing_time" />
                    </Field>
                </div>
            </section>

            <!-- Contact -->
            <section class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 space-y-4">
                <h2 class="text-sm font-semibold text-[#F59E0B] uppercase tracking-wide">Contact</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Field label="Phone" hint="Used for the tel: link on the location page.">
                        <PhoneInput v-model="form.phone" />
                    </Field>
                    <Field label="WhatsApp" hint="Number for the WhatsApp chat link. Leave empty to hide. Use international format (e.g. +381…).">
                        <PhoneInput v-model="form.whatsapp" />
                    </Field>
                    <Field label="Email">
                        <input v-model="form.email" type="email"
                            class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none">
                    </Field>
                    <Field label="Google Maps URL" hint="Optional shareable link.">
                        <input v-model="form.google_maps_url" type="url"
                            class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none">
                    </Field>
                </div>
            </section>

            <!-- Content -->
            <section class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 space-y-4">
                <h2 class="text-sm font-semibold text-[#F59E0B] uppercase tracking-wide">Content</h2>
                <Field label="Description (English)">
                    <RichEditor v-model="form.description" placeholder="Describe this location…" min-height="160px" />
                </Field>
                <Field label="Description (Serbian)">
                    <RichEditor v-model="form.description_sr" placeholder="Opis lokacije na srpskom…" min-height="160px" />
                </Field>
            </section>

            <!-- SEO -->
            <details class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                <summary class="cursor-pointer text-sm text-[#F59E0B] font-semibold uppercase tracking-wide">SEO meta (optional)</summary>
                <div class="mt-4 space-y-5">
                    <div class="space-y-3">
                        <p class="text-xs text-[#A0A0A0] uppercase tracking-wide">English</p>
                        <Field label="Meta title">
                            <input v-model="form.meta_title" maxlength="255"
                                class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none">
                        </Field>
                        <Field label="Meta description">
                            <textarea v-model="form.meta_description" rows="2" maxlength="500"
                                class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none"></textarea>
                        </Field>
                    </div>
                    <div class="space-y-3">
                        <p class="text-xs text-[#A0A0A0] uppercase tracking-wide">Srpski</p>
                        <Field label="Meta naslov">
                            <input v-model="form.meta_title_sr" maxlength="255"
                                class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none">
                        </Field>
                        <Field label="Meta opis">
                            <textarea v-model="form.meta_description_sr" rows="2" maxlength="500"
                                class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none"></textarea>
                        </Field>
                    </div>
                </div>
            </details>

            <!-- Active -->
            <section class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4">
                <Checkbox
                    v-model="form.is_active"
                    label="Active"
                    description="When off, this location is hidden from the public site."
                />
            </section>
        </form>

        <!-- Locker assignment (only on edit) -->
        <section v-if="!isNew && !loading" class="mt-8 bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-[#F59E0B] uppercase tracking-wide">Lockers at this location</h2>
                <span class="text-xs text-[#A0A0A0]">{{ assignedLockers.length }} assigned</span>
            </div>

            <!-- Assigned list (drag-sortable for site display order) -->
            <p v-if="assignedLockers.length" class="text-xs text-[#A0A0A0] mb-2">Drag to reorder how lockers appear on the public location page.</p>
            <draggable v-if="assignedLockers.length"
                v-model="assignedLockers"
                item-key="id"
                handle=".drag-handle"
                :animation="160"
                :scroll="true"
                :bubble-scroll="true"
                :scroll-sensitivity="120"
                :scroll-speed="18"
                :force-fallback="true"
                @end="onLockerSortEnd"
                class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2 mb-4">
                <template #item="{ element: l }">
                    <div class="bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 flex items-center justify-between gap-2">
                        <span class="drag-handle cursor-grab text-[#6B7280] hover:text-[#A0A0A0]" title="Drag">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="font-mono font-semibold text-sm">{{ l.number }}</div>
                            <div class="text-[10px] text-[#A0A0A0] uppercase">{{ l.size }}</div>
                        </div>
                        <button type="button" @click="unassign(l)"
                            class="text-[#EF4444] hover:text-red-300 shrink-0" title="Unassign">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
            </draggable>
            <p v-else class="text-sm text-[#A0A0A0] mb-4">No lockers assigned yet.</p>

            <!-- Assign form -->
            <div class="border-t border-[#2A2A2A] pt-4">
                <h3 class="text-sm font-semibold mb-3">Assign lockers</h3>
                <div v-if="unassignedLockers.length" class="flex flex-wrap gap-2">
                    <button v-for="l in unassignedLockers" :key="l.id"
                        type="button" @click="assign(l)"
                        class="bg-[#111] border border-[#2A2A2A] hover:border-[#F59E0B] rounded-lg px-3 py-2 text-sm transition">
                        <span class="font-mono font-semibold">{{ l.number }}</span>
                        <span class="text-[10px] text-[#A0A0A0] uppercase ml-2">{{ l.size }}</span>
                        <span v-if="l.location" class="text-[10px] text-[#6B7280] ml-2">(from {{ l.location.name }})</span>
                    </button>
                </div>
                <p v-else class="text-sm text-[#A0A0A0]">All lockers are already assigned here.</p>
            </div>
        </section>
    </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuth } from '../composables/useAuth';
import { useToast } from '../composables/useToast';
import { useConfirm } from '../composables/useConfirm';
import RichEditor from '../components/RichEditor.vue';
import Field from '../components/FormField.vue';
import PhoneInput from '../components/PhoneInput.vue';
import Checkbox from '../components/Checkbox.vue';
import EditPageHeader from '../components/EditPageHeader.vue';
import TimePicker from '../components/TimePicker.vue';
import LocationMap from '../components/LocationMap.vue';
import ImageUploader from '../components/ImageUploader.vue';
import draggable from 'vuedraggable';

const route = useRoute();
const router = useRouter();
const { apiFetch } = useAuth();
const toast = useToast();
const confirmDialog = useConfirm();

const isNew = computed(() => !route.params.id);
const loading = ref(!isNew.value);
const saving = ref(false);

const tabs = [
    { value: 'en', label: 'English' },
    { value: 'sr', label: 'Srpski' },
];
const activeTab = ref('en');

const form = ref({
    name: '', name_sr: '', slug: '',
    address: '', address_sr: '',
    city: 'Belgrade', city_sr: '',
    lat: 44.8176, lng: 20.4569,
    description: '', description_sr: '',
    is_24h: true, opening_time: null, closing_time: null,
    phone: '', whatsapp: '', email: '', google_maps_url: '',
    meta_title: '', meta_title_sr: '',
    meta_description: '', meta_description_sr: '',
    image_url: '', og_image: '',
    is_active: true,
});

const locationSearchAddress = computed(() => {
    const parts = [form.value.address, form.value.city].filter(Boolean);
    return parts.join(', ');
});

const allLockers = ref([]);
const locationId = computed(() => Number(route.params.id));

const assignedLockers = computed({
    get: () => allLockers.value
        .filter(l => l.location_id === locationId.value)
        .sort((a, b) => (a.site_sort_order ?? 999999) - (b.site_sort_order ?? 999999)),
    set: (next) => {
        const otherLockers = allLockers.value.filter(l => l.location_id !== locationId.value);
        next.forEach((l, idx) => { l.site_sort_order = idx; });
        allLockers.value = [...otherLockers, ...next];
    },
});
const unassignedLockers = computed(() =>
    allLockers.value.filter(l => l.location_id !== locationId.value)
);

const onLockerSortEnd = async () => {
    const ids = assignedLockers.value.map(l => l.id);
    try {
        const res = await apiFetch('/api/admin/lockers/reorder', {
            method: 'POST',
            body: JSON.stringify({ ids, field: 'site_sort_order' }),
        });
        if (!res.ok) throw new Error();
        toast.success('Locker order saved');
    } catch {
        toast.error('Failed to save locker order');
    }
};

let slugTouched = false;
const slugify = (s) => s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-').slice(0, 80);
const autoSlug = () => {
    if (isNew.value && !slugTouched) form.value.slug = slugify(form.value.name);
};

const save = async () => {
    if (!form.value.name?.trim()) { toast.error('Name is required'); activeTab.value = 'en'; return; }
    if (!form.value.slug?.trim()) { toast.error('Slug is required'); activeTab.value = 'en'; return; }
    if (!/^[a-z0-9-]+$/.test(form.value.slug)) { toast.error('Slug must be lowercase letters, digits and hyphens only'); activeTab.value = 'en'; return; }
    if (!form.value.address?.trim()) { toast.error('Address is required'); activeTab.value = 'en'; return; }
    if (!form.value.is_24h && form.value.opening_time && form.value.closing_time
        && form.value.opening_time >= form.value.closing_time) {
        toast.error('Closing time must be after opening time');
        return;
    }
    saving.value = true;
    try {
        const payload = { ...form.value };
        if (payload.is_24h) { payload.opening_time = null; payload.closing_time = null; }
        if (isNew.value) {
            const res = await apiFetch('/api/admin/locations', { method: 'POST', body: JSON.stringify(payload) });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Save failed');
            toast.success('Location created');
            router.replace(`/admin/locations/${data.id}/edit`);
        } else {
            const res = await apiFetch(`/api/admin/locations/${route.params.id}`, {
                method: 'PUT', body: JSON.stringify(payload),
            });
            if (!res.ok) {
                const err = await res.json();
                throw new Error(err.message || 'Save failed');
            }
            toast.success('Location saved');
        }
    } catch (e) {
        toast.error(e.message);
    } finally {
        saving.value = false;
    }
};

const confirmDelete = async () => {
    const ok = await confirmDialog.ask({
        title: 'Deactivate location?',
        message: 'Lockers will remain but the location will be hidden from the public site.',
        variant: 'danger',
        confirmText: 'Deactivate',
    });
    if (!ok) return;
    try {
        await apiFetch(`/api/admin/locations/${route.params.id}`, { method: 'DELETE' });
        toast.success('Location deactivated');
        router.push('/admin/locations');
    } catch (e) {
        toast.error('Failed to deactivate');
    }
};

const loadLockers = async () => {
    const res = await apiFetch('/api/admin/lockers');
    allLockers.value = await res.json();
};

const assign = async (locker) => {
    try {
        const res = await apiFetch(`/api/admin/lockers/${locker.id}`, {
            method: 'PUT',
            body: JSON.stringify({ location_id: locationId.value }),
        });
        if (!res.ok) throw new Error('Failed');
        locker.location_id = locationId.value;
        locker.location = { id: locationId.value, name: form.value.name };
        toast.success(`Locker ${locker.number} assigned here`);
    } catch (e) {
        toast.error('Failed to assign');
    }
};

const unassign = async (locker) => {
    const ok = await confirmDialog.ask({
        title: 'Remove locker?',
        message: `Locker ${locker.number} will be unassigned from this location.`,
        variant: 'warning',
        confirmText: 'Remove',
    });
    if (!ok) return;
    try {
        const res = await apiFetch(`/api/admin/lockers/${locker.id}`, {
            method: 'PUT',
            body: JSON.stringify({ location_id: null }),
        });
        if (!res.ok) throw new Error('Failed');
        locker.location_id = null;
        locker.location = null;
        toast.success(`Locker ${locker.number} unassigned`);
    } catch (e) {
        toast.error('Failed to unassign');
    }
};

onMounted(async () => {
    if (isNew.value) return;
    try {
        const [locRes] = await Promise.all([
            apiFetch(`/api/admin/locations/${route.params.id}`),
            loadLockers(),
        ]);
        const data = await locRes.json();
        form.value = {
            name: data.name || '', name_sr: data.name_sr || '', slug: data.slug || '',
            address: data.address || '', address_sr: data.address_sr || '',
            city: data.city || 'Belgrade', city_sr: data.city_sr || '',
            lat: Number(data.lat) || 44.8176, lng: Number(data.lng) || 20.4569,
            description: data.description || '', description_sr: data.description_sr || '',
            is_24h: !!data.is_24h,
            opening_time: data.opening_time || null, closing_time: data.closing_time || null,
            phone: data.phone || '', whatsapp: data.whatsapp || '',
            email: data.email || '', google_maps_url: data.google_maps_url || '',
            meta_title: data.meta_title || '', meta_title_sr: data.meta_title_sr || '',
            meta_description: data.meta_description || '', meta_description_sr: data.meta_description_sr || '',
            image_url: data.image_url || '', og_image: data.og_image || '',
            is_active: !!data.is_active,
        };
        slugTouched = true;
    } finally {
        loading.value = false;
    }
});
</script>

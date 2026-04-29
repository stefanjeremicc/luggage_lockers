<template>
    <div class="max-w-5xl">
        <div class="flex items-start justify-between mb-6 gap-3">
            <div class="min-w-0 flex-1">
                <router-link to="/admin/pages" class="text-xs text-[#A0A0A0] hover:text-white">← Pages</router-link>
                <h1 class="text-xl sm:text-2xl font-bold capitalize mt-1 truncate">{{ slug }}</h1>
                <p class="text-xs text-[#6B7280] mt-1 hidden sm:block">Edit SEO metadata and content for both languages.</p>
            </div>
            <button @click="save" :disabled="saving"
                class="hidden sm:inline-flex bg-[#F59E0B] text-black px-6 py-2.5 rounded-lg font-semibold text-sm hover:bg-[#D97706] disabled:opacity-50 shrink-0">
                {{ saving ? 'Saving…' : 'Save changes' }}
            </button>
        </div>

        <!-- Mobile: sticky bottom save bar -->
        <div class="sm:hidden fixed bottom-0 left-0 right-0 z-40 bg-[#0F0F0F] border-t border-[#2A2A2A] px-4 py-3" style="padding-bottom: max(12px, env(safe-area-inset-bottom));">
            <button @click="save" :disabled="saving" class="w-full bg-[#F59E0B] text-black px-6 py-3 rounded-lg font-semibold text-sm disabled:opacity-50">
                {{ saving ? 'Saving…' : 'Save changes' }}
            </button>
        </div>
        <div class="sm:hidden h-20"></div>

        <div v-if="loading" class="text-sm text-[#A0A0A0]">Loading…</div>

        <div v-else class="space-y-6">
            <div v-if="isLanding" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4">
                <label class="block text-xs text-[#A0A0A0] mb-1">Linked location (optional)</label>
                <select v-model="locationId" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-sm text-white focus:border-[#F59E0B] focus:outline-none">
                    <option :value="null">— None —</option>
                    <option v-for="l in locations" :key="l.id" :value="l.id">{{ l.name }}</option>
                </select>
                <p class="text-[11px] text-[#6B7280] mt-1">Booking CTAs on this landing will deep-link to this location.</p>
            </div>
            <div class="flex gap-2">
                <button v-for="loc in ['en', 'sr']" :key="loc" @click="activeLocale = loc"
                    class="px-4 py-2 rounded-lg text-sm transition border"
                    :class="activeLocale === loc ? 'bg-[#F59E0B] text-black border-[#F59E0B]' : 'border-[#2A2A2A] text-[#A0A0A0] hover:border-[#F59E0B]'">
                    {{ loc === 'en' ? 'English' : 'Srpski' }}
                </button>
            </div>

            <div v-for="loc in ['en', 'sr']" v-show="activeLocale === loc" :key="loc"
                class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 space-y-4">

                <div>
                    <label class="block text-sm text-white font-medium mb-1">Title</label>
                    <p class="text-xs text-[#6B7280] mb-2">Internal label (used in admin lists).</p>
                    <input v-model="data[loc].title" maxlength="200" type="text"
                        class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm text-white font-medium mb-1">Meta title</label>
                    <p class="text-xs text-[#6B7280] mb-2">Shown in browser tab and Google search. Up to 60 chars.</p>
                    <input v-model="data[loc].meta_title" maxlength="70" type="text"
                        class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none">
                    <p class="text-[11px] text-[#6B7280] mt-1 text-right">{{ (data[loc].meta_title || '').length }}/60</p>
                </div>

                <div>
                    <label class="block text-sm text-white font-medium mb-1">Meta description</label>
                    <p class="text-xs text-[#6B7280] mb-2">Shown under the title in Google results. Up to 160 chars.</p>
                    <textarea v-model="data[loc].meta_description" maxlength="300" rows="3"
                        class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none resize-y"></textarea>
                    <p class="text-[11px] text-[#6B7280] mt-1 text-right">{{ (data[loc].meta_description || '').length }}/160</p>
                </div>

                <div>
                    <label class="block text-sm text-white font-medium mb-1">OG image (social share)</label>
                    <p class="text-xs text-[#6B7280] mb-2">Path or full URL. 1200×630 recommended. Leave blank to skip.</p>
                    <ImageUploader v-model="data[loc].og_image" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm text-white font-medium mb-1">OG title</label>
                        <input v-model="data[loc].og_title" maxlength="200" type="text"
                            class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm text-white font-medium mb-1">Canonical URL</label>
                        <input v-model="data[loc].canonical_url" type="url" placeholder="https://…"
                            class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-white font-medium mb-1">OG description</label>
                    <textarea v-model="data[loc].og_description" maxlength="500" rows="2"
                        class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none resize-y"></textarea>
                </div>

                <div v-if="hasContent">
                    <label class="block text-sm text-white font-medium mb-1">Content (HTML)</label>
                    <p class="text-xs text-[#6B7280] mb-2">Rendered as the body of {{ slug }} page.</p>
                    <RichEditor v-model="data[loc].content" />
                </div>

                <!-- Homepage sections (structured) -->
                <template v-if="slug === 'home' && data[loc].sections">
                    <div class="border-t border-[#2A2A2A] pt-4 mt-2">
                        <h3 class="text-sm font-semibold text-[#F59E0B] uppercase tracking-wide mb-3">Hero</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs text-[#A0A0A0] mb-1">Title</label>
                                <input v-model="data[loc].sections.hero.title" type="text" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-sm text-white focus:border-[#F59E0B] focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs text-[#A0A0A0] mb-1">Subtitle</label>
                                <textarea v-model="data[loc].sections.hero.subtitle" rows="2" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-sm text-white focus:border-[#F59E0B] focus:outline-none resize-y"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-[#A0A0A0] mb-1">Primary CTA text</label>
                                    <input v-model="data[loc].sections.hero.cta_primary" type="text" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-sm text-white focus:border-[#F59E0B] focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs text-[#A0A0A0] mb-1">Secondary CTA text</label>
                                    <input v-model="data[loc].sections.hero.cta_secondary" type="text" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-sm text-white focus:border-[#F59E0B] focus:outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-[#A0A0A0] mb-1">Hero image (optional)</label>
                                <ImageUploader v-model="data[loc].sections.hero.image" />
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-[#2A2A2A] pt-4 mt-2">
                        <h3 class="text-sm font-semibold text-[#F59E0B] uppercase tracking-wide mb-3">How it works — heading</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                            <input v-model="data[loc].sections.how_it_works_heading.title" placeholder="Title (e.g. How To Use Our Lockers)" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-sm text-white focus:border-[#F59E0B] focus:outline-none">
                            <input v-model="data[loc].sections.how_it_works_heading.subtitle" placeholder="Subtitle" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-sm text-white focus:border-[#F59E0B] focus:outline-none">
                        </div>
                        <h3 class="text-sm font-semibold text-[#F59E0B] uppercase tracking-wide mb-3">How it works (4 steps)</h3>
                        <div class="space-y-3">
                            <div v-for="(step, i) in data[loc].sections.how_it_works" :key="i" class="bg-[#111] border border-[#2A2A2A] rounded-lg p-3 space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-[#6B7280] font-mono w-8">#{{ i + 1 }}</span>
                                    <select v-model="step.icon" class="bg-[#0A0A0A] border border-[#2A2A2A] rounded px-2 py-1 text-xs text-white">
                                        <option value="computer">computer</option>
                                        <option value="search">search</option>
                                        <option value="lock">lock</option>
                                        <option value="smile">smile</option>
                                    </select>
                                    <input v-model="step.title" placeholder="Step title" class="flex-1 bg-[#0A0A0A] border border-[#2A2A2A] rounded px-2 py-1 text-sm text-white focus:border-[#F59E0B] focus:outline-none">
                                </div>
                                <textarea v-model="step.desc" rows="2" placeholder="Step description" class="w-full bg-[#0A0A0A] border border-[#2A2A2A] rounded px-2 py-1.5 text-sm text-white focus:border-[#F59E0B] focus:outline-none resize-y"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-[#2A2A2A] pt-4 mt-2">
                        <h3 class="text-sm font-semibold text-[#F59E0B] uppercase tracking-wide mb-3">FAQ heading</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <input v-model="data[loc].sections.faq.title" placeholder="Title" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-sm text-white focus:border-[#F59E0B] focus:outline-none">
                            <input v-model="data[loc].sections.faq.subtitle" placeholder="Subtitle" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-sm text-white focus:border-[#F59E0B] focus:outline-none">
                        </div>
                    </div>

                    <div class="border-t border-[#2A2A2A] pt-4 mt-2">
                        <h3 class="text-sm font-semibold text-[#F59E0B] uppercase tracking-wide mb-3">Final CTA</h3>
                        <div class="space-y-3">
                            <input v-model="data[loc].sections.cta.title" placeholder="Title" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-sm text-white focus:border-[#F59E0B] focus:outline-none">
                            <textarea v-model="data[loc].sections.cta.subtitle" rows="2" placeholder="Subtitle" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-sm text-white focus:border-[#F59E0B] focus:outline-none resize-y"></textarea>
                            <input v-model="data[loc].sections.cta.button" placeholder="Button text" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-sm text-white focus:border-[#F59E0B] focus:outline-none">
                        </div>
                    </div>
                </template>

                <label class="inline-flex items-center gap-2 cursor-pointer select-none mt-2">
                    <input type="checkbox" v-model="data[loc].is_published"
                        class="w-4 h-4 rounded border-[#2A2A2A] bg-[#111] accent-[#F59E0B]">
                    <span class="text-sm text-[#A0A0A0]">Published in {{ loc.toUpperCase() }}</span>
                </label>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuth } from '../composables/useAuth';
import { useToast } from '../composables/useToast';
import ImageUploader from '../components/ImageUploader.vue';
import RichEditor from '../components/RichEditor.vue';

const route = useRoute();
const { apiFetch } = useAuth();
const toast = useToast();

const slug = route.params.slug;
const loading = ref(true);
const saving = ref(false);
const activeLocale = ref('en');

const blankSections = () => slug === 'home' ? ({
    hero: { title: '', subtitle: '', cta_primary: '', cta_secondary: '', image: '' },
    how_it_works_heading: { title: '', subtitle: '' },
    how_it_works: [
        { icon: 'computer', title: '', desc: '' },
        { icon: 'search', title: '', desc: '' },
        { icon: 'lock', title: '', desc: '' },
        { icon: 'smile', title: '', desc: '' },
    ],
    faq: { title: '', subtitle: '' },
    cta: { title: '', subtitle: '', button: '' },
}) : null;

const blank = () => ({ title: '', meta_title: '', meta_description: '', og_image: '', og_title: '', og_description: '', canonical_url: '', content: '', sections: blankSections(), is_published: true });
const data = ref({ en: blank(), sr: blank() });
const pageType = ref('page');
const locationId = ref(null);
const locations = ref([]);

const hasContent = computed(() => pageType.value === 'landing' || ['about', 'terms', 'privacy'].includes(slug));
const isLanding = computed(() => pageType.value === 'landing');

const normalizeSections = (raw) => {
    if (slug !== 'home') return raw || null;
    const base = blankSections();
    if (!raw || typeof raw !== 'object') return base;
    return {
        hero: { ...base.hero, ...(raw.hero || {}) },
        how_it_works_heading: { ...base.how_it_works_heading, ...(raw.how_it_works_heading || {}) },
        how_it_works: Array.isArray(raw.how_it_works) && raw.how_it_works.length === 4
            ? raw.how_it_works.map((s, i) => ({ ...base.how_it_works[i], ...s }))
            : base.how_it_works,
        faq: { ...base.faq, ...(raw.faq || {}) },
        cta: { ...base.cta, ...(raw.cta || {}) },
    };
};

const load = async () => {
    try {
        const [pRes, lRes] = await Promise.all([
            apiFetch(`/api/admin/pages/${slug}`),
            apiFetch('/api/admin/locations'),
        ]);
        const json = await pRes.json();
        pageType.value = json.type || 'page';
        locationId.value = json.location_id || null;
        const locJson = await lRes.json();
        locations.value = Array.isArray(locJson) ? locJson : (locJson.data || []);
        for (const loc of ['en', 'sr']) {
            const row = json[loc];
            data.value[loc] = row ? {
                title: row.title || '',
                meta_title: row.meta_title || '',
                meta_description: row.meta_description || '',
                og_image: row.og_image || '',
                og_title: row.og_title || '',
                og_description: row.og_description || '',
                canonical_url: row.canonical_url || '',
                content: row.content || '',
                sections: normalizeSections(row.sections),
                is_published: !!row.is_published,
            } : blank();
        }
    } catch { toast.error('Failed to load page'); }
    finally { loading.value = false; }
};

const save = async () => {
    saving.value = true;
    try {
        const payload = {
            location_id: locationId.value,
            en: { ...data.value.en, is_published: data.value.en.is_published ? 1 : 0 },
            sr: { ...data.value.sr, is_published: data.value.sr.is_published ? 1 : 0 },
        };
        const res = await apiFetch(`/api/admin/pages/${slug}`, {
            method: 'PUT',
            body: JSON.stringify(payload),
        });
        if (!res.ok) throw new Error((await res.json()).message || 'Save failed');
        toast.success('Saved');
    } catch (e) { toast.error(e.message); }
    finally { saving.value = false; }
};

onMounted(load);
</script>

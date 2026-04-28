<template>
    <div>
        <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
            <div>
                <h1 class="text-2xl font-bold">Pages &amp; SEO</h1>
                <p class="text-sm text-[#A0A0A0] mt-1">Edit titles, meta tags, and content for every public page. Bilingual (EN / SR).</p>
            </div>
            <button @click="creating = true"
                class="bg-[#F59E0B] text-black px-4 py-2 rounded-lg text-sm font-semibold hover:bg-[#D97706]">
                + New landing page
            </button>
        </div>

        <div v-if="loading" class="text-sm text-[#A0A0A0]">Loading…</div>

        <template v-else>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-[#A0A0A0] mt-2 mb-3">System pages</h2>
            <PagesTable :pages="systemPages" />

            <h2 class="text-sm font-semibold uppercase tracking-wide text-[#A0A0A0] mt-8 mb-3">SEO landing pages</h2>
            <p v-if="!landingPages.length" class="text-sm text-[#6B7280]">No landing pages yet. Click "+ New landing page" to create one.</p>
            <PagesTable v-else :pages="landingPages" :deletable="true" @delete="onDelete" />
        </template>

        <!-- Create modal -->
        <div v-if="creating" class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4" @click.self="creating = false">
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 w-full max-w-md space-y-4">
                <h3 class="text-lg font-semibold">New landing page</h3>
                <div>
                    <label class="block text-xs text-[#A0A0A0] mb-1">Slug (URL)</label>
                    <input v-model="newPage.slug" placeholder="lockers-near-city-centre" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-sm text-white focus:border-[#F59E0B] focus:outline-none">
                    <p class="text-[11px] text-[#6B7280] mt-1">Lowercase, hyphens only. Will become /{{ newPage.slug || 'your-slug' }}.</p>
                </div>
                <div>
                    <label class="block text-xs text-[#A0A0A0] mb-1">Internal title</label>
                    <input v-model="newPage.title" placeholder="Lockers near city centre" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-sm text-white focus:border-[#F59E0B] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs text-[#A0A0A0] mb-1">Linked location (optional)</label>
                    <select v-model="newPage.location_id" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-sm text-white focus:border-[#F59E0B] focus:outline-none">
                        <option :value="null">— None —</option>
                        <option v-for="l in locations" :key="l.id" :value="l.id">{{ l.name }}</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button @click="creating = false" class="px-4 py-2 text-sm text-[#A0A0A0] hover:text-white">Cancel</button>
                    <button @click="onCreate" :disabled="!newPage.slug || !newPage.title || saving"
                        class="bg-[#F59E0B] text-black px-4 py-2 rounded-lg text-sm font-semibold disabled:opacity-50">
                        {{ saving ? 'Creating…' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, computed, onMounted, h } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../composables/useAuth';
import { useToast } from '../composables/useToast';

const router = useRouter();

const { apiFetch } = useAuth();
const toast = useToast();
const pages = ref([]);
const locations = ref([]);
const loading = ref(true);
const creating = ref(false);
const saving = ref(false);
const newPage = ref({ slug: '', title: '', location_id: null });

const systemPages = computed(() => pages.value.filter(p => (p.type || 'page') !== 'landing'));
const landingPages = computed(() => pages.value.filter(p => p.type === 'landing'));

const PagesTable = {
    props: { pages: { type: Array, required: true }, deletable: { type: Boolean, default: false } },
    emits: ['delete'],
    setup(props, { emit }) {
        const status = (p) => (p.en?.is_published || p.sr?.is_published) ? 'Published' : 'Draft';
        const statusCls = (p) => (p.en?.is_published || p.sr?.is_published) ? 'bg-[#10B981]/20 text-[#10B981]' : 'bg-[#6B7280]/20 text-[#6B7280]';
        return () => h('div', null, [
            // Mobile cards
            h('div', { class: 'md:hidden space-y-2' }, props.pages.map(p =>
                h('div', { key: p.slug, class: 'bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4 flex items-center gap-3' }, [
                    h('a', { href: `/admin/pages/${p.slug}/edit`, onClick: (e) => { e.preventDefault(); router.push(`/admin/pages/${p.slug}/edit`); }, class: 'min-w-0 flex-1' }, [
                        h('div', { class: 'font-semibold capitalize' }, p.en?.title || p.sr?.title || p.slug),
                        h('div', { class: 'font-mono text-xs text-[#6B7280] mt-0.5' }, '/' + p.slug),
                    ]),
                    h('span', { class: 'px-2 py-0.5 rounded-full text-[10px] ' + statusCls(p) }, status(p)),
                    props.deletable ? h('button', { onClick: () => emit('delete', p), class: 'text-xs text-red-400 hover:text-red-300 ml-2' }, '✕') : null,
                ])
            )),
            // Desktop table
            h('div', { class: 'hidden md:block bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl overflow-x-auto' },
                h('table', { class: 'w-full text-sm' }, [
                    h('thead', { class: 'border-b border-[#2A2A2A]' },
                        h('tr', { class: 'text-[#A0A0A0] text-left' }, [
                            h('th', { class: 'px-4 py-3 font-medium' }, 'Page'),
                            h('th', { class: 'px-4 py-3 font-medium' }, 'Slug'),
                            h('th', { class: 'px-4 py-3 font-medium' }, 'EN title'),
                            h('th', { class: 'px-4 py-3 font-medium' }, 'SR title'),
                            h('th', { class: 'px-4 py-3 font-medium' }, 'Status'),
                            h('th', { class: 'px-4 py-3 font-medium text-right' }, 'Action'),
                        ])
                    ),
                    h('tbody', null, props.pages.map(p =>
                        h('tr', { key: p.slug, class: 'border-b border-[#2A2A2A]/50 hover:bg-[#111]' }, [
                            h('td', { class: 'px-4 py-3 font-medium capitalize' }, p.en?.title || p.sr?.title || p.slug),
                            h('td', { class: 'px-4 py-3 text-[#A0A0A0] font-mono text-xs' }, p.slug),
                            h('td', { class: 'px-4 py-3 text-[#A0A0A0] truncate max-w-[260px]' }, p.en?.meta_title || '—'),
                            h('td', { class: 'px-4 py-3 text-[#A0A0A0] truncate max-w-[260px]' }, p.sr?.meta_title || '—'),
                            h('td', { class: 'px-4 py-3' }, h('span', { class: 'px-2 py-0.5 rounded-full text-xs ' + statusCls(p) }, status(p))),
                            h('td', { class: 'px-4 py-3 text-right space-x-3' }, [
                                h('a', { href: `/admin/pages/${p.slug}/edit`, onClick: (e) => { e.preventDefault(); router.push(`/admin/pages/${p.slug}/edit`); }, class: 'text-xs text-[#F59E0B] hover:underline' }, 'Edit'),
                                props.deletable ? h('button', { onClick: () => emit('delete', p), class: 'text-xs text-red-400 hover:underline' }, 'Delete') : null,
                            ]),
                        ])
                    )),
                ])
            ),
        ]);
    },
};

const load = async () => {
    try {
        const [pRes, lRes] = await Promise.all([
            apiFetch('/api/admin/pages'),
            apiFetch('/api/admin/locations'),
        ]);
        pages.value = await pRes.json();
        const locJson = await lRes.json();
        locations.value = Array.isArray(locJson) ? locJson : (locJson.data || []);
    } catch { toast.error('Failed to load pages'); }
    finally { loading.value = false; }
};

const onCreate = async () => {
    saving.value = true;
    try {
        const slug = newPage.value.slug.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-');
        const res = await apiFetch('/api/admin/pages', {
            method: 'POST',
            body: JSON.stringify({ slug, type: 'landing', title: newPage.value.title, location_id: newPage.value.location_id }),
        });
        if (!res.ok) throw new Error((await res.json()).message || 'Create failed');
        toast.success('Landing page created');
        creating.value = false;
        newPage.value = { slug: '', title: '', location_id: null };
        await load();
    } catch (e) { toast.error(e.message); }
    finally { saving.value = false; }
};

const onDelete = async (p) => {
    if (!confirm(`Delete landing page /${p.slug}? This cannot be undone.`)) return;
    try {
        const res = await apiFetch(`/api/admin/pages/${p.slug}`, { method: 'DELETE' });
        if (!res.ok) throw new Error((await res.json()).message || 'Delete failed');
        toast.success('Deleted');
        await load();
    } catch (e) { toast.error(e.message); }
};

onMounted(load);
</script>

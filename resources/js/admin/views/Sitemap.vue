<template>
    <div class="p-4 sm:p-6 max-w-5xl mx-auto">
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
            <h1 class="text-xl font-bold text-white">Sitemap</h1>
            <div class="flex flex-wrap gap-2">
                <a :href="data?.sitemap_url || '/sitemap.xml'" target="_blank" rel="noopener"
                   class="px-3 py-1.5 text-sm rounded-lg border border-[#2A2A2A] text-[#A0A0A0] hover:text-white hover:border-[#3A3A3A] transition">
                    Open sitemap.xml ↗
                </a>
                <button @click="checkLinks" :disabled="checking || loading"
                        class="px-3 py-1.5 text-sm rounded-lg bg-[#F59E0B] text-black font-medium hover:bg-[#FBBF24] transition disabled:opacity-50">
                    {{ checking ? 'Checking…' : 'Check links' }}
                </button>
            </div>
        </div>
        <p class="text-sm text-[#6B7280] mb-5">
            The sitemap is generated dynamically — it always reflects current content, no manual regeneration needed.
            Use “Check links” to verify every URL responds (200) and catch broken pages or unexpected redirects.
        </p>

        <div v-if="loading" class="text-sm text-[#A0A0A0]">Loading sitemap…</div>
        <div v-else-if="error" class="text-sm text-[#EF4444]">{{ error }}</div>

        <template v-else-if="data">
            <!-- Summary cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4">
                    <p class="text-xs uppercase tracking-wide text-[#A0A0A0]">Total URLs</p>
                    <p class="text-2xl font-bold text-white mt-1">{{ data.count }}</p>
                </div>
                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4">
                    <p class="text-xs uppercase tracking-wide text-[#A0A0A0]">OK (200)</p>
                    <p class="text-2xl font-bold mt-1" :class="checked ? 'text-[#10B981]' : 'text-[#6B7280]'">{{ checked ? okCount : '—' }}</p>
                </div>
                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4">
                    <p class="text-xs uppercase tracking-wide text-[#A0A0A0]">Redirects</p>
                    <p class="text-2xl font-bold mt-1" :class="checked ? 'text-[#F59E0B]' : 'text-[#6B7280]'">{{ checked ? check.redirect_count : '—' }}</p>
                </div>
                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4">
                    <p class="text-xs uppercase tracking-wide text-[#A0A0A0]">Broken</p>
                    <p class="text-2xl font-bold mt-1" :class="checked ? (check.broken_count ? 'text-[#EF4444]' : 'text-[#10B981]') : 'text-[#6B7280]'">{{ checked ? check.broken_count : '—' }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-2 mb-3 text-xs">
                <button v-for="f in filters" :key="f.key" @click="activeFilter = f.key"
                        :class="activeFilter === f.key ? 'bg-[#F59E0B] text-black' : 'bg-[#1A1A1A] text-[#A0A0A0] border border-[#2A2A2A]'"
                        class="px-3 py-1 rounded-full font-medium transition">
                    {{ f.label }} <span class="opacity-70">{{ countFor(f.key) }}</span>
                </button>
            </div>

            <!-- URL table -->
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-[#6B7280] text-[11px] uppercase border-b border-[#2A2A2A]">
                                <th class="text-left py-2 px-3">URL</th>
                                <th class="text-left py-2 px-2">Type</th>
                                <th class="text-center py-2 px-2">Lang</th>
                                <th class="text-right py-2 px-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="u in filteredUrls" :key="u.url" class="border-b border-[#2A2A2A] last:border-0 hover:bg-[#222]">
                                <td class="py-2 px-3 max-w-[420px]">
                                    <a :href="u.url" target="_blank" rel="noopener" class="text-[#A0A0A0] hover:text-white break-all">{{ shortPath(u.url) }}</a>
                                </td>
                                <td class="py-2 px-2"><span class="text-xs text-[#6B7280] capitalize">{{ u.type }}</span></td>
                                <td class="py-2 px-2 text-center"><span class="text-[10px] uppercase px-1.5 py-0.5 rounded" :class="u.locale === 'sr' ? 'bg-blue-500/20 text-blue-400' : 'bg-[#2A2A2A] text-[#A0A0A0]'">{{ u.locale }}</span></td>
                                <td class="py-2 px-3 text-right tabular-nums">
                                    <span v-if="!checked" class="text-[#6B7280]">—</span>
                                    <span v-else-if="statusFor(u.url) === null" class="text-[#6B7280]">…</span>
                                    <span v-else class="font-medium" :class="statusClass(statusFor(u.url))">{{ statusFor(u.url) || 'ERR' }}</span>
                                </td>
                            </tr>
                            <tr v-if="!filteredUrls.length"><td colspan="4" class="py-4 px-3 text-center text-[#6B7280] italic">No URLs.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';

const { apiFetch } = useAuth();

const loading = ref(true);
const checking = ref(false);
const checked = ref(false);
const error = ref(null);
const data = ref(null);
const check = ref({ results: [], broken_count: 0, redirect_count: 0 });
const activeFilter = ref('all');

const statusMap = computed(() => {
    const m = {};
    for (const r of (check.value.results || [])) m[r.url] = r.status;
    return m;
});
const statusFor = (url) => (url in statusMap.value ? statusMap.value[url] : null);
const okCount = computed(() => (check.value.results || []).filter(r => r.ok).length);

const filters = [
    { key: 'all', label: 'All' },
    { key: 'static', label: 'Static' },
    { key: 'landing', label: 'Landing' },
    { key: 'blog', label: 'Blog' },
    { key: 'location', label: 'Locations' },
    { key: 'near', label: 'Near' },
    { key: 'broken', label: 'Broken' },
];
const countFor = (key) => {
    const urls = data.value?.urls || [];
    if (key === 'all') return urls.length;
    if (key === 'broken') return (check.value.results || []).filter(r => r.broken).length;
    return urls.filter(u => u.type === key || (key === 'static' && u.type === 'home')).length;
};
const filteredUrls = computed(() => {
    const urls = data.value?.urls || [];
    if (activeFilter.value === 'all') return urls;
    if (activeFilter.value === 'broken') {
        const broken = new Set((check.value.results || []).filter(r => r.broken).map(r => r.url));
        return urls.filter(u => broken.has(u.url));
    }
    return urls.filter(u => u.type === activeFilter.value || (activeFilter.value === 'static' && u.type === 'home'));
});

const shortPath = (url) => { try { return new URL(url).pathname || '/'; } catch { return url; } };
const statusClass = (s) => {
    if (s >= 200 && s < 300) return 'text-[#10B981]';
    if (s >= 300 && s < 400) return 'text-[#F59E0B]';
    return 'text-[#EF4444]';
};

const load = async () => {
    loading.value = true; error.value = null;
    try {
        const res = await apiFetch('/api/admin/sitemap');
        if (!res.ok) throw new Error('HTTP ' + res.status);
        data.value = await res.json();
    } catch (e) { error.value = 'Could not load sitemap: ' + e.message; }
    finally { loading.value = false; }
};

const checkLinks = async () => {
    checking.value = true;
    try {
        const res = await apiFetch('/api/admin/sitemap/check');
        if (!res.ok) throw new Error('HTTP ' + res.status);
        check.value = await res.json();
        checked.value = true;
    } catch (e) { error.value = 'Link check failed: ' + e.message; }
    finally { checking.value = false; }
};

onMounted(load);
</script>

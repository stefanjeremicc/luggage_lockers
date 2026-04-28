<template>
    <div>
        <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
            <div>
                <h1 class="text-2xl font-bold">Pages &amp; SEO</h1>
                <p class="text-sm text-[#A0A0A0] mt-1">Edit titles, meta tags, and content for every public page. Bilingual (EN / SR).</p>
            </div>
        </div>

        <div v-if="loading" class="text-sm text-[#A0A0A0]">Loading…</div>

        <!-- Mobile: cards -->
        <div v-else class="md:hidden space-y-2">
            <router-link v-for="p in pages" :key="p.slug"
                :to="`/admin/pages/${p.slug}/edit`"
                class="bg-[#1A1A1A] border border-[#2A2A2A] active:bg-[#222] rounded-xl p-4 flex items-center gap-3 transition">
                <div class="min-w-0 flex-1">
                    <div class="font-semibold capitalize">{{ p.en?.title || p.sr?.title || p.slug }}</div>
                    <div class="font-mono text-xs text-[#6B7280] mt-0.5">/{{ p.slug }}</div>
                    <div class="text-xs text-[#A0A0A0] truncate mt-1">EN: {{ p.en?.meta_title || '—' }}</div>
                    <div class="text-xs text-[#A0A0A0] truncate">SR: {{ p.sr?.meta_title || '—' }}</div>
                </div>
                <div class="flex flex-col items-end gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-[10px]"
                        :class="(p.en?.is_published || p.sr?.is_published) ? 'bg-[#10B981]/20 text-[#10B981]' : 'bg-[#6B7280]/20 text-[#6B7280]'">
                        {{ (p.en?.is_published || p.sr?.is_published) ? 'Published' : 'Draft' }}
                    </span>
                    <svg class="w-4 h-4 text-[#6B7280]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
            </router-link>
        </div>

        <!-- Desktop: table -->
        <div v-if="!loading" class="hidden md:block bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-[#2A2A2A]">
                    <tr class="text-[#A0A0A0] text-left">
                        <th class="px-4 py-3 font-medium">Page</th>
                        <th class="px-4 py-3 font-medium">Slug</th>
                        <th class="px-4 py-3 font-medium">EN title</th>
                        <th class="px-4 py-3 font-medium">SR title</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in pages" :key="p.slug" class="border-b border-[#2A2A2A]/50 hover:bg-[#111]">
                        <td class="px-4 py-3 font-medium capitalize">{{ p.en?.title || p.sr?.title || p.slug }}</td>
                        <td class="px-4 py-3 text-[#A0A0A0] font-mono text-xs">{{ p.slug }}</td>
                        <td class="px-4 py-3 text-[#A0A0A0] truncate max-w-[260px]">{{ p.en?.meta_title || '—' }}</td>
                        <td class="px-4 py-3 text-[#A0A0A0] truncate max-w-[260px]">{{ p.sr?.meta_title || '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs"
                                :class="(p.en?.is_published || p.sr?.is_published) ? 'bg-[#10B981]/20 text-[#10B981]' : 'bg-[#6B7280]/20 text-[#6B7280]'">
                                {{ (p.en?.is_published || p.sr?.is_published) ? 'Published' : 'Draft' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <router-link :to="`/admin/pages/${p.slug}/edit`" class="text-xs text-[#F59E0B] hover:underline">Edit</router-link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';
import { useToast } from '../composables/useToast';

const { apiFetch } = useAuth();
const toast = useToast();
const pages = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const res = await apiFetch('/api/admin/pages');
        pages.value = await res.json();
    } catch { toast.error('Failed to load pages'); }
    finally { loading.value = false; }
});
</script>

<template>
    <div class="space-y-8">
        <!-- FAQs -->
        <section>
            <PageHeader title="FAQs" :subtitle="`${faqs.length} entries · drag to reorder`">
                <template #actions>
                    <Btn as="router-link" to="/admin/faqs/new" variant="primary">
                        <template #icon>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        </template>
                        New FAQ
                    </Btn>
                </template>
            </PageHeader>

            <div v-if="loading" class="text-sm text-[#A0A0A0]">Loading…</div>
            <div v-else-if="!faqs.length" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-8 text-center text-[#A0A0A0]">
                No FAQs yet.
            </div>
            <draggable
                v-else
                v-model="faqs"
                item-key="id"
                handle=".drag-handle"
                ghost-class="opacity-40"
                animation="150"
                :scroll="true"
                :bubble-scroll="true"
                :scroll-sensitivity="120"
                :scroll-speed="18"
                :force-fallback="true"
                class="space-y-3"
                @end="persistFaqOrder"
            >
                <template #item="{ element: faq }">
                    <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4 flex items-center gap-3 hover:border-[#3A3A3A] transition">
                        <button type="button"
                            class="drag-handle shrink-0 p-1.5 text-[#6B7280] hover:text-[#F59E0B] cursor-grab active:cursor-grabbing"
                            title="Drag to reorder">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"/>
                            </svg>
                        </button>

                        <div class="flex-1 min-w-0">
                            <div class="font-medium truncate">{{ faq.question || faq.question_sr || '(untitled)' }}</div>
                            <div class="text-xs text-[#A0A0A0] mt-1 flex items-center gap-3 flex-wrap">
                                <span>{{ faq.category?.name || 'Uncategorized' }}</span>
                                <span v-if="!faq.is_active" class="px-1.5 py-0.5 rounded bg-[#6B7280]/20 text-[#6B7280] uppercase text-[10px]">Inactive</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <IconBtn @click="toggleActive(faq)" :title="faq.is_active ? 'Deactivate' : 'Activate'">
                                <svg v-if="faq.is_active" class="w-4 h-4 text-[#10B981]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg v-else class="w-4 h-4 text-[#6B7280]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </IconBtn>
                            <IconBtn as="router-link" :to="`/admin/faqs/${faq.id}/edit`" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </IconBtn>
                            <IconBtn variant="danger" @click="deleteFaq(faq)" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                            </IconBtn>
                        </div>
                    </div>
                </template>
            </draggable>
        </section>

        <!-- Categories -->
        <section class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
            <div class="flex items-center justify-between mb-4 gap-3 flex-wrap">
                <div>
                    <h2 class="text-sm font-semibold text-[#F59E0B] uppercase tracking-wide">Categories</h2>
                    <p class="text-xs text-[#A0A0A0] mt-1">Group related FAQs. Drag to reorder.</p>
                </div>
                <Btn variant="primary" size="sm" @click="addCategory">+ Add category</Btn>
            </div>

            <div v-if="newCategory" class="mb-3 bg-[#111] border border-[#F59E0B]/40 rounded-lg p-3 grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-2">
                <input v-model="newCategory.name" placeholder="Name (English)" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded px-3 py-2 text-sm focus:border-[#F59E0B] focus:outline-none">
                <input v-model="newCategory.name_sr" placeholder="Naziv (Srpski)" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded px-3 py-2 text-sm focus:border-[#F59E0B] focus:outline-none">
                <div class="flex gap-2">
                    <Btn variant="secondary" size="sm" @click="newCategory = null">Cancel</Btn>
                    <Btn variant="primary" size="sm" @click="saveNewCategory">Save</Btn>
                </div>
            </div>

            <div v-if="!categories.length && !newCategory" class="text-sm text-[#A0A0A0] py-4 text-center">No categories yet.</div>

            <draggable
                v-else-if="categories.length"
                v-model="categories"
                item-key="id"
                handle=".cat-handle"
                ghost-class="opacity-40"
                animation="150"
                :scroll="true"
                :bubble-scroll="true"
                :scroll-sensitivity="120"
                :scroll-speed="18"
                :force-fallback="true"
                class="space-y-2"
                @end="persistCategoryOrder"
            >
                <template #item="{ element: cat }">
                    <div class="bg-[#111] border border-[#2A2A2A] rounded-lg p-3 flex items-center gap-3">
                        <button class="cat-handle shrink-0 p-1 text-[#6B7280] hover:text-[#F59E0B] cursor-grab active:cursor-grabbing" title="Drag to reorder">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"/></svg>
                        </button>
                        <template v-if="editingCatId === cat.id">
                            <input v-model="editCat.name" class="flex-1 bg-[#1A1A1A] border border-[#2A2A2A] rounded px-3 py-1.5 text-sm focus:border-[#F59E0B] focus:outline-none" placeholder="Name (English)">
                            <input v-model="editCat.name_sr" class="flex-1 bg-[#1A1A1A] border border-[#2A2A2A] rounded px-3 py-1.5 text-sm focus:border-[#F59E0B] focus:outline-none" placeholder="Naziv (Srpski)">
                            <Btn variant="secondary" size="sm" @click="editingCatId = null">Cancel</Btn>
                            <Btn variant="primary" size="sm" @click="saveEditCategory">Save</Btn>
                        </template>
                        <template v-else>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium truncate">{{ cat.name }}</div>
                                <div class="text-xs text-[#A0A0A0] truncate">
                                    <span v-if="cat.name_sr">{{ cat.name_sr }} · </span>{{ cat.faqs_count ?? 0 }} FAQs
                                </div>
                            </div>
                            <IconBtn @click="beginEditCategory(cat)" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </IconBtn>
                            <IconBtn variant="danger" @click="deleteCategory(cat)" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                            </IconBtn>
                        </template>
                    </div>
                </template>
            </draggable>
        </section>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import draggable from 'vuedraggable';
import { useAuth } from '../composables/useAuth';
import { useToast } from '../composables/useToast';
import { useConfirm } from '../composables/useConfirm';
import Btn from '../components/Btn.vue';
import IconBtn from '../components/IconBtn.vue';
import PageHeader from '../components/PageHeader.vue';

const { apiFetch } = useAuth();
const toast = useToast();
const confirmDialog = useConfirm();

const faqs = ref([]);
const categories = ref([]);
const loading = ref(true);

const newCategory = ref(null);
const editingCatId = ref(null);
const editCat = ref({ name: '', name_sr: '' });

const fetchAll = async () => {
    loading.value = true;
    try {
        const [faqsRes, catsRes] = await Promise.all([
            apiFetch('/api/admin/faqs'),
            apiFetch('/api/admin/faq-categories'),
        ]);
        faqs.value = await faqsRes.json();
        categories.value = await catsRes.json();
    } finally {
        loading.value = false;
    }
};

const toggleActive = async (faq) => {
    const next = !faq.is_active;
    try {
        const res = await apiFetch(`/api/admin/faqs/${faq.id}`, {
            method: 'PUT',
            body: JSON.stringify({ ...faq, is_active: next }),
        });
        if (!res.ok) throw new Error();
        faq.is_active = next;
        toast.success(next ? 'FAQ activated' : 'FAQ deactivated');
    } catch {
        toast.error('Failed to update');
    }
};

const deleteFaq = async (faq) => {
    const ok = await confirmDialog.ask({
        title: 'Delete FAQ?',
        message: `“${faq.question || faq.question_sr}” will be permanently removed.`,
        variant: 'danger',
    });
    if (!ok) return;
    await apiFetch(`/api/admin/faqs/${faq.id}`, { method: 'DELETE' });
    faqs.value = faqs.value.filter(f => f.id !== faq.id);
    toast.success('FAQ deleted');
};

const persistFaqOrder = async () => {
    const ids = faqs.value.map(f => f.id);
    try {
        const res = await apiFetch('/api/admin/faqs/reorder', {
            method: 'POST', body: JSON.stringify({ ids }),
        });
        if (!res.ok) throw new Error();
    } catch {
        toast.error('Failed to save order');
        fetchAll();
    }
};

const addCategory = () => {
    newCategory.value = { name: '', name_sr: '' };
};

const saveNewCategory = async () => {
    if (!newCategory.value.name?.trim()) {
        toast.error('Category name is required');
        return;
    }
    try {
        const res = await apiFetch('/api/admin/faq-categories', {
            method: 'POST', body: JSON.stringify(newCategory.value),
        });
        if (!res.ok) throw new Error();
        newCategory.value = null;
        await fetchAll();
        toast.success('Category created');
    } catch {
        toast.error('Failed to create category');
    }
};

const beginEditCategory = (cat) => {
    editingCatId.value = cat.id;
    editCat.value = { name: cat.name, name_sr: cat.name_sr || '' };
};

const saveEditCategory = async () => {
    try {
        const res = await apiFetch(`/api/admin/faq-categories/${editingCatId.value}`, {
            method: 'PUT', body: JSON.stringify(editCat.value),
        });
        if (!res.ok) throw new Error();
        editingCatId.value = null;
        await fetchAll();
        toast.success('Category updated');
    } catch {
        toast.error('Failed to update category');
    }
};

const deleteCategory = async (cat) => {
    const ok = await confirmDialog.ask({
        title: 'Delete category?',
        message: `“${cat.name}” will be deleted. FAQs in it will become uncategorized.`,
        variant: 'danger',
    });
    if (!ok) return;
    try {
        await apiFetch(`/api/admin/faq-categories/${cat.id}`, { method: 'DELETE' });
        await fetchAll();
        toast.success('Category deleted');
    } catch {
        toast.error('Failed to delete category');
    }
};

const persistCategoryOrder = async () => {
    const ids = categories.value.map(c => c.id);
    try {
        const res = await apiFetch('/api/admin/faq-categories/reorder', {
            method: 'POST', body: JSON.stringify({ ids }),
        });
        if (!res.ok) throw new Error();
    } catch {
        toast.error('Failed to save order');
        fetchAll();
    }
};

onMounted(fetchAll);
</script>

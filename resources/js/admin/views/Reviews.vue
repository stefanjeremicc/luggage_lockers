<template>
    <div>
        <PageHeader title="Reviews" :subtitle="subtitle">
            <template #actions>
                <Btn variant="primary" @click="openNew">
                    <template #icon>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </template>
                    New Review
                </Btn>
            </template>
        </PageHeader>

<div v-if="loading" class="text-sm text-[#A0A0A0]">Loading…</div>
        <div v-else-if="!reviews.length && editingId !== 'new'" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-8 text-center text-[#A0A0A0]">
            No reviews yet.
        </div>

        <div v-else class="space-y-3">
            <!-- "New" card when creating -->
            <div v-if="editingId === 'new'" class="bg-[#1A1A1A] border border-[#F59E0B]/40 rounded-xl p-5">
                <ReviewEditor :review="form" :saving="saving" @save="save" @cancel="cancelEdit" />
            </div>

            <draggable
                v-model="reviews"
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
                @end="persistOrder"
            >
                <template #item="{ element: review }">
                    <div>
                        <!-- Inline edit form replaces the card when editing this review -->
                        <div v-if="editingId === review.id" class="bg-[#1A1A1A] border border-[#F59E0B]/40 rounded-xl p-5">
                            <ReviewEditor :review="form" :saving="saving" @save="save" @cancel="cancelEdit" />
                        </div>

                        <div v-else class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4 hover:border-[#3A3A3A] transition flex items-center gap-3">
                            <!-- Drag handle -->
                            <button type="button"
                                class="drag-handle shrink-0 p-1.5 text-[#6B7280] hover:text-[#F59E0B] cursor-grab active:cursor-grabbing"
                                title="Drag to reorder">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"/>
                                </svg>
                            </button>

                            <!-- Main info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-medium truncate">{{ review.name }}</span>
                                    <StarRating :model-value="review.rating" size="sm" readonly />
                                    <span v-if="review.status === 'pending'" class="text-[10px] uppercase px-1.5 py-0.5 rounded bg-[#F59E0B]/20 text-[#F59E0B] tracking-wide font-semibold">Pending</span>
                                    <span v-else-if="review.status === 'rejected'" class="text-[10px] uppercase px-1.5 py-0.5 rounded bg-[#EF4444]/20 text-[#EF4444] tracking-wide">Rejected</span>
                                    <span v-else-if="!review.is_active" class="text-[10px] uppercase px-1.5 py-0.5 rounded bg-[#6B7280]/20 text-[#6B7280] tracking-wide">Inactive</span>
                                    <span v-if="review.source === 'site'" class="text-[10px] uppercase px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400 tracking-wide">From site</span>
                                </div>
                                <p class="text-sm text-[#A0A0A0] mt-1 line-clamp-1">{{ review.text }}</p>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-1 shrink-0">
                                <IconBtn v-if="review.status === 'pending'" variant="primary" @click="approve(review)" title="Approve">
                                    <svg class="w-4 h-4 text-[#10B981]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </IconBtn>
                                <IconBtn v-if="review.status === 'pending'" @click="reject(review)" title="Reject">
                                    <svg class="w-4 h-4 text-[#EF4444]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </IconBtn>
                                <IconBtn v-if="review.status !== 'pending'" @click="toggleActive(review)" :title="review.is_active ? 'Hide from site' : 'Show on site'">
                                    <svg v-if="review.is_active" class="w-4 h-4 text-[#10B981]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg v-else class="w-4 h-4 text-[#6B7280]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </IconBtn>
                                <IconBtn @click="openEdit(review)" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </IconBtn>
                                <IconBtn variant="danger" @click="remove(review)" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                                </IconBtn>
                            </div>
                        </div>
                    </div>
                </template>
            </draggable>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, h, defineComponent } from 'vue';
import draggable from 'vuedraggable';
import { useAuth } from '../composables/useAuth';
import { useToast } from '../composables/useToast';
import { useConfirm } from '../composables/useConfirm';
import Btn from '../components/Btn.vue';
import IconBtn from '../components/IconBtn.vue';
import PageHeader from '../components/PageHeader.vue';
import StarRating from '../components/StarRating.vue';
import Checkbox from '../components/Checkbox.vue';
import Field from '../components/FormField.vue';

const { apiFetch } = useAuth();
const toast = useToast();
const confirmDialog = useConfirm();

const reviews = ref([]);
const loading = ref(true);
const editingId = ref(null); // id | 'new' | null
const form = ref(blank());
const saving = ref(false);

const subtitle = computed(() => {
    const pending = reviews.value.filter(r => r.status === 'pending').length;
    if (pending > 0) return `${reviews.value.length} reviews · ${pending} pending review${pending > 1 ? 's' : ''}`;
    return `${reviews.value.length} reviews · drag rows to reorder`;
});

function blank() {
    return { id: null, name: '', text: '', text_sr: '', rating: 5, is_active: true, status: 'approved' };
}

const fetchReviews = async () => {
    loading.value = true;
    try {
        const res = await apiFetch('/api/admin/reviews');
        reviews.value = await res.json();
    } finally {
        loading.value = false;
    }
};

const openNew = () => {
    if (editingId.value !== null) cancelEdit();
    form.value = blank();
    editingId.value = 'new';
};

const openEdit = (review) => {
    form.value = { ...review };
    editingId.value = review.id;
};

const cancelEdit = () => {
    editingId.value = null;
    form.value = blank();
};

const save = async (payload) => {
    saving.value = true;
    try {
        const body = { ...payload, source: 'google' };
        const url = payload.id
            ? `/api/admin/reviews/${payload.id}`
            : '/api/admin/reviews';
        const res = await apiFetch(url, {
            method: payload.id ? 'PUT' : 'POST',
            body: JSON.stringify(body),
        });
        if (!res.ok) {
            const err = await res.json();
            throw new Error(err.message || 'Save failed');
        }
        toast.success(payload.id ? 'Review updated' : 'Review created');
        cancelEdit();
        await fetchReviews();
    } catch (e) {
        toast.error(e.message);
    } finally {
        saving.value = false;
    }
};

const approve = async (review) => {
    try {
        const res = await apiFetch(`/api/admin/reviews/${review.id}/approve`, { method: 'POST' });
        if (!res.ok) throw new Error();
        review.status = 'approved';
        review.is_active = true;
        toast.success('Review approved');
    } catch {
        toast.error('Failed to approve');
    }
};

const reject = async (review) => {
    const ok = await confirmDialog.ask({
        title: 'Reject review?',
        message: `"${review.text.slice(0, 80)}..." will be marked as rejected and hidden from the site.`,
        variant: 'warning',
        confirmText: 'Reject',
    });
    if (!ok) return;
    try {
        const res = await apiFetch(`/api/admin/reviews/${review.id}/reject`, { method: 'POST' });
        if (!res.ok) throw new Error();
        review.status = 'rejected';
        review.is_active = false;
        toast.success('Review rejected');
    } catch {
        toast.error('Failed to reject');
    }
};

const toggleActive = async (review) => {
    const next = !review.is_active;
    try {
        const res = await apiFetch(`/api/admin/reviews/${review.id}`, {
            method: 'PUT',
            body: JSON.stringify({ ...review, is_active: next, source: review.source || 'google' }),
        });
        if (!res.ok) throw new Error();
        review.is_active = next;
        toast.success(next ? 'Review activated' : 'Review deactivated');
    } catch {
        toast.error('Failed to update');
    }
};

const remove = async (review) => {
    const ok = await confirmDialog.ask({
        title: 'Delete review?',
        message: `“${review.text.slice(0, 80)}${review.text.length > 80 ? '…' : ''}” by ${review.name} will be permanently removed.`,
        variant: 'danger',
        confirmText: 'Delete',
    });
    if (!ok) return;
    try {
        await apiFetch(`/api/admin/reviews/${review.id}`, { method: 'DELETE' });
        reviews.value = reviews.value.filter(r => r.id !== review.id);
        toast.success('Review deleted');
    } catch (e) {
        toast.error('Failed to delete');
    }
};

const persistOrder = async () => {
    const ids = reviews.value.map(r => r.id);
    try {
        const res = await apiFetch('/api/admin/reviews/reorder', {
            method: 'POST',
            body: JSON.stringify({ ids }),
        });
        if (!res.ok) throw new Error('reorder failed');
    } catch {
        toast.error('Failed to save order');
        fetchReviews();
    }
};

// Inline editor component
const ReviewEditor = defineComponent({
    props: { review: { type: Object, required: true }, saving: { type: Boolean, default: false } },
    emits: ['save', 'cancel'],
    setup(props, { emit }) {
        const local = ref({ ...props.review });
        const errors = ref({});

        const validate = () => {
            errors.value = {};
            if (!local.value.name?.trim()) errors.value.name = 'Reviewer name is required';
            if (!local.value.text?.trim() || local.value.text.length < 5) errors.value.text = 'Review text is too short';
            if (!local.value.rating || local.value.rating < 1 || local.value.rating > 5) errors.value.rating = 'Pick a rating';
            return Object.keys(errors.value).length === 0;
        };

        const onSave = () => {
            if (!validate()) return;
            emit('save', { ...local.value });
        };

        return () => h('div', { class: 'space-y-4' }, [
            h('h3', { class: 'text-sm font-semibold text-[#F59E0B] uppercase tracking-wide' },
                local.value.id ? 'Edit review' : 'New review'),
            h(Field, { label: 'Reviewer name', required: true, error: errors.value.name }, () =>
                h('input', {
                    value: local.value.name,
                    onInput: e => (local.value.name = e.target.value),
                    class: 'w-full bg-[#111] border rounded-lg px-4 py-2.5 text-white focus:outline-none h-[42px] ' +
                        (errors.value.name ? 'border-[#EF4444]' : 'border-[#2A2A2A] focus:border-[#F59E0B]'),
                })),
            h(Field, { label: 'Review text (English)', required: true, error: errors.value.text }, () =>
                h('textarea', {
                    value: local.value.text,
                    onInput: e => (local.value.text = e.target.value),
                    rows: 3,
                    class: 'w-full bg-[#111] border rounded-lg px-4 py-2.5 text-white focus:outline-none ' +
                        (errors.value.text ? 'border-[#EF4444]' : 'border-[#2A2A2A] focus:border-[#F59E0B]'),
                })),
            h(Field, { label: 'Review text (Serbian)' }, () =>
                h('textarea', {
                    value: local.value.text_sr || '',
                    onInput: e => (local.value.text_sr = e.target.value),
                    rows: 3,
                    placeholder: 'Prevod na srpski (opciono)',
                    class: 'w-full bg-[#111] border border-[#2A2A2A] focus:border-[#F59E0B] rounded-lg px-4 py-2.5 text-white focus:outline-none',
                })),
            h(Field, { label: 'Rating', required: true, error: errors.value.rating }, () =>
                h(StarRating, {
                    modelValue: local.value.rating,
                    'onUpdate:modelValue': v => (local.value.rating = v),
                    size: 'md',
                })),
            h(Checkbox, {
                modelValue: local.value.is_active,
                'onUpdate:modelValue': v => (local.value.is_active = v),
                label: 'Active',
                description: 'When off, this review is hidden from the homepage.',
            }),
            h('div', { class: 'flex justify-end gap-2 pt-4 border-t border-[#2A2A2A]' }, [
                h(Btn, { variant: 'secondary', onClick: () => emit('cancel') }, () => 'Cancel'),
                h(Btn, { variant: 'primary', loading: props.saving, onClick: onSave },
                    () => local.value.id ? 'Save changes' : 'Create review'),
            ]),
        ]);
    },
});

onMounted(fetchReviews);
</script>

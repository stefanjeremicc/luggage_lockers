<template>
    <div class="max-w-3xl mx-auto">
        <EditPageHeader
            :title="isNew ? 'New FAQ' : 'Edit FAQ'"
            back-to="/admin/faqs"
            back-label="Back to FAQs"
            :saving="saving"
            :show-delete="!isNew"
            @save="save"
            @delete="confirmDelete"
        />

        <div v-if="loading" class="text-sm text-[#A0A0A0]">Loading…</div>

        <form v-else @submit.prevent="save" class="space-y-5">
            <!-- Language tabs -->
            <div class="flex border-b border-[#2A2A2A]">
                <button type="button" v-for="t in tabs" :key="t.value"
                    @click="activeTab = t.value"
                    class="px-4 py-2.5 text-sm font-medium transition border-b-2 -mb-px"
                    :class="activeTab === t.value
                        ? 'border-[#F59E0B] text-[#F59E0B]'
                        : 'border-transparent text-[#A0A0A0] hover:text-white'">
                    {{ t.label }}
                </button>
            </div>

            <!-- English -->
            <div v-show="activeTab === 'en'" class="space-y-4">
                <Field label="Question (English)" required :error="errors.question">
                    <input v-model="form.question"
                        class="w-full bg-[#111] border rounded-lg px-4 py-2.5 text-white focus:outline-none"
                        :class="errors.question ? 'border-[#EF4444]' : 'border-[#2A2A2A] focus:border-[#F59E0B]'">
                </Field>
                <Field label="Answer (English)" required :error="errors.answer">
                    <RichEditor v-model="form.answer" placeholder="Write the answer…" min-height="180px" />
                </Field>
            </div>

            <!-- Serbian -->
            <div v-show="activeTab === 'sr'" class="space-y-4">
                <Field label="Question (Srpski)">
                    <input v-model="form.question_sr"
                        class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none">
                </Field>
                <Field label="Answer (Srpski)">
                    <RichEditor v-model="form.answer_sr" placeholder="Napišite odgovor…" min-height="180px" />
                </Field>
            </div>

            <!-- Meta -->
            <Field label="Category">
                <Select v-model="form.faq_category_id" :options="categoryOptions" placeholder="Uncategorized" />
            </Field>

            <Checkbox
                v-model="form.is_active"
                label="Active"
                description="When off, this FAQ is hidden from the public site."
            />
        </form>
    </div>
</template>
<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuth } from '../composables/useAuth';
import { useToast } from '../composables/useToast';
import { useConfirm } from '../composables/useConfirm';
import RichEditor from '../components/RichEditor.vue';
import Field from '../components/FormField.vue';
import Select from '../components/Select.vue';
import Checkbox from '../components/Checkbox.vue';
import EditPageHeader from '../components/EditPageHeader.vue';

const tabs = [
    { value: 'en', label: 'English' },
    { value: 'sr', label: 'Srpski' },
];

const route = useRoute();
const router = useRouter();
const { apiFetch } = useAuth();
const toast = useToast();
const confirmDialog = useConfirm();

const isNew = computed(() => !route.params.id);
const loading = ref(true);
const saving = ref(false);
const activeTab = ref('en');
const errors = ref({});

const form = ref({
    question: '', question_sr: '',
    answer: '', answer_sr: '',
    faq_category_id: null,
    is_active: true,
});

const categories = ref([]);
const categoryOptions = computed(() => [
    { value: null, label: 'Uncategorized' },
    ...categories.value.map(c => ({ value: c.id, label: c.name })),
]);

const validate = () => {
    errors.value = {};
    if (!form.value.question?.trim()) errors.value.question = 'English question is required';
    if (!form.value.answer?.trim()) errors.value.answer = 'English answer is required';
    return Object.keys(errors.value).length === 0;
};

const save = async () => {
    if (!validate()) {
        activeTab.value = 'en';
        toast.error('Please fix errors before saving');
        return;
    }
    saving.value = true;
    try {
        const payload = { ...form.value };
        if (isNew.value) {
            const res = await apiFetch('/api/admin/faqs', {
                method: 'POST', body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Save failed');
            toast.success('FAQ created');
            router.push('/admin/faqs');
            return;
        } else {
            const res = await apiFetch(`/api/admin/faqs/${route.params.id}`, {
                method: 'PUT', body: JSON.stringify(payload),
            });
            if (!res.ok) {
                const err = await res.json();
                throw new Error(err.message || 'Save failed');
            }
            toast.success('FAQ saved');
        }
    } catch (e) {
        toast.error(e.message);
    } finally {
        saving.value = false;
    }
};

const confirmDelete = async () => {
    const ok = await confirmDialog.ask({
        title: 'Delete FAQ?',
        message: 'This FAQ will be permanently removed.',
        variant: 'danger',
        confirmText: 'Delete',
    });
    if (!ok) return;
    try {
        await apiFetch(`/api/admin/faqs/${route.params.id}`, { method: 'DELETE' });
        toast.success('FAQ deleted');
        router.push('/admin/faqs');
    } catch (e) {
        toast.error('Failed to delete');
    }
};

onMounted(async () => {
    try {
        const catRes = await apiFetch('/api/admin/faq-categories');
        categories.value = await catRes.json();

        if (isNew.value) return;
        const res = await apiFetch(`/api/admin/faqs/${route.params.id}`);
        const data = await res.json();
        form.value = {
            question: data.question || '',
            question_sr: data.question_sr || '',
            answer: data.answer || '',
            answer_sr: data.answer_sr || '',
            faq_category_id: data.faq_category_id ?? null,
            is_active: data.is_active ?? true,
        };
    } finally {
        loading.value = false;
    }
});
</script>

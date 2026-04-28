<template>
    <div class="max-w-4xl mx-auto">
        <EditPageHeader
            :title="isNew ? 'New Blog Post' : 'Edit Blog Post'"
            back-to="/admin/blog"
            back-label="Back to posts"
            :saving="saving"
            :show-delete="!isNew"
            @save="save"
            @delete="confirmDelete"
        />

        <div v-if="loading" class="text-sm text-[#A0A0A0]">Loading…</div>

        <form v-else @submit.prevent="save" class="space-y-6">
            <!-- Shared: image (right, square), category + author (left) -->
            <section class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-6 items-start">
                    <div class="space-y-4">
                        <Field label="Category">
                            <Select v-model="form.blog_category_id" :options="categoryOptions" placeholder="Uncategorized" />
                        </Field>
                        <Field label="Author name">
                            <input v-model="form.author_name"
                                class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none">
                        </Field>
                    </div>
                    <Field label="Featured image" required :error="errors.featured_image">
                        <div class="w-[220px] h-[220px]">
                            <ImageUploader v-model="form.featured_image" folder="blog" square />
                        </div>
                    </Field>
                </div>
            </section>

            <!-- Language tabs -->
            <div class="flex border-b border-[#2A2A2A]">
                <button type="button" v-for="t in tabs" :key="t.value"
                    @click="activeTab = t.value"
                    class="px-4 py-2.5 text-sm font-medium transition border-b-2 -mb-px"
                    :class="activeTab === t.value ? 'border-[#F59E0B] text-[#F59E0B]' : 'border-transparent text-[#A0A0A0] hover:text-white'">
                    {{ t.label }}
                </button>
            </div>

            <!-- English -->
            <section v-show="activeTab === 'en'" class="space-y-4">
                <Field label="Title (English)" required :error="errors.title">
                    <input v-model="form.title" @input="autoSlug"
                        class="w-full bg-[#111] border rounded-lg px-4 py-2.5 text-white focus:outline-none"
                        :class="errors.title ? 'border-[#EF4444]' : 'border-[#2A2A2A] focus:border-[#F59E0B]'">
                </Field>
                <Field label="Slug (English)" required :error="errors.slug" hint="URL: /blog/{slug}">
                    <input v-model="form.slug" @input="slugTouched = true"
                        class="w-full bg-[#111] border rounded-lg px-4 py-2.5 text-white font-mono text-sm focus:outline-none"
                        :class="errors.slug ? 'border-[#EF4444]' : 'border-[#2A2A2A] focus:border-[#F59E0B]'">
                </Field>
                <Field label="Excerpt (English)" required :error="errors.excerpt">
                    <textarea v-model="form.excerpt" rows="3"
                        class="w-full bg-[#111] border rounded-lg px-4 py-2.5 text-white focus:outline-none"
                        :class="errors.excerpt ? 'border-[#EF4444]' : 'border-[#2A2A2A] focus:border-[#F59E0B]'"></textarea>
                </Field>
                <Field label="Content (English)" required :error="errors.content">
                    <RichEditor v-model="form.content" placeholder="Write the body of your post…" />
                </Field>

                <!-- SEO (always visible, required) -->
                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 space-y-4">
                    <h3 class="text-sm font-semibold text-[#F59E0B] uppercase tracking-wide">SEO meta (English)</h3>
                    <Field label="Meta title" required :error="errors.meta_title">
                        <input v-model="form.meta_title"
                            class="w-full bg-[#111] border rounded-lg px-4 py-2.5 text-white focus:outline-none"
                            :class="errors.meta_title ? 'border-[#EF4444]' : 'border-[#2A2A2A] focus:border-[#F59E0B]'">
                    </Field>
                    <Field label="Meta description" required :error="errors.meta_description">
                        <textarea v-model="form.meta_description" rows="2"
                            class="w-full bg-[#111] border rounded-lg px-4 py-2.5 text-white focus:outline-none"
                            :class="errors.meta_description ? 'border-[#EF4444]' : 'border-[#2A2A2A] focus:border-[#F59E0B]'"></textarea>
                    </Field>
                </div>
            </section>

            <!-- Serbian -->
            <section v-show="activeTab === 'sr'" class="space-y-4">
                <Field label="Naslov (Srpski)">
                    <input v-model="form.title_sr" @input="autoSlugSr"
                        class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none">
                </Field>
                <Field label="Slug (Srpski)" :error="errors.slug_sr" hint="URL: /sr/blog/{slug-sr} — ostavite prazno da koristi engleski slug.">
                    <input v-model="form.slug_sr" @input="slugSrTouched = true"
                        class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white font-mono text-sm focus:border-[#F59E0B] focus:outline-none">
                </Field>
                <Field label="Kratak opis (Srpski)">
                    <textarea v-model="form.excerpt_sr" rows="3"
                        class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none"></textarea>
                </Field>
                <Field label="Sadržaj (Srpski)">
                    <RichEditor v-model="form.content_sr" placeholder="Napišite sadržaj posta…" />
                </Field>

                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 space-y-4">
                    <h3 class="text-sm font-semibold text-[#F59E0B] uppercase tracking-wide">SEO meta (Srpski)</h3>
                    <Field label="Meta naslov">
                        <input v-model="form.meta_title_sr"
                            class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none">
                    </Field>
                    <Field label="Meta opis">
                        <textarea v-model="form.meta_description_sr" rows="2"
                            class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white focus:border-[#F59E0B] focus:outline-none"></textarea>
                    </Field>
                </div>
            </section>

            <!-- Publish -->
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4 space-y-3">
                <Checkbox
                    v-model="form.is_published"
                    label="Published"
                    description="When checked, this post is visible on the public site."
                />
                <Checkbox
                    v-model="form.is_featured"
                    label="Featured on home"
                    description="Featured posts appear in the 'From the Blog' section on the homepage (top 3 newest featured)."
                />
            </div>
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
import ImageUploader from '../components/ImageUploader.vue';

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
    slug: '', slug_sr: '', title: '', title_sr: '',
    excerpt: '', excerpt_sr: '',
    content: '', content_sr: '',
    featured_image: '',
    blog_category_id: null,
    author_name: '',
    meta_title: '', meta_title_sr: '',
    meta_description: '', meta_description_sr: '',
    is_published: false,
    is_featured: false,
});

const categories = ref([]);
const categoryOptions = computed(() => [
    { value: null, label: 'Uncategorized' },
    ...categories.value.map(c => ({ value: c.id, label: c.name })),
]);

let slugTouched = false;
let slugSrTouched = false;
const slugify = (s) => s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-').slice(0, 80);
const autoSlug = () => {
    if (!slugTouched) form.value.slug = slugify(form.value.title);
};
const autoSlugSr = () => {
    if (!slugSrTouched) form.value.slug_sr = slugify(form.value.title_sr);
};

const validate = () => {
    errors.value = {};
    if (!form.value.slug?.trim()) errors.value.slug = 'Slug is required';
    if (!form.value.featured_image) errors.value.featured_image = 'Featured image is required';
    if (!form.value.title?.trim()) errors.value.title = 'English title is required';
    if (!form.value.excerpt?.trim()) errors.value.excerpt = 'English excerpt is required';
    if (!form.value.content?.trim()) errors.value.content = 'English content is required';
    if (!form.value.meta_title?.trim()) errors.value.meta_title = 'Meta title is required';
    if (!form.value.meta_description?.trim()) errors.value.meta_description = 'Meta description is required';
    return Object.keys(errors.value).length === 0;
};

const save = async () => {
    if (!validate()) {
        activeTab.value = 'en';
        toast.error('Please fill in all required fields');
        return;
    }
    saving.value = true;
    try {
        const payload = { ...form.value };
        if (isNew.value) {
            const res = await apiFetch('/api/admin/blog-posts', { method: 'POST', body: JSON.stringify(payload) });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Save failed');
            toast.success('Post created');
            router.replace(`/admin/blog/${data.id}/edit`);
        } else {
            const res = await apiFetch(`/api/admin/blog-posts/${route.params.id}`, { method: 'PUT', body: JSON.stringify(payload) });
            if (!res.ok) {
                const err = await res.json();
                throw new Error(err.message || 'Save failed');
            }
            toast.success('Post saved');
        }
    } catch (e) {
        toast.error(e.message);
    } finally {
        saving.value = false;
    }
};

const confirmDelete = async () => {
    const ok = await confirmDialog.ask({
        title: 'Delete blog post?',
        message: `"${form.value.title}" will be permanently removed.`,
        variant: 'danger',
        confirmText: 'Delete',
    });
    if (!ok) return;
    try {
        await apiFetch(`/api/admin/blog-posts/${route.params.id}`, { method: 'DELETE' });
        toast.success('Post deleted');
        router.push('/admin/blog');
    } catch (e) {
        toast.error('Failed to delete');
    }
};

onMounted(async () => {
    try {
        const catRes = await apiFetch('/api/admin/blog-categories');
        categories.value = await catRes.json();

        if (isNew.value) return;
        const res = await apiFetch(`/api/admin/blog-posts/${route.params.id}`);
        const data = await res.json();
        form.value = {
            slug: data.slug || '', slug_sr: data.slug_sr || '',
            title: data.title || '', title_sr: data.title_sr || '',
            excerpt: data.excerpt || '', excerpt_sr: data.excerpt_sr || '',
            content: data.content || '', content_sr: data.content_sr || '',
            featured_image: data.featured_image || '',
            blog_category_id: data.blog_category_id ?? null,
            author_name: data.author_name || '',
            meta_title: data.meta_title || '', meta_title_sr: data.meta_title_sr || '',
            meta_description: data.meta_description || '', meta_description_sr: data.meta_description_sr || '',
            is_published: !!data.is_published,
            is_featured: !!data.is_featured,
        };
        slugTouched = !!data.slug;
        slugSrTouched = !!data.slug_sr;
    } finally {
        loading.value = false;
    }
});
</script>

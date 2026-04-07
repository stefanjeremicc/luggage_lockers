<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Blog Posts</h1>
            <button @click="showForm = !showForm" class="bg-[#F59E0B] text-black px-4 py-2 rounded-lg text-sm font-semibold hover:bg-[#D97706]">+ New Post</button>
        </div>

        <!-- Form -->
        <div v-if="showForm" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-6 mb-6">
            <div class="grid grid-cols-2 gap-4">
                <input v-model="form.title" placeholder="Title" class="bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2 text-white">
                <input v-model="form.slug" placeholder="Slug" class="bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2 text-white">
                <input v-model="form.category" placeholder="Category" class="bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2 text-white">
                <select v-model="form.locale" class="bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2 text-white">
                    <option value="en">English</option>
                    <option value="sr">Serbian</option>
                </select>
            </div>
            <textarea v-model="form.excerpt" placeholder="Excerpt" rows="2" class="w-full mt-4 bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2 text-white"></textarea>
            <textarea v-model="form.content" placeholder="Content (HTML)" rows="8" class="w-full mt-4 bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2 text-white font-mono text-sm"></textarea>
            <div class="flex gap-3 mt-4">
                <button @click="savePost" class="bg-[#F59E0B] text-black px-6 py-2 rounded-lg text-sm font-semibold">Save</button>
                <label class="flex items-center gap-2 text-sm text-[#A0A0A0]"><input type="checkbox" v-model="form.is_published"> Published</label>
            </div>
        </div>

        <!-- List -->
        <div class="space-y-3">
            <div v-for="post in posts" :key="post.id" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4 flex items-center justify-between">
                <div>
                    <div class="font-medium">{{ post.title }}</div>
                    <div class="text-xs text-[#A0A0A0] mt-1">{{ post.slug }} | {{ post.locale }} | {{ post.category || 'No category' }}</div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs" :class="post.is_published ? 'text-[#10B981]' : 'text-[#A0A0A0]'">{{ post.is_published ? 'Published' : 'Draft' }}</span>
                    <button @click="deletePost(post.id)" class="text-xs text-[#EF4444] hover:underline">Delete</button>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';

const { apiFetch } = useAuth();
const posts = ref([]);
const showForm = ref(false);
const form = ref({ title: '', slug: '', locale: 'en', excerpt: '', content: '', category: '', is_published: false });

const fetchPosts = async () => { const res = await apiFetch('/api/admin/blog-posts'); const data = await res.json(); posts.value = data.data || data; };
const savePost = async () => {
    await apiFetch('/api/admin/blog-posts', { method: 'POST', body: JSON.stringify(form.value) });
    form.value = { title: '', slug: '', locale: 'en', excerpt: '', content: '', category: '', is_published: false };
    showForm.value = false;
    fetchPosts();
};
const deletePost = async (id) => { if (!confirm('Delete?')) return; await apiFetch(`/api/admin/blog-posts/${id}`, { method: 'DELETE' }); fetchPosts(); };

onMounted(fetchPosts);
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">FAQs</h1>
            <button @click="showForm = !showForm" class="bg-[#F59E0B] text-black px-4 py-2 rounded-lg text-sm font-semibold hover:bg-[#D97706]">+ New FAQ</button>
        </div>

        <div v-if="showForm" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-6 mb-6">
            <input v-model="form.question" placeholder="Question" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2 text-white mb-3">
            <textarea v-model="form.answer" placeholder="Answer" rows="4" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2 text-white mb-3"></textarea>
            <div class="flex gap-3">
                <input v-model="form.category" placeholder="Category" class="bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2 text-white">
                <select v-model="form.locale" class="bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2 text-white">
                    <option value="en">EN</option><option value="sr">SR</option>
                </select>
                <button @click="saveFaq" class="bg-[#F59E0B] text-black px-6 py-2 rounded-lg text-sm font-semibold">Save</button>
            </div>
        </div>

        <div class="space-y-3">
            <div v-for="faq in faqs" :key="faq.id" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4 flex items-center justify-between">
                <div>
                    <div class="font-medium">{{ faq.question }}</div>
                    <div class="text-xs text-[#A0A0A0] mt-1">{{ faq.category || 'General' }} | {{ faq.locale }}</div>
                </div>
                <button @click="deleteFaq(faq.id)" class="text-xs text-[#EF4444] hover:underline">Delete</button>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';

const { apiFetch } = useAuth();
const faqs = ref([]);
const showForm = ref(false);
const form = ref({ question: '', answer: '', category: '', locale: 'en' });

const fetchFaqs = async () => { const res = await apiFetch('/api/admin/faqs'); faqs.value = await res.json(); };
const saveFaq = async () => {
    await apiFetch('/api/admin/faqs', { method: 'POST', body: JSON.stringify(form.value) });
    form.value = { question: '', answer: '', category: '', locale: 'en' };
    showForm.value = false;
    fetchFaqs();
};
const deleteFaq = async (id) => { if (!confirm('Delete?')) return; await apiFetch(`/api/admin/faqs/${id}`, { method: 'DELETE' }); fetchFaqs(); };

onMounted(fetchFaqs);
</script>

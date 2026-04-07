<template>
    <div>
        <h1 class="text-2xl font-bold mb-6">Settings</h1>
        <div v-for="(group, groupName) in settings" :key="groupName" class="mb-6">
            <h2 class="text-lg font-semibold mb-3 text-[#F59E0B] capitalize">{{ groupName }}</h2>
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-6 space-y-4">
                <div v-for="(value, key) in group" :key="key" class="flex items-center justify-between">
                    <label class="text-sm text-[#A0A0A0]">{{ key }}</label>
                    <input v-model="settings[groupName][key]" class="bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2 text-white text-right w-64 focus:border-[#F59E0B] focus:outline-none text-sm">
                </div>
            </div>
        </div>
        <button @click="save" :disabled="saving" class="bg-[#F59E0B] text-black px-8 py-3 rounded-lg font-semibold hover:bg-[#D97706]">
            {{ saving ? 'Saving...' : 'Save Settings' }}
        </button>
        <span v-if="saved" class="ml-3 text-[#10B981] text-sm">Saved!</span>
    </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';

const { apiFetch } = useAuth();
const settings = ref({});
const saving = ref(false);
const saved = ref(false);

const save = async () => {
    saving.value = true;
    const flat = {};
    Object.values(settings.value).forEach(group => Object.assign(flat, group));
    await apiFetch('/api/admin/settings', { method: 'PUT', body: JSON.stringify({ settings: flat }) });
    saving.value = false;
    saved.value = true;
    setTimeout(() => saved.value = false, 2000);
};

onMounted(async () => {
    const res = await apiFetch('/api/admin/settings');
    settings.value = await res.json();
});
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold mb-6">Pricing Rules</h1>
        <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="border-b border-[#2A2A2A]">
                    <tr class="text-[#A0A0A0] text-left">
                        <th class="px-4 py-3">Location</th>
                        <th class="px-4 py-3">Size</th>
                        <th class="px-4 py-3">Duration</th>
                        <th class="px-4 py-3">Price (EUR)</th>
                        <th class="px-4 py-3">Active</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in rules" :key="r.id" class="border-b border-[#2A2A2A]/50">
                        <td class="px-4 py-3">{{ r.location?.name || 'Global' }}</td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs" :class="r.locker_size === 'large' ? 'bg-purple-500/20 text-purple-400' : 'bg-blue-500/20 text-blue-400'">{{ r.locker_size }}</span></td>
                        <td class="px-4 py-3">{{ r.duration_key }}</td>
                        <td class="px-4 py-3">
                            <input v-model.number="r.price_eur" @change="updatePrice(r)" type="number" step="0.01" min="0"
                                class="bg-[#111] border border-[#2A2A2A] rounded px-3 py-1 w-24 text-white text-right focus:border-[#F59E0B] focus:outline-none">
                        </td>
                        <td class="px-4 py-3">
                            <button @click="r.is_active = !r.is_active; updatePrice(r)" class="w-4 h-4 rounded-full" :class="r.is_active ? 'bg-[#10B981]' : 'bg-[#6B7280]'"></button>
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

const { apiFetch } = useAuth();
const rules = ref([]);

const updatePrice = async (r) => {
    await apiFetch(`/api/admin/pricing-rules/${r.id}`, { method: 'PUT', body: JSON.stringify({ price_eur: r.price_eur, is_active: r.is_active }) });
};

onMounted(async () => {
    const res = await apiFetch('/api/admin/pricing-rules');
    rules.value = await res.json();
});
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Bookings</h1>
            <input v-model="search" @input="fetchBookings" placeholder="Search..." class="bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2 text-sm text-white focus:border-[#F59E0B] focus:outline-none w-64">
        </div>

        <div class="flex gap-2 mb-4 flex-wrap">
            <button v-for="s in ['all','pending','confirmed','active','completed','cancelled','expired']" :key="s"
                @click="statusFilter = s; fetchBookings()"
                class="px-3 py-1.5 rounded-full text-xs border transition"
                :class="statusFilter === s ? 'bg-[#F59E0B] text-black border-[#F59E0B]' : 'border-[#2A2A2A] text-[#A0A0A0] hover:border-[#F59E0B]'">
                {{ s === 'all' ? 'All' : s.charAt(0).toUpperCase() + s.slice(1) }}
            </button>
        </div>

        <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="border-b border-[#2A2A2A]">
                    <tr class="text-[#A0A0A0] text-left">
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Location</th>
                        <th class="px-4 py-3">Check-in</th>
                        <th class="px-4 py-3">Size</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Payment</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="b in bookings" :key="b.id" class="border-b border-[#2A2A2A]/50 hover:bg-[#111]">
                        <td class="px-4 py-3">
                            <div>{{ b.customer?.full_name }}</div>
                            <div class="text-xs text-[#A0A0A0]">{{ b.customer?.email }}</div>
                        </td>
                        <td class="px-4 py-3">{{ b.location?.name }}</td>
                        <td class="px-4 py-3 text-[#A0A0A0]">{{ formatDate(b.check_in) }}</td>
                        <td class="px-4 py-3">{{ b.locker_qty }}x {{ b.locker_size }}</td>
                        <td class="px-4 py-3 text-[#F59E0B] font-medium">&euro;{{ Number(b.total_eur).toFixed(2) }}</td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs" :class="statusClass(b.booking_status)">{{ b.booking_status }}</span></td>
                        <td class="px-4 py-3"><span class="text-xs" :class="b.payment_status === 'paid' ? 'text-[#10B981]' : 'text-[#A0A0A0]'">{{ b.payment_status }}</span></td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <button v-if="b.payment_status !== 'paid'" @click="markPaid(b.id)" class="text-xs text-[#10B981] hover:underline">Paid</button>
                                <button v-if="['confirmed','active'].includes(b.booking_status)" @click="cancelBooking(b.id)" class="text-xs text-[#EF4444] hover:underline">Cancel</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-if="pagination?.last_page > 1" class="flex justify-center gap-2 mt-4">
            <button v-for="p in pagination.last_page" :key="p" @click="page = p; fetchBookings()"
                class="w-8 h-8 rounded text-xs" :class="page === p ? 'bg-[#F59E0B] text-black' : 'bg-[#1A1A1A] text-[#A0A0A0]'">{{ p }}</button>
        </div>
    </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';

const { apiFetch } = useAuth();
const bookings = ref([]);
const pagination = ref(null);
const search = ref('');
const statusFilter = ref('all');
const page = ref(1);

const fetchBookings = async () => {
    let url = `/api/admin/bookings?page=${page.value}`;
    if (statusFilter.value !== 'all') url += `&status=${statusFilter.value}`;
    if (search.value) url += `&search=${search.value}`;
    const res = await apiFetch(url);
    const data = await res.json();
    bookings.value = data.data;
    pagination.value = data;
};

const markPaid = async (id) => {
    await apiFetch(`/api/admin/bookings/${id}/mark-paid`, { method: 'POST' });
    fetchBookings();
};

const cancelBooking = async (id) => {
    if (!confirm('Cancel this booking?')) return;
    await apiFetch(`/api/admin/bookings/${id}`, { method: 'DELETE' });
    fetchBookings();
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';

const statusClass = (s) => ({
    confirmed: 'bg-[#10B981]/20 text-[#10B981]',
    active: 'bg-blue-500/20 text-blue-400',
    pending: 'bg-[#F59E0B]/20 text-[#F59E0B]',
    completed: 'bg-[#A0A0A0]/20 text-[#A0A0A0]',
    cancelled: 'bg-[#EF4444]/20 text-[#EF4444]',
    expired: 'bg-[#6B7280]/20 text-[#6B7280]',
}[s] || 'bg-[#2A2A2A] text-[#A0A0A0]');

onMounted(fetchBookings);
</script>

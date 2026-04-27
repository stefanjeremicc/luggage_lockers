<template>
    <div>
        <div class="flex items-center justify-between mb-4 sm:mb-6 gap-3 flex-wrap">
            <h1 class="text-2xl font-bold">Bookings</h1>
            <input v-model="search" @input="fetchBookings" placeholder="Search…"
                class="bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2 text-sm text-white focus:border-[#F59E0B] focus:outline-none w-full sm:w-64">
        </div>

        <div class="flex gap-2 mb-4 flex-wrap">
            <button v-for="s in ['all','pending','confirmed','active','completed','cancelled','expired']" :key="s"
                @click="statusFilter = s; fetchBookings()"
                class="px-3 py-1.5 rounded-full text-xs border transition"
                :class="statusFilter === s ? 'bg-[#F59E0B] text-black border-[#F59E0B]' : 'border-[#2A2A2A] text-[#A0A0A0] hover:border-[#F59E0B]'">
                {{ s === 'all' ? 'All' : s.charAt(0).toUpperCase() + s.slice(1) }}
            </button>
        </div>

        <!-- Mobile card view -->
        <div class="md:hidden space-y-3">
            <div v-if="!bookings.length" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-8 text-center text-[#A0A0A0] text-sm">
                No bookings.
            </div>
            <article v-for="b in bookings" :key="b.id" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="min-w-0 flex-1">
                        <div class="font-medium truncate">{{ b.customer?.full_name || '—' }}</div>
                        <div class="text-xs text-[#A0A0A0] truncate">{{ b.customer?.email }}</div>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-xs shrink-0" :class="statusClass(b.booking_status)">{{ b.booking_status }}</span>
                </div>
                <dl class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-xs mt-3">
                    <dt class="text-[#6B7280]">Location</dt>
                    <dd class="text-right">{{ b.location?.name || '—' }}</dd>
                    <dt class="text-[#6B7280]">Check-in</dt>
                    <dd class="text-right">{{ formatDate(b.check_in) }}</dd>
                    <dt class="text-[#6B7280]">Size</dt>
                    <dd class="text-right">
                        <span class="px-2 py-0.5 rounded-full" :class="sizeClass(b.locker_size)">{{ b.locker_qty }}× {{ b.locker_size }}</span>
                    </dd>
                    <dt class="text-[#6B7280]">Total</dt>
                    <dd class="text-right text-[#F59E0B] font-semibold">€{{ Number(b.total_eur).toFixed(2) }}</dd>
                    <dt class="text-[#6B7280]">Payment</dt>
                    <dd class="text-right">
                        <span class="inline-flex items-center gap-1" :class="b.payment_status === 'paid' ? 'text-[#10B981]' : 'text-[#A0A0A0]'">
                            <span class="w-1.5 h-1.5 rounded-full" :class="b.payment_status === 'paid' ? 'bg-[#10B981]' : 'bg-[#6B7280]'"></span>
                            {{ b.payment_status === 'paid' ? 'Paid' : 'Unpaid' }}
                        </span>
                    </dd>
                </dl>
                <div v-if="showActions(b)" class="mt-3 pt-3 border-t border-[#2A2A2A] flex gap-2 flex-wrap">
                    <button v-if="b.payment_status !== 'paid'" @click="markPaid(b.id)"
                        class="flex-1 bg-[#10B981]/15 text-[#10B981] rounded-lg px-3 py-2 text-xs font-semibold hover:bg-[#10B981]/25">
                        Mark cash paid
                    </button>
                    <button v-if="['confirmed','active'].includes(b.booking_status)" @click="cancelBooking(b.id)"
                        class="flex-1 bg-[#EF4444]/15 text-[#EF4444] rounded-lg px-3 py-2 text-xs font-semibold hover:bg-[#EF4444]/25">
                        Cancel
                    </button>
                </div>
            </article>
        </div>

        <!-- Desktop table view -->
        <div class="hidden md:block bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-[#2A2A2A]">
                    <tr class="text-[#A0A0A0] text-left">
                        <th class="px-4 py-3 font-medium">Customer</th>
                        <th class="px-4 py-3 font-medium">Location</th>
                        <th class="px-4 py-3 font-medium">Check-in</th>
                        <th class="px-4 py-3 font-medium">Size</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Payment</th>
                        <th class="px-4 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!bookings.length">
                        <td colspan="8" class="px-4 py-8 text-center text-[#A0A0A0]">No bookings.</td>
                    </tr>
                    <tr v-for="b in bookings" :key="b.id" class="border-b border-[#2A2A2A]/50 hover:bg-[#111]">
                        <td class="px-4 py-3">
                            <div>{{ b.customer?.full_name }}</div>
                            <div class="text-xs text-[#A0A0A0]">{{ b.customer?.email }}</div>
                        </td>
                        <td class="px-4 py-3">{{ b.location?.name }}</td>
                        <td class="px-4 py-3 text-[#A0A0A0] whitespace-nowrap">{{ formatDate(b.check_in) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs" :class="sizeClass(b.locker_size)">{{ b.locker_qty }}× {{ b.locker_size }}</span>
                        </td>
                        <td class="px-4 py-3 text-[#F59E0B] font-medium whitespace-nowrap">€{{ Number(b.total_eur).toFixed(2) }}</td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs" :class="statusClass(b.booking_status)">{{ b.booking_status }}</span></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 text-xs" :class="b.payment_status === 'paid' ? 'text-[#10B981]' : 'text-[#A0A0A0]'">
                                <span class="w-1.5 h-1.5 rounded-full" :class="b.payment_status === 'paid' ? 'bg-[#10B981]' : 'bg-[#6B7280]'"></span>
                                {{ b.payment_status === 'paid' ? 'Paid (cash)' : 'Unpaid' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <button v-if="b.payment_status !== 'paid'" @click="markPaid(b.id)" class="text-xs text-[#10B981] hover:underline">Mark cash paid</button>
                                <button v-if="['confirmed','active'].includes(b.booking_status)" @click="cancelBooking(b.id)" class="text-xs text-[#EF4444] hover:underline">Cancel</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="pagination?.last_page > 1" class="flex justify-center gap-2 mt-4 flex-wrap">
            <button v-for="p in pagination.last_page" :key="p" @click="page = p; fetchBookings()"
                class="w-8 h-8 rounded text-xs" :class="page === p ? 'bg-[#F59E0B] text-black' : 'bg-[#1A1A1A] text-[#A0A0A0]'">{{ p }}</button>
        </div>
    </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';
import { useConfirm } from '../composables/useConfirm';

const { apiFetch } = useAuth();
const confirmDialog = useConfirm();
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
    const ok = await confirmDialog.ask({
        title: 'Cancel booking?',
        message: 'This booking will be cancelled.',
        variant: 'danger',
        confirmText: 'Cancel booking',
        cancelText: 'Keep',
    });
    if (!ok) return;
    await apiFetch(`/api/admin/bookings/${id}`, { method: 'DELETE' });
    fetchBookings();
};

const showActions = (b) => b.payment_status !== 'paid' || ['confirmed', 'active'].includes(b.booking_status);

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';

const statusClass = (s) => ({
    confirmed: 'bg-[#10B981]/20 text-[#10B981]',
    active: 'bg-blue-500/20 text-blue-400',
    pending: 'bg-[#F59E0B]/20 text-[#F59E0B]',
    completed: 'bg-[#A0A0A0]/20 text-[#A0A0A0]',
    cancelled: 'bg-[#EF4444]/20 text-[#EF4444]',
    expired: 'bg-[#6B7280]/20 text-[#6B7280]',
}[s] || 'bg-[#2A2A2A] text-[#A0A0A0]');

const sizeClass = (s) => s === 'large'
    ? 'bg-fuchsia-500/20 text-fuchsia-400'
    : 'bg-cyan-500/20 text-cyan-400';

onMounted(fetchBookings);
</script>

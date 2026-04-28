<template>
    <div>
        <div class="flex items-center justify-between mb-4 sm:mb-6 gap-3 flex-wrap">
            <h1 class="text-2xl font-bold">Bookings</h1>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <div class="relative w-full sm:w-64">
                    <input v-model="search" @input="onSearchInput" placeholder="Search…"
                        class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2 text-sm text-white focus:border-[#F59E0B] focus:outline-none">
                    <span v-if="loading" class="absolute right-3 top-1/2 -translate-y-1/2 w-3 h-3 border-2 border-[#F59E0B] border-t-transparent rounded-full animate-spin"></span>
                </div>
            </div>
        </div>

        <div class="flex gap-2 mb-4 flex-wrap">
            <button v-for="s in ['all','pending','confirmed','active','completed','cancelled','expired']" :key="s"
                @click="setStatus(s)"
                class="px-3 py-1.5 rounded-full text-xs border transition"
                :class="statusFilter === s ? 'bg-[#F59E0B] text-black border-[#F59E0B]' : 'border-[#2A2A2A] text-[#A0A0A0] hover:border-[#F59E0B]'">
                {{ s === 'all' ? 'All' : s.charAt(0).toUpperCase() + s.slice(1) }}
            </button>
        </div>

        <!-- Mobile card view -->
        <div class="md:hidden space-y-3">
            <div v-if="loading && !bookings.length" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-8 text-center text-[#A0A0A0] text-sm">
                Loading…
            </div>
            <div v-else-if="!bookings.length" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-8 text-center text-[#A0A0A0] text-sm">
                No bookings.
            </div>
            <article v-for="b in bookings" :key="b.id" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4 active:bg-[#222] transition" @click="openDetails(b)">
                <!-- Top: name + status -->
                <header class="flex items-start justify-between gap-3 mb-3 pb-3 border-b border-[#2A2A2A]">
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-base truncate">{{ b.customer?.full_name || '—' }}</div>
                        <div class="text-xs text-[#A0A0A0] truncate mt-0.5">{{ b.customer?.email }}</div>
                    </div>
                    <span class="px-2 py-1 rounded-full text-[10px] font-semibold uppercase tracking-wide shrink-0" :class="statusClass(b.booking_status)">{{ b.booking_status }}</span>
                </header>

                <!-- Stacked rows: label + value, one per line -->
                <dl class="space-y-2 text-sm">
                    <div class="flex items-baseline gap-3">
                        <dt class="text-[10px] uppercase tracking-wide text-[#6B7280] w-20 shrink-0">Location</dt>
                        <dd class="text-white truncate">{{ b.location?.name || '—' }}</dd>
                    </div>
                    <div class="flex items-baseline gap-3">
                        <dt class="text-[10px] uppercase tracking-wide text-[#6B7280] w-20 shrink-0">Check-in</dt>
                        <dd class="text-white">{{ formatDate(b.check_in) }}</dd>
                    </div>
                    <div class="flex items-baseline gap-3">
                        <dt class="text-[10px] uppercase tracking-wide text-[#6B7280] w-20 shrink-0">Check-out</dt>
                        <dd class="text-white">{{ formatDate(b.check_out) }}</dd>
                    </div>
                    <div class="flex items-baseline gap-3">
                        <dt class="text-[10px] uppercase tracking-wide text-[#6B7280] w-20 shrink-0">Items</dt>
                        <dd class="flex flex-wrap gap-1">
                            <span v-for="(line, i) in sizeBreakdown(b)" :key="i"
                                class="px-2 py-0.5 rounded-full text-xs" :class="sizeClass(line.size)">
                                {{ line.qty }}× {{ line.size }}<span v-if="line.duration"> · {{ durationLabel(line.duration) }}</span>
                            </span>
                            <span v-if="!sizeBreakdown(b).length" class="text-[#6B7280]">—</span>
                        </dd>
                    </div>
                    <div class="flex items-baseline gap-3">
                        <dt class="text-[10px] uppercase tracking-wide text-[#6B7280] w-20 shrink-0">Locker / PIN</dt>
                        <dd v-if="b.pins?.length" class="font-mono text-xs space-y-0.5">
                            <div v-for="p in b.pins" :key="p.locker_number">
                                <span class="text-white font-semibold">{{ p.locker_number || '—' }}</span>
                                <span class="text-[#F59E0B] font-bold ml-1">({{ p.pin || '——' }})</span>
                            </div>
                        </dd>
                        <dd v-else class="text-[#6B7280]">—</dd>
                    </div>
                    <div class="flex items-baseline gap-3">
                        <dt class="text-[10px] uppercase tracking-wide text-[#6B7280] w-20 shrink-0">Total</dt>
                        <dd class="font-bold text-[#F59E0B]">€{{ Number(b.total_eur).toFixed(2) }}</dd>
                    </div>
                    <div class="flex items-baseline gap-3">
                        <dt class="text-[10px] uppercase tracking-wide text-[#6B7280] w-20 shrink-0">Payment</dt>
                        <dd>
                            <span class="inline-flex items-center gap-1 text-xs" :class="b.payment_status === 'paid' ? 'text-[#10B981]' : 'text-[#A0A0A0]'">
                                <span class="w-1.5 h-1.5 rounded-full" :class="b.payment_status === 'paid' ? 'bg-[#10B981]' : 'bg-[#6B7280]'"></span>
                                {{ b.payment_status === 'paid' ? 'Paid' : 'Unpaid' }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex items-baseline gap-3">
                        <dt class="text-[10px] uppercase tracking-wide text-[#6B7280] w-20 shrink-0">Booking ID</dt>
                        <dd class="font-mono text-xs text-[#6B7280]">#{{ b.id }}</dd>
                    </div>
                </dl>

                <div class="mt-4 pt-3 border-t border-[#2A2A2A] grid grid-cols-2 gap-2" @click.stop>
                    <button v-if="b.payment_status !== 'paid' && !isFinal(b)" @click="markPaid(b.id)"
                        class="bg-[#10B981]/15 text-[#10B981] rounded-lg px-3 py-2.5 text-xs font-semibold active:bg-[#10B981]/25">Mark paid</button>
                    <button v-if="['confirmed','active'].includes(b.booking_status)" @click="reissuePin(b.id)"
                        class="bg-[#F59E0B]/15 text-[#F59E0B] rounded-lg px-3 py-2.5 text-xs font-semibold active:bg-[#F59E0B]/25">New PIN</button>
                    <button v-if="['confirmed','active'].includes(b.booking_status)" @click="extendOpen = b"
                        class="bg-[#2A2A2A] text-[#A0A0A0] rounded-lg px-3 py-2.5 text-xs font-semibold active:bg-[#333]">Extend</button>
                    <button v-if="!isFinal(b)" @click="resendConfirmation(b.id)"
                        class="bg-[#2A2A2A] text-[#A0A0A0] rounded-lg px-3 py-2.5 text-xs font-semibold active:bg-[#333]">Resend</button>
                    <button v-if="['confirmed','active'].includes(b.booking_status)" @click="cancelBooking(b.id)"
                        class="bg-[#EF4444]/15 text-[#EF4444] rounded-lg px-3 py-2.5 text-xs font-semibold active:bg-[#EF4444]/25 col-span-2">Cancel booking</button>
                    <button v-if="isFinal(b)" @click="deleteBooking(b.id)"
                        class="bg-[#EF4444]/15 text-[#EF4444] rounded-lg px-3 py-2.5 text-xs font-semibold active:bg-[#EF4444]/25 col-span-2">Delete</button>
                </div>
            </article>
        </div>

        <!-- Desktop table view -->
        <div class="hidden md:block bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl overflow-x-auto relative">
            <div v-if="loading" class="absolute inset-x-0 top-0 h-0.5 bg-[#F59E0B]/30 overflow-hidden">
                <div class="h-full w-1/3 bg-[#F59E0B] animate-[loadbar_1s_linear_infinite]"></div>
            </div>
            <table class="w-full text-sm">
                <thead class="border-b border-[#2A2A2A]">
                    <tr class="text-[#A0A0A0] text-left">
                        <th class="px-3 py-3 font-medium">ID</th>
                        <th class="px-4 py-3 font-medium">Customer</th>
                        <th class="px-4 py-3 font-medium">Location</th>
                        <th class="px-4 py-3 font-medium">Period</th>
                        <th class="px-4 py-3 font-medium">Size</th>
                        <th class="px-4 py-3 font-medium">Locker + PIN</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Payment</th>
                        <th class="px-4 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!bookings.length && !loading">
                        <td colspan="10" class="px-4 py-8 text-center text-[#A0A0A0]">No bookings.</td>
                    </tr>
                    <tr v-if="!bookings.length && loading">
                        <td colspan="10" class="px-4 py-8 text-center text-[#A0A0A0]">Loading…</td>
                    </tr>
                    <tr v-for="b in bookings" :key="b.id" class="border-b border-[#2A2A2A]/50 hover:bg-[#111]">
                        <td class="px-3 py-3 text-[#6B7280] font-mono text-xs whitespace-nowrap">#{{ b.id }}</td>
                        <td class="px-4 py-3 max-w-[220px]">
                            <div class="truncate">{{ b.customer?.full_name }}</div>
                            <div class="text-xs text-[#A0A0A0] truncate">{{ b.customer?.email }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ b.location?.name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-xs leading-tight">
                            <div class="text-white">{{ formatDate(b.check_in) }}</div>
                            <div class="text-[#6B7280] mt-1">{{ formatDate(b.check_out) }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div v-if="sizeBreakdown(b).length" class="flex flex-col gap-0.5">
                                <span v-for="(line, i) in sizeBreakdown(b)" :key="i"
                                    class="px-2 py-0.5 rounded-full text-xs inline-block w-fit" :class="sizeClass(line.size)">
                                    {{ line.qty }}× {{ line.size }}<span v-if="line.duration" class="text-[10px] opacity-80"> · {{ durationLabel(line.duration) }}</span>
                                </span>
                            </div>
                            <span v-else class="px-2 py-0.5 rounded-full text-xs" :class="sizeClass(b.locker_size)">{{ b.locker_qty }}× {{ b.locker_size }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div v-if="b.pins?.length" class="flex flex-col gap-0.5 font-mono text-xs">
                                <span v-for="p in b.pins" :key="p.locker_number" class="flex items-center gap-1.5">
                                    <span class="font-semibold text-white">{{ p.locker_number || '—' }}</span>
                                    <span class="font-bold text-[#F59E0B]">({{ p.pin || '——' }})</span>
                                    <span :title="p.ttlock_registered ? 'Registered on smart lock' : 'Not yet on smart lock'"
                                        class="w-1.5 h-1.5 rounded-full" :class="p.ttlock_registered ? 'bg-[#10B981]' : 'bg-[#F59E0B]'"></span>
                                </span>
                            </div>
                            <span v-else class="text-[#6B7280] text-xs">—</span>
                        </td>
                        <td class="px-4 py-3 text-[#F59E0B] font-medium whitespace-nowrap">€{{ Number(b.total_eur).toFixed(2) }}</td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs" :class="statusClass(b.booking_status)">{{ b.booking_status }}</span></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 text-xs" :class="b.payment_status === 'paid' ? 'text-[#10B981]' : 'text-[#A0A0A0]'">
                                <span class="w-1.5 h-1.5 rounded-full" :class="b.payment_status === 'paid' ? 'bg-[#10B981]' : 'bg-[#6B7280]'"></span>
                                {{ b.payment_status === 'paid' ? 'Paid (cash)' : 'Unpaid' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1">
                                <button @click="openDetails(b)" title="Details"
                                    class="action-icon" aria-label="Details">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                                <button v-if="b.payment_status !== 'paid' && !isFinal(b)" @click="markPaid(b.id)" title="Mark cash paid"
                                    class="action-icon text-[#10B981] hover:bg-[#10B981]/15" aria-label="Mark paid">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                                </button>
                                <button v-if="!isFinal(b)" @click="resendConfirmation(b.id)" title="Resend confirmation"
                                    class="action-icon" aria-label="Resend">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12a8 8 0 018-8v4l5-5-5-5v4a10 10 0 100 20"/></svg>
                                </button>
                                <button v-if="['confirmed','active'].includes(b.booking_status)" @click="reissuePin(b.id)" title="Generate new PIN"
                                    class="action-icon text-[#F59E0B] hover:bg-[#F59E0B]/15" aria-label="New PIN">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="16" r="1"/><path d="M7 11V7a5 5 0 0110 0v4"/><rect x="3" y="11" width="18" height="11" rx="2"/></svg>
                                </button>
                                <button v-if="['confirmed','active'].includes(b.booking_status)" @click="extendOpen = b" title="Extend duration"
                                    class="action-icon" aria-label="Extend">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                </button>
                                <button v-if="['confirmed','active'].includes(b.booking_status)" @click="cancelBooking(b.id)" title="Cancel booking"
                                    class="action-icon text-[#EF4444] hover:bg-[#EF4444]/15" aria-label="Cancel">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                </button>
                                <button v-if="isFinal(b)" @click="deleteBooking(b.id)" title="Delete permanently"
                                    class="action-icon text-[#EF4444] hover:bg-[#EF4444]/15" aria-label="Delete">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 01-2 2H9a2 2 0 01-2-2L5 6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination?.last_page > 1" class="flex items-center justify-between gap-2 mt-4 text-sm flex-wrap">
            <div class="text-[#A0A0A0]">
                {{ pagination.from }}–{{ pagination.to }} of {{ pagination.total }}
            </div>
            <div class="flex items-center gap-1 flex-wrap">
                <button @click="goPage(page - 1)" :disabled="page <= 1" aria-label="Previous"
                    class="w-8 h-8 rounded-lg bg-[#1A1A1A] border border-[#2A2A2A] text-[#A0A0A0] hover:border-[#F59E0B] hover:text-white disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:border-[#2A2A2A] flex items-center justify-center">←</button>
                <template v-for="(item, idx) in pageList" :key="idx">
                    <button v-if="item !== '...'" @click="goPage(item)"
                        class="min-w-[2rem] h-8 px-2 rounded-lg text-xs font-medium border transition"
                        :class="page === item ? 'bg-[#F59E0B] text-black border-[#F59E0B]' : 'bg-[#1A1A1A] border-[#2A2A2A] text-[#A0A0A0] hover:border-[#F59E0B] hover:text-white'">
                        {{ item }}
                    </button>
                    <span v-else class="px-1 text-[#6B7280]">…</span>
                </template>
                <button @click="goPage(page + 1)" :disabled="page >= pagination.last_page" aria-label="Next"
                    class="w-8 h-8 rounded-lg bg-[#1A1A1A] border border-[#2A2A2A] text-[#A0A0A0] hover:border-[#F59E0B] hover:text-white disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:border-[#2A2A2A] flex items-center justify-center">→</button>
            </div>
        </div>

        <!-- Details modal -->
        <Modal :model-value="!!detailsBooking" @update:model-value="v => !v && (detailsBooking = null)"
            size="lg" title="Booking details" :subtitle="detailsBooking ? '#' + (detailsBooking.uuid?.slice(0, 8) || '') : ''" no-padding>
            <div v-if="detailsBooking" class="p-4 space-y-4 text-sm">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-xs text-[#6B7280] mb-1">Status</div>
                            <span class="px-2 py-0.5 rounded-full text-xs" :class="statusClass(detailsBooking.booking_status)">{{ detailsBooking.booking_status }}</span>
                        </div>
                        <div>
                            <div class="text-xs text-[#6B7280] mb-1">Payment</div>
                            <span class="inline-flex items-center gap-1 text-xs" :class="detailsBooking.payment_status === 'paid' ? 'text-[#10B981]' : 'text-[#A0A0A0]'">
                                <span class="w-1.5 h-1.5 rounded-full" :class="detailsBooking.payment_status === 'paid' ? 'bg-[#10B981]' : 'bg-[#6B7280]'"></span>
                                {{ detailsBooking.payment_status === 'paid' ? 'Paid (cash)' : 'Unpaid' }}
                            </span>
                        </div>
                        <div>
                            <div class="text-xs text-[#6B7280] mb-1">Customer</div>
                            <div>{{ detailsBooking.customer?.full_name }}</div>
                            <div class="text-xs text-[#A0A0A0]">{{ detailsBooking.customer?.email }}</div>
                            <div v-if="detailsBooking.customer?.phone" class="text-xs text-[#A0A0A0]">{{ detailsBooking.customer.phone }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-[#6B7280] mb-1">Location</div>
                            <div class="text-[#F59E0B] font-medium">{{ detailsBooking.location?.name }}</div>
                            <div class="text-xs text-[#A0A0A0]">{{ detailsBooking.location?.address }}<template v-if="detailsBooking.location?.city">, {{ detailsBooking.location.city }}</template></div>
                        </div>
                        <div>
                            <div class="text-xs text-[#6B7280] mb-1">Check-in</div>
                            <div>{{ formatDate(detailsBooking.check_in) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-[#6B7280] mb-1">Check-out</div>
                            <div>{{ formatDate(detailsBooking.check_out) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-[#6B7280] mb-1">Lockers &amp; PINs</div>
                            <div v-if="detailsBooking.pins?.length" class="space-y-1">
                                <div v-for="p in detailsBooking.pins" :key="p.locker_number" class="flex items-center gap-2 text-xs">
                                    <span class="font-mono font-semibold">#{{ p.locker_number }}</span>
                                    <span class="font-mono font-bold tracking-wider text-[#F59E0B]">{{ p.pin || '——' }}</span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px]"
                                        :class="p.ttlock_registered ? 'bg-[#10B981]/15 text-[#10B981]' : 'bg-[#F59E0B]/15 text-[#F59E0B]'">
                                        {{ p.ttlock_registered ? 'On lock' : 'Not on lock' }}
                                    </span>
                                </div>
                            </div>
                            <div v-else class="font-mono text-xs">{{ lockerNumbers(detailsBooking) }} ({{ detailsBooking.locker_qty }}× {{ detailsBooking.locker_size }})</div>
                        </div>
                        <div>
                            <div class="text-xs text-[#6B7280] mb-1">Total</div>
                            <div class="text-[#F59E0B] font-semibold">€{{ Number(detailsBooking.total_eur).toFixed(2) }}</div>
                        </div>
                    </div>

                    <div v-if="detailsBooking.cancel_reason">
                        <div class="text-xs text-[#6B7280] mb-1">Cancel reason</div>
                        <div class="text-[#EF4444]">{{ detailsBooking.cancel_reason }}</div>
                    </div>

                    <div v-if="detailsBooking.notification_logs?.length">
                        <div class="text-xs text-[#6B7280] mb-2">Notification history</div>
                        <table class="w-full text-xs bg-[#111] border border-[#2A2A2A] rounded-lg overflow-hidden">
                            <thead class="bg-[#1A1A1A]">
                                <tr class="text-[#6B7280] text-left">
                                    <th class="px-3 py-1.5 font-medium">Channel</th>
                                    <th class="px-3 py-1.5 font-medium">Event</th>
                                    <th class="px-3 py-1.5 font-medium">Recipient</th>
                                    <th class="px-3 py-1.5 font-medium">When</th>
                                    <th class="px-3 py-1.5 font-medium text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="log in detailsBooking.notification_logs" :key="log.id" class="border-t border-[#2A2A2A]/60">
                                    <td class="px-3 py-1.5">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="log.channel === 'email' ? 'bg-blue-400' : 'bg-[#10B981]'"></span>
                                            {{ log.channel === 'email' ? 'Email' : 'WhatsApp' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-1.5">{{ eventLabel(log.template) }}</td>
                                    <td class="px-3 py-1.5 text-[#A0A0A0] truncate max-w-[160px]">{{ log.recipient }}</td>
                                    <td class="px-3 py-1.5 text-[#A0A0A0] whitespace-nowrap">{{ formatDate(log.sent_at || log.created_at) }}</td>
                                    <td class="px-3 py-1.5 text-right whitespace-nowrap">
                                        <span :class="statusBadge(log.status)" class="px-1.5 py-0.5 rounded text-[10px] font-medium">{{ statusText(log.status) }}</span>
                                        <button v-if="log.payload" @click="previewNotification(detailsBooking.id, log.id)"
                                            class="ml-1.5 text-[#F59E0B] hover:underline text-[10px]">View</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
            </div>
        </Modal>

        <!-- Extend modal -->
        <Modal :model-value="!!extendOpen" @update:model-value="v => !v && (extendOpen = null)"
            size="sm" title="Extend booking">
            <template v-if="extendOpen">
                <p class="text-xs text-[#A0A0A0] mb-4">Add more time on top of current check-out ({{ formatDate(extendOpen.check_out) }}).</p>
                <select v-model="extendDuration" class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2 text-sm focus:border-[#F59E0B] focus:outline-none">
                    <option value="6h">+ 6 hours</option>
                    <option value="24h">+ 24 hours</option>
                    <option value="2_days">+ 2 days</option>
                    <option value="3_days">+ 3 days</option>
                    <option value="5_days">+ 5 days</option>
                    <option value="1_week">+ 1 week</option>
                    <option value="2_weeks">+ 2 weeks</option>
                    <option value="1_month">+ 1 month</option>
                </select>
            </template>
            <template #footer>
                <div class="flex gap-2 justify-end">
                    <Btn variant="secondary" size="sm" @click="extendOpen = null">Cancel</Btn>
                    <Btn variant="primary" size="sm" @click="confirmExtend()">Extend</Btn>
                </div>
            </template>
        </Modal>
    </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';
import { useConfirm } from '../composables/useConfirm';
import { useToast } from '../composables/useToast';
import Modal from '../components/Modal.vue';
import Btn from '../components/Btn.vue';

const { apiFetch } = useAuth();
const confirmDialog = useConfirm();
const toast = useToast();

const bookings = ref([]);
const pagination = ref(null);
const search = ref('');
const statusFilter = ref('all');
const page = ref(1);
const loading = ref(false);
const openMenuId = ref(null);
const detailsBooking = ref(null);
const extendOpen = ref(null);
const extendDuration = ref('24h');

let searchTimer = null;
let reqToken = 0;

const fetchBookings = async () => {
    const myToken = ++reqToken;
    loading.value = true;
    try {
        const params = new URLSearchParams({ page: page.value });
        if (statusFilter.value !== 'all') params.set('status', statusFilter.value);
        if (search.value) params.set('search', search.value);
        const res = await apiFetch(`/api/admin/bookings?${params}`);
        const data = await res.json();
        if (myToken !== reqToken) return;
        bookings.value = data.data;
        pagination.value = data;
    } catch (e) {
        if (myToken === reqToken) toast.error('Failed to load bookings');
    } finally {
        if (myToken === reqToken) loading.value = false;
    }
};

const onSearchInput = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { page.value = 1; fetchBookings(); }, 300);
};

const setStatus = (s) => { statusFilter.value = s; page.value = 1; fetchBookings(); };
const goPage = (p) => { if (p < 1 || p > pagination.value?.last_page) return; page.value = p; fetchBookings(); };

const toggleMenu = (id) => { openMenuId.value = openMenuId.value === id ? null : id; };
const closeMenu = () => { openMenuId.value = null; };

const isFinal = (b) => ['cancelled', 'completed', 'expired'].includes(b.booking_status);

// Group by booking_items first (authoritative — carries qty + duration + size).
// Falls back to booking_lockers (b.pins) for old data shapes.
const sizeBreakdown = (b) => {
    if (Array.isArray(b.items) && b.items.length) {
        return b.items.map(it => ({
            size: it.locker_size,
            qty: it.qty,
            duration: it.duration_key,
            line_total_eur: it.line_total_eur,
        }));
    }
    if (!b.pins?.length) return [];
    const map = {};
    for (const p of b.pins) {
        const s = p.size || 'standard';
        map[s] = (map[s] || 0) + 1;
    }
    return ['standard', 'large']
        .filter(s => map[s])
        .map(s => ({ size: s, qty: map[s] }));
};

// Friendly label for a duration key. Falls back to the raw key.
const durationLabels = {
    '6h': '6h', '24h': '24h', '2_days': '2d', '3_days': '3d', '4_days': '4d',
    '5_days': '5d', '1_week': '1w', '2_weeks': '2w', '1_month': '1mo',
};
const durationLabel = (key) => durationLabels[key] || key || '';

// Numeric pagination with smart ellipsis: 1 … 4 5 [6] 7 8 … 20
const pageList = computed(() => {
    const last = pagination.value?.last_page || 1;
    const cur = page.value;
    if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
    const out = [1];
    const start = Math.max(2, cur - 1);
    const end = Math.min(last - 1, cur + 1);
    if (start > 2) out.push('...');
    for (let i = start; i <= end; i++) out.push(i);
    if (end < last - 1) out.push('...');
    out.push(last);
    return out;
});

const eventLabel = (template) => ({
    booking_confirmed: 'Booking confirmed',
    locker_pin_delivered: 'PIN delivered',
    booking_cancelled: 'Booking cancelled',
    booking_reminder: 'Reminder',
}[template] || template);

const statusText = (s) => ({ sent: 'Sent', failed: 'Failed', stub: 'Not sent (stub)', pending: 'Pending' }[s] || s);
const statusBadge = (s) => ({
    sent: 'bg-[#10B981]/15 text-[#10B981]',
    failed: 'bg-[#EF4444]/15 text-[#EF4444]',
    stub: 'bg-[#6B7280]/15 text-[#A0A0A0]',
    pending: 'bg-[#F59E0B]/15 text-[#F59E0B]',
}[s] || 'bg-[#2A2A2A] text-[#A0A0A0]');

const markPaid = async (id) => {
    try {
        await apiFetch(`/api/admin/bookings/${id}/mark-paid`, { method: 'POST' });
        toast.success('Marked as paid');
        fetchBookings();
    } catch { toast.error('Failed to mark paid'); }
};

const resendConfirmation = async (id) => {
    try {
        await apiFetch(`/api/admin/bookings/${id}/resend`, { method: 'POST' });
        toast.success('Confirmation queued');
    } catch { toast.error('Failed to resend'); }
};

const previewNotification = async (bookingId, logId) => {
    try {
        const res = await apiFetch(`/api/admin/bookings/${bookingId}/notifications/${logId}/preview`);
        if (!res.ok) { toast.error('Preview not available'); return; }
        const html = await res.text();
        const blob = new Blob([html], { type: 'text/html' });
        const url = URL.createObjectURL(blob);
        window.open(url, '_blank', 'width=720,height=820');
        setTimeout(() => URL.revokeObjectURL(url), 60_000);
    } catch { toast.error('Preview failed'); }
};

const reissuePin = async (id) => {
    const ok = await confirmDialog.ask({
        title: 'Re-issue PIN?',
        message: 'A new PIN will be generated and registered on the smart lock. The old PIN will stop working. Customer will receive the new PIN via email/WhatsApp.',
        confirmText: 'Generate new PIN',
        cancelText: 'Keep current',
    });
    if (!ok) return;
    try {
        const res = await apiFetch(`/api/admin/bookings/${id}/reissue-pin`, { method: 'POST' });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) { toast.error(data.message || 'Failed to re-issue'); return; }
        toast.success(data.message || 'New PIN generated');
        fetchBookings();
        if (detailsBooking.value?.id === id) openDetails(detailsBooking.value);
    } catch { toast.error('Failed to re-issue PIN'); }
};

const cancelBooking = async (id) => {
    const ok = await confirmDialog.ask({
        title: 'Cancel booking?',
        message: 'Locker will be freed and a cancellation email sent to the customer.',
        variant: 'danger',
        confirmText: 'Cancel booking',
        cancelText: 'Keep',
    });
    if (!ok) return;
    try {
        await apiFetch(`/api/admin/bookings/${id}`, { method: 'DELETE' });
        toast.success('Booking cancelled');
        fetchBookings();
    } catch { toast.error('Failed to cancel'); }
};

const deleteBooking = async (id) => {
    const ok = await confirmDialog.ask({
        title: 'Delete from system?',
        message: 'This permanently removes the booking and its notification history. This cannot be undone.',
        variant: 'danger',
        confirmText: 'Delete permanently',
        cancelText: 'Keep',
    });
    if (!ok) return;
    try {
        const res = await apiFetch(`/api/admin/bookings/${id}/force`, { method: 'DELETE' });
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            toast.error(data.message || 'Failed to delete');
            return;
        }
        toast.success('Booking deleted');
        if (detailsBooking.value?.id === id) detailsBooking.value = null;
        fetchBookings();
    } catch { toast.error('Failed to delete'); }
};

const openDetails = async (b) => {
    detailsBooking.value = b;
    try {
        const res = await apiFetch(`/api/admin/bookings/${b.id}`);
        const full = await res.json();
        if (detailsBooking.value?.id === b.id) detailsBooking.value = full;
    } catch { /* keep partial */ }
};

const confirmExtend = async () => {
    const id = extendOpen.value?.id;
    if (!id) return;
    try {
        await apiFetch(`/api/admin/bookings/${id}/extend`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ duration: extendDuration.value }),
        });
        toast.success('Booking extended');
        extendOpen.value = null;
        fetchBookings();
    } catch { toast.error('Failed to extend'); }
};

const copyText = async (text) => {
    try { await navigator.clipboard.writeText(text); toast.success('Copied'); }
    catch { toast.error('Copy failed'); }
};
const bookingUrl = (b) => `${window.location.origin}/booking/${b.uuid}`;

// Numeric d.m.Y H:i — matches App\Helpers\Dates so admin matches the public site.
const formatDate = (d) => {
    if (!d) return '';
    const dt = new Date(d);
    if (Number.isNaN(dt.getTime())) return '';
    const pad = n => String(n).padStart(2, '0');
    return `${pad(dt.getDate())}.${pad(dt.getMonth() + 1)}.${dt.getFullYear()} ${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
};

const statusClass = (s) => ({
    confirmed: 'bg-[#10B981]/20 text-[#10B981]',
    active: 'bg-blue-500/20 text-blue-400',
    pending: 'bg-[#F59E0B]/20 text-[#F59E0B]',
    completed: 'bg-[#A0A0A0]/20 text-[#A0A0A0]',
    cancelled: 'bg-[#EF4444]/20 text-[#EF4444]',
    expired: 'bg-[#6B7280]/20 text-[#6B7280]',
}[s] || 'bg-[#2A2A2A] text-[#A0A0A0]');

const lockerNumbers = (b) => b.lockers?.length ? b.lockers.map(l => l.number).join(', ') : '—';

const sizeClass = (s) => s === 'large'
    ? 'bg-fuchsia-500/20 text-fuchsia-400'
    : 'bg-cyan-500/20 text-cyan-400';

const vClickOutside = {
    mounted(el, binding) {
        el._clickOutside = (e) => { if (!el.contains(e.target)) binding.value(); };
        setTimeout(() => document.addEventListener('click', el._clickOutside), 0);
    },
    unmounted(el) { document.removeEventListener('click', el._clickOutside); },
};

onMounted(fetchBookings);
</script>
<style>
@keyframes loadbar { 0% { transform: translateX(-100%); } 100% { transform: translateX(400%); } }
.action-icon {
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    color: #A0A0A0;
    transition: background 0.15s, color 0.15s;
}
.action-icon:hover { background: #2A2A2A; color: #fff; }
</style>

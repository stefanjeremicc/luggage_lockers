<template>
    <div>
        <div class="flex items-center justify-between mb-4 sm:mb-6 gap-3 flex-wrap">
            <h1 class="text-2xl font-bold">Bookings</h1>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <div class="relative flex-1 sm:w-64 sm:flex-none">
                    <input v-model="search" @input="onSearchInput" placeholder="Search…"
                        class="w-full bg-[#111] border border-[#2A2A2A] rounded-lg px-4 h-[42px] text-sm text-white focus:border-[#F59E0B] focus:outline-none">
                    <span v-if="loading" class="absolute right-3 top-1/2 -translate-y-1/2 w-3 h-3 border-2 border-[#F59E0B] border-t-transparent rounded-full animate-spin"></span>
                </div>
                <!-- Mobile-only sort: square icon button matching the search input -->
                <div class="md:hidden relative shrink-0">
                    <button type="button" @click="sortMenuOpen = !sortMenuOpen" aria-label="Sort"
                        class="h-[42px] w-[42px] flex items-center justify-center bg-[#111] border rounded-lg transition"
                        :class="sortCol ? 'border-[#F59E0B] text-[#F59E0B]' : 'border-[#2A2A2A] text-[#A0A0A0]'">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M6 12h12M9 18h6"/></svg>
                    </button>
                    <div v-if="sortMenuOpen" class="fixed inset-0 z-40" @click="sortMenuOpen = false"></div>
                    <div v-if="sortMenuOpen" class="absolute right-0 top-full mt-1 w-56 bg-[#111] border border-[#2A2A2A] rounded-lg shadow-2xl z-50 overflow-hidden">
                        <button v-for="opt in mobileSortOptions" :key="opt.value" type="button"
                            @click="setMobileSort(opt.value); sortMenuOpen = false"
                            class="w-full text-left px-3 py-2.5 text-xs flex items-center justify-between gap-2 transition"
                            :class="mobileSort === opt.value ? 'bg-[#F59E0B]/15 text-[#F59E0B]' : 'text-white hover:bg-[#1A1A1A]'">
                            <span class="truncate">{{ opt.label }}</span>
                            <svg v-if="mobileSort === opt.value" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative z-20 flex gap-2 mb-4 overflow-x-auto pb-1 -mx-1 px-1 sm:flex-wrap sm:overflow-visible sm:mx-0 sm:px-0 sm:pb-0">
            <button v-for="t in tabs" :key="t.key"
                type="button"
                @click="setTab(t.key)"
                class="px-3 py-1.5 rounded-full text-xs border transition shrink-0 whitespace-nowrap"
                :class="tab === t.key ? 'bg-[#F59E0B] text-black border-[#F59E0B]' : 'border-[#2A2A2A] text-[#A0A0A0] hover:border-[#F59E0B]'">
                {{ t.label }}
            </button>
        </div>

        <div class="md:hidden mb-3 flex items-center gap-3 text-xs text-[#6B7280]">
            <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span>PIN on lock</span>
            <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-[#F59E0B]"></span>no PIN / syncing</span>
            <span class="flex items-center gap-1.5"><svg class="w-3 h-3 text-[#10B981]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 018 0M5 11h14a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1v-7a1 1 0 011-1z"/></svg>opened / in use</span>
        </div>

        <!-- Mobile card view -->
        <div class="md:hidden space-y-3">
            <div v-if="loading && !bookings.length" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-8 text-center text-[#A0A0A0] text-sm">
                Loading…
            </div>
            <div v-else-if="!bookings.length" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-8 text-center text-[#A0A0A0] text-sm">
                No bookings.
            </div>
            <template v-for="g in mobileGroups" :key="g.key">
            <div v-if="g.label" class="sticky top-0 z-[5] -mx-px px-3 py-1.5 bg-[#0A0A0A]/95 backdrop-blur text-xs font-semibold uppercase tracking-wide text-[#A0A0A0] border-b border-[#2A2A2A]">{{ g.label }}</div>
            <article v-for="b in g.items" :key="b.id" class="border rounded-xl p-4" :class="b.payment_status === 'paid' ? 'bg-[#10B981]/[0.16] border-[#10B981]/60' : 'bg-[#1A1A1A] border-[#2A2A2A]'">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="booking-pill" :class="statusClass((b.display_status ?? b.booking_status))">{{ statusLabel(b.display_status ?? b.booking_status) }}</span>
                        <span v-if="(b.display_status ?? b.booking_status) !== 'cancelled'" class="booking-pill" :class="b.payment_status === 'paid' ? 'bg-[#10B981]/20 text-[#10B981]' : 'bg-[#EF4444]/20 text-[#EF4444]'">{{ b.payment_status === 'paid' ? 'paid' : 'unpaid' }}</span>
                    </div>
                    <div v-if="b.pins?.length" class="flex flex-col items-end gap-1 text-xs">
                        <div v-for="p in b.pins" :key="p.locker_number" class="flex items-center gap-1.5">
                            <span class="w-5 h-5 rounded-full text-[10px] font-bold flex items-center justify-center" :class="sizeClass(p.size || 'standard')">{{ (p.size || 'standard') === 'large' ? 'B' : 'R' }}</span>
                            <span class="font-mono font-semibold text-white text-[15px]">{{ p.locker_number || '—' }}</span>
                            <span class="font-mono font-bold text-[#F59E0B] text-[15px]">({{ p.pin ? p.pin + '#' : '——' }})</span>
                            <span v-if="['confirmed','active'].includes((b.display_status ?? b.booking_status))"
                                :title="p.ttlock_registered ? 'PIN active on smart lock' : 'PIN waiting for gateway sync'"
                                class="w-1.5 h-1.5 rounded-full shrink-0" :class="p.ttlock_registered ? 'bg-[#10B981]' : 'bg-[#F59E0B]'"></span>
                            <svg v-if="p.opened" title="Locker opened — in use" class="w-3.5 h-3.5 text-[#10B981] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 018 0M5 11h14a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1v-7a1 1 0 011-1z"/></svg>
                        </div>
                    </div>
                </div>
                <dl class="booking-dl">
                    <div><dt class="booking-label">Booking ID</dt>
                        <dd class="booking-value font-mono">#{{ b.booking_number ?? b.id }}</dd></div>
                    <div><dt class="booking-label">Name</dt>
                        <dd class="booking-value">{{ b.customer?.full_name || '—' }}</dd></div>
                    <div v-if="b.customer?.email"><dt class="booking-label">Email</dt>
                        <dd class="booking-value">{{ b.customer.email }}</dd></div>
                    <div v-if="b.customer?.phone"><dt class="booking-label">Phone</dt>
                        <dd class="booking-value">{{ b.customer.phone }}</dd></div>
                    <div><dt class="booking-label">Check-in</dt>
                        <dd class="booking-value">{{ formatDate(b.check_in) || '—' }}</dd></div>
                    <div><dt class="booking-label">Check-out</dt>
                        <dd class="booking-value">{{ formatDate(b.check_out) || '—' }}</dd></div>
                    <div><dt class="booking-label">Total</dt>
                        <dd class="booking-value !text-[#F59E0B] font-semibold">€{{ Number(b.total_eur).toFixed(2) }}</dd></div>
                </dl>

                <div class="mt-4 pt-3 border-t border-[#2A2A2A] space-y-2">
                    <!-- Row 1: New PIN / Extend / Resend / Details (4 across, Details far right) -->
                    <div class="grid grid-cols-4 gap-2">
                        <button v-if="['confirmed','active'].includes((b.display_status ?? b.booking_status))" @click="reissuePin(b.id)"
                            class="bg-[#2A2A2A] text-[#A0A0A0] rounded-lg px-1 py-2.5 text-xs font-semibold active:bg-[#333]">New PIN</button>
                        <button v-if="['confirmed','active'].includes((b.display_status ?? b.booking_status))" @click="extendOpen = b"
                            class="bg-[#2A2A2A] text-[#A0A0A0] rounded-lg px-1 py-2.5 text-xs font-semibold active:bg-[#333]">Extend</button>
                        <button v-if="!isFinal(b)" @click="resendConfirmation(b.id)"
                            class="bg-[#2A2A2A] text-[#A0A0A0] rounded-lg px-1 py-2.5 text-xs font-semibold active:bg-[#333]">Resend</button>
                        <button @click="openDetails(b)"
                            class="col-start-4 bg-[#2A2A2A] text-[#A0A0A0] rounded-lg px-1 py-2.5 text-xs font-semibold active:bg-[#333]">Details</button>
                    </div>
                    <!-- Row 2: Cancel/Delete (left) + Mark paid (right) -->
                    <div class="grid grid-cols-2 gap-2">
                        <button v-if="['confirmed','active'].includes((b.display_status ?? b.booking_status))" @click="cancelBooking(b.id)"
                            class="bg-[#2A2A2A] text-[#A0A0A0] rounded-lg px-3 py-2.5 text-xs font-semibold active:bg-[#333]">Cancel booking</button>
                        <button v-else-if="isFinal(b)" @click="deleteBooking(b.id)"
                            class="bg-[#EF4444]/15 text-[#EF4444] rounded-lg px-3 py-2.5 text-xs font-semibold active:bg-[#EF4444]/25">Delete</button>
                        <span v-else></span>
                        <button v-if="(b.display_status ?? b.booking_status) !== 'cancelled'" @click="togglePaid(b)"
                            class="rounded-lg px-3 py-2.5 text-xs font-semibold"
                            :class="b.payment_status === 'paid' ? 'bg-[#2A2A2A] text-[#A0A0A0] active:bg-[#333]' : 'bg-[#10B981]/15 text-[#10B981] active:bg-[#10B981]/25'">
                            {{ b.payment_status === 'paid' ? 'Mark as unpaid' : 'Mark as paid' }}</button>
                    </div>
                </div>
            </article>
            </template>
        </div>

        <!-- Desktop table view -->
        <div class="hidden md:block bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl overflow-x-auto relative">
            <div v-if="loading" class="absolute inset-x-0 top-0 h-0.5 bg-[#F59E0B]/30 overflow-hidden">
                <div class="h-full w-1/3 bg-[#F59E0B] animate-[loadbar_1s_linear_infinite]"></div>
            </div>
            <table class="w-full text-sm">
                <thead class="border-b border-[#2A2A2A]">
                    <tr class="text-[#A0A0A0] text-left select-none">
                        <th class="px-3 py-3 font-medium cursor-pointer hover:text-white" @click="toggleSort('number')">ID<span v-if="sortCol==='number'" class="text-[#F59E0B] ml-0.5">{{ sortDir==='asc'?'↑':'↓' }}</span></th>
                        <th class="px-4 py-3 font-medium cursor-pointer hover:text-white" @click="toggleSort('customer')">Customer<span v-if="sortCol==='customer'" class="text-[#F59E0B] ml-0.5">{{ sortDir==='asc'?'↑':'↓' }}</span></th>
                        <th class="px-4 py-3 font-medium cursor-pointer hover:text-white" @click="toggleSort('period')">Period<span v-if="sortCol==='period'" class="text-[#F59E0B] ml-0.5">{{ sortDir==='asc'?'↑':'↓' }}</span></th>
                        <th class="px-4 py-3 font-medium cursor-pointer hover:text-white" @click="toggleSort('type')">Type + Locker + PIN<span v-if="sortCol==='type'" class="text-[#F59E0B] ml-0.5">{{ sortDir==='asc'?'↑':'↓' }}</span></th>
                        <th class="px-4 py-3 font-medium cursor-pointer hover:text-white" @click="toggleSort('total')">Total<span v-if="sortCol==='total'" class="text-[#F59E0B] ml-0.5">{{ sortDir==='asc'?'↑':'↓' }}</span></th>
                        <th class="px-4 py-3 font-medium cursor-pointer hover:text-white" @click="toggleSort('status')">Status<span v-if="sortCol==='status'" class="text-[#F59E0B] ml-0.5">{{ sortDir==='asc'?'↑':'↓' }}</span></th>
                        <th class="px-4 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!bookings.length && !loading">
                        <td colspan="7" class="px-4 py-8 text-center text-[#A0A0A0]">No bookings.</td>
                    </tr>
                    <tr v-if="!bookings.length && loading">
                        <td colspan="7" class="px-4 py-8 text-center text-[#A0A0A0]">Loading…</td>
                    </tr>
                    <tr v-for="b in bookings" :key="b.id" class="border-b border-[#2A2A2A]/50" :class="b.payment_status === 'paid' ? 'bg-[#10B981]/[0.16] hover:bg-[#10B981]/25' : 'hover:bg-[#111]'">
                        <td class="px-3 py-3 text-[#6B7280] font-mono text-xs whitespace-nowrap">#{{ b.booking_number ?? b.id }}</td>
                        <td class="px-4 py-3 max-w-[220px]">
                            <div class="truncate">{{ b.customer?.full_name }}</div>
                            <div class="text-xs text-[#A0A0A0] truncate">{{ b.customer?.email }}</div>
                            <div v-if="b.customer?.phone" class="text-xs text-[#A0A0A0] truncate">{{ b.customer.phone }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-xs leading-tight">
                            <div class="text-white">{{ formatDate(b.check_in) }}</div>
                            <div class="text-[#6B7280] mt-1">{{ formatDate(b.check_out) }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div v-if="b.pins?.length" class="flex flex-col gap-1 text-xs">
                                <div v-for="p in b.pins" :key="p.locker_number" class="flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-full text-[10px] font-bold flex items-center justify-center" :class="sizeClass(p.size || 'standard')">{{ (p.size || 'standard') === 'large' ? 'B' : 'R' }}</span>
                                    <span class="font-mono font-semibold text-white text-[15px]">{{ p.locker_number || '—' }}</span>
                                    <span class="font-mono font-bold text-[#F59E0B] text-[15px]">({{ p.pin ? p.pin + '#' : '——' }})</span>
                                    <span v-if="['confirmed','active'].includes((b.display_status ?? b.booking_status))"
                                        :title="p.ttlock_registered ? 'PIN active on smart lock' : 'PIN waiting for gateway sync'"
                                        class="w-1.5 h-1.5 rounded-full" :class="p.ttlock_registered ? 'bg-[#10B981]' : 'bg-[#F59E0B]'"></span>
                                    <svg v-if="p.opened" title="Locker opened — in use" class="w-3.5 h-3.5 text-[#10B981]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 018 0M5 11h14a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1v-7a1 1 0 011-1z"/></svg>
                                </div>
                            </div>
                            <div v-else-if="sizeBreakdown(b).length" class="flex flex-col gap-1">
                                <span v-for="(line, i) in sizeBreakdown(b)" :key="i"
                                    class="px-2 py-0.5 rounded-full text-xs inline-block w-fit" :class="sizeClass(line.size)">
                                    {{ line.qty }}× {{ line.size }}<span v-if="line.duration" class="text-[10px] opacity-80"> · {{ durationLabel(line.duration) }}</span>
                                </span>
                            </div>
                            <span v-else class="text-[#6B7280] text-xs">—</span>
                        </td>
                        <td class="px-4 py-3 text-[#F59E0B] font-medium whitespace-nowrap">€{{ Number(b.total_eur).toFixed(2) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex flex-col gap-1 items-start">
                                <span class="px-2 py-0.5 rounded-full text-xs text-center min-w-[5.5rem] inline-block" :class="statusClass((b.display_status ?? b.booking_status))">{{ statusLabel(b.display_status ?? b.booking_status) }}</span>
                                <span class="px-2 py-0.5 rounded-full text-xs text-center min-w-[5.5rem] inline-block"
                                    :class="b.payment_status === 'paid' ? 'bg-[#10B981]/20 text-[#10B981]' : 'bg-[#EF4444]/20 text-[#EF4444]'">
                                    {{ b.payment_status === 'paid' ? 'paid' : 'unpaid' }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1">
                                <button @click="openDetails(b)" title="Details"
                                    class="action-icon" aria-label="Details">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                                <button v-if="(b.display_status ?? b.booking_status) !== 'cancelled'" @click="togglePaid(b)" :title="b.payment_status === 'paid' ? 'Paid — click to mark unpaid' : 'Mark as paid'"
                                    class="action-icon" :class="b.payment_status === 'paid' ? 'text-[#10B981] bg-[#10B981]/10 hover:bg-[#10B981]/20' : 'text-[#A0A0A0] hover:bg-[#10B981]/15 hover:text-[#10B981]'" aria-label="Toggle paid">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                                </button>
                                <button v-if="!isFinal(b)" @click="resendConfirmation(b.id)" title="Resend confirmation"
                                    class="action-icon" aria-label="Resend">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12a8 8 0 018-8v4l5-5-5-5v4a10 10 0 100 20"/></svg>
                                </button>
                                <button v-if="['confirmed','active'].includes((b.display_status ?? b.booking_status))" @click="reissuePin(b.id)" title="Generate new PIN"
                                    class="action-icon" aria-label="New PIN">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="16" r="1"/><path d="M7 11V7a5 5 0 0110 0v4"/><rect x="3" y="11" width="18" height="11" rx="2"/></svg>
                                </button>
                                <button v-if="['confirmed','active'].includes((b.display_status ?? b.booking_status))" @click="extendOpen = b" title="Extend duration"
                                    class="action-icon" aria-label="Extend">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                </button>
                                <button v-if="['confirmed','active'].includes((b.display_status ?? b.booking_status))" @click="cancelBooking(b.id)" title="Cancel booking"
                                    class="action-icon" aria-label="Cancel">
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

        <!-- Pagination + page size -->
        <div v-if="pagination" class="flex items-center justify-between gap-3 mt-4 text-sm flex-wrap">
            <div class="flex items-center gap-3">
                <span class="text-[#A0A0A0]">{{ pagination.from || 0 }}–{{ pagination.to || 0 }} of {{ pagination.total || 0 }}</span>
                <div class="flex items-center gap-1.5 text-[#A0A0A0]">
                    <span class="text-xs">Per page</span>
                    <div class="w-20">
                        <Select size="sm" :model-value="perPage" :options="perPageOptions" @update:model-value="setPerPage" />
                    </div>
                </div>
                <div class="hidden sm:flex items-center gap-3 text-xs text-[#6B7280] border-l border-[#2A2A2A] pl-3">
                    <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span>PIN on lock</span>
                    <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-[#F59E0B]"></span>no PIN / syncing</span>
                    <span class="flex items-center gap-1.5"><svg class="w-3 h-3 text-[#10B981]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 018 0M5 11h14a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1v-7a1 1 0 011-1z"/></svg>opened / in use</span>
                </div>
            </div>
            <div v-if="pagination.last_page > 1" class="flex items-center gap-1 flex-wrap">
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
            size="lg" position="center" title="Booking details" no-padding>
            <div v-if="detailsBooking" class="p-4 space-y-5">
                <!-- Status + traffic source on one line -->
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <span class="booking-pill" :class="statusClass((detailsBooking.display_status ?? detailsBooking.booking_status))">{{ statusLabel(detailsBooking.display_status ?? detailsBooking.booking_status) }}</span>
                    <span class="flex items-center gap-1.5 text-sm text-[#A0A0A0]">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: sourceMeta(detailsBooking.marketing_source).color }"></span>
                        {{ sourceMeta(detailsBooking.marketing_source).label }}
                        <span v-if="detailsBooking.utm_campaign" class="text-xs text-[#6B7280]">· {{ detailsBooking.utm_campaign }}</span>
                    </span>
                </div>
                <dl class="booking-dl">
                    <div v-if="detailsBooking.created_at"><dt class="booking-label">Created</dt>
                        <dd class="booking-value">{{ formatDate(detailsBooking.created_at) }}</dd></div>
                    <div><dt class="booking-label">Booking ID</dt>
                        <dd class="booking-value font-mono">#{{ detailsBooking.booking_number ?? detailsBooking.id }}</dd></div>
                    <div><dt class="booking-label">Name</dt>
                        <dd class="booking-value">{{ detailsBooking.customer?.full_name || '—' }}</dd></div>
                    <div v-if="detailsBooking.customer?.email"><dt class="booking-label">Email</dt>
                        <dd class="booking-value">{{ detailsBooking.customer.email }}</dd></div>
                    <div v-if="detailsBooking.customer?.phone"><dt class="booking-label">Phone</dt>
                        <dd class="booking-value">{{ detailsBooking.customer.phone }}</dd></div>
                    <div><dt class="booking-label">Location</dt>
                        <dd class="booking-value">{{ detailsBooking.location?.name || '—' }}</dd></div>
                    <div><dt class="booking-label">Check-in</dt>
                        <dd class="booking-value">{{ formatDate(detailsBooking.check_in) || '—' }}</dd></div>
                    <div><dt class="booking-label">Check-out</dt>
                        <dd class="booking-value">{{ formatDate(detailsBooking.check_out) || '—' }}</dd></div>
                    <div v-if="durationSummary(detailsBooking)"><dt class="booking-label">Duration</dt>
                        <dd class="booking-value">{{ durationSummary(detailsBooking) }}</dd></div>
                    <div class="items-start"><dt class="booking-label">Items</dt>
                        <dd>
                            <div v-if="pinRows(detailsBooking).length" class="flex flex-col gap-1.5">
                                <div v-for="p in pinRows(detailsBooking)" :key="p.number" class="flex items-center flex-wrap gap-x-2 gap-y-0.5">
                                    <span class="w-5 h-5 rounded-full text-[10px] font-bold flex items-center justify-center shrink-0" :class="sizeClass(p.size)">{{ p.size === 'large' ? 'B' : 'R' }}</span>
                                    <span class="booking-value font-mono">{{ p.number || '—' }}</span>
                                    <span class="font-mono !text-[#F59E0B]">({{ p.pin ? p.pin + '#' : '——' }})</span>
                                </div>
                            </div>
                            <div v-else-if="sizeBreakdown(detailsBooking).length" class="flex flex-col gap-1">
                                <div v-for="(line, i) in sizeBreakdown(detailsBooking)" :key="i" class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full text-[10px] font-bold flex items-center justify-center shrink-0" :class="sizeClass(line.size)">{{ line.size === 'large' ? 'B' : 'R' }}</span>
                                    <span class="booking-value">{{ line.qty }}×</span>
                                </div>
                            </div>
                            <span v-else class="booking-value text-[#6B7280]">—</span>
                        </dd></div>
                    <div><dt class="booking-label">Total</dt>
                        <dd class="booking-value !text-[#F59E0B] font-semibold">€{{ Number(detailsBooking.total_eur).toFixed(2) }}</dd></div>
                    <div v-if="detailsBooking.cancel_reason" class="items-start"><dt class="booking-label">Cancel reason</dt>
                        <dd class="booking-value text-[#EF4444]">{{ detailsBooking.cancel_reason }}</dd></div>
                </dl>

                    <div v-if="detailsBooking.notification_logs?.length" class="pt-4 border-t border-[#2A2A2A]">
                        <button @click="notifBooking = detailsBooking"
                            class="w-full bg-[#111] border border-[#2A2A2A] hover:border-[#3A3A3A] rounded-lg px-4 py-3 flex items-center justify-between transition">
                            <span class="booking-label">Notification history</span>
                            <span class="flex items-center gap-2">
                                <span class="booking-value text-[#A0A0A0] text-xs">{{ detailsBooking.notification_logs.length }} sent</span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-[#A0A0A0]"><path d="M9 18l6-6-6-6"/></svg>
                            </span>
                        </button>
                    </div>
            </div>
        </Modal>

        <!-- Notification history modal — opened from booking details, works on desktop and mobile -->
        <Modal :model-value="!!notifBooking" @update:model-value="v => !v && (notifBooking = null)"
            size="lg" position="center" title="Notification history" :subtitle="notifBooking ? '#' + (notifBooking.booking_number ?? notifBooking.id) : ''">
            <ul v-if="notifBooking" class="flex flex-col gap-2">
                <li v-for="log in notifBooking.notification_logs" :key="log.id"
                    class="bg-[#111] border border-[#2A2A2A]/60 rounded-lg px-3 py-2 grid grid-cols-[auto_1fr_auto_auto] items-center gap-x-3 gap-y-1">
                    <span class="booking-pill shrink-0" :class="statusBadge(log.status)">{{ statusText(log.status) }}</span>
                    <div class="min-w-0 flex flex-col">
                        <span class="booking-value font-medium truncate">{{ eventLabel(log.template) }}</span>
                        <span class="booking-value text-[#A0A0A0] text-xs truncate">{{ 'Email' }} · {{ log.recipient }}</span>
                    </div>
                    <span class="booking-value text-[#6B7280] text-xs whitespace-nowrap">{{ formatDate(log.sent_at || log.created_at) }}</span>
                    <button v-if="log.payload" @click="previewNotification(notifBooking.id, log.id)"
                        class="booking-value text-[#F59E0B] hover:underline text-xs whitespace-nowrap">View</button>
                </li>
            </ul>
        </Modal>

        <!-- Extend modal -->
        <Modal :model-value="!!extendOpen" @update:model-value="v => !v && (extendOpen = null)"
            size="sm" position="center" title="Extend booking">
            <template v-if="extendOpen">
                <p class="text-xs text-[#A0A0A0] mb-4">Add more time on top of current check-out ({{ formatDate(extendOpen.check_out) }}).</p>
                <Select :model-value="extendDuration" :options="extendOptions" @update:model-value="extendDuration = $event" />
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
import Select from '../components/Select.vue';

const perPageOptions = [
    { value: 20, label: '20' },
    { value: 50, label: '50' },
    { value: 100, label: '100' },
    { value: 'all', label: 'All' },
];

const tabs = [
    { key: 'active', label: 'Active & Upcoming' },
    { key: 'completed', label: 'Completed' },
    { key: 'cancelled', label: 'Cancelled' },
    { key: 'all', label: 'All' },
];

// Sortable columns -> label for the mobile sort dropdown.
const sortColumns = [
    { key: 'period', label: 'Check-in' },
    { key: 'number', label: 'Booking #' },
    { key: 'customer', label: 'Customer' },
    { key: 'location', label: 'Location' },
    { key: 'type', label: 'Type' },
    { key: 'total', label: 'Total' },
    { key: 'status', label: 'Status' },
    { key: 'payment', label: 'Payment' },
];
const mobileSortOptions = [
    { value: '', label: 'Default order' },
    ...sortColumns.flatMap(c => [
        { value: `${c.key}:asc`, label: `${c.label} ↑` },
        { value: `${c.key}:desc`, label: `${c.label} ↓` },
    ]),
];

const { apiFetch } = useAuth();
const confirmDialog = useConfirm();
const toast = useToast();

const bookings = ref([]);
const pagination = ref(null);
const search = ref('');
const tab = ref('active');
const sortCol = ref(null);
const sortDir = ref('asc');
const page = ref(1);
const perPage = ref(20);
const loading = ref(false);
const openMenuId = ref(null);
const detailsBooking = ref(null);
const sortMenuOpen = ref(false);
const notifBooking = ref(null);
const extendOpen = ref(null);
const extendDuration = ref('24h');
const extendOptions = [
    { value: '6h', label: '+ 6 hours' },
    { value: '24h', label: '+ 24 hours' },
    { value: '2_days', label: '+ 2 days' },
    { value: '3_days', label: '+ 3 days' },
    { value: '5_days', label: '+ 5 days' },
    { value: '1_week', label: '+ 1 week' },
    { value: '2_weeks', label: '+ 2 weeks' },
    { value: '1_month', label: '+ 1 month' },
];

let searchTimer = null;
let reqToken = 0;

const fetchBookings = async () => {
    const myToken = ++reqToken;
    loading.value = true;
    try {
        const params = new URLSearchParams({ page: page.value });
        params.set('per_page', perPage.value);
        params.set('tab', tab.value);
        if (sortCol.value) { params.set('sort', sortCol.value); params.set('dir', sortDir.value); }
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

const setTab = (t) => {
    tab.value = t;
    sortCol.value = null;
    page.value = 1;
    bookings.value = [];   // clear immediately so a stale list can never linger
    fetchBookings();
};
const toggleSort = (col) => {
    if (sortCol.value === col) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    else { sortCol.value = col; sortDir.value = 'asc'; }
    page.value = 1; fetchBookings();
};
// Mobile sort dropdown emits "col:dir"; null = back to the per-tab default.
const mobileSort = computed(() => sortCol.value ? `${sortCol.value}:${sortDir.value}` : '');
const setMobileSort = (val) => {
    if (!val) { sortCol.value = null; } else { const [c, d] = val.split(':'); sortCol.value = c; sortDir.value = d; }
    page.value = 1; fetchBookings();
};
const goPage = (p) => { if (p < 1 || p > pagination.value?.last_page) return; page.value = p; fetchBookings(); };
const setPerPage = (v) => { perPage.value = v; page.value = 1; fetchBookings(); };

const toggleMenu = (id) => { openMenuId.value = openMenuId.value === id ? null : id; };
const closeMenu = () => { openMenuId.value = null; };

const isFinal = (b) => ['cancelled', 'completed', 'expired'].includes((b.display_status ?? b.booking_status));

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

// One row per locker, joining pin info with item duration.
const pinRows = (b) => {
    if (!b.pins?.length) return [];
    const dur = {};
    (b.items || []).forEach(it => { dur[it.locker_size] = it.duration_key; });
    return b.pins.map(p => ({
        size: p.size || 'standard',
        duration: dur[p.size || 'standard'] || null,
        number: p.locker_number,
        pin: p.pin,
        ttlock: p.ttlock_registered,
    }));
};

// Friendly label for a duration key. Falls back to the raw key.
const durationLabels = {
    '6h': '6 hours', '24h': '24 hours', '2_days': '2 days', '3_days': '3 days', '4_days': '4 days',
    '5_days': '5 days', '1_week': '1 week', '2_weeks': '2 weeks', '1_month': '1 month',
};
const sizeLabel = (s) => s === 'large' ? 'Big' : 'Regular';
const durationLabel = (key) => durationLabels[key] || key || '';
// Distinct duration label(s) for a booking, e.g. "6 hours" (or "6 hours · 1 day"
// when a multi-size booking mixes durations). Shown as its own modal row.
const durationSummary = (b) => {
    const rows = pinRows(b).length ? pinRows(b) : sizeBreakdown(b);
    const set = [...new Set(rows.map(r => r.duration).filter(Boolean))];
    return set.map(durationLabel).join(' · ');
};

// Marketing-attribution channel → label + colour (matches Dashboard widget).
const SOURCE_META = {
    google_ads: { label: 'Google Ads', color: '#4285F4' },
    facebook:   { label: 'Facebook / Meta', color: '#1877F2' },
    organic:    { label: 'Organic search', color: '#10B981' },
    referral:   { label: 'Referral', color: '#A78BFA' },
    direct:     { label: 'Direct', color: '#F59E0B' },
    other:      { label: 'Other', color: '#6B7280' },
};
const sourceMeta = (key) => SOURCE_META[key] || { label: 'Unknown', color: '#3A3A3A' };

// Mobile: group the (already-sorted) cards under date sub-headers so long lists
// stay scannable. The grouping date is picked per tab: check-in for active/
// upcoming, check-out for completed, created date for All, cancel date for
// cancelled. If the user sorts by a non-date column we skip headers (one group).
const groupDateKey = (b) => {
    let v;
    if (tab.value === 'completed') v = b.check_out;
    else if (tab.value === 'cancelled') v = b.cancelled_at || b.check_in;
    else if (tab.value === 'all') v = b.created_at;
    else v = b.check_in; // active & upcoming (default)
    if (!v) return '';
    const dt = new Date(v);
    if (isNaN(dt.getTime())) return '';
    return dt.getFullYear() + '-' + String(dt.getMonth() + 1).padStart(2, '0') + '-' + String(dt.getDate()).padStart(2, '0');
};
const groupDateLabel = (key) => {
    if (!key) return '—';
    const [y, m, d] = key.split('-').map(Number);
    const dt = new Date(y, m - 1, d);
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const diff = Math.round((dt - today) / 86400000);
    if (diff === 0) return 'Today';
    if (diff === 1) return 'Tomorrow';
    if (diff === -1) return 'Yesterday';
    return dt.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short' });
};
const mobileGroups = computed(() => {
    const list = bookings.value || [];
    // Only group by date when the order is date-based (default per-tab order or
    // an explicit "period" sort). Otherwise show a single, header-less group.
    const dateOrdered = !sortCol.value || sortCol.value === 'period';
    if (!dateOrdered) return [{ key: 'all', label: null, items: list }];
    const groups = [];
    let cur = null;
    for (const b of list) {
        const key = groupDateKey(b) || 'na';
        if (!cur || cur.key !== key) {
            cur = { key, label: groupDateLabel(groupDateKey(b)), items: [] };
            groups.push(cur);
        }
        cur.items.push(b);
    }
    return groups;
});

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

const togglePaid = async (b) => {
    try {
        const res = await apiFetch(`/api/admin/bookings/${b.id}/mark-paid`, { method: 'POST' });
        const data = await res.json();
        b.payment_status = data.payment_status;
        if (detailsBooking.value && detailsBooking.value.id === b.id) detailsBooking.value.payment_status = data.payment_status;
        toast.success(data.message || 'Updated');
        fetchBookings();
    } catch { toast.error('Failed to update payment'); }
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
        message: 'A new PIN will be generated and registered on the smart lock. The old PIN will stop working. Customer will receive the new PIN via email.',
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
        const res = await apiFetch(`/api/admin/bookings/${id}`, { method: 'DELETE' });
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            toast.error(data.message || 'Failed to cancel');
            return;
        }
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
    confirmed: 'bg-blue-500/20 text-blue-400',
    active: 'bg-[#10B981]/20 text-[#10B981]',
    pending: 'bg-[#F59E0B]/20 text-[#F59E0B]',
    completed: 'bg-[#A0A0A0]/20 text-[#A0A0A0]',
    cancelled: 'bg-[#EF4444]/20 text-[#EF4444]',
    expired: 'bg-[#6B7280]/20 text-[#6B7280]',
}[s] || 'bg-[#2A2A2A] text-[#A0A0A0]');

// Display label for a status: 'confirmed' reads as 'upcoming' (clearer vs
// 'active'); everything else shows its raw value. Styling still keys off the
// raw value via statusClass().
const statusLabel = (s) => (s === 'confirmed' ? 'upcoming' : s);

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

/* Single source of truth for booking detail typography */
.booking-dl > div {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 6px 0;
}
.booking-label {
    flex: 0 0 110px;
    font-size: 11px;
    font-weight: 500;
    line-height: 1.3;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #6B7280;
    margin: 0;
}
.booking-value {
    font-size: 13px;
    font-weight: 400;
    line-height: 1.4;
    color: #A0A0A0;
}
.booking-pill {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 500;
    line-height: 1.4;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
}
</style>

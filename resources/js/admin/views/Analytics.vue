<template>
    <div>
        <div class="flex items-center justify-between gap-3 mb-4">
            <h1 class="text-2xl font-bold">Analytics</h1>
            <button @click="showHelp = !showHelp"
                class="flex items-center gap-1.5 text-xs text-[#A0A0A0] hover:text-white transition shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ showHelp ? 'Hide guide' : 'What do these mean?' }}
            </button>
        </div>

        <!-- Legend / explanation -->
        <div v-if="showHelp" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4 mb-6 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 text-sm">
            <div>
                <h3 class="text-xs uppercase tracking-wide text-[#A0A0A0] mb-2">Money (per check-in date)</h3>
                <dl class="space-y-1.5 text-[#A0A0A0]">
                    <div><span class="text-[#10B981] font-semibold">Paid</span> — money already collected.</div>
                    <div><span class="text-[#F59E0B] font-semibold">Unpaid</span> — still expected (not yet marked paid).</div>
                    <div><span class="text-white font-semibold">Total</span> — Paid + Unpaid (cancelled excluded).</div>
                    <div><span class="text-[#6B7280] font-semibold">Cancelled</span> — count + value of cancelled bookings (not counted as revenue).</div>
                </dl>
                <p class="text-xs text-[#6B7280] mt-3"><b>Over time</b> chart: each bar = a day/week/month. Toggle <b>Revenue</b> (€) or <b>Bookings</b> (count). Y-axis shows the scale.</p>
            </div>
            <div>
                <h3 class="text-xs uppercase tracking-wide text-[#A0A0A0] mb-2">Where visitors came from (channel)</h3>
                <dl class="space-y-1.5 text-[#A0A0A0]">
                    <div v-for="c in channelLegend" :key="c.key" class="flex gap-2">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0 mt-1" :style="{ backgroundColor: channelMeta(c.key).color }"></span>
                        <span><span class="text-white font-medium">{{ channelMeta(c.key).label }}</span> — {{ c.desc }}</span>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4 mb-6">
            <!-- Presets (left) + Filters toggle (right). Stacks on mobile;
                 natural-width + spread on larger screens. -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <div class="flex gap-1.5 sm:gap-2">
                    <button v-for="p in presets" :key="p.key" @click="setPreset(p.key)"
                        class="flex-1 sm:flex-none px-1 sm:px-4 py-2 rounded-lg text-[11px] sm:text-sm font-medium whitespace-nowrap transition"
                        :class="activePreset === p.key ? 'bg-[#F59E0B] text-black' : 'bg-[#111] border border-[#2A2A2A] text-[#A0A0A0] hover:text-white'">
                        {{ p.label }}
                    </button>
                </div>

                <button @click="filtersOpen = !filtersOpen"
                    class="w-full sm:w-auto sm:ml-auto flex items-center justify-between sm:justify-start gap-2 px-3 py-2.5 sm:py-2 rounded-lg bg-[#111] border border-[#2A2A2A] text-xs sm:text-sm text-[#A0A0A0] hover:text-white transition">
                    <span class="flex items-center gap-2 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M6 8h12M9 12h6M11 16h2"/></svg>
                        Filters
                    </span>
                    <span class="flex items-center gap-2 min-w-0">
                        <span v-if="refreshing" class="w-3 h-3 border-2 border-[#F59E0B] border-t-transparent rounded-full animate-spin shrink-0"></span>
                        <span v-else class="text-[#6B7280] truncate tabular-nums text-[11px] sm:text-xs">{{ srDate(filters.from) }} → {{ srDate(filters.to) }}</span>
                        <svg class="w-4 h-4 shrink-0 transition" :class="filtersOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </span>
                </button>
            </div>

            <div v-show="filtersOpen" class="mt-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-3">
                    <label class="block">
                        <span class="block text-xs text-[#A0A0A0] mb-1">From</span>
                        <DatePicker v-model="filters.from" :max="filters.to" @update:modelValue="activePreset = ''" />
                    </label>
                    <label class="block">
                        <span class="block text-xs text-[#A0A0A0] mb-1">To</span>
                        <DatePicker v-model="filters.to" :min="filters.from" @update:modelValue="activePreset = ''" />
                    </label>
                    <label class="block">
                        <span class="block text-xs text-[#A0A0A0] mb-1">Group by</span>
                        <Select v-model="filters.group" :options="groupOptions" />
                    </label>
                    <label class="block">
                        <span class="block text-xs text-[#A0A0A0] mb-1">Location</span>
                        <Select v-model="filters.location_id" :options="locationOptions" searchable />
                    </label>
                    <label class="block">
                        <span class="block text-xs text-[#A0A0A0] mb-1">Channel</span>
                        <Select v-model="filters.channel" :options="channelOptions" />
                    </label>
                    <label class="block">
                        <span class="block text-xs text-[#A0A0A0] mb-1">Payment</span>
                        <Select v-model="filters.payment" :options="paymentOptions" />
                    </label>
                </div>

                <div class="flex items-center gap-2 mt-4">
                    <button @click="apply" class="flex-1 sm:flex-none px-6 py-2.5 rounded-lg bg-[#F59E0B] text-black text-sm font-semibold hover:bg-[#D97706] transition">Apply</button>
                    <button @click="reset" class="flex-1 sm:flex-none px-5 py-2.5 rounded-lg border border-[#2A2A2A] text-[#A0A0A0] text-sm hover:text-white transition">Reset</button>
                </div>
                <p class="text-xs text-[#6B7280] mt-3">Revenue is attributed to the booking's check-in date.</p>
            </div>
        </div>

        <div v-if="loading" class="text-sm text-[#A0A0A0]">Loading…</div>
        <div v-else-if="error" class="bg-[#EF4444]/10 border border-[#EF4444]/30 rounded-xl p-4 text-sm text-[#EF4444]">
            Failed to load analytics: {{ error }}
        </div>

        <template v-else-if="data">
            <!-- Summary cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                    <p class="text-xs uppercase tracking-wide text-[#A0A0A0]">Bookings</p>
                    <p class="text-2xl font-bold mt-1 text-white">{{ data.summary.bookings }}</p>
                </div>
                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                    <p class="text-xs uppercase tracking-wide text-[#A0A0A0]">Paid</p>
                    <p class="text-lg sm:text-2xl font-bold mt-1 whitespace-nowrap text-[#10B981]">€{{ money(data.summary.paid_eur) }} <span class="text-xs sm:text-sm font-normal text-[#A0A0A0]">/ {{ data.summary.paid_count }}</span></p>
                </div>
                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                    <p class="text-xs uppercase tracking-wide text-[#A0A0A0]">Unpaid</p>
                    <p class="text-lg sm:text-2xl font-bold mt-1 whitespace-nowrap text-[#F59E0B]">€{{ money(data.summary.unpaid_eur) }} <span class="text-xs sm:text-sm font-normal text-[#A0A0A0]">/ {{ data.summary.unpaid_count }}</span></p>
                </div>
                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                    <p class="text-xs uppercase tracking-wide text-[#A0A0A0]">Total</p>
                    <p class="text-lg sm:text-2xl font-bold mt-1 whitespace-nowrap text-white">€{{ money(data.summary.total_eur) }} <span class="text-xs sm:text-sm font-normal text-[#A0A0A0]">/ {{ data.summary.total_count }}</span></p>
                </div>
                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                    <p class="text-xs uppercase tracking-wide text-[#A0A0A0]">Cancelled</p>
                    <p class="text-lg sm:text-2xl font-bold mt-1 whitespace-nowrap text-[#EF4444]">€{{ money(data.summary.cancelled_eur) }} <span class="text-xs sm:text-sm font-normal text-[#A0A0A0]">/ {{ data.summary.cancelled_count }}</span></p>
                </div>
            </div>

            <!-- Site traffic (Google Analytics) -->
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-[#A0A0A0]">Site traffic · Google Analytics</h2>
                    <span class="text-xs text-[#6B7280]">same date range</span>
                </div>
                <div v-if="gaLoading" class="text-sm text-[#A0A0A0]">Loading traffic…</div>
                <div v-else-if="!ga || !ga.ok" class="text-sm text-[#6B7280] italic">{{ gaMessage }}</div>
                <template v-else>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-5">
                        <div><p class="text-xs uppercase tracking-wide text-[#A0A0A0]">Sessions</p><p class="text-xl font-bold mt-1 text-white">{{ ga.headline.sessions }}</p></div>
                        <div><p class="text-xs uppercase tracking-wide text-[#A0A0A0]">Users</p><p class="text-xl font-bold mt-1 text-white">{{ ga.headline.users }}</p></div>
                        <div><p class="text-xs uppercase tracking-wide text-[#A0A0A0]">New users</p><p class="text-xl font-bold mt-1 text-white">{{ ga.headline.new_users }}</p></div>
                        <div><p class="text-xs uppercase tracking-wide text-[#A0A0A0]">Pageviews</p><p class="text-xl font-bold mt-1 text-white">{{ ga.headline.pageviews }}</p></div>
                        <div><p class="text-xs uppercase tracking-wide text-[#A0A0A0]">Conversion</p><p class="text-xl font-bold mt-1 text-[#10B981]">{{ conversionRate }}%</p></div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-xs uppercase tracking-wide text-[#6B7280] mb-2">Traffic by channel</h3>
                            <table class="w-full text-sm">
                                <tr v-for="(c,i) in ga.channels" :key="i" class="border-t border-[#2A2A2A]">
                                    <td class="py-1.5">{{ c.channel }}</td>
                                    <td class="py-1.5 text-right tabular-nums">{{ c.sessions }}</td>
                                </tr>
                            </table>
                        </div>
                        <div>
                            <h3 class="text-xs uppercase tracking-wide text-[#6B7280] mb-2">Top landing pages</h3>
                            <table class="w-full text-sm">
                                <tr v-for="(c,i) in ga.landing" :key="i" class="border-t border-[#2A2A2A]">
                                    <td class="py-1.5 max-w-[240px] truncate">
                                        <a v-if="isPath(c.page)" :href="origin + c.page" target="_blank" class="text-[#F59E0B] hover:underline" :title="c.page">{{ c.page }}</a>
                                        <span v-else class="text-[#A0A0A0]">{{ c.page }}</span>
                                    </td>
                                    <td class="py-1.5 text-right tabular-nums">{{ c.sessions }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Time series -->
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-[#A0A0A0]">Over time ({{ filters.group }})</h2>
                        <p class="text-[11px] mt-0.5" :class="activeBar ? 'text-white' : 'text-[#6B7280]'">
                            <template v-if="activeBar">{{ srDate(activeBar.period) }} · {{ activeBar.bookings }} bookings · <span class="text-[#10B981]">€{{ money(activeBar.revenue) }}</span></template>
                            <template v-else>{{ tsMetric === 'revenue' ? '€ collected per ' + filters.group : 'bookings per ' + filters.group }}</template>
                        </p>
                    </div>
                    <div class="flex gap-1 text-xs shrink-0">
                        <button @click="tsMetric = 'revenue'" :class="tsMetric==='revenue' ? activeTab : idleTab">Revenue</button>
                        <button @click="tsMetric = 'bookings'" :class="tsMetric==='bookings' ? activeTab : idleTab">Bookings</button>
                    </div>
                </div>
                <div v-if="!data.timeseries.length" class="text-sm text-[#6B7280] italic">No data in range.</div>
                <template v-else>
                    <div class="flex">
                        <!-- Fixed Y axis (narrow, stays put while bars scroll) -->
                        <div class="flex flex-col justify-between text-[9px] text-[#6B7280] text-right w-8 shrink-0 h-44 pr-1 py-0.5">
                            <span v-for="t in axisTicksDesc" :key="t">{{ axisLabel(t) }}</span>
                        </div>
                        <!-- Scrollable plot: fixed-width bars + dates scroll together -->
                        <div ref="chartScroll" class="flex-1 overflow-x-auto">
                            <div class="w-max min-w-full">
                                <div class="relative h-44 border-l border-b border-[#2A2A2A]">
                                    <div class="absolute inset-0 flex flex-col justify-between pointer-events-none">
                                        <div v-for="(t, i) in axisTicksDesc" :key="i" class="border-t border-[#2A2A2A]/50"></div>
                                    </div>
                                    <div class="relative h-full flex items-end gap-2 px-2">
                                        <div v-for="(p, i) in data.timeseries" :key="p.period"
                                            @mouseenter="hoverIdx = i" @mouseleave="hoverIdx = null"
                                            @click="clickIdx = clickIdx === i ? null : i"
                                            class="w-7 shrink-0 h-full flex flex-col items-center justify-end relative cursor-pointer">
                                            <div class="w-full rounded-t transition-all"
                                                :class="(hoverIdx === i || clickIdx === i) ? 'bg-[#F59E0B]' : 'bg-[#F59E0B]/80'"
                                                :style="{ height: barHeight(p) }"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Date labels under each bar (same widths → aligned, scroll together) -->
                                <div class="flex gap-2 px-2 mt-1">
                                    <div v-for="(p, i) in data.timeseries" :key="i"
                                        class="w-7 shrink-0 text-center text-[9px] text-[#6B7280] whitespace-nowrap">{{ shortDate(p.period) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Channels + Landing pages -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-[#A0A0A0] mb-4">By channel</h2>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-[#6B7280] text-xs uppercase">
                                <th class="text-left py-1">Channel</th>
                                <SortTh col="count" :s="sChannel" @sort="sortBy(sChannel,$event)">Bookings</SortTh>
                                <SortTh col="revenue" :s="sChannel" @sort="sortBy(sChannel,$event)">Revenue</SortTh>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in sorted(data.by_channel, sChannel)" :key="c.key" class="border-t border-[#2A2A2A]">
                                <td class="py-2 flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: channelMeta(c.key).color }"></span>
                                    {{ channelMeta(c.key).label }}
                                </td>
                                <td class="py-2 text-right tabular-nums">{{ c.count }}</td>
                                <td class="py-2 text-right tabular-nums text-[#10B981]">€{{ money(c.revenue) }}</td>
                            </tr>
                            <tr v-if="!data.by_channel.length"><td colspan="3" class="py-3 text-[#6B7280] italic">No data.</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-[#A0A0A0] mb-4">By entry page (landing)</h2>
                    <table class="w-full text-sm table-fixed">
                        <colgroup><col /><col style="width:60px" /><col style="width:84px" /></colgroup>
                        <thead>
                            <tr class="text-[#6B7280] text-xs uppercase">
                                <th class="text-left py-1">Page</th>
                                <SortTh col="count" :s="sLanding" @sort="sortBy(sLanding,$event)">Book.</SortTh>
                                <SortTh col="revenue" :s="sLanding" @sort="sortBy(sLanding,$event)">Rev.</SortTh>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(c,i) in sorted(data.by_landing, sLanding)" :key="i" class="border-t border-[#2A2A2A]">
                                <td class="py-2 pr-2 truncate">
                                    <a v-if="isPath(c.landing_page)" :href="origin + c.landing_page" target="_blank"
                                        class="text-[#F59E0B] hover:underline" :title="c.landing_page">{{ c.landing_page }}</a>
                                    <span v-else class="text-[#A0A0A0]" :title="c.landing_page">{{ c.landing_page }}</span>
                                </td>
                                <td class="py-2 text-right tabular-nums">{{ c.count }}</td>
                                <td class="py-2 text-right tabular-nums text-[#10B981]">€{{ money(c.revenue) }}</td>
                            </tr>
                            <tr v-if="!data.by_landing.length"><td colspan="3" class="py-3 text-[#6B7280] italic">No data.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Source links (utm) — only shown once there are tagged campaigns
                 (otherwise it just duplicates "By channel"). -->
            <div v-if="hasTaggedCampaigns" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 mb-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-[#A0A0A0] mb-1">By source & campaign</h2>
                <p class="text-xs text-[#6B7280] mb-4"><b>Source</b> = the UTM tag if the link had one, otherwise the detected channel (e.g. Google Ads is recognised via Google’s gclid, not a UTM). <b>Medium / Campaign</b> show only for manually-tagged links — <b>“—”</b> means that tag wasn’t set.</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[560px]">
                        <thead>
                            <tr class="text-[#6B7280] text-xs uppercase">
                                <th class="text-left py-1">Source</th>
                                <th class="text-left py-1">Medium</th>
                                <th class="text-left py-1">Campaign</th>
                                <SortTh col="count" :s="sSource" @sort="sortBy(sSource,$event)">Bookings</SortTh>
                                <SortTh col="revenue" :s="sSource" @sort="sortBy(sSource,$event)">Revenue</SortTh>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(c,i) in sorted(data.by_source, sSource)" :key="i" class="border-t border-[#2A2A2A]">
                                <td class="py-2">{{ sourceLabel(c.utm_source) }}</td>
                                <td class="py-2 text-[#A0A0A0]">{{ c.utm_medium || '—' }}</td>
                                <td class="py-2 text-[#A0A0A0]">{{ c.utm_campaign || '—' }}</td>
                                <td class="py-2 text-right tabular-nums">{{ c.count }}</td>
                                <td class="py-2 text-right tabular-nums text-[#10B981]">€{{ money(c.revenue) }}</td>
                            </tr>
                            <tr v-if="!data.by_source.length"><td colspan="5" class="py-3 text-[#6B7280] italic">No data.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Location + Status -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-[#A0A0A0] mb-4">By location</h2>
                    <table class="w-full text-sm table-fixed">
                        <colgroup><col /><col style="width:60px" /><col style="width:84px" /></colgroup>
                        <thead>
                            <tr class="text-[#6B7280] text-xs uppercase">
                                <th class="text-left py-1">Location</th>
                                <SortTh col="count" :s="sLoc" @sort="sortBy(sLoc,$event)">Book.</SortTh>
                                <SortTh col="revenue" :s="sLoc" @sort="sortBy(sLoc,$event)">Rev.</SortTh>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in sorted(data.by_location, sLoc)" :key="c.id" class="border-t border-[#2A2A2A]">
                                <td class="py-2 pr-2 truncate" :title="c.name">{{ c.name }}</td>
                                <td class="py-2 text-right tabular-nums">{{ c.count }}</td>
                                <td class="py-2 text-right tabular-nums text-[#10B981]">€{{ money(c.revenue) }}</td>
                            </tr>
                            <tr v-if="!data.by_location.length"><td colspan="3" class="py-3 text-[#6B7280] italic">No data.</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-[#A0A0A0] mb-4">By status</h2>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-[#6B7280] text-xs uppercase">
                                <th class="text-left py-1">Status</th>
                                <SortTh col="count" :s="sStatus" @sort="sortBy(sStatus,$event)">Bookings</SortTh>
                                <SortTh col="revenue" :s="sStatus" @sort="sortBy(sStatus,$event)">Revenue</SortTh>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in sorted(statusBuckets, sStatus)" :key="c.key" class="border-t border-[#2A2A2A]">
                                <td class="py-2"><span class="px-2 py-0.5 rounded-full text-xs" :class="c.cls">{{ c.label }}</span></td>
                                <td class="py-2 text-right tabular-nums">{{ c.count }}</td>
                                <td class="py-2 text-right tabular-nums text-[#10B981]">€{{ money(c.revenue) }}</td>
                            </tr>
                            <tr v-if="!data.by_status.length"><td colspan="3" class="py-3 text-[#6B7280] italic">No data.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>
</template>
<script setup>
import { ref, reactive, computed, onMounted, nextTick, h } from 'vue';
import { useAuth } from '../composables/useAuth';
import Select from '../components/Select.vue';
import DatePicker from '../components/DatePicker.vue';

const { apiFetch } = useAuth();
const data = ref(null);
const meta = ref({ locations: [], channels: [], statuses: [] });
const loading = ref(true);
const refreshing = ref(false);
const error = ref(null);
const filtersOpen = ref(false);
const showHelp = ref(false);

// Plain-language channel descriptions for the legend.
const channelLegend = [
    { key: 'direct', desc: 'typed the address or used a bookmark / QR with no tag.' },
    { key: 'organic', desc: 'found you via a search engine (Google, Bing…) unpaid.' },
    { key: 'google_ads', desc: 'clicked a paid Google ad.' },
    { key: 'facebook', desc: 'came from Facebook or Instagram.' },
    { key: 'qr', desc: 'scanned a QR code link tagged utm_source=qr.' },
    { key: 'referral', desc: 'came from another website / partner link.' },
    { key: 'unknown', desc: 'booked before source tracking existed.' },
];
const tsMetric = ref('revenue');
const hoverIdx = ref(null);
const clickIdx = ref(null);
const origin = window.location.origin;
const ga = ref(null);
const gaLoading = ref(true);
const chartScroll = ref(null);

const today = new Date();
const iso = (d) => d.toISOString().slice(0, 10);
const filters = reactive({
    from: iso(new Date(today.getFullYear(), today.getMonth(), 1)), // 1st of this month
    to: iso(today),
    group: 'day',
    location_id: '',
    channel: '',
    payment: 'all',
});

const activeTab = 'px-2 py-1 rounded bg-[#F59E0B] text-black font-medium';
const idleTab = 'px-2 py-1 rounded text-[#A0A0A0] hover:text-white';

const money = (v) => Number(v || 0).toFixed(2);
const isPath = (p) => typeof p === 'string' && p.startsWith('/');
// ISO 'YYYY-MM-DD' → Serbian 'DD.MM.YYYY' (leaves week/month keys untouched).
const srDate = (s) => {
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s || '');
    return m ? `${m[3]}.${m[2]}.${m[1]}` : (s || '');
};
// Compact axis label: 'YYYY-MM-DD' → 'DD.MM'; week/month keys shown as-is.
const shortDate = (s) => {
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s || '');
    return m ? `${m[3]}.${m[2]}` : (s || '');
};
// Show ~8 x-axis labels max so they don't overlap.
const labelStep = computed(() => Math.max(1, Math.ceil((data.value?.timeseries?.length || 1) / 8)));
// The bar the user is hovering/tapping — shown in the chart header (never
// clipped by the horizontal-scroll container, unlike an above-bar tooltip).
const activeBar = computed(() => {
    const idx = clickIdx.value !== null ? clickIdx.value : hoverIdx.value;
    return idx !== null ? (data.value?.timeseries?.[idx] ?? null) : null;
});
// Show the source/campaign table only once at least one booking carries a
// utm_medium or utm_campaign — until then it just mirrors "By channel".
const hasTaggedCampaigns = computed(() => (data.value?.by_source ?? []).some(r => r.utm_medium || r.utm_campaign));

const CHANNEL_META = {
    google_ads: { label: 'Google Ads', color: '#4285F4' },
    facebook:   { label: 'Facebook / Meta', color: '#1877F2' },
    organic:    { label: 'Organic search', color: '#10B981' },
    referral:   { label: 'Referral', color: '#A78BFA' },
    qr:         { label: 'QR code', color: '#EC4899' },
    direct:     { label: 'Direct', color: '#F59E0B' },
    other:      { label: 'Other', color: '#6B7280' },
    unknown:    { label: 'Unknown', color: '#3A3A3A' },
};
const channelMeta = (key) => CHANNEL_META[key] || { label: key, color: '#6B7280' };
// In the source table a value may be a UTM source (e.g. "chatgpt.com") or a
// detected channel key (e.g. "google_ads") — show the friendly channel label
// for the latter, otherwise the raw source.
const sourceLabel = (s) => CHANNEL_META[s] ? CHANNEL_META[s].label : s;

// Booking-status pill styling/labels — identical to the Bookings list so the
// vocabulary matches the rest of the dashboard (confirmed reads as "upcoming").
const statusClass = (s) => ({
    confirmed: 'bg-blue-500/20 text-blue-400',
    active: 'bg-[#10B981]/20 text-[#10B981]',
    pending: 'bg-[#F59E0B]/20 text-[#F59E0B]',
    completed: 'bg-[#A0A0A0]/20 text-[#A0A0A0]',
    cancelled: 'bg-[#EF4444]/20 text-[#EF4444]',
    expired: 'bg-[#6B7280]/20 text-[#6B7280]',
}[s] || 'bg-[#2A2A2A] text-[#A0A0A0]');
const statusLabel = (s) => (s === 'confirmed' ? 'upcoming' : s);

// Roll the raw DB statuses up into the 4 business buckets the dashboard uses.
const STATUS_BUCKETS = [
    { key: 'completed', label: 'Completed', cls: 'bg-[#A0A0A0]/20 text-[#A0A0A0]', raw: ['completed', 'expired'] },
    { key: 'active',    label: 'Active',    cls: 'bg-[#10B981]/20 text-[#10B981]', raw: ['active'] },
    { key: 'upcoming',  label: 'Upcoming',  cls: 'bg-blue-500/20 text-blue-400',   raw: ['confirmed', 'pending'] },
    { key: 'cancelled', label: 'Cancelled', cls: 'bg-[#EF4444]/20 text-[#EF4444]', raw: ['cancelled'] },
];
const statusBuckets = computed(() => {
    const rows = data.value?.by_status ?? [];
    return STATUS_BUCKETS.map(b => {
        const hits = rows.filter(r => b.raw.includes(r.status));
        return {
            key: b.key, label: b.label, cls: b.cls,
            count: hits.reduce((s, r) => s + (r.count || 0), 0),
            revenue: Math.round(hits.reduce((s, r) => s + (r.revenue || 0), 0) * 100) / 100,
        };
    });
});

// Custom-select option lists.
const groupOptions = [
    { value: 'day', label: 'Day' },
    { value: 'week', label: 'Week' },
    { value: 'month', label: 'Month' },
];
const paymentOptions = [
    { value: 'all', label: 'All payments' },
    { value: 'paid', label: 'Paid' },
    { value: 'unpaid', label: 'Unpaid' },
];
const locationOptions = computed(() => [
    { value: '', label: 'All locations' },
    ...((meta.value.locations) || []).map(l => ({ value: l.id, label: l.name })),
]);
const channelOptions = computed(() => [
    { value: '', label: 'All channels' },
    ...((meta.value.channels) || []).map(c => ({ value: c, label: channelMeta(c).label })),
]);

// One-tap date presets.
const presets = [
    { key: 'today', label: 'Today' },
    { key: 'yesterday', label: 'Yesterday' },
    { key: '7', label: '7 days' },
    { key: 'month', label: 'This month' },
];
const activePreset = ref('month');
const setPreset = (key) => {
    const t = new Date();
    if (key === 'today') { filters.from = iso(t); filters.to = iso(t); }
    else if (key === 'yesterday') { const y = new Date(t.getTime() - 86400000); filters.from = iso(y); filters.to = iso(y); }
    else if (key === '7') { filters.from = iso(new Date(t.getTime() - 6 * 86400000)); filters.to = iso(t); }
    else if (key === '30') { filters.from = iso(new Date(t.getTime() - 29 * 86400000)); filters.to = iso(t); }
    else if (key === 'month') { filters.from = iso(new Date(t.getFullYear(), t.getMonth(), 1)); filters.to = iso(t); }
    activePreset.value = key;
    load();
};

// Time-series bar height relative to the max value of the chosen metric.
const tsMax = computed(() => {
    if (!data.value?.timeseries?.length) return 1;
    return Math.max(1, ...data.value.timeseries.map(p => p[tsMetric.value]));
});
// "Nice numbers" axis: round max + round step (1/2/5 × 10ⁿ) so ticks read as
// 0/20/40/60/80/100 instead of arbitrary values like €95/€48.
const niceScale = (max, count = 5) => {
    if (!max || max <= 0) return { max: 1, ticks: [0, 1] };
    const rawStep = max / count;
    const mag = Math.pow(10, Math.floor(Math.log10(rawStep)));
    const norm = rawStep / mag;
    const step = (norm <= 1 ? 1 : norm <= 2 ? 2 : norm <= 5 ? 5 : 10) * mag;
    const niceMax = Math.ceil(max / step) * step;
    const ticks = [];
    for (let v = 0; v <= niceMax + step * 0.001; v += step) ticks.push(Math.round(v * 100) / 100);
    return { max: niceMax, ticks };
};
const axisScale = computed(() => niceScale(tsMax.value, 5));
const axisTicksDesc = computed(() => [...axisScale.value.ticks].reverse());

const barHeight = (p) => `${Math.max(0, (p[tsMetric.value] / axisScale.value.max) * 100)}%`;
// Y-axis tick label — money for revenue, plain count for bookings.
const axisLabel = (v) => tsMetric.value === 'revenue' ? '€' + Math.round(v) : String(Math.round(v));

// ── Client-side sorting per table ────────────────────────────────────────────
const sChannel = reactive({ col: 'count', dir: 'desc' });
const sLanding = reactive({ col: 'count', dir: 'desc' });
const sSource  = reactive({ col: 'count', dir: 'desc' });
const sLoc     = reactive({ col: 'count', dir: 'desc' });
const sStatus  = reactive({ col: 'count', dir: 'desc' });

const sortBy = (state, col) => {
    if (state.col === col) state.dir = state.dir === 'asc' ? 'desc' : 'asc';
    else { state.col = col; state.dir = 'desc'; }
};
const sorted = (arr, state) => {
    if (!arr) return [];
    const m = state.dir === 'asc' ? 1 : -1;
    return [...arr].sort((a, b) => (a[state.col] > b[state.col] ? 1 : a[state.col] < b[state.col] ? -1 : 0) * m);
};

// Sortable header cell.
const SortTh = {
    props: ['col', 's'],
    emits: ['sort'],
    setup(props, { slots, emit }) {
        return () => h('th', {
            class: 'text-right py-1 cursor-pointer select-none hover:text-white',
            onClick: () => emit('sort', props.col),
        }, [
            slots.default ? slots.default() : null,
            props.s.col === props.col ? (props.s.dir === 'asc' ? ' ▲' : ' ▼') : '',
        ]);
    },
};

// Conversion = booking count (our DB) / GA sessions, for the same range.
const conversionRate = computed(() => {
    const s = ga.value?.headline?.sessions || 0;
    const b = data.value?.summary?.bookings || 0;
    return s > 0 ? ((b / s) * 100).toFixed(1) : '0.0';
});
const gaMessage = computed(() => {
    if (!ga.value) return 'No traffic data.';
    if (ga.value.reason === 'not_configured') return 'Google Analytics credentials not installed on the server yet.';
    if (ga.value.reason === 'api_error') return 'GA access not authorized yet — add the ga-reader service account as Viewer on the GA property (it may take up to ~1h to propagate).';
    return 'No traffic data for this range.';
});

const loadGa = async () => {
    gaLoading.value = true;
    try {
        const qs = new URLSearchParams({ from: filters.from, to: filters.to }).toString();
        const res = await apiFetch('/api/admin/analytics/ga?' + qs);
        ga.value = res.ok ? await res.json() : { ok: false, reason: 'api_error' };
    } catch {
        ga.value = { ok: false, reason: 'api_error' };
    } finally {
        gaLoading.value = false;
    }
};

const load = async () => {
    // Full-screen "Loading…" only on the very first load; subsequent filter
    // changes keep the current data visible and just dim slightly (no flicker).
    if (!data.value) loading.value = true;
    else refreshing.value = true;
    error.value = null;
    loadGa(); // async, non-blocking — GA panel fills in when ready
    try {
        const qs = new URLSearchParams({ ...filters }).toString();
        const res = await apiFetch('/api/admin/analytics?' + qs);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        data.value = await res.json();
        meta.value = data.value.filters;
        // Jump the chart to the most recent dates (today is at the far right).
        await nextTick();
        if (chartScroll.value) chartScroll.value.scrollLeft = chartScroll.value.scrollWidth;
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
        refreshing.value = false;
    }
};

const apply = () => load();
const reset = () => {
    filters.from = iso(new Date(today.getFullYear(), today.getMonth(), 1));
    filters.to = iso(today);
    filters.group = 'day';
    filters.location_id = '';
    filters.channel = '';
    filters.payment = 'all';
    activePreset.value = 'month';
    load();
};

onMounted(load);
</script>
<style scoped>
.filt {
    width: 100%;
    height: 42px;
    background: #111;
    border: 1px solid #2A2A2A;
    border-radius: 0.5rem;
    padding: 0 0.7rem;
    font-size: 0.875rem;
    color: #fff;
    /* Render the native date picker in dark mode so the calendar icon + popup
       are visible on the dark theme (was invisible black-on-black = "broken"). */
    color-scheme: dark;
}
.filt:focus { outline: none; border-color: #F59E0B; }
.filt::-webkit-calendar-picker-indicator {
    filter: invert(0.7);
    cursor: pointer;
    opacity: 0.9;
}
</style>

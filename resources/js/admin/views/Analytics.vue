<template>
    <div>
        <!-- Floating chart tooltip (fixed → never clipped by the scroll area) -->
        <div v-if="activeBar" class="fixed z-50 pointer-events-none px-2.5 py-1.5 rounded-lg bg-[#0A0A0A] border border-[#3A3A3A] shadow-xl text-[11px]"
            :style="{ left: tipX + 'px', top: (tipY - 12) + 'px', transform: 'translate(-50%, -100%)' }">
            <div class="text-white font-semibold whitespace-nowrap">{{ srDate(activeBar.period) }}</div>
            <div class="text-[#A0A0A0] whitespace-nowrap">{{ activeBar.bookings }} bookings · <span class="text-[#10B981]">€{{ money(activeBar.revenue) }}</span></div>
        </div>

        <!-- Landing-page reveal toast (full URL + open link); backdrop closes it -->
        <div v-if="pageTip" class="fixed inset-0 z-40" @click="pageTip = null"></div>
        <div v-if="pageTip" class="fixed z-50 px-3 py-2 rounded-lg bg-[#0A0A0A] border border-[#3A3A3A] shadow-xl text-[11px]"
            :style="pageTipStyle">
            <div class="text-white break-all">{{ pageTip.landing_page }}</div>
            <div class="text-[#A0A0A0] mt-1">{{ pageTip.count }} bookings · <span class="text-[#10B981]">€{{ money(pageTip.revenue) }}</span></div>
            <a v-if="isPath(pageTip.landing_page)" :href="origin + pageTip.landing_page" target="_blank" rel="noopener" class="inline-block mt-1.5 text-[#F59E0B] hover:underline">Open ↗</a>
        </div>

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
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-3 sm:p-5 mb-8 overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-[#A0A0A0]">Site traffic · Google Analytics</h2>
                    <span class="text-xs text-[#6B7280]">{{ rangeLabel }}</span>
                </div>
                <div v-if="gaLoading" class="text-sm text-[#A0A0A0]">Loading traffic…</div>
                <div v-else-if="!ga || !ga.ok" class="text-sm text-[#6B7280] italic">{{ gaMessage }}</div>
                <template v-else>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-5">
                        <div><p class="text-xs uppercase tracking-wide text-[#A0A0A0] flex items-center gap-1">Sessions <InfoTip text="Visits to your site — a session groups one visitor's activity (ends after ~30 min idle)." /></p><p class="text-xl font-bold mt-1 text-white">{{ ga.headline.sessions }}</p></div>
                        <div><p class="text-xs uppercase tracking-wide text-[#A0A0A0] flex items-center gap-1">Users <InfoTip text="Unique people who visited (one person can have several sessions)." /></p><p class="text-xl font-bold mt-1 text-white">{{ ga.headline.users }}</p></div>
                        <div><p class="text-xs uppercase tracking-wide text-[#A0A0A0] flex items-center gap-1">New users <InfoTip text="First-time visitors in this period." /></p><p class="text-xl font-bold mt-1 text-white">{{ ga.headline.new_users }}</p></div>
                        <div><p class="text-xs uppercase tracking-wide text-[#A0A0A0] flex items-center gap-1">Pageviews <InfoTip text="Total pages opened — one visit can open many pages." /></p><p class="text-xl font-bold mt-1 text-white">{{ ga.headline.pageviews }}</p></div>
                        <div><p class="text-xs uppercase tracking-wide text-[#A0A0A0] flex items-center gap-1">Avg. time <InfoTip text="Average time a visitor actively spent on the site per session." /></p><p class="text-xl font-bold mt-1 text-white">{{ fmtDuration(ga.headline.avg_duration) }}</p></div>
                        <div><p class="text-xs uppercase tracking-wide text-[#A0A0A0] flex items-center gap-1">Pages/visit <InfoTip text="Average number of pages opened per visit (browsing depth)." /></p><p class="text-xl font-bold mt-1 text-white">{{ ga.headline.pages_per_session ?? '—' }}</p></div>
                        <div><p class="text-xs uppercase tracking-wide text-[#A0A0A0] flex items-center gap-1">Engagement <InfoTip text="Share of visits that were engaged: lasted 10s+, converted, or viewed 2+ pages." /></p><p class="text-xl font-bold mt-1 text-white">{{ Math.round((ga.headline.engagement_rate || 0) * 100) }}%</p></div>
                        <div><p class="text-xs uppercase tracking-wide text-[#A0A0A0] flex items-center gap-1">Conversion <InfoTip text="Bookings ÷ sessions — how many visits turned into a booking." /></p><p class="text-xl font-bold mt-1 text-[#10B981]">{{ conversionRate }}%</p></div>
                    </div>
                    <!-- Channel + new vs returning -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-xs uppercase tracking-wide text-[#6B7280] mb-2 flex items-center gap-1">Traffic by channel <InfoTip text="Where visitors came from, grouped into Google's channels (Direct, Organic Search, Paid Search…)." /></h3>
                            <table class="w-full text-sm">
                                <tr v-for="(c,i) in ga.channels" :key="i" class="border-t border-[#2A2A2A]">
                                    <td class="py-1.5"><span class="inline-flex items-center gap-1">{{ c.channel }} <InfoTip :text="channelInfo(c.channel)" /></span></td>
                                    <td class="py-1.5 text-right tabular-nums">{{ c.sessions }}</td>
                                </tr>
                            </table>
                        </div>
                        <div>
                            <h3 class="text-xs uppercase tracking-wide text-[#6B7280] mb-2 flex items-center gap-1">New vs returning <InfoTip text="First-time visitors vs people coming back again." /></h3>
                            <table class="w-full text-sm">
                                <tr v-for="(c,i) in ga.new_returning" :key="i" class="border-t border-[#2A2A2A]">
                                    <td class="py-1.5 capitalize">{{ c.type || '(unknown)' }}</td>
                                    <td class="py-1.5 text-right tabular-nums">{{ c.sessions }}</td>
                                </tr>
                                <tr v-if="!ga.new_returning || !ga.new_returning.length"><td colspan="2" class="py-2 text-[#6B7280] italic">No data.</td></tr>
                            </table>
                        </div>
                    </div>

                    <!-- When (by hour) + by day of week -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                        <div>
                            <h3 class="text-xs uppercase tracking-wide text-[#6B7280] mb-2 flex items-center gap-1">When visitors come (by hour) <InfoTip text="Sessions by hour of day — taller bars = busier hours." /></h3>
                            <div class="flex items-end gap-0.5 h-28 border-b border-[#2A2A2A]">
                                <div v-for="(s,h) in (ga.by_hour || [])" :key="h"
                                    class="flex-1 bg-[#F59E0B]/80 rounded-t hover:bg-[#F59E0B]"
                                    :style="{ height: Math.max(2, (s / gaHourMax) * 100) + '%' }"
                                    :title="h + ':00 — ' + s + ' sessions'"></div>
                            </div>
                            <div class="flex gap-0.5 text-[8px] leading-none text-[#6B7280] mt-1">
                                <span v-for="(s,h) in (ga.by_hour || [])" :key="h" class="flex-1 text-center tabular-nums">{{ String(h).padStart(2,'0') }}</span>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xs uppercase tracking-wide text-[#6B7280] mb-2 flex items-center gap-1">By day of week <InfoTip text="Sessions per weekday — which days are busiest." /></h3>
                            <div class="flex items-end gap-1.5 h-28 border-b border-[#2A2A2A]">
                                <div v-for="(s,d) in (ga.by_dow || [])" :key="d"
                                    class="flex-1 bg-[#F59E0B]/80 rounded-t hover:bg-[#F59E0B]"
                                    :style="{ height: Math.max(2, (s / gaDowMax) * 100) + '%' }"
                                    :title="dowNames[d] + ' — ' + s + ' sessions'"></div>
                            </div>
                            <div class="flex justify-between text-[10px] text-[#6B7280] mt-1"><span v-for="(n,d) in dowNames" :key="d">{{ n }}</span></div>
                        </div>
                    </div>

                    <!-- Devices + countries -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                        <div>
                            <h3 class="text-xs uppercase tracking-wide text-[#6B7280] mb-2 flex items-center gap-1">Devices <InfoTip text="What device visitors used — phone, computer or tablet." /></h3>
                            <table class="w-full text-sm">
                                <tr v-for="(c,i) in ga.devices" :key="i" class="border-t border-[#2A2A2A]">
                                    <td class="py-1.5 capitalize">{{ c.device }}</td>
                                    <td class="py-1.5 text-right tabular-nums">{{ c.sessions }}</td>
                                </tr>
                            </table>
                        </div>
                        <div>
                            <h3 class="text-xs uppercase tracking-wide text-[#6B7280] mb-2 flex items-center gap-1">Top countries <InfoTip text="Which countries your visitors are in (by sessions)." /></h3>
                            <table class="w-full text-sm">
                                <tr v-for="(c,i) in ga.countries" :key="i" class="border-t border-[#2A2A2A]">
                                    <td class="py-1.5">{{ c.country }}</td>
                                    <td class="py-1.5 text-right tabular-nums">{{ c.sessions }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Pages (bottom): landing + most viewed side by side -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                        <div>
                            <h3 class="text-xs uppercase tracking-wide text-[#6B7280] mb-2 flex items-center gap-1">Top landing pages <InfoTip text="The FIRST page each visit started on — how people enter the site (counted by sessions)." /></h3>
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
                        <div>
                            <h3 class="text-xs uppercase tracking-wide text-[#6B7280] mb-2 flex items-center gap-1">Most viewed pages <InfoTip text="Pages opened most often across ALL visits (counted by views, not just entry pages)." /></h3>
                            <table class="w-full text-sm">
                                <tr v-for="(c,i) in ga.pages" :key="i" class="border-t border-[#2A2A2A]">
                                    <td class="py-1.5 max-w-[240px] truncate">
                                        <a v-if="isPath(c.page)" :href="origin + c.page" target="_blank" class="text-[#F59E0B] hover:underline" :title="c.page">{{ c.page }}</a>
                                        <span v-else class="text-[#A0A0A0]" :title="c.page">{{ c.page || '(other)' }}</span>
                                    </td>
                                    <td class="py-1.5 text-right tabular-nums">{{ c.views }}</td>
                                </tr>
                                <tr v-if="!ga.pages || !ga.pages.length"><td colspan="2" class="py-2 text-[#6B7280] italic">No data.</td></tr>
                            </table>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Search performance (Google Search Console) -->
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-3 sm:p-5 mb-8 overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-[#A0A0A0] flex items-center gap-1">Search · Google Search Console <InfoTip text="What people searched on Google to find you (queries), how often you appeared (impressions), how many clicked through, and your average position. Data lags ~2 days." /></h2>
                    <span class="text-xs text-[#6B7280]">{{ rangeLabel }}</span>
                </div>
                <div v-if="scLoading" class="text-sm text-[#A0A0A0]">Loading search data…</div>
                <div v-else-if="!sc || !sc.ok" class="text-sm text-[#6B7280] italic">{{ scMessage }}</div>
                <template v-else>
                    <!-- Headline metrics -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
                        <div><p class="text-xs uppercase tracking-wide text-[#A0A0A0] flex items-center gap-1">Clicks <InfoTip text="Times someone clicked your site in Google search results." /></p><p class="text-xl font-bold mt-1 text-[#10B981]">{{ sc.headline.clicks }}</p></div>
                        <div><p class="text-xs uppercase tracking-wide text-[#A0A0A0] flex items-center gap-1">Impressions <InfoTip text="Times your site appeared in search results (whether clicked or not)." /></p><p class="text-xl font-bold mt-1 text-white">{{ sc.headline.impressions }}</p></div>
                        <div><p class="text-xs uppercase tracking-wide text-[#A0A0A0] flex items-center gap-1">CTR <InfoTip text="Click-through rate = clicks ÷ impressions. Higher means your title/description is compelling." /></p><p class="text-xl font-bold mt-1 text-white">{{ sc.headline.ctr }}%</p></div>
                        <div><p class="text-xs uppercase tracking-wide text-[#A0A0A0] flex items-center gap-1">Avg. position <InfoTip text="Average ranking of your site in results (1 = top). Lower is better." /></p><p class="text-xl font-bold mt-1 text-white">{{ sc.headline.position }}</p></div>
                    </div>

                    <!-- Impressions trend -->
                    <div v-if="sc.timeseries && sc.timeseries.length" class="mb-6">
                        <h3 class="text-xs uppercase tracking-wide text-[#6B7280] mb-2 flex items-center gap-1">Impressions over time <InfoTip text="How often you appeared in search each day across the selected range." /></h3>
                        <div class="overflow-x-auto">
                            <div class="w-max min-w-full">
                                <div class="h-24 flex items-end gap-1 px-1">
                                    <div v-for="(d,i) in sc.timeseries" :key="i" :title="d.date + ': ' + d.impressions + ' impressions, ' + d.clicks + ' clicks'"
                                        class="w-3 shrink-0 bg-[#3B82F6]/70 hover:bg-[#3B82F6] rounded-t"
                                        :style="{ height: Math.max(2, Math.round((d.impressions / scMax) * 96)) + 'px' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top queries + Top pages side by side -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="min-w-0">
                            <h3 class="text-xs uppercase tracking-wide text-[#6B7280] mb-2 flex items-center gap-1">Top search queries <InfoTip text="The actual phrases people typed on Google, ranked by clicks." /></h3>
                            <div class="overflow-x-auto -mx-1 px-1">
                                <table class="w-full text-sm min-w-[380px]">
                                    <thead><tr class="text-[#6B7280] text-[11px] uppercase">
                                        <th class="text-left py-1">Query</th>
                                        <th class="text-right py-1">Clicks</th>
                                        <th class="text-right py-1">Impr.</th>
                                        <th class="text-right py-1">CTR</th>
                                        <th class="text-right py-1">Pos.</th>
                                    </tr></thead>
                                    <tbody>
                                        <tr v-for="(q,i) in sc.queries" :key="i" class="border-t border-[#2A2A2A]">
                                            <td class="py-1.5" :title="q.query">{{ trimText(q.query) }}</td>
                                            <td class="py-1.5 text-right tabular-nums text-[#10B981]">{{ q.clicks }}</td>
                                            <td class="py-1.5 text-right tabular-nums">{{ q.impressions }}</td>
                                            <td class="py-1.5 text-right tabular-nums">{{ q.ctr }}%</td>
                                            <td class="py-1.5 text-right tabular-nums">{{ q.position }}</td>
                                        </tr>
                                        <tr v-if="!sc.queries.length"><td colspan="5" class="py-2 text-[#6B7280] italic">No queries in range.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-xs uppercase tracking-wide text-[#6B7280] mb-2 flex items-center gap-1">Top pages in search <InfoTip text="Which of your pages got the most clicks from Google search." /></h3>
                            <div class="overflow-x-auto -mx-1 px-1">
                                <table class="w-full text-sm min-w-[380px]">
                                    <thead><tr class="text-[#6B7280] text-[11px] uppercase">
                                        <th class="text-left py-1">Page</th>
                                        <th class="text-right py-1">Clicks</th>
                                        <th class="text-right py-1">Impr.</th>
                                        <th class="text-right py-1">CTR</th>
                                        <th class="text-right py-1">Pos.</th>
                                    </tr></thead>
                                    <tbody>
                                        <tr v-for="(p,i) in sc.pages" :key="i" class="border-t border-[#2A2A2A]">
                                            <td class="py-1.5" :title="p.page">{{ trimText(p.page.replace(/^https?:\/\/[^/]+/, '') || '/') }}</td>
                                            <td class="py-1.5 text-right tabular-nums text-[#10B981]">{{ p.clicks }}</td>
                                            <td class="py-1.5 text-right tabular-nums">{{ p.impressions }}</td>
                                            <td class="py-1.5 text-right tabular-nums">{{ p.ctr }}%</td>
                                            <td class="py-1.5 text-right tabular-nums">{{ p.position }}</td>
                                        </tr>
                                        <tr v-if="!sc.pages.length"><td colspan="5" class="py-2 text-[#6B7280] italic">No pages in range.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Time series -->
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-[#A0A0A0] flex items-center gap-1">Over time ({{ filters.group }}) <InfoTip text="Revenue (€ collected) or number of bookings per day/week/month over the selected range. Attributed to the check-in date. Hover/tap a bar for details." /></h2>
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
                                            @mouseenter="hoverIdx = i; tipX = $event.clientX; tipY = $event.clientY"
                                            @mousemove="tipX = $event.clientX; tipY = $event.clientY"
                                            @mouseleave="hoverIdx = null"
                                            @click="clickIdx = clickIdx === i ? null : i; tipX = $event.clientX; tipY = $event.clientY"
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
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-[#A0A0A0] mb-4 flex items-center gap-1">By channel <InfoTip text="Which marketing source each BOOKING came from (from the visitor's first-touch attribution)." /></h2>
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
                                <td class="py-2">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: channelMeta(c.key).color }"></span>
                                        {{ channelMeta(c.key).label }}
                                        <InfoTip :text="bookingChannelInfo(c.key)" />
                                    </span>
                                </td>
                                <td class="py-2 text-right tabular-nums">{{ c.count }}</td>
                                <td class="py-2 text-right tabular-nums text-[#10B981]">€{{ money(c.revenue) }}</td>
                            </tr>
                            <tr v-if="!data.by_channel.length"><td colspan="3" class="py-3 text-[#6B7280] italic">No data.</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-[#A0A0A0] mb-4 flex items-center gap-1">By entry page (landing) <InfoTip text="The page the visitor first landed on before this booking (their first-touch landing page). Tap a row to see the full URL." /></h2>
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
                                <td class="py-2 pr-2">
                                    <button @click="openPageTip($event, c)" class="flex items-center gap-1.5 w-full text-left">
                                        <span class="truncate" :class="isPath(c.landing_page) ? 'text-[#F59E0B]' : 'text-[#A0A0A0]'">{{ c.landing_page }}</span>
                                        <svg class="w-3.5 h-3.5 shrink-0 text-[#6B7280]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
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
                <div v-if="hasMultipleLocations" class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
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
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-[#A0A0A0] mb-4 flex items-center gap-1">By status <InfoTip text="Bookings grouped into Completed / Active / Upcoming / Cancelled. Value = total € of all bookings in that status (paid + unpaid), shown in the status colour." /></h2>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-[#6B7280] text-xs uppercase">
                                <th class="text-left py-1">Status</th>
                                <SortTh col="count" :s="sStatus" @sort="sortBy(sStatus,$event)">Bookings</SortTh>
                                <SortTh col="value" :s="sStatus" @sort="sortBy(sStatus,$event)">Value</SortTh>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in sorted(statusBuckets, sStatus)" :key="c.key" class="border-t border-[#2A2A2A]">
                                <td class="py-2"><span class="inline-flex items-center gap-1"><span class="px-2 py-0.5 rounded-full text-xs" :class="c.cls">{{ c.label }}</span> <InfoTip :text="statusInfo(c.key)" /></span></td>
                                <td class="py-2 text-right tabular-nums">{{ c.count }}</td>
                                <td class="py-2 text-right tabular-nums font-medium" :class="c.txt">€{{ money(c.value) }}</td>
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
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, h } from 'vue';
import { useAuth } from '../composables/useAuth';
import Select from '../components/Select.vue';
import DatePicker from '../components/DatePicker.vue';
import InfoTip from '../components/InfoTip.vue';

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
const sc = ref(null);
const scLoading = ref(true);
const chartScroll = ref(null);
const tipX = ref(0);
const tipY = ref(0);

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
// The bar the user is hovering/tapping — shown in the chart header (never
// clipped by the horizontal-scroll container, unlike an above-bar tooltip).
const activeBar = computed(() => {
    const idx = clickIdx.value !== null ? clickIdx.value : hoverIdx.value;
    return idx !== null ? (data.value?.timeseries?.[idx] ?? null) : null;
});
// Show the source/campaign table only once at least one booking carries a
// utm_medium or utm_campaign — until then it just mirrors "By channel".
const hasTaggedCampaigns = computed(() => (data.value?.by_source ?? []).some(r => r.utm_medium || r.utm_campaign));
// Hide the By-location table while there's only one location (nothing to compare).
const hasMultipleLocations = computed(() => (data.value?.by_location?.length ?? 0) > 1);

// Tap-to-reveal full landing page (long auto-tagged URLs truncate in the table).
// The toast is anchored to the clicked row, clamped on-screen, and re-positioned
// on scroll/resize so it tracks the element instead of staying stuck.
const pageTip = ref(null);
const pageTipEl = ref(null);
const pageTipStyle = ref({});
const PAGETIP_MARGIN = 16;
const computePageTipPos = () => {
    const el = pageTipEl.value;
    if (!el) return;
    const r = el.getBoundingClientRect();
    if (r.bottom < 0 || r.top > window.innerHeight) { pageTip.value = null; return; } // scrolled away
    const w = Math.min(280, window.innerWidth - PAGETIP_MARGIN * 2);
    const left = Math.min(Math.max(PAGETIP_MARGIN, r.left), window.innerWidth - w - PAGETIP_MARGIN);
    pageTipStyle.value = { left: left + 'px', top: (r.top - 8) + 'px', transform: 'translateY(-100%)', maxWidth: w + 'px' };
};
const openPageTip = (e, row) => {
    if (pageTip.value === row) { pageTip.value = null; return; }
    pageTip.value = row;
    pageTipEl.value = e.currentTarget;
    computePageTipPos();
};
const onPageTipReposition = () => { if (pageTip.value) computePageTipPos(); };
onMounted(() => {
    window.addEventListener('scroll', onPageTipReposition, true);
    window.addEventListener('resize', onPageTipReposition);
});
onUnmounted(() => {
    window.removeEventListener('scroll', onPageTipReposition, true);
    window.removeEventListener('resize', onPageTipReposition);
});

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

// Plain-language explanations for GA's channel names (shown via InfoTip).
const GA_CHANNEL_INFO = {
    'Direct': 'Typed the address or used a bookmark — no referring source.',
    'Organic Search': 'Came from unpaid Google/Bing search results.',
    'Paid Search': 'Clicked a paid search ad (Google Ads).',
    'Paid Social': 'Clicked a paid social ad (Facebook/Instagram).',
    'Organic Social': 'Came from a social post (not paid).',
    'Referral': 'Came from a link on another website.',
    'Email': 'Came from an email link.',
    'Cross-network': 'Google Ads shown across networks (Search + Display + YouTube).',
    'Display': 'Clicked a banner / display ad.',
    'Affiliates': 'Came via an affiliate link.',
    'Unassigned': 'GA could not classify the source (often blocked/missing tags).',
};
const channelInfo = (c) => GA_CHANNEL_INFO[c] || 'Traffic source as classified by Google Analytics.';

// Booking-side (our own marketing_source) channel + status explanations.
const BOOKING_CHANNEL_INFO = {
    direct: 'Typed the address, a bookmark, or an untagged QR — no source.',
    organic: 'Found you via a search engine (unpaid).',
    google_ads: 'Clicked a paid Google ad (gclid detected).',
    facebook: 'Came from Facebook or Instagram.',
    qr: 'Scanned a QR-code link tagged utm_source=qr.',
    referral: 'Came from a link on another website / partner.',
    unknown: 'Booked before source tracking existed.',
    other: 'Other tagged source.',
};
const bookingChannelInfo = (k) => BOOKING_CHANNEL_INFO[k] || 'Marketing source of the booking.';
const STATUS_INFO = {
    completed: 'Stay finished (completed + expired bookings).',
    active: 'Currently in use — checked in, not yet checked out.',
    upcoming: 'Confirmed / pending bookings starting in the future.',
    cancelled: 'Cancelled bookings. Value shown is the lost booking amount (not counted as earned revenue).',
};
const statusInfo = (k) => STATUS_INFO[k] || '';
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
    { key: 'completed', label: 'Completed', cls: 'bg-[#A0A0A0]/20 text-[#A0A0A0]', txt: 'text-[#10B981]',  raw: ['completed', 'expired'] },
    { key: 'active',    label: 'Active',    cls: 'bg-[#10B981]/20 text-[#10B981]', txt: 'text-[#10B981]', raw: ['active'] },
    { key: 'upcoming',  label: 'Upcoming',  cls: 'bg-blue-500/20 text-blue-400',   txt: 'text-blue-400',  raw: ['confirmed', 'pending'] },
    { key: 'cancelled', label: 'Cancelled', cls: 'bg-[#EF4444]/20 text-[#EF4444]', txt: 'text-[#EF4444]', raw: ['cancelled'] },
];
const statusBuckets = computed(() => {
    const rows = data.value?.by_status ?? [];
    return STATUS_BUCKETS.map(b => {
        const hits = rows.filter(r => b.raw.includes(r.status));
        return {
            key: b.key, label: b.label, cls: b.cls, txt: b.txt,
            count: hits.reduce((s, r) => s + (r.count || 0), 0),
            // `value` = gross booking value for the status (paid + unpaid),
            // so cancelled/upcoming/active show their real € amount, not €0.
            value: Math.round(hits.reduce((s, r) => s + (r.value || 0), 0) * 100) / 100,
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
// Human label for the active range — preset name, or the custom dd.mm→dd.mm.
const rangeLabel = computed(() => {
    const p = presets.find(x => x.key === activePreset.value);
    return p ? p.label : `${srDate(filters.from)} → ${srDate(filters.to)}`;
});
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
// Seconds → "Xm Ys" (or "Ys"); used for avg engagement time.
const fmtDuration = (sec) => {
    const s = Math.round(sec || 0);
    return s >= 60 ? `${Math.floor(s / 60)}m ${s % 60}s` : `${s}s`;
};
const gaHourMax = computed(() => Math.max(1, ...((ga.value?.by_hour) || [0])));
const gaDowMax = computed(() => Math.max(1, ...((ga.value?.by_dow) || [0])));
const dowNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; // GA4: 0=Sunday
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

// ── Search Console ──────────────────────────────────────────────────────────
const scMax = computed(() => {
    const ts = sc.value?.timeseries || [];
    return Math.max(1, ...ts.map(d => d.impressions || 0));
});
const scMessage = computed(() => {
    const r = sc.value?.reason;
    if (r === 'not_configured') return 'Search Console credentials not installed on the server yet.';
    if (r === 'auth_failed') return 'Could not authenticate with Google. Check the service-account key on the server.';
    if (r === 'forbidden') return `Search Console access not granted yet. In Search Console → Settings → Users and permissions → Add user, add: ${sc.value?.service_account || 'the ga-reader service account'} (Full or Restricted). Data appears within ~1h after granting.`;
    if (r === 'api_error') return 'Search Console API error — try again shortly.';
    return 'No Search Console data for this range (data also lags ~2 days).';
});
const loadSc = async () => {
    scLoading.value = true;
    try {
        const qs = new URLSearchParams({ from: filters.from, to: filters.to }).toString();
        const res = await apiFetch('/api/admin/analytics/search-console?' + qs);
        sc.value = res.ok ? await res.json() : { ok: false, reason: 'api_error' };
    } catch {
        sc.value = { ok: false, reason: 'api_error' };
    } finally {
        scLoading.value = false;
    }
};
// Trim a long URL/query for display.
const trimText = (s, n = 48) => {
    s = String(s || '');
    return s.length > n ? s.slice(0, n - 1) + '…' : s;
};

const load = async () => {
    // Full-screen "Loading…" only on the very first load; subsequent filter
    // changes keep the current data visible and just dim slightly (no flicker).
    if (!data.value) loading.value = true;
    else refreshing.value = true;
    error.value = null;
    loadGa(); // async, non-blocking — GA panel fills in when ready
    loadSc(); // async, non-blocking — Search Console panel fills in when ready
    try {
        const qs = new URLSearchParams({ ...filters }).toString();
        const res = await apiFetch('/api/admin/analytics?' + qs);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        data.value = await res.json();
        meta.value = data.value.filters;
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
        refreshing.value = false;
    }
    // After the chart has actually rendered (loading is now false), jump it to
    // the most recent dates so today is visible immediately.
    await nextTick();
    if (chartScroll.value && data.value) chartScroll.value.scrollLeft = chartScroll.value.scrollWidth;
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

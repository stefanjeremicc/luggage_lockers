<template>
    <div>
        <h1 class="text-2xl font-bold mb-6">Analytics</h1>

        <!-- Filters -->
        <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4 mb-6">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <label class="block">
                    <span class="text-xs text-[#A0A0A0]">From</span>
                    <input type="date" v-model="filters.from" class="filt" />
                </label>
                <label class="block">
                    <span class="text-xs text-[#A0A0A0]">To</span>
                    <input type="date" v-model="filters.to" class="filt" />
                </label>
                <label class="block">
                    <span class="text-xs text-[#A0A0A0]">Group by</span>
                    <select v-model="filters.group" class="filt">
                        <option value="day">Day</option>
                        <option value="week">Week</option>
                        <option value="month">Month</option>
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs text-[#A0A0A0]">Location</span>
                    <select v-model="filters.location_id" class="filt">
                        <option value="">All</option>
                        <option v-for="l in meta.locations" :key="l.id" :value="l.id">{{ l.name }}</option>
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs text-[#A0A0A0]">Channel</span>
                    <select v-model="filters.channel" class="filt">
                        <option value="">All</option>
                        <option v-for="c in meta.channels" :key="c" :value="c">{{ channelMeta(c).label }}</option>
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs text-[#A0A0A0]">Payment</span>
                    <select v-model="filters.payment" class="filt">
                        <option value="all">All</option>
                        <option value="paid">Paid</option>
                        <option value="unpaid">Unpaid</option>
                    </select>
                </label>
            </div>
            <div class="flex flex-wrap items-center gap-2 mt-3">
                <button @click="apply" class="px-4 py-1.5 rounded-lg bg-[#F59E0B] text-black text-sm font-medium hover:bg-[#D97706]">Apply</button>
                <button @click="reset" class="px-3 py-1.5 rounded-lg border border-[#2A2A2A] text-[#A0A0A0] text-sm hover:text-white">Reset</button>
                <span class="text-xs text-[#6B7280] ml-auto">Revenue is attributed to the booking's check-in date.</span>
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
                    <p class="text-xs uppercase tracking-wide text-[#A0A0A0]">Collected (paid)</p>
                    <p class="text-2xl font-bold mt-1 text-[#10B981]">€{{ money(data.summary.paid_eur) }}</p>
                </div>
                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                    <p class="text-xs uppercase tracking-wide text-[#A0A0A0]">Outstanding (unpaid)</p>
                    <p class="text-2xl font-bold mt-1 text-[#F59E0B]">€{{ money(data.summary.unpaid_eur) }}</p>
                </div>
                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                    <p class="text-xs uppercase tracking-wide text-[#A0A0A0]">Total (non-cancelled)</p>
                    <p class="text-2xl font-bold mt-1 text-white">€{{ money(data.summary.total_eur) }}</p>
                </div>
                <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5">
                    <p class="text-xs uppercase tracking-wide text-[#A0A0A0]">Cancelled</p>
                    <p class="text-2xl font-bold mt-1 text-[#6B7280]">{{ data.summary.cancelled_count }} <span class="text-sm font-normal">/ €{{ money(data.summary.cancelled_eur) }}</span></p>
                </div>
            </div>

            <!-- Time series -->
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-[#A0A0A0]">Over time ({{ filters.group }})</h2>
                    <div class="flex gap-1 text-xs">
                        <button @click="tsMetric = 'revenue'" :class="tsMetric==='revenue' ? activeTab : idleTab">Revenue</button>
                        <button @click="tsMetric = 'bookings'" :class="tsMetric==='bookings' ? activeTab : idleTab">Bookings</button>
                    </div>
                </div>
                <div v-if="!data.timeseries.length" class="text-sm text-[#6B7280] italic">No data in range.</div>
                <div v-else class="flex items-end gap-1 h-44 overflow-x-auto pb-1">
                    <div v-for="p in data.timeseries" :key="p.period"
                        class="flex-1 min-w-[10px] flex flex-col items-center justify-end group relative">
                        <div class="w-full rounded-t bg-[#F59E0B]/80 hover:bg-[#F59E0B] transition-all"
                            :style="{ height: barHeight(p) }"
                            :title="`${p.period} · ${p.bookings} bookings · €${money(p.revenue)}`"></div>
                    </div>
                </div>
                <div v-if="data.timeseries.length" class="flex justify-between text-[10px] text-[#6B7280] mt-2">
                    <span>{{ data.timeseries[0].period }}</span>
                    <span>{{ data.timeseries[data.timeseries.length-1].period }}</span>
                </div>
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
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-[#6B7280] text-xs uppercase">
                                <th class="text-left py-1">Page</th>
                                <SortTh col="count" :s="sLanding" @sort="sortBy(sLanding,$event)">Bookings</SortTh>
                                <SortTh col="revenue" :s="sLanding" @sort="sortBy(sLanding,$event)">Revenue</SortTh>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(c,i) in sorted(data.by_landing, sLanding)" :key="i" class="border-t border-[#2A2A2A]">
                                <td class="py-2 max-w-[220px] truncate">
                                    <a v-if="isPath(c.landing_page)" :href="origin + c.landing_page" target="_blank"
                                        class="text-[#F59E0B] hover:underline" :title="c.landing_page">{{ c.landing_page }}</a>
                                    <span v-else class="text-[#A0A0A0]">{{ c.landing_page }}</span>
                                </td>
                                <td class="py-2 text-right tabular-nums">{{ c.count }}</td>
                                <td class="py-2 text-right tabular-nums text-[#10B981]">€{{ money(c.revenue) }}</td>
                            </tr>
                            <tr v-if="!data.by_landing.length"><td colspan="3" class="py-3 text-[#6B7280] italic">No data.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Source links (utm) -->
            <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-5 mb-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-[#A0A0A0] mb-1">By link (UTM source / medium / campaign)</h2>
                <p class="text-xs text-[#6B7280] mb-4">Each marketing link (QR code, Google Ads, etc.) tagged with its own UTM shows here.</p>
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
                                <td class="py-2">{{ c.utm_source }}</td>
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
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-[#6B7280] text-xs uppercase">
                                <th class="text-left py-1">Location</th>
                                <SortTh col="count" :s="sLoc" @sort="sortBy(sLoc,$event)">Bookings</SortTh>
                                <SortTh col="revenue" :s="sLoc" @sort="sortBy(sLoc,$event)">Revenue</SortTh>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in sorted(data.by_location, sLoc)" :key="c.id" class="border-t border-[#2A2A2A]">
                                <td class="py-2">{{ c.name }}</td>
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
                            <tr v-for="c in sorted(data.by_status, sStatus)" :key="c.status" class="border-t border-[#2A2A2A]">
                                <td class="py-2 capitalize">{{ c.status }}</td>
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
import { ref, reactive, computed, onMounted, h } from 'vue';
import { useAuth } from '../composables/useAuth';

const { apiFetch } = useAuth();
const data = ref(null);
const meta = ref({ locations: [], channels: [], statuses: [] });
const loading = ref(true);
const error = ref(null);
const tsMetric = ref('revenue');
const origin = window.location.origin;

const today = new Date();
const iso = (d) => d.toISOString().slice(0, 10);
const filters = reactive({
    from: iso(new Date(today.getTime() - 29 * 86400000)),
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

// Time-series bar height relative to the max value of the chosen metric.
const tsMax = computed(() => {
    if (!data.value?.timeseries?.length) return 1;
    return Math.max(1, ...data.value.timeseries.map(p => p[tsMetric.value]));
});
const barHeight = (p) => `${Math.max(2, (p[tsMetric.value] / tsMax.value) * 100)}%`;

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

const load = async () => {
    loading.value = true;
    error.value = null;
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
    }
};

const apply = () => load();
const reset = () => {
    filters.from = iso(new Date(today.getTime() - 29 * 86400000));
    filters.to = iso(today);
    filters.group = 'day';
    filters.location_id = '';
    filters.channel = '';
    filters.payment = 'all';
    load();
};

onMounted(load);
</script>
<style scoped>
.filt {
    width: 100%;
    margin-top: 2px;
    background: #0A0A0A;
    border: 1px solid #2A2A2A;
    border-radius: 0.5rem;
    padding: 0.4rem 0.6rem;
    font-size: 0.85rem;
    color: #fff;
}
.filt:focus { outline: none; border-color: #F59E0B; }
</style>

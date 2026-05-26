<template>
    <div>
        <h1 class="text-2xl font-bold mb-6">Analytics</h1>

        <!-- Filters -->
        <div class="bg-[#1A1A1A] border border-[#2A2A2A] rounded-xl p-4 mb-6">
            <!-- Quick date presets (one-tap, mobile-friendly) -->
            <div class="flex flex-wrap gap-2 mb-4">
                <button v-for="p in presets" :key="p.key" @click="setPreset(p.key)"
                    class="px-3 py-2 rounded-lg text-xs font-medium transition"
                    :class="activePreset === p.key ? 'bg-[#F59E0B] text-black' : 'bg-[#111] border border-[#2A2A2A] text-[#A0A0A0] hover:text-white'">
                    {{ p.label }}
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-3">
                <label class="block">
                    <span class="block text-xs text-[#A0A0A0] mb-1">From</span>
                    <input type="date" v-model="filters.from" @change="activePreset = ''" class="filt" />
                </label>
                <label class="block">
                    <span class="block text-xs text-[#A0A0A0] mb-1">To</span>
                    <input type="date" v-model="filters.to" @change="activePreset = ''" class="filt" />
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
import Select from '../components/Select.vue';

const { apiFetch } = useAuth();
const data = ref(null);
const meta = ref({ locations: [], channels: [], statuses: [] });
const loading = ref(true);
const error = ref(null);
const tsMetric = ref('revenue');
const origin = window.location.origin;
const ga = ref(null);
const gaLoading = ref(true);

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
    { key: '7', label: 'Last 7 days' },
    { key: '30', label: 'Last 30 days' },
    { key: 'month', label: 'This month' },
];
const activePreset = ref('30');
const setPreset = (key) => {
    const t = new Date();
    if (key === 'today') { filters.from = iso(t); filters.to = iso(t); }
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
    loading.value = true;
    error.value = null;
    loadGa(); // async, non-blocking — GA panel fills in when ready
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
    activePreset.value = '30';
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

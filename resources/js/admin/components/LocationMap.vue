<template>
    <div class="space-y-3">
        <div class="flex gap-2">
            <input
                v-model="searchQuery"
                @keydown.enter.prevent="geocode"
                type="text"
                placeholder="Search address (e.g. Kralja Milana 5, Belgrade)"
                class="flex-1 bg-[#111] border border-[#2A2A2A] rounded-lg px-4 py-2.5 text-white text-sm focus:border-[#F59E0B] focus:outline-none"
            >
            <button type="button" @click="geocode" :disabled="loading"
                class="px-4 py-2.5 bg-[#F59E0B] hover:bg-[#D97706] text-black text-sm font-medium rounded-lg transition disabled:opacity-50 cursor-pointer">
                <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" class="opacity-75"/></svg>
                <span v-else>Search</span>
            </button>
        </div>
        <p v-if="error" class="text-xs text-[#EF4444]">{{ error }}</p>
        <p v-else class="text-xs text-[#A0A0A0]">
            Drag the marker to fine-tune the position. Coordinates update automatically.
        </p>

        <div ref="mapEl" class="rounded-xl overflow-hidden border border-[#2A2A2A]" style="height: 360px"></div>

        <div class="grid grid-cols-2 gap-3 text-xs">
            <div class="bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2">
                <div class="text-[#6B7280] uppercase tracking-wide text-[10px]">Latitude</div>
                <div class="font-mono text-white mt-0.5">{{ Number(lat).toFixed(6) }}</div>
            </div>
            <div class="bg-[#111] border border-[#2A2A2A] rounded-lg px-3 py-2">
                <div class="text-[#6B7280] uppercase tracking-wide text-[10px]">Longitude</div>
                <div class="font-mono text-white mt-0.5">{{ Number(lng).toFixed(6) }}</div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    lat: { type: [Number, String], default: 44.8125 },
    lng: { type: [Number, String], default: 20.4612 },
    address: { type: String, default: '' },
});
const emit = defineEmits(['update:lat', 'update:lng', 'address-resolved']);

const mapEl = ref(null);
const searchQuery = ref(props.address || '');
const loading = ref(false);
const error = ref('');

let map = null;
let marker = null;

const initialLat = () => Number(props.lat) || 44.8125;
const initialLng = () => Number(props.lng) || 20.4612;

const customIcon = L.divIcon({
    html: `<div style="background:#F59E0B;border:2px solid #0A0A0A;width:18px;height:18px;border-radius:50%;box-shadow:0 0 0 3px rgba(245,158,11,0.35);"></div>`,
    className: '',
    iconSize: [18, 18],
    iconAnchor: [9, 9],
});

onMounted(() => {
    map = L.map(mapEl.value, {
        center: [initialLat(), initialLng()],
        zoom: 15,
        zoomControl: true,
    });

    // Dark tile layer (CARTO Dark Matter — free)
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20,
    }).addTo(map);

    marker = L.marker([initialLat(), initialLng()], {
        icon: customIcon,
        draggable: true,
    }).addTo(map);

    marker.on('dragend', () => {
        const pos = marker.getLatLng();
        emit('update:lat', Number(pos.lat.toFixed(6)));
        emit('update:lng', Number(pos.lng.toFixed(6)));
    });

    map.on('click', (e) => {
        marker.setLatLng(e.latlng);
        emit('update:lat', Number(e.latlng.lat.toFixed(6)));
        emit('update:lng', Number(e.latlng.lng.toFixed(6)));
    });
});

onBeforeUnmount(() => {
    if (map) {
        map.remove();
        map = null;
        marker = null;
    }
});

watch(() => [props.lat, props.lng], ([la, lo]) => {
    if (!marker || !map) return;
    const newLat = Number(la);
    const newLng = Number(lo);
    if (isNaN(newLat) || isNaN(newLng)) return;
    const cur = marker.getLatLng();
    if (Math.abs(cur.lat - newLat) > 1e-7 || Math.abs(cur.lng - newLng) > 1e-7) {
        marker.setLatLng([newLat, newLng]);
        map.setView([newLat, newLng], map.getZoom());
    }
});

watch(() => props.address, (v) => {
    if (v && !searchQuery.value) searchQuery.value = v;
});

const geocode = async () => {
    const q = searchQuery.value?.trim();
    if (!q) return;
    error.value = '';
    loading.value = true;
    try {
        const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(q)}`;
        const res = await fetch(url, {
            headers: { 'Accept': 'application/json' },
        });
        if (!res.ok) throw new Error('Geocoding service unavailable');
        const arr = await res.json();
        if (!arr.length) {
            error.value = 'No results for that address.';
            return;
        }
        const r = arr[0];
        const newLat = Number(parseFloat(r.lat).toFixed(6));
        const newLng = Number(parseFloat(r.lon).toFixed(6));
        emit('update:lat', newLat);
        emit('update:lng', newLng);
        emit('address-resolved', { display: r.display_name, lat: newLat, lng: newLng });
        if (marker && map) {
            marker.setLatLng([newLat, newLng]);
            map.setView([newLat, newLng], 17);
        }
    } catch (e) {
        error.value = e.message || 'Geocoding failed';
    } finally {
        loading.value = false;
    }
};
</script>

<style>
.leaflet-container {
    background: #0A0A0A;
    font-family: inherit;
}
.leaflet-control-attribution {
    background: rgba(15, 15, 15, 0.85) !important;
    color: #6B7280 !important;
    font-size: 10px !important;
}
.leaflet-control-attribution a { color: #A0A0A0 !important; }
.leaflet-control-zoom a {
    background: #1A1A1A !important;
    color: #F59E0B !important;
    border-color: #2A2A2A !important;
}
.leaflet-control-zoom a:hover {
    background: #2A2A2A !important;
}
</style>

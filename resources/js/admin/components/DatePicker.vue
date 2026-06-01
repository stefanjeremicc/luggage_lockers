<template>
    <!-- Custom date picker that matches the admin dark/amber theme. Drop-in
         replacement for the previous native <input type="date"> wrapper:
         same API (modelValue is "YYYY-MM-DD"), same min/max props. -->
    <div ref="root" class="ll-dp" :class="{ 'll-dp--open': open }">
        <!-- The whole field is clickable (not just the icon). -->
        <button type="button" class="ll-dp-field" @click="toggle" :aria-expanded="open">
            <svg class="ll-dp-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="5" width="18" height="16" rx="2"/>
                <path d="M3 9h18M8 3v4M16 3v4"/>
            </svg>
            <span class="ll-dp-value" :class="{ 'll-dp-placeholder': !modelValue }">
                {{ modelValue ? displayValue : (placeholder || 'Select date') }}
            </span>
            <span v-if="clearable && modelValue" class="ll-dp-clear" @click.stop="clear" aria-label="Clear">×</span>
            <svg class="ll-dp-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
            </svg>
        </button>

        <!-- Dropdown calendar — Teleported to body so it escapes any parent
             overflow (e.g. modal body or scrollable filter strip). -->
        <Teleport to="body">
        <Transition name="ll-dp-fade">
            <div v-if="open" ref="pop" class="ll-dp-pop"
                :style="{ top: popTop + 'px', left: popLeft + 'px' }"
                @click.stop>
                <!-- Header: month/year + nav -->
                <div class="ll-dp-head">
                    <button type="button" class="ll-dp-monthbtn" @click="showYearGrid = !showYearGrid">
                        {{ monthNames[viewMonth] }} {{ viewYear }}
                        <svg class="ll-dp-monthchev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
                        </svg>
                    </button>
                    <div class="ll-dp-nav">
                        <button type="button" class="ll-dp-navbtn" @click="prev" aria-label="Previous">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" class="ll-dp-navbtn" @click="next" aria-label="Next">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Year grid (when month title clicked) -->
                <div v-if="showYearGrid" class="ll-dp-yeargrid">
                    <button v-for="y in yearOptions" :key="y" type="button"
                        class="ll-dp-yearcell"
                        :class="{ 'll-dp-yearcell--sel': y === viewYear }"
                        @click="viewYear = y; showYearGrid = false">{{ y }}</button>
                </div>

                <!-- Day grid -->
                <template v-else>
                    <div class="ll-dp-weekrow">
                        <span v-for="d in weekHeaders" :key="d" class="ll-dp-weeklabel">{{ d }}</span>
                    </div>
                    <div class="ll-dp-grid">
                        <button v-for="(cell, i) in cells" :key="i"
                            type="button"
                            class="ll-dp-cell"
                            :class="{
                                'll-dp-cell--out': !cell.in,
                                'll-dp-cell--today': cell.today,
                                'll-dp-cell--sel': cell.selected,
                                'll-dp-cell--disabled': cell.disabled,
                            }"
                            :disabled="cell.disabled"
                            @click="pick(cell)">{{ cell.day }}</button>
                    </div>
                </template>

                <!-- Footer -->
                <div class="ll-dp-foot">
                    <button type="button" class="ll-dp-footbtn" @click="clear" :disabled="!modelValue">Clear</button>
                    <button type="button" class="ll-dp-footbtn ll-dp-footbtn--accent" @click="pickToday">Today</button>
                </div>
            </div>
        </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: null }, // YYYY-MM-DD
    min: { type: String, default: null },
    max: { type: String, default: null },
    placeholder: { type: String, default: '' },
    clearable: { type: Boolean, default: true },
});
const emit = defineEmits(['update:modelValue']);

const root = ref(null);
const pop = ref(null);
const open = ref(false);
const showYearGrid = ref(false);
const popTop = ref(0);
const popLeft = ref(0);
const POPUP_WIDTH = 280;
const POPUP_HEIGHT_EST = 340;

const positionPop = () => {
    if (!root.value) return;
    const r = root.value.getBoundingClientRect();
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    if (vw <= 640) {
        popLeft.value = 16;
        popTop.value = Math.max(16, Math.min(vh - POPUP_HEIGHT_EST - 16, r.bottom + 6));
        return;
    }
    let left = r.left;
    const maxLeft = vw - POPUP_WIDTH - 8;
    if (left > maxLeft) left = Math.max(8, maxLeft);
    let top = r.bottom + 6;
    if (top + POPUP_HEIGHT_EST > vh - 8 && r.top - POPUP_HEIGHT_EST - 6 > 8) {
        top = r.top - POPUP_HEIGHT_EST - 6;
    }
    popLeft.value = left;
    popTop.value = top;
};

const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const weekHeaders = ['Mo','Tu','We','Th','Fr','Sa','Su'];

const toParts = (iso) => {
    if (!iso) return null;
    const [y, m, d] = iso.split('-').map(Number);
    if (!y || !m || !d) return null;
    return { y, m: m - 1, d };
};
const pad = (n) => String(n).padStart(2, '0');
const fmt = (y, m, d) => `${y}-${pad(m + 1)}-${pad(d)}`;

// Initial view: selected date if set, else today.
const now = new Date();
const viewYear = ref((toParts(props.modelValue)?.y) || now.getFullYear());
const viewMonth = ref((toParts(props.modelValue)?.m) ?? now.getMonth());

watch(() => props.modelValue, (v) => {
    const p = toParts(v);
    if (p) { viewYear.value = p.y; viewMonth.value = p.m; }
});

const displayValue = computed(() => {
    const p = toParts(props.modelValue);
    if (!p) return '';
    return `${pad(p.d)}.${pad(p.m + 1)}.${p.y}`;
});

const minParts = computed(() => toParts(props.min));
const maxParts = computed(() => toParts(props.max));

// Year selector range: ±10 years around the view year.
const yearOptions = computed(() => {
    const base = viewYear.value;
    const arr = [];
    for (let i = base - 10; i <= base + 10; i++) arr.push(i);
    return arr;
});

// Build 42-cell grid (6 weeks × 7 days). Mon-first week.
const cells = computed(() => {
    const y = viewYear.value, m = viewMonth.value;
    const first = new Date(y, m, 1);
    // JS: 0=Sun..6=Sat. We want Mon-first → offset.
    const jsDow = first.getDay(); // 0-6
    const monOffset = (jsDow + 6) % 7; // 0=Mon
    const start = new Date(y, m, 1 - monOffset);
    const todayIso = fmt(now.getFullYear(), now.getMonth(), now.getDate());
    const selIso = props.modelValue || '';
    const out = [];
    for (let i = 0; i < 42; i++) {
        const dt = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
        const iso = fmt(dt.getFullYear(), dt.getMonth(), dt.getDate());
        const inMonth = dt.getMonth() === m;
        const disabled =
            (minParts.value && iso < fmt(minParts.value.y, minParts.value.m, minParts.value.d)) ||
            (maxParts.value && iso > fmt(maxParts.value.y, maxParts.value.m, maxParts.value.d));
        out.push({
            day: dt.getDate(),
            iso,
            in: inMonth,
            today: iso === todayIso,
            selected: iso === selIso,
            disabled,
        });
    }
    return out;
});

const toggle = () => {
    open.value = !open.value;
    if (!open.value) showYearGrid.value = false;
    if (open.value) {
        positionPop();
        requestAnimationFrame(positionPop);
    }
};

const close = () => { open.value = false; showYearGrid.value = false; };

const prev = () => {
    if (viewMonth.value === 0) { viewMonth.value = 11; viewYear.value--; }
    else viewMonth.value--;
};
const next = () => {
    if (viewMonth.value === 11) { viewMonth.value = 0; viewYear.value++; }
    else viewMonth.value++;
};

const pick = (cell) => {
    if (cell.disabled) return;
    emit('update:modelValue', cell.iso);
    close();
};

const pickToday = () => {
    const iso = fmt(now.getFullYear(), now.getMonth(), now.getDate());
    // Respect min/max even on the "Today" shortcut.
    if (minParts.value && iso < fmt(minParts.value.y, minParts.value.m, minParts.value.d)) return;
    if (maxParts.value && iso > fmt(maxParts.value.y, maxParts.value.m, maxParts.value.d)) return;
    emit('update:modelValue', iso);
    viewYear.value = now.getFullYear();
    viewMonth.value = now.getMonth();
    close();
};

const clear = () => { emit('update:modelValue', null); close(); };

const onDocClick = (e) => {
    if (!open.value) return;
    if (root.value && !root.value.contains(e.target)) close();
};
const onKey = (e) => { if (e.key === 'Escape' && open.value) close(); };

const onReposition = () => { if (open.value) positionPop(); };

onMounted(() => {
    document.addEventListener('click', onDocClick);
    document.addEventListener('keydown', onKey);
    window.addEventListener('scroll', onReposition, true);
    window.addEventListener('resize', onReposition);
});
onBeforeUnmount(() => {
    document.removeEventListener('click', onDocClick);
    document.removeEventListener('keydown', onKey);
    window.removeEventListener('scroll', onReposition, true);
    window.removeEventListener('resize', onReposition);
});
</script>

<style scoped>
.ll-dp {
    position: relative;
    width: 100%;
}

/* The visible field button. Entire surface is clickable. */
.ll-dp-field {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    height: 42px;
    padding: 0 12px;
    background: #111;
    border: 1px solid #2A2A2A;
    border-radius: 8px;
    color: #fff;
    font-size: 14px;
    line-height: 1;
    transition: border-color 0.15s, background 0.15s;
    cursor: pointer;
    text-align: left;
}
.ll-dp-field:hover { border-color: #3A3A3A; }
.ll-dp--open .ll-dp-field,
.ll-dp-field:focus-visible {
    border-color: #F59E0B;
    outline: none;
}
.ll-dp-icon { width: 16px; height: 16px; color: #F59E0B; flex-shrink: 0; }
.ll-dp-value { flex: 1; min-width: 0; }
.ll-dp-placeholder { color: #6B7280; }
.ll-dp-chevron {
    width: 14px; height: 14px; color: #6B7280; flex-shrink: 0;
    transition: transform 0.15s;
}
.ll-dp--open .ll-dp-chevron { transform: rotate(180deg); color: #F59E0B; }
.ll-dp-clear {
    width: 18px; height: 18px;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 50%;
    background: transparent;
    color: #6B7280;
    font-size: 16px;
    line-height: 1;
    flex-shrink: 0;
    transition: background 0.15s, color 0.15s;
}
.ll-dp-clear:hover { background: #2A2A2A; color: #fff; }

/* Dropdown */
.ll-dp-pop {
    /* Teleported to body — fixed positioning via inline top/left so the popup
       never makes the modal/page scroll. */
    position: fixed;
    z-index: 100;
    width: 280px;
    background: #1A1A1A;
    border: 1px solid #2A2A2A;
    border-radius: 12px;
    padding: 10px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    user-select: none;
}

.ll-dp-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 8px;
}
.ll-dp-monthbtn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 8px;
    border-radius: 6px;
    background: transparent;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    transition: background 0.15s;
}
.ll-dp-monthbtn:hover { background: #2A2A2A; }
.ll-dp-monthchev { width: 12px; height: 12px; color: #A0A0A0; }
.ll-dp-nav { display: inline-flex; gap: 2px; }
.ll-dp-navbtn {
    width: 28px; height: 28px;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 6px;
    background: transparent;
    color: #A0A0A0;
    transition: background 0.15s, color 0.15s;
}
.ll-dp-navbtn:hover { background: #2A2A2A; color: #fff; }
.ll-dp-navbtn svg { width: 14px; height: 14px; }

.ll-dp-weekrow {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
    margin-bottom: 4px;
}
.ll-dp-weeklabel {
    text-align: center;
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #6B7280;
    padding: 4px 0;
}

.ll-dp-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
}
.ll-dp-cell {
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 6px;
    color: #D4D4D4;
    font-size: 13px;
    font-weight: 500;
    transition: background 0.12s, color 0.12s, border-color 0.12s;
}
.ll-dp-cell:hover:not(:disabled) { background: #2A2A2A; color: #fff; }
.ll-dp-cell--out { color: #4A4A4A; }
.ll-dp-cell--today {
    border-color: rgba(245, 158, 11, 0.5);
    color: #F59E0B;
}
.ll-dp-cell--sel {
    background: #F59E0B !important;
    color: #000 !important;
    border-color: #F59E0B;
    font-weight: 700;
}
.ll-dp-cell--disabled {
    color: #3A3A3A;
    cursor: not-allowed;
}
.ll-dp-cell--disabled:hover { background: transparent; }

/* Year grid */
.ll-dp-yeargrid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 4px;
    padding: 4px 0;
}
.ll-dp-yearcell {
    padding: 10px 0;
    background: transparent;
    border-radius: 6px;
    color: #D4D4D4;
    font-size: 13px;
    font-weight: 500;
    transition: background 0.12s, color 0.12s;
}
.ll-dp-yearcell:hover { background: #2A2A2A; color: #fff; }
.ll-dp-yearcell--sel { background: rgba(245, 158, 11, 0.15); color: #F59E0B; font-weight: 700; }

/* Footer */
.ll-dp-foot {
    display: flex;
    justify-content: space-between;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #2A2A2A;
}
.ll-dp-footbtn {
    padding: 6px 12px;
    background: transparent;
    border-radius: 6px;
    color: #A0A0A0;
    font-size: 12px;
    font-weight: 600;
    transition: background 0.15s, color 0.15s;
}
.ll-dp-footbtn:hover:not(:disabled) { background: #2A2A2A; color: #fff; }
.ll-dp-footbtn:disabled { opacity: 0.4; cursor: not-allowed; }
.ll-dp-footbtn--accent { color: #F59E0B; }
.ll-dp-footbtn--accent:hover:not(:disabled) { background: rgba(245, 158, 11, 0.15); color: #FBBF24; }

/* Transition */
.ll-dp-fade-enter-active, .ll-dp-fade-leave-active {
    transition: opacity 0.15s, transform 0.15s;
}
.ll-dp-fade-enter-from, .ll-dp-fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

/* Mobile: full-width dropdown so it doesn't overflow the screen. */
@media (max-width: 640px) {
    .ll-dp-pop {
        width: calc(100vw - 32px);
        left: 50%;
        transform: translateX(-50%);
    }
    .ll-dp-fade-enter-from, .ll-dp-fade-leave-to {
        opacity: 0;
        transform: translateX(-50%) translateY(-4px);
    }
}
</style>

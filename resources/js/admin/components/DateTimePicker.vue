<template>
    <!-- Custom datetime picker matching the admin dark/amber theme.
         Drop-in replacement for the previous @vuepic/vue-datepicker wrapper and
         <input type="datetime-local"> usages. Accepts ISO or "YYYY-MM-DDTHH:MM"
         input, emits "YYYY-MM-DDTHH:MM" (local-time, no TZ) — which both the
         Bookings Edit endpoint and the LockerController passcode endpoint
         parse via Carbon::parse($value, config('app.display_timezone')). -->
    <div ref="root" class="ll-dtp" :class="{ 'll-dtp--open': open }">
        <button type="button" class="ll-dtp-field" @click="toggle" :aria-expanded="open">
            <svg class="ll-dtp-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="5" width="18" height="16" rx="2"/>
                <path d="M3 9h18M8 3v4M16 3v4"/>
            </svg>
            <span class="ll-dtp-value" :class="{ 'll-dtp-placeholder': !modelValue }">
                {{ modelValue ? displayValue : (placeholder || 'Select date & time') }}
            </span>
            <span v-if="clearable && modelValue" class="ll-dtp-clear" @click.stop="clear" aria-label="Clear">×</span>
            <svg class="ll-dtp-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
            </svg>
        </button>

        <Teleport to="body">
        <Transition name="ll-dtp-fade">
            <div v-if="open" ref="pop" class="ll-dtp-pop"
                :style="{ top: popTop + 'px', left: popLeft + 'px' }"
                @click.stop>
                <!-- Header: month/year + nav -->
                <div class="ll-dtp-head">
                    <button type="button" class="ll-dtp-monthbtn" @click="showYearGrid = !showYearGrid">
                        {{ monthNames[viewMonth] }} {{ viewYear }}
                        <svg class="ll-dtp-monthchev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
                        </svg>
                    </button>
                    <div class="ll-dtp-nav">
                        <button type="button" class="ll-dtp-navbtn" @click="prev" aria-label="Previous month">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" class="ll-dtp-navbtn" @click="next" aria-label="Next month">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                <div v-if="showYearGrid" class="ll-dtp-yeargrid">
                    <button v-for="y in yearOptions" :key="y" type="button"
                        class="ll-dtp-yearcell"
                        :class="{ 'll-dtp-yearcell--sel': y === viewYear }"
                        @click="viewYear = y; showYearGrid = false">{{ y }}</button>
                </div>

                <template v-else>
                    <div class="ll-dtp-weekrow">
                        <span v-for="d in weekHeaders" :key="d" class="ll-dtp-weeklabel">{{ d }}</span>
                    </div>
                    <div class="ll-dtp-grid">
                        <button v-for="(cell, i) in cells" :key="i"
                            type="button"
                            class="ll-dtp-cell"
                            :class="{
                                'll-dtp-cell--out': !cell.in,
                                'll-dtp-cell--today': cell.today,
                                'll-dtp-cell--sel': cell.selected,
                                'll-dtp-cell--disabled': cell.disabled,
                            }"
                            :disabled="cell.disabled"
                            @click="pickDay(cell)">{{ cell.day }}</button>
                    </div>
                </template>

                <!-- Time strip -->
                <div class="ll-dtp-time">
                    <span class="ll-dtp-time-label">Time</span>
                    <div class="ll-dtp-time-spinner">
                        <button type="button" class="ll-dtp-time-btn" @click="bumpHour(1)" aria-label="Hour up">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                        </button>
                        <input type="text" inputmode="numeric" maxlength="2"
                            class="ll-dtp-time-input"
                            :value="pad(hour)"
                            @focus="$event.target.select()"
                            @input="onHourInput"
                            @blur="normalizeHour" />
                        <button type="button" class="ll-dtp-time-btn" @click="bumpHour(-1)" aria-label="Hour down">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>
                    <span class="ll-dtp-time-sep">:</span>
                    <div class="ll-dtp-time-spinner">
                        <button type="button" class="ll-dtp-time-btn" @click="bumpMinute(5)" aria-label="Minute up">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                        </button>
                        <input type="text" inputmode="numeric" maxlength="2"
                            class="ll-dtp-time-input"
                            :value="pad(minute)"
                            @focus="$event.target.select()"
                            @input="onMinuteInput"
                            @blur="normalizeMinute" />
                        <button type="button" class="ll-dtp-time-btn" @click="bumpMinute(-5)" aria-label="Minute down">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Footer -->
                <div class="ll-dtp-foot">
                    <button type="button" class="ll-dtp-footbtn" @click="clear" :disabled="!modelValue">Clear</button>
                    <div class="flex gap-1.5">
                        <button type="button" class="ll-dtp-footbtn" @click="setNow">Now</button>
                        <button type="button" class="ll-dtp-footbtn ll-dtp-footbtn--accent" @click="apply">Apply</button>
                    </div>
                </div>
            </div>
        </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Date, null], default: null },
    min: { type: [String, Date, null], default: null },
    max: { type: [String, Date, null], default: null },
    placeholder: { type: String, default: '' },
    clearable: { type: Boolean, default: true },
});
const emit = defineEmits(['update:modelValue']);

const root = ref(null);
const pop = ref(null);
const open = ref(false);
const showYearGrid = ref(false);
// Teleported popup position (viewport-fixed). Recomputed every time we open
// and on scroll/resize while open, so the dropdown stays visually attached to
// the trigger even though it lives on document.body (escapes modal overflow).
const popTop = ref(0);
const popLeft = ref(0);
const POPUP_WIDTH = 300; // matches .ll-dtp-pop CSS width
const POPUP_HEIGHT_EST = 420; // header + grid + time + footer; close enough for flip-up decision

const positionPop = () => {
    if (!root.value) return;
    const r = root.value.getBoundingClientRect();
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const isMobile = vw <= 640;
    if (isMobile) {
        // On mobile we deliberately span almost the full viewport — center it.
        popLeft.value = 16;
        popTop.value = Math.max(16, Math.min(vh - POPUP_HEIGHT_EST - 16, r.bottom + 6));
        return;
    }
    // Desktop: prefer below; flip above if not enough room.
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

const pad = (n) => String(n).padStart(2, '0');

// Parse incoming value (ISO with TZ, "YYYY-MM-DDTHH:MM", or Date) into a local Date.
const toDate = (v) => {
    if (!v) return null;
    if (v instanceof Date) return Number.isNaN(v.getTime()) ? null : v;
    const dt = new Date(v);
    return Number.isNaN(dt.getTime()) ? null : dt;
};

const fmtLocal = (dt) => {
    if (!dt) return null;
    return `${dt.getFullYear()}-${pad(dt.getMonth() + 1)}-${pad(dt.getDate())}T${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
};

// Internal draft — only committed on Apply / Now / day click + time edit.
const initial = toDate(props.modelValue) || new Date();
const viewYear = ref(initial.getFullYear());
const viewMonth = ref(initial.getMonth());
const selectedDay = ref(toDate(props.modelValue) ? initial.getDate() : null);
const selectedYear = ref(toDate(props.modelValue) ? initial.getFullYear() : null);
const selectedMonth = ref(toDate(props.modelValue) ? initial.getMonth() : null);
const hour = ref(initial.getHours());
const minute = ref(initial.getMinutes());

// Sync when external value changes (parent reset, etc.).
watch(() => props.modelValue, (v) => {
    const dt = toDate(v);
    if (dt) {
        viewYear.value = dt.getFullYear();
        viewMonth.value = dt.getMonth();
        selectedYear.value = dt.getFullYear();
        selectedMonth.value = dt.getMonth();
        selectedDay.value = dt.getDate();
        hour.value = dt.getHours();
        minute.value = dt.getMinutes();
    } else {
        selectedYear.value = null;
        selectedMonth.value = null;
        selectedDay.value = null;
    }
});

const displayValue = computed(() => {
    const dt = toDate(props.modelValue);
    if (!dt) return '';
    return `${pad(dt.getDate())}.${pad(dt.getMonth() + 1)}.${dt.getFullYear()} ${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
});

const minDate = computed(() => toDate(props.min));
const maxDate = computed(() => toDate(props.max));

const yearOptions = computed(() => {
    const base = viewYear.value;
    const arr = [];
    for (let i = base - 10; i <= base + 10; i++) arr.push(i);
    return arr;
});

// Build day grid (Mon-first). "Disabled" compares full datetime against min/max
// at midnight — fine-grained min/max for the time portion is enforced on Apply.
const cells = computed(() => {
    const y = viewYear.value, m = viewMonth.value;
    const first = new Date(y, m, 1);
    const jsDow = first.getDay();
    const monOffset = (jsDow + 6) % 7;
    const start = new Date(y, m, 1 - monOffset);
    const today = new Date();
    const out = [];
    for (let i = 0; i < 42; i++) {
        const dt = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
        const inMonth = dt.getMonth() === m;
        const isToday = dt.toDateString() === today.toDateString();
        const isSel = selectedYear.value === dt.getFullYear()
            && selectedMonth.value === dt.getMonth()
            && selectedDay.value === dt.getDate();
        // Day-level min/max check: compare to start-of-day vs min, end-of-day vs max
        let disabled = false;
        if (minDate.value) {
            const minDay = new Date(minDate.value.getFullYear(), minDate.value.getMonth(), minDate.value.getDate());
            if (dt < minDay) disabled = true;
        }
        if (maxDate.value) {
            const maxDay = new Date(maxDate.value.getFullYear(), maxDate.value.getMonth(), maxDate.value.getDate());
            if (dt > maxDay) disabled = true;
        }
        out.push({
            day: dt.getDate(), month: dt.getMonth(), year: dt.getFullYear(),
            in: inMonth, today: isToday, selected: isSel, disabled,
        });
    }
    return out;
});

const toggle = () => {
    open.value = !open.value;
    if (!open.value) showYearGrid.value = false;
    // When opening with no value, default hour:minute to a sensible "now".
    if (open.value && !props.modelValue) {
        const n = new Date();
        hour.value = n.getHours();
        minute.value = n.getMinutes() - (n.getMinutes() % 5); // snap to 5
    }
    if (open.value) {
        positionPop();
        // Defer once more after the popup renders so we can also flip-up if needed.
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

const pickDay = (cell) => {
    if (cell.disabled) return;
    selectedYear.value = cell.year;
    selectedMonth.value = cell.month;
    selectedDay.value = cell.day;
    // If view drifted (clicked an out-of-month cell), follow it.
    viewYear.value = cell.year;
    viewMonth.value = cell.month;
    // Live emit — feels responsive, while time can still be tweaked before close.
    emitCurrent();
};

const bumpHour = (delta) => {
    let h = hour.value + delta;
    if (h < 0) h = 23;
    if (h > 23) h = 0;
    hour.value = h;
    if (selectedDay.value !== null) emitCurrent();
};
const bumpMinute = (delta) => {
    let m = minute.value + delta;
    if (m < 0) m = 60 + m; // -5 → 55
    if (m >= 60) m = m - 60;
    minute.value = m;
    if (selectedDay.value !== null) emitCurrent();
};
const onHourInput = (e) => {
    const v = parseInt(e.target.value.replace(/\D/g, ''), 10);
    if (Number.isNaN(v)) return;
    hour.value = Math.min(23, Math.max(0, v));
};
const normalizeHour = () => {
    if (selectedDay.value !== null) emitCurrent();
};
const onMinuteInput = (e) => {
    const v = parseInt(e.target.value.replace(/\D/g, ''), 10);
    if (Number.isNaN(v)) return;
    minute.value = Math.min(59, Math.max(0, v));
};
const normalizeMinute = () => {
    if (selectedDay.value !== null) emitCurrent();
};

const buildCurrent = () => {
    if (selectedDay.value === null || selectedYear.value === null || selectedMonth.value === null) return null;
    const dt = new Date(selectedYear.value, selectedMonth.value, selectedDay.value, hour.value, minute.value, 0, 0);
    // Enforce min/max on full datetime.
    if (minDate.value && dt < minDate.value) return fmtLocal(minDate.value);
    if (maxDate.value && dt > maxDate.value) return fmtLocal(maxDate.value);
    return fmtLocal(dt);
};
const emitCurrent = () => {
    const v = buildCurrent();
    if (v !== null) emit('update:modelValue', v);
};

const setNow = () => {
    const n = new Date();
    viewYear.value = n.getFullYear();
    viewMonth.value = n.getMonth();
    selectedYear.value = n.getFullYear();
    selectedMonth.value = n.getMonth();
    selectedDay.value = n.getDate();
    hour.value = n.getHours();
    minute.value = n.getMinutes();
    emitCurrent();
};

const apply = () => {
    if (selectedDay.value !== null) emitCurrent();
    close();
};

const clear = () => {
    selectedYear.value = null;
    selectedMonth.value = null;
    selectedDay.value = null;
    emit('update:modelValue', null);
    close();
};

const onDocClick = (e) => {
    if (!open.value) return;
    if (root.value && !root.value.contains(e.target)) close();
};
const onKey = (e) => { if (e.key === 'Escape' && open.value) close(); };

const onReposition = () => { if (open.value) positionPop(); };

onMounted(() => {
    document.addEventListener('click', onDocClick);
    document.addEventListener('keydown', onKey);
    // Reposition on scroll/resize so the teleported popup follows the trigger
    // even when the modal body or the page is scrolled (capture catches scrolls
    // on any ancestor, not just window).
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
.ll-dtp { position: relative; width: 100%; }

.ll-dtp-field {
    display: flex; align-items: center; gap: 10px;
    width: 100%; height: 42px;
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
    box-sizing: border-box;
    min-width: 0;
}
.ll-dtp-field:hover { border-color: #3A3A3A; }
.ll-dtp--open .ll-dtp-field,
.ll-dtp-field:focus-visible { border-color: #F59E0B; outline: none; }

.ll-dtp-icon { width: 16px; height: 16px; color: #F59E0B; flex-shrink: 0; }
.ll-dtp-value { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ll-dtp-placeholder { color: #6B7280; }
.ll-dtp-chevron { width: 14px; height: 14px; color: #6B7280; flex-shrink: 0; transition: transform 0.15s; }
.ll-dtp--open .ll-dtp-chevron { transform: rotate(180deg); color: #F59E0B; }
.ll-dtp-clear {
    width: 18px; height: 18px;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 50%;
    color: #6B7280; font-size: 16px; line-height: 1;
    flex-shrink: 0;
    transition: background 0.15s, color 0.15s;
}
.ll-dtp-clear:hover { background: #2A2A2A; color: #fff; }

.ll-dtp-pop {
    /* Teleported to body and positioned via inline top/left styles so the
       popup escapes any parent overflow (e.g. Modal body's overflow-y-auto)
       and never makes the modal content scroll. */
    position: fixed;
    z-index: 100;
    width: 300px;
    background: #1A1A1A;
    border: 1px solid #2A2A2A;
    border-radius: 12px;
    padding: 10px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    user-select: none;
}

.ll-dtp-head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 8px; margin-bottom: 8px;
}
.ll-dtp-monthbtn {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 6px 8px; border-radius: 6px;
    background: transparent; color: #fff;
    font-size: 13px; font-weight: 600;
    transition: background 0.15s;
}
.ll-dtp-monthbtn:hover { background: #2A2A2A; }
.ll-dtp-monthchev { width: 12px; height: 12px; color: #A0A0A0; }

.ll-dtp-nav { display: inline-flex; gap: 2px; }
.ll-dtp-navbtn {
    width: 28px; height: 28px;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 6px; background: transparent; color: #A0A0A0;
    transition: background 0.15s, color 0.15s;
}
.ll-dtp-navbtn:hover { background: #2A2A2A; color: #fff; }
.ll-dtp-navbtn svg { width: 14px; height: 14px; }

.ll-dtp-weekrow { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; margin-bottom: 4px; }
.ll-dtp-weeklabel {
    text-align: center; font-size: 10px; font-weight: 500;
    letter-spacing: 0.05em; text-transform: uppercase;
    color: #6B7280; padding: 4px 0;
}
.ll-dtp-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
.ll-dtp-cell {
    height: 34px;
    display: inline-flex; align-items: center; justify-content: center;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 6px;
    color: #D4D4D4;
    font-size: 13px; font-weight: 500;
    transition: background 0.12s, color 0.12s, border-color 0.12s;
}
.ll-dtp-cell:hover:not(:disabled) { background: #2A2A2A; color: #fff; }
.ll-dtp-cell--out { color: #4A4A4A; }
.ll-dtp-cell--today { border-color: rgba(245, 158, 11, 0.5); color: #F59E0B; }
.ll-dtp-cell--sel { background: #F59E0B !important; color: #000 !important; border-color: #F59E0B; font-weight: 700; }
.ll-dtp-cell--disabled { color: #3A3A3A; cursor: not-allowed; }
.ll-dtp-cell--disabled:hover { background: transparent; }

.ll-dtp-yeargrid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; padding: 4px 0; }
.ll-dtp-yearcell {
    padding: 10px 0;
    background: transparent;
    border-radius: 6px;
    color: #D4D4D4;
    font-size: 13px; font-weight: 500;
    transition: background 0.12s, color 0.12s;
}
.ll-dtp-yearcell:hover { background: #2A2A2A; color: #fff; }
.ll-dtp-yearcell--sel { background: rgba(245, 158, 11, 0.15); color: #F59E0B; font-weight: 700; }

/* Time strip */
.ll-dtp-time {
    display: flex; align-items: center; gap: 8px;
    margin-top: 10px;
    padding: 10px;
    background: #111;
    border: 1px solid #2A2A2A;
    border-radius: 8px;
}
.ll-dtp-time-label {
    font-size: 11px; font-weight: 500;
    text-transform: uppercase; letter-spacing: 0.05em;
    color: #6B7280;
    margin-right: auto;
}
.ll-dtp-time-spinner {
    display: flex; flex-direction: column; align-items: center;
    gap: 2px;
}
.ll-dtp-time-btn {
    width: 24px; height: 18px;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 4px;
    background: transparent; color: #A0A0A0;
    transition: background 0.15s, color 0.15s;
}
.ll-dtp-time-btn:hover { background: #2A2A2A; color: #F59E0B; }
.ll-dtp-time-btn svg { width: 12px; height: 12px; }
.ll-dtp-time-input {
    width: 38px; height: 32px;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 4px;
    color: #fff;
    font-family: ui-monospace, monospace;
    font-size: 16px;
    font-weight: 600;
    text-align: center;
    transition: border-color 0.15s, background 0.15s;
}
.ll-dtp-time-input:focus { outline: none; border-color: #F59E0B; background: #0A0A0A; }
.ll-dtp-time-sep { color: #6B7280; font-size: 18px; font-weight: 600; align-self: center; }

/* Footer */
.ll-dtp-foot {
    display: flex; justify-content: space-between; align-items: center;
    gap: 8px;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #2A2A2A;
}
.ll-dtp-footbtn {
    padding: 6px 12px;
    background: transparent;
    border-radius: 6px;
    color: #A0A0A0;
    font-size: 12px;
    font-weight: 600;
    transition: background 0.15s, color 0.15s;
}
.ll-dtp-footbtn:hover:not(:disabled) { background: #2A2A2A; color: #fff; }
.ll-dtp-footbtn:disabled { opacity: 0.4; cursor: not-allowed; }
.ll-dtp-footbtn--accent {
    background: #F59E0B; color: #000;
}
.ll-dtp-footbtn--accent:hover:not(:disabled) { background: #FBBF24; color: #000; }

.ll-dtp-fade-enter-active, .ll-dtp-fade-leave-active { transition: opacity 0.15s, transform 0.15s; }
.ll-dtp-fade-enter-from, .ll-dtp-fade-leave-to { opacity: 0; transform: translateY(-4px); }

@media (max-width: 640px) {
    .ll-dtp-pop {
        /* Override inline left set by positionPop on mobile so the popup is
           padded uniformly to the viewport. positionPop also pins left=16. */
        width: calc(100vw - 32px);
    }
}
</style>

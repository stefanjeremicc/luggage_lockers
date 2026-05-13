// Custom HH:MM picker matching the booking flow's date calendar design.
// Lives next to the bookingFlow Alpine component — it reaches up via
// $root.time / $root.resolvedDate / $root.checkoutPreview so the parent state
// stays the single source of truth. We only manage UI state (open + draft
// hour/minute) until the user clicks Confirm.
export default function timePicker() {
    return {
        open: false,
        selHour: null,
        selMinute: null,

        onOpen() {
            // Seed the wheels:
            //   1. If parent already has a time, restore it.
            //   2. Otherwise, for today's date, pre-select "now + 1 minute" so
            //      the customer literally cannot pick something in the past —
            //      and they always see a highlighted starting point.
            //   3. For future dates, default to 12:00 so the picker isn't empty.
            if (this.$root.time) {
                const [h, m] = this.$root.time.split(':').map(Number);
                this.selHour = h;
                this.selMinute = m;
            } else if (this._isToday) {
                const next = this._nowPlusOne;
                this.selHour = next.h;
                this.selMinute = next.m;
            } else {
                this.selHour = 12;
                this.selMinute = 0;
            }
            this.open = !this.open;
            if (this.open) {
                // Scroll the selected value into view once Alpine has rendered.
                this.$nextTick(() => this.scrollSelectedIntoView());
            }
        },

        /**
         * Current wall-clock + 1 minute, wrapped across the hour boundary if
         * needed. We pre-select this so the customer's default is always a
         * future-valid time.
         */
        get _nowPlusOne() {
            const d = new Date();
            d.setMinutes(d.getMinutes() + 1);
            return { h: d.getHours(), m: d.getMinutes() };
        },

        scrollSelectedIntoView() {
            const center = (container, idx) => {
                if (!container || idx < 0) return;
                const target = container.children[idx];
                if (target) target.scrollIntoView({ block: 'nearest' });
            };
            // Find the position in the (possibly filtered) lists.
            const hi = this.hours.findIndex(h => h.value === this.selHour);
            const mi = this.minutes.findIndex(m => m.value === this.selMinute);
            center(this.$refs.hoursList, hi);
            center(this.$refs.minutesList, mi);
        },

        /**
         * Is the booking date "today"? Drives the in-the-past disable logic.
         * resolvedDate is exposed by bookingFlow as the YYYY-MM-DD form of the
         * selected day (today / tomorrow / explicit custom date).
         */
        get _isToday() {
            const today = new Date().toISOString().split('T')[0];
            return this.$root.resolvedDate === today;
        },

        get _now() {
            const d = new Date();
            return { h: d.getHours(), m: d.getMinutes() };
        },

        get hours() {
            const list = [];
            const isToday = this._isToday;
            const now = this._now;
            for (let h = 0; h < 24; h++) {
                list.push({
                    value: h,
                    label: String(h).padStart(2, '0'),
                    // Hours strictly before the current hour are unreachable today.
                    disabled: isToday && h < now.h,
                });
            }
            return list;
        },

        get minutes() {
            const list = [];
            const isToday = this._isToday;
            const now = this._now;
            const sameHour = isToday && this.selHour === now.h;
            for (let m = 0; m < 60; m++) {
                list.push({
                    value: m,
                    label: String(m).padStart(2, '0'),
                    // Within the current hour today, minutes up to & including
                    // "now" are in the past — block them.
                    disabled: sameHour && m <= now.m,
                });
            }
            return list;
        },

        selectHour(h) {
            this.selHour = h;
            // If the current minute is now in the past (e.g. they jumped to the
            // current hour), clear it so they re-pick.
            if (this._isToday && h === this._now.h && this.selMinute !== null && this.selMinute <= this._now.m) {
                this.selMinute = null;
            }
        },

        selectMinute(m) {
            this.selMinute = m;
        },

        get checkoutPreview() {
            return this.$root.checkoutPreview;
        },

        get time() {
            return this.$root.time;
        },

        confirm() {
            if (this.selHour === null || this.selMinute === null) return;
            const hh = String(this.selHour).padStart(2, '0');
            const mm = String(this.selMinute).padStart(2, '0');
            this.$root.time = `${hh}:${mm}`;
            this.open = false;
        },
    };
}

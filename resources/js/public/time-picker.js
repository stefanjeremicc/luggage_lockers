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
        /** Reactive handle on the bookingFlow data so we can read/write
         *  `time`, `resolvedDate`, etc. across the x-data boundary.
         *  $root only points at OUR x-data root, not the parent. */
        parent: null,

        init() {
            const el = this.$el.closest('[x-data*="bookingFlow"]');
            this.parent = el ? window.Alpine.$data(el) : null;
        },

        onOpen() {
            // Seed the wheels:
            //   1. If parent already has a time, restore it.
            //   2. Otherwise pre-select "now + 1 minute" — always, regardless
            //      of whether a date is picked yet. That way the picker is
            //      never empty and the customer's default is guaranteed-future.
            if (this.parent.time) {
                const [h, m] = this.parent.time.split(':').map(Number);
                this.selHour = h;
                this.selMinute = m;
            } else {
                const next = this._nowPlusOne;
                this.selHour = next.h;
                this.selMinute = next.m;
            }
            this.open = !this.open;
            if (this.open) {
                // Pin the selection to the TOP of the scroller so the customer
                // sees what's selected immediately, without scrolling.
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
            // Position the selection at the TOP of each scroller. We scroll
            // the container directly (not scrollIntoView) so the page itself
            // doesn't jump when the picker opens near the viewport edge.
            const pinTop = (container, idx) => {
                if (!container || idx < 0) return;
                const target = container.children[idx];
                if (!target) return;
                container.scrollTop = target.offsetTop - container.offsetTop;
            };
            const hi = this.hours.findIndex(h => h.value === this.selHour);
            const mi = this.minutes.findIndex(m => m.value === this.selMinute);
            pinTop(this.$refs.hoursList, hi);
            pinTop(this.$refs.minutesList, mi);
        },

        /**
         * Past hours/minutes are forbidden when the booking date is today
         * (or when the customer hasn't selected a date yet — same intent).
         * Future dates (tomorrow onwards) accept the full 24-hour range.
         */
        get _isTodayOrUnset() {
            if (!this.parent.resolvedDate) return true;
            const today = new Date().toISOString().split('T')[0];
            return this.parent.resolvedDate === today;
        },

        get _now() {
            const d = new Date();
            return { h: d.getHours(), m: d.getMinutes() };
        },

        /**
         * Hour list — for "today", we drop past hours entirely so the
         * customer can't even scroll up to them. For future dates, full 24h.
         */
        get hours() {
            const list = [];
            const isToday = this._isTodayOrUnset;
            const startHour = isToday ? this._now.h : 0;
            for (let h = startHour; h < 24; h++) {
                list.push({ value: h, label: String(h).padStart(2, '0') });
            }
            return list;
        },

        /**
         * Minute list — same idea: when today AND the selected hour is the
         * current hour, the list starts at now+1 so past minutes can't be
         * scrolled to. Otherwise full 0-59.
         */
        get minutes() {
            const list = [];
            const isToday = this._isTodayOrUnset;
            const sameHour = isToday && this.selHour === this._now.h;
            const startMin = sameHour ? this._now.m + 1 : 0;
            for (let m = startMin; m < 60; m++) {
                list.push({ value: m, label: String(m).padStart(2, '0') });
            }
            return list;
        },

        selectHour(h) {
            const previous = this.selHour;
            this.selHour = h;

            // When the customer changes the hour, reset the minute so the
            // picker always lands at the top of the new hour's window —
            // otherwise "10:54 → click 11" leaves a stale 11:54 selection
            // that doesn't match where the scroller is pinned.
            if (h !== previous) {
                if (this._isTodayOrUnset && h === this._now.h) {
                    // Current hour today: first valid minute is now+1.
                    const firstValid = this._now.m + 1;
                    this.selMinute = firstValid <= 59 ? firstValid : 0;
                } else {
                    // Any future hour (or any hour on a future date): 00.
                    this.selMinute = 0;
                }
            }
            // Re-pin the minute scroller to the new top after the list shape changes.
            this.$nextTick(() => this.scrollSelectedIntoView());
        },

        selectMinute(m) {
            this.selMinute = m;
        },

        get checkoutPreview() {
            return this.parent.checkoutPreview;
        },

        get time() {
            return this.parent.time;
        },

        confirm() {
            if (this.selHour === null || this.selMinute === null) return;
            const hh = String(this.selHour).padStart(2, '0');
            const mm = String(this.selMinute).padStart(2, '0');
            this.parent.time = `${hh}:${mm}`;
            this.open = false;
        },
    };
}

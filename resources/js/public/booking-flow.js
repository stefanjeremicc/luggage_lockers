import allCountries from './countries.js';

export default () => ({
    step: 1,
    date: 'today',
    customDate: null,
    time: null,
    // Multi-size cart: each size has its own qty AND its own duration. Replacing
    // the previous global `duration` lets the customer book e.g. 1× standard for
    // 6h and 2× large for 2 days in a single transaction.
    qtys: { standard: 0, large: 0 },
    itemDurations: { standard: null, large: null },
    showMoreDurations: false,
    availability: { standard: { total: 0, booked: 0, available: 0 }, large: { total: 0, booked: 0, available: 0 } },
    pricing: null,
    loading: false,
    locationId: null,

    // Customer
    customer: null,
    guestForm: {
        full_name: '',
        email: '',
        phone: '',
        country_code: 'RS',
        whatsapp_opt_in: false,
        agree_terms: false,
    },
    formErrors: {},

    // All world countries with flags and dial codes
    phoneCountries: allCountries,

    // Duration pricing — hydrated from data-durations on the root element (server-rendered from PricingRule)
    _durations: { standard: [], large: [] },

    // Translations injected from blade
    i18n: {
        lockers_available: ':count lockers available',
        no_lockers: 'No lockers available',
        standard: 'Standard',
        large: 'Large',
        errors: {
            full_name: 'Please enter your full name',
            email_required: 'Email is required',
            email_invalid: 'Please enter a valid email',
            phone_invalid: 'Please enter a valid phone number',
            registration: 'Registration failed.',
            network: 'Network error. Please try again.',
            booking: 'Booking failed.',
        },
        weekdays: ['Mo','Tu','We','Th','Fr','Sa','Su'],
    },

    bookingResult: null,
    error: null,
    _availabilityTimer: null,

    init() {
        this.locationId = this.$el.dataset.locationId;
        try {
            const d = this.$el.dataset.durations;
            if (d) this._durations = JSON.parse(d);
        } catch { /* keep defaults */ }
        try {
            const i = this.$el.dataset.i18n;
            if (i) this.i18n = { ...this.i18n, ...JSON.parse(i) };
        } catch { /* keep defaults */ }

        this.$watch('date', () => this.onParamsChange());
        this.$watch('customDate', () => { if (this.date === 'custom') this.onParamsChange(); });
        this.$watch('time', () => this.onParamsChange());
        this.$watch('qtys.standard', () => this.onParamsChange());
        this.$watch('qtys.large', () => this.onParamsChange());
        this.$watch('itemDurations.standard', () => this.onParamsChange());
        this.$watch('itemDurations.large', () => this.onParamsChange());

        this.$el.addEventListener('custom-date-picked', (e) => {
            this.customDate = e.detail;
        });
    },

    // Duration list — show standard's price set as the default reference (the cart can mix).
    // Each card pulls its own per-size prices via durationPriceFor() helper below.
    get _allDurations() {
        return this._durations.standard?.length ? this._durations.standard : [];
    },

    get durationOptions() {
        const shortKeys = new Set(['6h','24h','2_days','3_days','4_days','5_days']);
        return this._allDurations.filter(d => shortKeys.has(d.key));
    },

    get moreDurationOptions() {
        const longKeys = new Set(['1_week','2_weeks','1_month']);
        return this._allDurations.filter(d => longKeys.has(d.key));
    },

    get allDurationOptions() {
        return this._allDurations;
    },

    durationPriceFor(size, key) {
        const list = this._durations[size] || [];
        const found = list.find(d => d.key === key);
        return found ? found.price : '';
    },

    // Returns the duration KEY currently selected for a size, or null.
    durationFor(size) {
        return this.itemDurations[size] || null;
    },

    setDurationFor(size, key) {
        this.itemDurations[size] = key;
    },

    durationLabelFor(size) {
        const key = this.itemDurations[size];
        if (!key) return '';
        const found = this._allDurations.find(d => d.key === key);
        return found ? found.label : key;
    },

    availabilityLabelFor(size) {
        const a = this.availability[size]?.available;
        if (a === undefined || a === null) return '';
        if (!a || a <= 0) return this.i18n.no_lockers;
        return this.i18n.lockers_available.replace(':count', a);
    },

    sizeLabelFor(size) {
        return size === 'large' ? this.i18n.large : this.i18n.standard;
    },

    get totalQty() {
        return (this.qtys.standard || 0) + (this.qtys.large || 0);
    },

    get cartItems() {
        return ['standard', 'large']
            .filter(s => (this.qtys[s] || 0) > 0)
            .map(s => ({ size: s, qty: this.qtys[s], duration: this.itemDurations[s] }));
    },

    // True when every selected size has a duration set. Drives Continue button
    // and the order summary "complete" state.
    get cartItemsReady() {
        const sel = this.cartItems;
        return sel.length > 0 && sel.every(l => !!l.duration);
    },

    // True when all selected sizes share one duration (UI can collapse the
    // duration display in summary). False when mixed → render per-line.
    get sharedDurationKey() {
        const sel = this.cartItems;
        if (!sel.length) return null;
        const first = sel[0].duration;
        return sel.every(l => l.duration === first) ? first : null;
    },

    incrementSize(size) {
        // If availability hasn't been fetched yet (no date+time+duration for this size),
        // allow user to increment up to a sane fallback. Backend re-validates at booking.
        const max = (this.availability[size]?.total > 0)
            ? (this.availability[size].available || 0)
            : 10;
        if ((this.qtys[size] || 0) < max) this.qtys[size]++;
    },
    decrementSize(size) {
        if ((this.qtys[size] || 0) > 0) {
            this.qtys[size]--;
            // Drop the duration when qty drops to 0 so a stale selection doesn't
            // bleed into the next add.
            if (this.qtys[size] === 0) this.itemDurations[size] = null;
        }
    },

    get canContinueStep1() {
        return this.date && this.time
            && (this.date !== 'custom' || this.customDate);
    },

    get canContinueStep2() {
        return this.cartItemsReady;
    },

    get canContinueStep3() {
        if (this.customer) return true;
        const f = this.guestForm;
        return f.full_name.length >= 2 && this.isValidEmail(f.email) && this.isValidPhone(f.phone) && f.agree_terms;
    },

    get resolvedDate() {
        if (this.date === 'today') return new Date().toISOString().split('T')[0];
        if (this.date === 'tomorrow') {
            const d = new Date();
            d.setDate(d.getDate() + 1);
            return d.toISOString().split('T')[0];
        }
        return this.customDate;
    },

    maxQtyFor(size) {
        const avail = this.availability[size]?.available;
        return (avail && avail > 0) ? avail : 10;
    },

    get orderSummary() {
        if (!this.pricing) return null;
        return {
            lines: this.pricing.lines || [],
            qty: this.pricing.qty,
            subtotal: this.pricing.subtotal_eur,
            serviceFee: this.pricing.service_fee_eur,
            total: this.pricing.total_eur,
            totalRsd: this.pricing.total_rsd,
            durationLabel: this.pricing.duration_label, // null when items have mixed durations
            sharedDuration: this.pricing.shared_duration,
        };
    },

    // Country helpers
    countryLabel() {
        const c = this.phoneCountries.find(c => c.code === this.guestForm.country_code);
        return c ? c.name : 'Serbia';
    },

    countryFlag() {
        const c = this.phoneCountries.find(c => c.code === this.guestForm.country_code);
        return c ? c.flag : '🇷🇸';
    },

    selectedDialCode() {
        const c = this.phoneCountries.find(c => c.code === this.guestForm.country_code);
        return c ? c.dial : '+381';
    },

    // Validation
    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },

    isValidPhone(phone) {
        const digits = phone.replace(/[\s\-\(\)]/g, '');
        return digits.length >= 7 && digits.length <= 15;
    },

    validateField(field) {
        const errors = { ...this.formErrors };
        const f = this.guestForm;

        if (field === 'full_name') {
            if (f.full_name.length < 2) errors.full_name = this.i18n.errors.full_name;
            else delete errors.full_name;
        }
        if (field === 'email') {
            if (!f.email) errors.email = this.i18n.errors.email_required;
            else if (!this.isValidEmail(f.email)) errors.email = this.i18n.errors.email_invalid;
            else delete errors.email;
        }
        if (field === 'phone') {
            if (!this.isValidPhone(f.phone)) errors.phone = this.i18n.errors.phone_invalid;
            else delete errors.phone;
        }

        this.formErrors = errors;
    },

    async onParamsChange() {
        this.error = null;
        clearTimeout(this._availabilityTimer);
        this._availabilityTimer = setTimeout(async () => {
            if (this.resolvedDate && this.time && this.cartItems.some(l => l.duration)) {
                await this.fetchAvailability();
            }
            if (this.cartItemsReady) {
                await this.fetchPricing();
            } else {
                this.pricing = null;
            }
        }, 300);
    },

    async fetchAvailability() {
        if (!this.resolvedDate || !this.time) return;
        // Build per-item availability request — one entry per size with a duration.
        const items = this.cartItems.filter(l => l.duration).map(l => ({ size: l.size, duration: l.duration }));
        if (!items.length) return;
        this.loading = true;
        try {
            const params = new URLSearchParams({ date: this.resolvedDate, time: this.time });
            items.forEach((it, i) => {
                params.append(`items[${i}][size]`, it.size);
                params.append(`items[${i}][duration]`, it.duration);
            });
            const res = await fetch(`/api/locations/${this.locationId}/availability?${params}`);
            if (res.ok) {
                const data = await res.json();
                // Merge per-size availability — leave untouched sizes intact.
                for (const s of ['standard', 'large']) {
                    if (data[s]) this.availability[s] = data[s];
                }
                // Only clamp sizes we actually queried — others may have qty selected
                // before a duration is picked, and we shouldn't reset them to 0.
                const queriedSizes = new Set(items.map(i => i.size));
                for (const s of queriedSizes) {
                    const max = this.availability[s]?.available ?? 0;
                    if (this.qtys[s] > max) this.qtys[s] = max;
                }
            }
        } catch { /* availability will retry on next param change */ }
        finally { this.loading = false; }
    },

    async fetchPricing() {
        if (!this.cartItemsReady) return;
        try {
            const res = await fetch(`/api/locations/${this.locationId}/pricing`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    // Locale isn't in the URL for /api/* — pass via header so the
                    // backend's translated duration_label comes back in the right language.
                    'X-Locale': document.documentElement.lang || 'en',
                },
                body: JSON.stringify({ items: this.cartItems }),
            });
            if (res.ok) this.pricing = await res.json();
        } catch { /* pricing will retry on next param change */ }
    },

    goToStep(s) {
        this.error = null;
        this.step = s;
        window.scrollTo({ top: 0, behavior: 'smooth' });

        if (s === 3 && this.resolvedDate && this.time && this.cartItemsReady) {
            this.fetchAvailability();
        }
    },

    async submitGuestForm() {
        // Validate all fields first
        this.validateField('full_name');
        this.validateField('email');
        this.validateField('phone');
        if (Object.keys(this.formErrors).length > 0) return;

        this.error = null;
        this.loading = true;
        try {
            const payload = { ...this.guestForm };
            payload.phone = this.selectedDialCode() + ' ' + payload.phone;

            const res = await fetch('/api/auth/guest', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (res.ok) { this.customer = data; this.goToStep(4); }
            else { this.error = data.message || this.i18n.errors.registration; }
        } catch (e) { this.error = this.i18n.errors.network; }
        finally { this.loading = false; }
    },

    async submitBooking() {
        this.error = null;
        this.loading = true;
        try {
            const res = await fetch('/api/bookings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${this.customer.token}`,
                },
                body: JSON.stringify({
                    location_id: this.locationId,
                    items: this.cartItems, // each item carries its own duration
                    date: this.resolvedDate,
                    time: this.time,
                    payment_method: 'cash',
                }),
            });
            const data = await res.json();
            if (res.ok) { window.location.href = data.redirect_url; }
            else if (res.status === 409) {
                this.error = data.message || 'Lockers no longer available.';
                if (data.available) {
                    this.availability = {
                        standard: data.available.standard || this.availability.standard,
                        large: data.available.large || this.availability.large,
                    };
                }
                this.goToStep(1);
            } else { this.error = data.message || this.i18n.errors.booking; }
        } catch (e) { this.error = this.i18n.errors.network; }
        finally { this.loading = false; }
    },

    _locale() {
        const html = document.documentElement.lang || 'en';
        return html === 'sr' ? 'sr-RS' : 'en-US';
    },
    formatPrice(amount) {
        return new Intl.NumberFormat(this._locale(), { style: 'currency', currency: 'EUR' }).format(amount);
    },
    formatTime(time) {
        const [h, m] = time.split(':');
        return `${String(parseInt(h)).padStart(2, '0')}:${m}`;
    },
    // Numeric d.m.Y format for the Serbian latin / EN summary (matches App\Helpers\Dates).
    formatSummaryDate() {
        if (!this.resolvedDate) return '';
        const [y, m, d] = this.resolvedDate.split('-');
        return `${d}.${m}.${y}`;
    },
    formatConfirmDate() {
        if (!this.resolvedDate) return '';
        const dateStr = this.formatSummaryDate();
        return this.time ? `${dateStr} ${this.formatTime(this.time)}` : dateStr;
    },
    generateTimeSlots() {
        const slots = [];
        const now = new Date();
        const isToday = this.date === 'today';
        for (let h = 0; h < 24; h++) {
            for (let m = 0; m < 60; m += 15) {
                if (isToday && (h * 60 + m) < (now.getHours() * 60 + now.getMinutes() + 20)) continue;
                slots.push(`${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`);
            }
        }
        return slots;
    },
});

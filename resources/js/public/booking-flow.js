export default () => ({
    step: 1,
    date: 'today',
    customDate: null,
    time: null,
    lockerSize: null,
    qty: 1,
    duration: null,
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

    // Phone countries with flags and dial codes
    phoneCountries: [
        { code: 'RS', name: 'Serbia', flag: '🇷🇸', dial: '+381' },
        { code: 'US', name: 'United States', flag: '🇺🇸', dial: '+1' },
        { code: 'GB', name: 'United Kingdom', flag: '🇬🇧', dial: '+44' },
        { code: 'DE', name: 'Germany', flag: '🇩🇪', dial: '+49' },
        { code: 'FR', name: 'France', flag: '🇫🇷', dial: '+33' },
        { code: 'IT', name: 'Italy', flag: '🇮🇹', dial: '+39' },
        { code: 'ES', name: 'Spain', flag: '🇪🇸', dial: '+34' },
        { code: 'NL', name: 'Netherlands', flag: '🇳🇱', dial: '+31' },
        { code: 'AT', name: 'Austria', flag: '🇦🇹', dial: '+43' },
        { code: 'CH', name: 'Switzerland', flag: '🇨🇭', dial: '+41' },
        { code: 'TR', name: 'Turkey', flag: '🇹🇷', dial: '+90' },
        { code: 'GR', name: 'Greece', flag: '🇬🇷', dial: '+30' },
        { code: 'HR', name: 'Croatia', flag: '🇭🇷', dial: '+385' },
        { code: 'BA', name: 'Bosnia & Herzegovina', flag: '🇧🇦', dial: '+387' },
        { code: 'ME', name: 'Montenegro', flag: '🇲🇪', dial: '+382' },
        { code: 'MK', name: 'North Macedonia', flag: '🇲🇰', dial: '+389' },
        { code: 'BG', name: 'Bulgaria', flag: '🇧🇬', dial: '+359' },
        { code: 'RO', name: 'Romania', flag: '🇷🇴', dial: '+40' },
        { code: 'HU', name: 'Hungary', flag: '🇭🇺', dial: '+36' },
        { code: 'CZ', name: 'Czech Republic', flag: '🇨🇿', dial: '+420' },
        { code: 'PL', name: 'Poland', flag: '🇵🇱', dial: '+48' },
        { code: 'SE', name: 'Sweden', flag: '🇸🇪', dial: '+46' },
        { code: 'NO', name: 'Norway', flag: '🇳🇴', dial: '+47' },
        { code: 'DK', name: 'Denmark', flag: '🇩🇰', dial: '+45' },
        { code: 'FI', name: 'Finland', flag: '🇫🇮', dial: '+358' },
        { code: 'PT', name: 'Portugal', flag: '🇵🇹', dial: '+351' },
        { code: 'IE', name: 'Ireland', flag: '🇮🇪', dial: '+353' },
        { code: 'BE', name: 'Belgium', flag: '🇧🇪', dial: '+32' },
        { code: 'AL', name: 'Albania', flag: '🇦🇱', dial: '+355' },
        { code: 'UA', name: 'Ukraine', flag: '🇺🇦', dial: '+380' },
        { code: 'RU', name: 'Russia', flag: '🇷🇺', dial: '+7' },
        { code: 'IL', name: 'Israel', flag: '🇮🇱', dial: '+972' },
        { code: 'AE', name: 'UAE', flag: '🇦🇪', dial: '+971' },
        { code: 'JP', name: 'Japan', flag: '🇯🇵', dial: '+81' },
        { code: 'CN', name: 'China', flag: '🇨🇳', dial: '+86' },
        { code: 'KR', name: 'South Korea', flag: '🇰🇷', dial: '+82' },
        { code: 'AU', name: 'Australia', flag: '🇦🇺', dial: '+61' },
        { code: 'CA', name: 'Canada', flag: '🇨🇦', dial: '+1' },
        { code: 'BR', name: 'Brazil', flag: '🇧🇷', dial: '+55' },
        { code: 'IN', name: 'India', flag: '🇮🇳', dial: '+91' },
    ],

    // Duration pricing — populated based on locker size
    _standardPrices: {
        '6h': '€5', '24h': '€10', '2_days': '€18', '3_days': '€25',
        '4_days': '€30', '5_days': '€35', '1_week': '€50', '2_weeks': '€85', '1_month': '€150'
    },
    _largePrices: {
        '6h': '€10', '24h': '€15', '2_days': '€27', '3_days': '€38',
        '4_days': '€45', '5_days': '€52', '1_week': '€75', '2_weeks': '€130', '1_month': '€230'
    },

    bookingResult: null,
    error: null,
    _availabilityTimer: null,

    init() {
        this.locationId = this.$el.dataset.locationId;

        this.$watch('date', () => this.onParamsChange());
        this.$watch('customDate', () => { if (this.date === 'custom') this.onParamsChange(); });
        this.$watch('time', () => this.onParamsChange());
        this.$watch('lockerSize', () => this.onParamsChange());
        this.$watch('qty', () => this.fetchPricing());
        this.$watch('duration', () => this.onParamsChange());

        this.$el.addEventListener('custom-date-picked', (e) => {
            this.customDate = e.detail;
        });
    },

    get durationOptions() {
        const prices = this.lockerSize === 'large' ? this._largePrices : this._standardPrices;
        return [
            { key: '6h', label: 'Up to 6 hours', price: prices['6h'] },
            { key: '24h', label: '24 hours', price: prices['24h'] },
            { key: '2_days', label: '2 days', price: prices['2_days'] },
            { key: '3_days', label: '3 days', price: prices['3_days'] },
            { key: '4_days', label: '4 days', price: prices['4_days'] },
            { key: '5_days', label: '5 days', price: prices['5_days'] },
        ];
    },

    get moreDurationOptions() {
        const prices = this.lockerSize === 'large' ? this._largePrices : this._standardPrices;
        return [
            { key: '1_week', label: '1 week', price: prices['1_week'] },
            { key: '2_weeks', label: '2 weeks', price: prices['2_weeks'] },
            { key: '1_month', label: '1 month', price: prices['1_month'] },
        ];
    },

    get canContinueStep1() {
        return this.date && this.lockerSize && this.qty > 0
            && (this.date !== 'custom' || this.customDate);
    },

    get canContinueStep2() {
        return this.duration && this.time;
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

    get maxQty() {
        if (!this.lockerSize || !this.availability[this.lockerSize]) return 10;
        const avail = this.availability[this.lockerSize].available;
        return avail > 0 ? avail : 10;
    },

    get orderSummary() {
        if (!this.pricing) return null;
        return {
            unitPrice: this.pricing.unit_price_eur,
            qty: this.pricing.qty,
            subtotal: this.pricing.subtotal_eur,
            serviceFee: this.pricing.service_fee_eur,
            total: this.pricing.total_eur,
            totalRsd: this.pricing.total_rsd,
            durationLabel: this.pricing.duration_label,
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
            if (f.full_name.length < 2) errors.full_name = 'Please enter your full name';
            else delete errors.full_name;
        }
        if (field === 'email') {
            if (!f.email) errors.email = 'Email is required';
            else if (!this.isValidEmail(f.email)) errors.email = 'Please enter a valid email';
            else delete errors.email;
        }
        if (field === 'phone') {
            if (!this.isValidPhone(f.phone)) errors.phone = 'Please enter a valid phone number';
            else delete errors.phone;
        }

        this.formErrors = errors;
    },

    async onParamsChange() {
        this.error = null;
        clearTimeout(this._availabilityTimer);
        this._availabilityTimer = setTimeout(async () => {
            if (this.resolvedDate && this.time && this.duration) {
                await this.fetchAvailability();
            }
            if (this.lockerSize && this.duration && this.qty > 0) {
                await this.fetchPricing();
            }
        }, 300);
    },

    async fetchAvailability() {
        if (!this.resolvedDate || !this.time || !this.duration) return;
        this.loading = true;
        try {
            const params = new URLSearchParams({ date: this.resolvedDate, time: this.time, duration: this.duration });
            const res = await fetch(`/api/locations/${this.locationId}/availability?${params}`);
            if (res.ok) {
                this.availability = await res.json();
                if (this.lockerSize && this.qty > this.maxQty) this.qty = Math.max(1, this.maxQty);
            }
        } catch (e) { console.error('Availability fetch failed:', e); }
        finally { this.loading = false; }
    },

    async fetchPricing() {
        if (!this.lockerSize || !this.duration || !this.qty) return;
        try {
            const params = new URLSearchParams({ size: this.lockerSize, duration: this.duration, qty: this.qty });
            const res = await fetch(`/api/locations/${this.locationId}/pricing?${params}`);
            if (res.ok) this.pricing = await res.json();
        } catch (e) { console.error('Pricing fetch failed:', e); }
    },

    incrementQty() { if (this.qty < this.maxQty) this.qty++; },
    decrementQty() { if (this.qty > 1) this.qty--; },

    goToStep(s) {
        this.error = null;
        this.step = s;
        window.scrollTo({ top: 0, behavior: 'smooth' });
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
            // Prepend dial code to phone
            payload.phone = this.selectedDialCode() + ' ' + payload.phone;

            const res = await fetch('/api/auth/guest', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (res.ok) { this.customer = data; this.goToStep(4); }
            else { this.error = data.message || 'Registration failed.'; }
        } catch (e) { this.error = 'Network error. Please try again.'; }
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
                    locker_size: this.lockerSize,
                    locker_qty: this.qty,
                    date: this.resolvedDate,
                    time: this.time,
                    duration: this.duration,
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
            } else { this.error = data.message || 'Booking failed.'; }
        } catch (e) { this.error = 'Network error. Please try again.'; }
        finally { this.loading = false; }
    },

    formatPrice(amount) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'EUR' }).format(amount);
    },
    formatTime(time) {
        const [h, m] = time.split(':');
        const hour = parseInt(h);
        return `${hour % 12 || 12}:${m} ${hour >= 12 ? 'PM' : 'AM'}`;
    },
    formatSummaryDate() {
        if (!this.resolvedDate) return '';
        const d = new Date(this.resolvedDate + 'T00:00:00');
        return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    },
    formatConfirmDate() {
        if (!this.resolvedDate) return '';
        const d = new Date(this.resolvedDate + 'T00:00:00');
        const dateStr = d.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
        return this.time ? `${dateStr} at ${this.formatTime(this.time)}` : dateStr;
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

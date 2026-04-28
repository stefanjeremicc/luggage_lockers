export default (config = {}) => ({
    currentMonth: null, // 0-indexed
    currentYear: null,
    selectedDate: null, // 'YYYY-MM-DD'
    minDate: config.minDate || null,
    maxDate: config.maxDate || null,

    init() {
        const today = new Date();
        this.currentMonth = today.getMonth();
        this.currentYear = today.getFullYear();
    },

    get monthLabel() {
        const d = new Date(this.currentYear, this.currentMonth, 1);
        return d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    },

    get canGoPrev() {
        if (!this.minDate) return true;
        const min = new Date(this.minDate + 'T00:00:00');
        return this.currentYear > min.getFullYear() ||
            (this.currentYear === min.getFullYear() && this.currentMonth > min.getMonth());
    },

    get canGoNext() {
        if (!this.maxDate) return true;
        const max = new Date(this.maxDate + 'T00:00:00');
        return this.currentYear < max.getFullYear() ||
            (this.currentYear === max.getFullYear() && this.currentMonth < max.getMonth());
    },

    prevMonth() {
        if (!this.canGoPrev) return;
        if (this.currentMonth === 0) {
            this.currentMonth = 11;
            this.currentYear--;
        } else {
            this.currentMonth--;
        }
    },

    nextMonth() {
        if (!this.canGoNext) return;
        if (this.currentMonth === 11) {
            this.currentMonth = 0;
            this.currentYear++;
        } else {
            this.currentMonth++;
        }
    },

    get calendarDays() {
        const cells = [];
        const firstDay = new Date(this.currentYear, this.currentMonth, 1);
        // Monday = 0, Sunday = 6
        let startDow = firstDay.getDay() - 1;
        if (startDow < 0) startDow = 6;

        const daysInMonth = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();

        // Empty cells for days before 1st
        for (let i = 0; i < startDow; i++) {
            cells.push({ key: 'e' + i, day: null, date: null, enabled: false });
        }

        // Actual days
        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${this.currentYear}-${String(this.currentMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            let enabled = true;
            if (this.minDate && dateStr < this.minDate) enabled = false;
            if (this.maxDate && dateStr > this.maxDate) enabled = false;
            cells.push({ key: dateStr, day: d, date: dateStr, enabled });
        }

        return cells;
    },

    selectDay(cell) {
        if (!cell.enabled || !cell.date) return;
        this.selectedDate = cell.date;
    },

    cellClasses(cell) {
        if (!cell.day) return '';
        if (!cell.enabled) {
            // Disabled (past or beyond max) — readable but visibly muted with strikethrough
            // so the user understands they exist but cannot be picked.
            return 'text-[#5A5A5A] line-through cursor-not-allowed';
        }
        if (cell.date === this.selectedDate) {
            return 'bg-[#F59E0B] text-black font-bold';
        }
        // Today indicator
        const today = new Date().toISOString().split('T')[0];
        if (cell.date === today) {
            return 'border border-[#F59E0B] text-white hover:bg-[#F59E0B]/20 cursor-pointer';
        }
        return 'text-[#E0E0E0] hover:bg-[#2A2A2A] hover:text-white cursor-pointer';
    },
});

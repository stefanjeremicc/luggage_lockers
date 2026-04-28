import Alpine from 'alpinejs';

// Import Alpine components
import bookingFlow from './booking-flow';
import calendarPicker from './calendar-picker';

// Register components
Alpine.data('bookingFlow', bookingFlow);
Alpine.data('calendarPicker', calendarPicker);

// Smooth-scroll for in-page section links without polluting the URL hash.
// Mark a link with data-scroll-to="elementId" and we scroll there with an
// offset for the sticky header (header height varies, so measure live).
// Registered before Alpine.start() so it always attaches even if Alpine
// throws on a hot-reloaded re-import (which it does — Alpine can only start once).
if (!window.__scrollToHandlerInstalled) {
    window.__scrollToHandlerInstalled = true;
    document.addEventListener('click', (e) => {
        const link = e.target.closest('[data-scroll-to]');
        if (!link) return;
        const id = link.getAttribute('data-scroll-to');
        const target = id && document.getElementById(id);
        if (!target) return;
        e.preventDefault();
        const header = document.querySelector('header.site-header, header[data-site-header], header');
        const offset = (header?.offsetHeight ?? 0) + 12;
        const top = target.getBoundingClientRect().top + window.scrollY - offset;
        window.scrollTo({ top, behavior: 'smooth' });
    });
}

// Start Alpine
window.Alpine = Alpine;
Alpine.start();

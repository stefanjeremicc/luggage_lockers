import Alpine from 'alpinejs';

// Import Alpine components
import bookingFlow from './booking-flow';
import calendarPicker from './calendar-picker';

// Register components
Alpine.data('bookingFlow', bookingFlow);
Alpine.data('calendarPicker', calendarPicker);

// Start Alpine
window.Alpine = Alpine;
Alpine.start();

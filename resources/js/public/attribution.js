// First-touch marketing attribution.
//
// On the very first page a visitor lands on, we capture where they came from
// (UTM tags, Google Ads gclid, Facebook fbclid, referrer, landing page) and
// store it in a 90-day cookie. Because we only write the cookie when it's not
// already present, this is *first-touch*: the original source is preserved even
// if the visitor later returns directly or via another channel before booking.
//
// At booking time, getAttribution() reads the cookie so the payload can be sent
// to the API, which derives the marketing_source channel server-side.

const COOKIE = 'll_attr';
const MAX_AGE = 60 * 60 * 24 * 90; // 90 days

function readCookie(name) {
    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : null;
}

function writeCookie(name, value) {
    const secure = location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${name}=${encodeURIComponent(value)}; Max-Age=${MAX_AGE}; Path=/; SameSite=Lax${secure}`;
}

function captureFirstTouch() {
    // Already captured on a previous visit → keep first-touch, do nothing.
    if (readCookie(COOKIE)) return;

    const params = new URLSearchParams(location.search);
    const data = {
        utm_source: params.get('utm_source') || '',
        utm_medium: params.get('utm_medium') || '',
        utm_campaign: params.get('utm_campaign') || '',
        gclid: params.get('gclid') || '',
        fbclid: params.get('fbclid') || '',
        // Only keep an external referrer (ignore our own domain so internal
        // navigation doesn't overwrite the true entry source).
        referrer: isExternalReferrer(document.referrer) ? document.referrer : '',
        landing_page: location.pathname + location.search,
    };

    try {
        writeCookie(COOKIE, JSON.stringify(data));
    } catch (_) { /* cookies disabled — attribution simply stays empty */ }
}

function isExternalReferrer(ref) {
    if (!ref) return false;
    try {
        return new URL(ref).hostname !== location.hostname;
    } catch (_) {
        return false;
    }
}

export function getAttribution() {
    const raw = readCookie(COOKIE);
    if (!raw) return {};
    try {
        return JSON.parse(raw);
    } catch (_) {
        return {};
    }
}

// Capture as early as possible on every public page load.
captureFirstTouch();

// Expose for non-module consumers (Alpine inline handlers / Blade scripts).
window.getAttribution = getAttribution;

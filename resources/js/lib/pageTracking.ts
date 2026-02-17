const ANONYMOUS_ID_STORAGE_KEY = 'sprinkle.anonymous_id';
const ANONYMOUS_ID_COOKIE_KEY = 'sprinkle_anonymous_id';
const LAST_TRACKED_PATH_KEY = 'sprinkle.last_tracked_path';
const LAST_TRACKED_AT_KEY = 'sprinkle.last_tracked_at';
const TRACKING_ENDPOINT = '/api/tracking/page-views';
const ANONYMOUS_ID_COOKIE_MAX_AGE_SECONDS = 60 * 60 * 24 * 365;

let trackingInitialized = false;
let activePath = '';
let activePageKey = '';
let activeReferrer: string | null = null;
let activeStartedAt = 0;
let pageIsVisible = true;

type TrackingEventType = 'view' | 'engagement';

interface TrackingPayload {
    anonymous_id: string;
    page_key: string;
    path: string;
    referrer: string | null;
    event_type: TrackingEventType;
    duration_seconds: number | null;
}

interface CustomTrackingOptions {
    path?: string;
    referrer?: string | null;
    eventType?: TrackingEventType;
    durationSeconds?: number | null;
}

function safeLocalStorageGet(key: string): string | null {
    try {
        return window.localStorage.getItem(key);
    } catch {
        return null;
    }
}

function safeLocalStorageSet(key: string, value: string): void {
    try {
        window.localStorage.setItem(key, value);
    } catch {
        // Ignore storage write failures.
    }
}

function safeCookieGet(key: string): string | null {
    if (typeof document === 'undefined') {
        return null;
    }

    const encodedKey = encodeURIComponent(key);
    const row = document.cookie
        .split('; ')
        .find((cookieRow) => cookieRow.startsWith(`${encodedKey}=`));

    if (!row) {
        return null;
    }

    return decodeURIComponent(row.split('=').slice(1).join('='));
}

function safeCookieSet(key: string, value: string): void {
    if (typeof document === 'undefined') {
        return;
    }

    const encodedKey = encodeURIComponent(key);
    const encodedValue = encodeURIComponent(value);

    document.cookie = `${encodedKey}=${encodedValue}; Max-Age=${ANONYMOUS_ID_COOKIE_MAX_AGE_SECONDS}; Path=/; SameSite=Lax`;
}

function sanitizeAnonymousId(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    return value.replace(/[^a-zA-Z0-9_-]/g, '').slice(0, 80);
}

function randomToken(length = 24): string {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = '';

    for (let index = 0; index < length; index += 1) {
        result += chars[Math.floor(Math.random() * chars.length)] ?? 'x';
    }

    return result;
}

function generateAnonymousId(): string {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return `anon_${crypto.randomUUID().replace(/[^a-zA-Z0-9_-]/g, '')}`;
    }

    return `anon_${randomToken()}`;
}

export function getOrCreateAnonymousId(): string {
    const localStorageId = sanitizeAnonymousId(safeLocalStorageGet(ANONYMOUS_ID_STORAGE_KEY));
    if (localStorageId !== '') {
        safeCookieSet(ANONYMOUS_ID_COOKIE_KEY, localStorageId);

        return localStorageId;
    }

    const cookieId = sanitizeAnonymousId(safeCookieGet(ANONYMOUS_ID_COOKIE_KEY));
    if (cookieId !== '') {
        safeLocalStorageSet(ANONYMOUS_ID_STORAGE_KEY, cookieId);
        safeCookieSet(ANONYMOUS_ID_COOKIE_KEY, cookieId);

        return cookieId;
    }

    const generatedId = sanitizeAnonymousId(generateAnonymousId());
    safeLocalStorageSet(ANONYMOUS_ID_STORAGE_KEY, generatedId);
    safeCookieSet(ANONYMOUS_ID_COOKIE_KEY, generatedId);

    return generatedId;
}

function normalizePath(path: string): string {
    const clean = path.trim();

    if (clean === '' || clean === '/') {
        return '/';
    }

    return clean.endsWith('/') ? clean.slice(0, -1) : clean;
}

function isAdminPath(path: string): boolean {
    return normalizePath(path).startsWith('/admin');
}

function derivePageKey(path: string): string {
    const normalized = normalizePath(path);

    const knownPages: Record<string, string> = {
        '/': 'home',
        '/about': 'about',
        '/faq': 'faq',
        '/terms-and-conditions': 'terms',
        '/services': 'services',
        '/face-painting': 'face_painting',
        '/glitter-tattoos': 'glitter_tattoos',
        '/festival-face-painting': 'festival_face_painting',
        '/events': 'events',
        '/gallery': 'gallery',
        '/designs': 'designs',
        '/testimonials': 'testimonials',
        '/add-testimonial': 'add_testimonial',
        '/quote': 'quote',
    };

    if (knownPages[normalized]) {
        return knownPages[normalized];
    }

    if (normalized.startsWith('/admin')) {
        const segments = normalized.split('/').filter(Boolean);

        return segments.join('_');
    }

    return normalized.replace(/\//g, '_').replace(/^_+/, '') || 'unknown';
}

function shouldTrackPath(path: string): boolean {
    const normalized = normalizePath(path);

    if (isAdminPath(normalized)) {
        return false;
    }

    const lastPath = safeLocalStorageGet(LAST_TRACKED_PATH_KEY);
    const lastTrackedAt = Number(safeLocalStorageGet(LAST_TRACKED_AT_KEY) ?? '0');
    const now = Date.now();

    if (lastPath === normalized && now - lastTrackedAt < 1500) {
        return false;
    }

    safeLocalStorageSet(LAST_TRACKED_PATH_KEY, normalized);
    safeLocalStorageSet(LAST_TRACKED_AT_KEY, String(now));

    return true;
}

function sendTrackingPayload(payload: TrackingPayload, preferBeacon = false): void {
    const body = JSON.stringify(payload);

    if (preferBeacon && typeof navigator !== 'undefined' && typeof navigator.sendBeacon === 'function') {
        const blob = new Blob([body], { type: 'application/json' });
        const sent = navigator.sendBeacon(TRACKING_ENDPOINT, blob);

        if (sent) {
            return;
        }
    }

    fetch(TRACKING_ENDPOINT, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body,
        keepalive: true,
    }).catch(() => {
        // Ignore tracking failures.
    });
}

export function trackCustomTrackingEvent(pageKey: string, options: CustomTrackingOptions = {}): void {
    if (typeof window === 'undefined') {
        return;
    }

    const normalizedPath = normalizePath(options.path ?? window.location.pathname);

    if (isAdminPath(normalizedPath)) {
        return;
    }

    const eventType = options.eventType ?? 'engagement';
    const durationSeconds = eventType === 'engagement'
        ? Math.max(0, Math.round(Number(options.durationSeconds ?? 0)))
        : null;

    sendTrackingPayload({
        anonymous_id: getOrCreateAnonymousId(),
        page_key: pageKey.trim().slice(0, 80) || 'unknown',
        path: normalizedPath,
        referrer: options.referrer ?? document.referrer ?? null,
        event_type: eventType,
        duration_seconds: durationSeconds,
    });
}

function sendPageView(path: string, referrer: string | null): void {
    sendTrackingPayload({
        anonymous_id: getOrCreateAnonymousId(),
        page_key: derivePageKey(path),
        path,
        referrer,
        event_type: 'view',
        duration_seconds: null,
    });
}

function flushEngagement(preferBeacon = true): void {
    if (activePath === '' || activeStartedAt === 0 || !pageIsVisible) {
        return;
    }

    const now = Date.now();
    const durationSeconds = Math.round((now - activeStartedAt) / 1000);

    if (durationSeconds <= 0) {
        return;
    }

    sendTrackingPayload(
        {
            anonymous_id: getOrCreateAnonymousId(),
            page_key: activePageKey,
            path: activePath,
            referrer: activeReferrer,
            event_type: 'engagement',
            duration_seconds: durationSeconds,
        },
        preferBeacon,
    );

    activeStartedAt = now;
}

function beginTrackingForCurrentPath(referrer: string | null): void {
    const path = normalizePath(window.location.pathname);

    if (isAdminPath(path)) {
        activePath = '';
        activePageKey = '';
        activeReferrer = null;
        activeStartedAt = 0;
        pageIsVisible = document.visibilityState !== 'hidden';

        return;
    }

    activePath = path;
    activePageKey = derivePageKey(path);
    activeReferrer = referrer;
    activeStartedAt = Date.now();
    pageIsVisible = document.visibilityState !== 'hidden';

    if (shouldTrackPath(path)) {
        sendPageView(path, referrer);
    }
}

function visibilityChangeHandler(): void {
    if (document.visibilityState === 'hidden') {
        flushEngagement(true);
        pageIsVisible = false;

        return;
    }

    pageIsVisible = true;
    activeStartedAt = Date.now();
}

function inertiaNavigateHandler(): void {
    const previousPath = activePath;

    flushEngagement(false);

    window.setTimeout(() => {
        const internalReferrer = previousPath !== '' ? `${window.location.origin}${previousPath}` : null;
        beginTrackingForCurrentPath(internalReferrer || document.referrer || null);
    }, 0);
}

export function initPageTracking(): void {
    if (trackingInitialized || typeof window === 'undefined') {
        return;
    }

    trackingInitialized = true;

    beginTrackingForCurrentPath(document.referrer || null);

    document.addEventListener('inertia:navigate', inertiaNavigateHandler);
    document.addEventListener('visibilitychange', visibilityChangeHandler);
    window.addEventListener('pagehide', () => flushEngagement(true));
    window.addEventListener('beforeunload', () => flushEngagement(true));
}

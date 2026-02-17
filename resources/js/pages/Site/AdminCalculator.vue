<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref } from 'vue';
import AdminMenu from '@/components/admin/AdminMenu.vue';
import { csrfHeaders, fetchWithCsrfRetry, withCsrfToken } from '@/lib/csrf';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const packageOptions = [
    {
        name: 'Mini Party Sparkle',
        hours: 2,
        baseAmount: 260,
        guestGuide: 'Up to 20 kids',
        summary: 'Great for smaller birthday groups and short sessions.',
        features: [
            'Quick queue-friendly face designs',
            'Glitter finish options included',
            'Ideal for intimate birthday setups',
        ],
    },
    {
        name: 'Classic Birthday Magic',
        hours: 3,
        baseAmount: 360,
        guestGuide: 'Around 30 kids',
        summary: 'Best all-round package for party flow and full variety.',
        features: [
            'Full design menu for mixed age groups',
            'Balanced pacing for party schedules',
            'Most popular birthday package',
        ],
    },
    {
        name: 'Festival Crowd Package',
        hours: 4,
        baseAmount: 520,
        guestGuide: 'Large public events',
        summary: 'Built for schools, markets, fairs, and high-volume lines.',
        features: [
            'High-throughput design flow',
            'Flexible timing for peak periods',
            'Best for community and public events',
        ],
    },
];

const addOnOptions = [
    {
        name: 'Premium Glitter Bar',
        amount: 65,
        summary: 'Extra sparkle station for guests who want glitter-only looks.',
    },
    {
        name: 'Festival Gems',
        amount: 55,
        summary: 'Adhesive face gems and jewels for standout photos.',
    },
    {
        name: 'Express Queue Upgrade',
        amount: 95,
        summary: 'Fast-design menu for high-volume events.',
    },
    {
        name: 'Theme-Matched Design Set',
        amount: 75,
        summary: 'Design board tailored to your event theme.',
    },
];

const defaultPackage = packageOptions[1];

const settingsLoading = ref(false);
const settingsError = ref('');

function defaultCalculatorSettings() {
    return {
        performance_note_sentence:
            'Last event I did about 90 paints in 4 hours — that’s 2.7 minutes per face (including mirror time and getting on/off the chair), or about 22.5 faces per hour. On average, face painters aim for around 12 per hour.',
        artist: {
            name: 'Melody',
            email: 'info@sprinklefairydust.co.nz',
            website: 'https://www.facebook.com/melfairysfacepainting/',
            mobile: '021 555 3921',
        },
        form: {
            organizerName: '',
            organizerEmail: '',
            organizerPhone: '',
            eventName: '',
            eventDate: '',
            startTime: '',
            endTime: '',
            guestCount: 0,
            packageName: '',
            servicesRequested: '',
            travelArea: '',
            venueType: '',
            heardAbout: '',
            eventAddress: '',
            notes: '',
            termsAccepted: false,
            paymentType: 'hourly',
            rate: 120,
            hours: 8,
            pricePerFace: 10,
            numFaces: 30,
            packageHours: defaultPackage.hours,
            packageBaseAmount: defaultPackage.baseAmount,
            selectedAddOns: [],
            customAddOns: [],
            includeSetup: false,
            setupRate: 60,
            setupHours: 2,
            travelType: 'perkm',
            distance: 20,
            travelRate: 0.85,
            flatTravel: 0,
            includePerformance: false,
            perfFaces: 90,
            perfHours: 4,
            includeGST: true,
        },
    };
}

const defaults = defaultCalculatorSettings();
const performanceNoteSentence = ref(defaults.performance_note_sentence);
const artist = ref({ ...defaults.artist });
const form = ref({ ...defaults.form });

const showResult = ref(false);
const notification = ref('');
const savingQuote = ref(false);
const saveQuoteSuccess = ref('');
const saveQuoteError = ref('');
const sourceQuoteId = ref('');
const quoteOutputPanelRef = ref(null);

const result = ref({
    baseLine: '',
    baseAmount: 0,
    addOnsLine: '',
    addOnsAmount: 0,
    setupLine: '',
    setupAmount: 0,
    travelLine: '',
    travelAmount: 0,
    subtotal: 0,
    gstAmount: 0,
    total: 0,
    startDisplay: '—',
    endDisplay: '—',
    hoursDisplay: 0,
    eventSummary: '',
});

const canSaveQuote = computed(() => {
    const name = String(form.value.organizerName ?? '').trim();
    const email = String(form.value.organizerEmail ?? '').trim();

    return name.length > 0 && email.length > 0;
});

const backToQuoteId = computed(() => {
    const quoteId = String(sourceQuoteId.value ?? '').trim();

    return quoteId;
});

const backToQuoteHref = computed(() => {
    if (!backToQuoteId.value) {
        return '/admin/quotes';
    }

    const params = new URLSearchParams({
        quote_id: backToQuoteId.value,
    });

    return `/admin/quotes?${params.toString()}#quote-${encodeURIComponent(backToQuoteId.value)}`;
});

const backToQuoteLabel = computed(() => {
    if (!backToQuoteId.value) {
        return 'Back to Quotes';
    }

    return `Back to Quote #${backToQuoteId.value}`;
});

function toNumber(value) {
    const number = Number(value);
    return Number.isFinite(number) ? number : 0;
}

function normalizeAddOnSelection(value) {
    if (!Array.isArray(value)) {
        return [];
    }

    return [...new Set(value
        .map((item) => String(item ?? '').trim())
        .filter(Boolean)
        .slice(0, 20))];
}

function normalizeCustomAddOns(value) {
    if (!Array.isArray(value)) {
        return [];
    }

    return value
        .map((item) => {
            if (!item || typeof item !== 'object') {
                return null;
            }

            const name = String(item.name ?? '').trim().slice(0, 80);
            const amount = Math.max(0, toNumber(item.amount));

            if (!name) {
                return null;
            }

            return {
                name,
                amount,
            };
        })
        .filter(Boolean)
        .slice(0, 20);
}

function selectedPackageOption() {
    const packageName = String(form.value.packageName ?? '').trim();

    return packageOptions.find((item) => item.name === packageName) ?? null;
}

function resolvedAddOnItems() {
    const selectedNames = normalizeAddOnSelection(form.value.selectedAddOns);
    const selectedItems = addOnOptions
        .filter((item) => selectedNames.includes(item.name))
        .map((item) => ({
            name: item.name,
            amount: toNumber(item.amount),
        }));
    const customItems = normalizeCustomAddOns(form.value.customAddOns);

    return [...selectedItems, ...customItems];
}

function formatAddOnList(items) {
    if (!items.length) {
        return '';
    }

    return items
        .map((item) => `${item.name} ($${toNumber(item.amount).toFixed(2)})`)
        .join(', ');
}

function applyPackagePreset(packageName) {
    const option = packageOptions.find((item) => item.name === packageName);

    if (!option) {
        return;
    }

    form.value.packageName = option.name;
    form.value.packageHours = option.hours;
    form.value.packageBaseAmount = option.baseAmount;
}

function togglePresetAddOn(addOnName) {
    const selected = normalizeAddOnSelection(form.value.selectedAddOns);

    if (selected.includes(addOnName)) {
        form.value.selectedAddOns = selected.filter((item) => item !== addOnName);
        return;
    }

    form.value.selectedAddOns = [...selected, addOnName];
}

function addCustomAddOnRow() {
    const current = Array.isArray(form.value.customAddOns) ? form.value.customAddOns : [];

    form.value.customAddOns = [...current, { name: '', amount: 0 }];
}

function removeCustomAddOnRow(index) {
    if (!Array.isArray(form.value.customAddOns)) {
        form.value.customAddOns = [];
        return;
    }

    form.value.customAddOns = form.value.customAddOns.filter((_, rowIndex) => rowIndex !== index);
}

const selectedPackage = computed(() => selectedPackageOption());
const selectedAddOnSummary = computed(() => formatAddOnList(resolvedAddOnItems()));

function normalizeCalculatorSettings(source) {
    const payload = source && typeof source === 'object' ? source : {};
    const payloadArtist = payload.artist && typeof payload.artist === 'object' ? payload.artist : {};
    const payloadForm = payload.form && typeof payload.form === 'object' ? payload.form : {};

    return {
        performance_note_sentence: String(payload.performance_note_sentence ?? defaults.performance_note_sentence),
        artist: {
            name: String(payloadArtist.name ?? defaults.artist.name),
            email: String(payloadArtist.email ?? defaults.artist.email),
            website: String(payloadArtist.website ?? defaults.artist.website),
            mobile: String(payloadArtist.mobile ?? defaults.artist.mobile),
        },
        form: {
            organizerName: String(payloadForm.organizerName ?? defaults.form.organizerName),
            organizerEmail: String(payloadForm.organizerEmail ?? defaults.form.organizerEmail),
            organizerPhone: String(payloadForm.organizerPhone ?? defaults.form.organizerPhone),
            eventName: String(payloadForm.eventName ?? defaults.form.eventName),
            eventDate: String(payloadForm.eventDate ?? defaults.form.eventDate),
            startTime: String(payloadForm.startTime ?? defaults.form.startTime),
            endTime: String(payloadForm.endTime ?? defaults.form.endTime),
            guestCount: toNumber(payloadForm.guestCount ?? defaults.form.guestCount),
            packageName: String(payloadForm.packageName ?? defaults.form.packageName),
            servicesRequested: String(payloadForm.servicesRequested ?? defaults.form.servicesRequested),
            travelArea: String(payloadForm.travelArea ?? defaults.form.travelArea),
            venueType: String(payloadForm.venueType ?? defaults.form.venueType),
            heardAbout: String(payloadForm.heardAbout ?? defaults.form.heardAbout),
            eventAddress: String(payloadForm.eventAddress ?? defaults.form.eventAddress),
            notes: String(payloadForm.notes ?? defaults.form.notes),
            termsAccepted: Boolean(payloadForm.termsAccepted ?? defaults.form.termsAccepted),
            paymentType: ['hourly', 'perface', 'package'].includes(String(payloadForm.paymentType))
                ? String(payloadForm.paymentType)
                : defaults.form.paymentType,
            rate: toNumber(payloadForm.rate ?? defaults.form.rate),
            hours: toNumber(payloadForm.hours ?? defaults.form.hours),
            pricePerFace: toNumber(payloadForm.pricePerFace ?? defaults.form.pricePerFace),
            numFaces: toNumber(payloadForm.numFaces ?? defaults.form.numFaces),
            packageHours: toNumber(payloadForm.packageHours ?? defaults.form.packageHours),
            packageBaseAmount: toNumber(payloadForm.packageBaseAmount ?? defaults.form.packageBaseAmount),
            selectedAddOns: normalizeAddOnSelection(payloadForm.selectedAddOns ?? defaults.form.selectedAddOns),
            customAddOns: normalizeCustomAddOns(payloadForm.customAddOns ?? defaults.form.customAddOns),
            includeSetup: Boolean(payloadForm.includeSetup ?? defaults.form.includeSetup),
            setupRate: toNumber(payloadForm.setupRate ?? defaults.form.setupRate),
            setupHours: toNumber(payloadForm.setupHours ?? defaults.form.setupHours),
            travelType: ['perkm', 'flat'].includes(String(payloadForm.travelType))
                ? String(payloadForm.travelType)
                : defaults.form.travelType,
            distance: toNumber(payloadForm.distance ?? defaults.form.distance),
            travelRate: toNumber(payloadForm.travelRate ?? defaults.form.travelRate),
            flatTravel: toNumber(payloadForm.flatTravel ?? defaults.form.flatTravel),
            includePerformance: Boolean(payloadForm.includePerformance ?? defaults.form.includePerformance),
            perfFaces: toNumber(payloadForm.perfFaces ?? defaults.form.perfFaces),
            perfHours: toNumber(payloadForm.perfHours ?? defaults.form.perfHours),
            includeGST: Boolean(payloadForm.includeGST ?? defaults.form.includeGST),
        },
    };
}

function applyCalculatorSettings(source) {
    const settings = normalizeCalculatorSettings(source);

    performanceNoteSentence.value = settings.performance_note_sentence;
    artist.value = settings.artist;
    form.value = settings.form;
}

async function loadCalculatorSettings() {
    settingsLoading.value = true;
    settingsError.value = '';

    try {
        const response = await fetch('/admin/settings/calculator', {
            headers: {
                Accept: 'application/json',
            },
        });

        const data = await response.json();

        if (!response.ok) {
            settingsError.value = data.message || data.error || 'Failed to load calculator defaults.';
            applyCalculatorSettings(defaults);
            return;
        }

        applyCalculatorSettings(data);
    } catch {
        settingsError.value = 'Failed to load calculator defaults.';
        applyCalculatorSettings(defaults);
    } finally {
        settingsLoading.value = false;
    }
}

function applyQueryParams() {
    const params = new URLSearchParams(window.location.search);

    const quoteId = params.get('quote_id') ?? '';
    const name = params.get('name') ?? '';
    const email = params.get('email') ?? '';
    const date = params.get('date') ?? '';
    const event = params.get('event') ?? '';
    const type = params.get('type') ?? '';

    const start = params.get('start') ?? '';
    const end = params.get('end') ?? '';
    const hours = params.get('hours') ?? '';
    const phone = params.get('phone') ?? '';
    const guestCount = params.get('guest_count') ?? '';
    const packageName = params.get('package_name') ?? '';
    const services = params.get('services') ?? '';
    const travelArea = params.get('travel_area') ?? '';
    const venueType = params.get('venue_type') ?? '';
    const heardAbout = params.get('heard_about') ?? '';
    const address = params.get('address') ?? '';
    const notes = params.get('notes') ?? '';

    if (quoteId) {
        sourceQuoteId.value = quoteId;
    }

    if (name) {
        form.value.organizerName = name;
    }

    if (email) {
        form.value.organizerEmail = email;
    }

    if (phone) {
        form.value.organizerPhone = phone;
    }

    if (date) {
        form.value.eventDate = date;
    }

    if (start) {
        form.value.startTime = start;
    }

    if (end) {
        form.value.endTime = end;
    }

    if (hours) {
        form.value.hours = toNumber(hours);
    }

    if (guestCount) {
        form.value.guestCount = toNumber(guestCount);
    }

    if (packageName) {
        form.value.packageName = packageName;
        applyPackagePreset(packageName);
    }

    if (services) {
        form.value.servicesRequested = services;
    }

    if (travelArea) {
        form.value.travelArea = travelArea;
    }

    if (venueType) {
        form.value.venueType = venueType;
    }

    if (heardAbout) {
        form.value.heardAbout = heardAbout;
    }

    if (address) {
        form.value.eventAddress = address;
    }

    if (notes) {
        form.value.notes = notes;
    }

    if (event && !form.value.eventName) {
        form.value.eventName = event;
    }

    if (type) {
        const paymentMatch = ['hourly', 'perface', 'package'].includes(type.toLowerCase())
            ? type.toLowerCase()
            : null;

        if (paymentMatch) {
            form.value.paymentType = paymentMatch;
        }

        if (!event && name && !form.value.eventName) {
            const formattedType = `${type.charAt(0).toUpperCase()}${type.slice(1).toLowerCase()}`;
            form.value.eventName = `${name} ${formattedType}`;
        }
    }
}

function calculateTotal() {
    saveQuoteSuccess.value = '';
    saveQuoteError.value = '';

    const paymentMode = form.value.paymentType;
    const addOnItems = resolvedAddOnItems();
    const addOnTotal = addOnItems.reduce((sum, item) => sum + toNumber(item.amount), 0);
    const addOnLine = addOnItems.length
        ? `Add-ons: ${formatAddOnList(addOnItems)} = $${addOnTotal.toFixed(2)}`
        : '';

    let baseTotal = 0;
    let baseLine = '';
    let hoursDisplay = toNumber(form.value.hours);
    let eventSummary = '';

    if (paymentMode === 'hourly') {
        const rate = toNumber(form.value.rate);
        const hours = toNumber(form.value.hours);
        baseTotal = rate * hours;
        baseLine = `Hourly: ${hours} hours × $${rate.toFixed(2)} = $${baseTotal.toFixed(2)}`;
        hoursDisplay = hours;
        eventSummary = `Hours: ${hours} · Rate: $${rate.toFixed(2)}/hr`;
    } else if (paymentMode === 'perface') {
        const pricePerFace = toNumber(form.value.pricePerFace);
        const numFaces = toNumber(form.value.numFaces);
        baseTotal = pricePerFace * numFaces;
        baseLine = `Per Face: ${numFaces} faces × $${pricePerFace.toFixed(2)} = $${baseTotal.toFixed(2)}`;
        eventSummary = `Per Face: ${numFaces} faces`;
    } else {
        const packageName = String(form.value.packageName ?? '').trim() || 'Custom Package';
        const packageHours = toNumber(form.value.packageHours);
        const packageAmount = toNumber(form.value.packageBaseAmount);
        const hoursText = packageHours > 0 ? `${packageHours} hours` : 'hours TBC';
        baseTotal = packageAmount;
        baseLine = `Package: ${packageName} (${hoursText}) = $${packageAmount.toFixed(2)}`;
        hoursDisplay = packageHours;
        eventSummary = `Package: ${packageName} · ${hoursText}`;
    }

    const setupRate = toNumber(form.value.setupRate);
    const setupHours = toNumber(form.value.setupHours);
    const setupTotal = form.value.includeSetup ? setupRate * setupHours : 0;

    const setupLine = form.value.includeSetup
        ? `Setup: ${setupHours} hours × $${setupRate.toFixed(2)} = $${setupTotal.toFixed(2)}`
        : '';

    let travelTotal = 0;
    let travelLine = '';

    if (form.value.travelType === 'perkm') {
        const distance = toNumber(form.value.distance);
        const travelRate = toNumber(form.value.travelRate);
        const roundTripKm = distance * 2;
        travelTotal = roundTripKm * travelRate;
        travelLine = `Travel: ${roundTripKm} km × $${travelRate.toFixed(2)} = $${travelTotal.toFixed(2)}`;
    } else {
        const flatTravel = toNumber(form.value.flatTravel);
        travelTotal = flatTravel;
        travelLine = `Travel (flat): $${flatTravel.toFixed(2)}`;
    }

    const baseAmount = baseTotal + addOnTotal;
    const subtotal = baseAmount + setupTotal + travelTotal;
    const gstAmount = form.value.includeGST ? subtotal * 0.15 : 0;
    const total = subtotal + gstAmount;
    const startDisplay = form.value.startTime || '—';
    const endDisplay = form.value.endTime || '—';

    result.value = {
        baseLine,
        baseAmount,
        addOnsLine: addOnLine,
        addOnsAmount: addOnTotal,
        setupLine,
        setupAmount: setupTotal,
        travelLine,
        travelAmount: travelTotal,
        subtotal,
        gstAmount,
        total,
        startDisplay,
        endDisplay,
        hoursDisplay,
        eventSummary,
    };

    showResult.value = true;
    scrollToQuoteOutput();
}

function scrollToQuoteOutput() {
    nextTick(() => {
        window.requestAnimationFrame(() => {
            quoteOutputPanelRef.value?.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        });
    });
}

const quoteText = computed(() => {
    if (!showResult.value) {
        return '';
    }

    const lines = [];

    if (form.value.organizerName || form.value.organizerEmail) {
        lines.push(`Organizer: ${form.value.organizerName} (${form.value.organizerEmail})`.trim());
    }

    if (form.value.organizerPhone) {
        lines.push(`Phone: ${form.value.organizerPhone}`);
    }

    if (form.value.eventName || form.value.eventDate) {
        lines.push(`Event: ${form.value.eventName} on ${form.value.eventDate || 'date TBC'}`.trim());
    }

    if (toNumber(form.value.guestCount) > 0) {
        lines.push(`Guest Count: ${Math.round(toNumber(form.value.guestCount))}`);
    }

    if (form.value.packageName) {
        lines.push(`Package: ${form.value.packageName}`);
    }

    if (form.value.servicesRequested) {
        lines.push(`Services: ${form.value.servicesRequested}`);
    }

    if (selectedAddOnSummary.value) {
        lines.push(`Add-ons: ${selectedAddOnSummary.value}`);
    }

    if (form.value.travelArea) {
        lines.push(`Travel Area: ${form.value.travelArea}`);
    }

    if (form.value.venueType) {
        lines.push(`Venue Type: ${form.value.venueType}`);
    }

    if (form.value.heardAbout) {
        lines.push(`Heard About Us: ${form.value.heardAbout}`);
    }

    if (form.value.eventAddress) {
        lines.push(`Address: ${form.value.eventAddress}`);
    }

    if (form.value.notes) {
        lines.push(`Notes: ${form.value.notes}`);
    }

    lines.push(`Terms Accepted: ${form.value.termsAccepted ? 'Yes' : 'No'}`);

    lines.push(`Start Time: ${result.value.startDisplay}`);
    lines.push(`End Time: ${result.value.endDisplay}`);
    lines.push(`Total Hours: ${result.value.hoursDisplay}`);
    lines.push('');
    lines.push(result.value.baseLine);

    if (result.value.addOnsLine) {
        lines.push(result.value.addOnsLine);
    }

    if (result.value.setupLine) {
        lines.push(result.value.setupLine);
    }

    lines.push(result.value.travelLine);
    lines.push(`Subtotal: $${result.value.subtotal.toFixed(2)}`);

    if (form.value.includeGST) {
        lines.push(`GST (15%): $${result.value.gstAmount.toFixed(2)}`);
    } else {
        lines.push('GST: Not included');
    }

    lines.push(`Total: $${result.value.total.toFixed(2)}`);

    if (form.value.includePerformance) {
        lines.push('');
        lines.push('Performance Note:');
        lines.push(performanceNoteSentence.value);
    }

    return ['Sprinkle Fairydust Face Painting Quote', '', ...lines].join('\n');
});

async function copyQuote() {
    if (!quoteText.value) {
        return;
    }

    try {
        await navigator.clipboard.writeText(quoteText.value);
        notification.value = 'Quote copied to clipboard.';
    } catch {
        notification.value = 'Could not copy quote automatically.';
    }
}

function generateEmail() {
    if (!quoteText.value) {
        return;
    }

    const subject = encodeURIComponent('Your Face Painting Quote');
    const body = encodeURIComponent(quoteText.value);
    window.location.href = `mailto:?subject=${subject}&body=${body}`;
}

function resetForm() {
    window.location.reload();
}

function firstValidationError(errors) {
    if (!errors || typeof errors !== 'object') {
        return null;
    }

    for (const value of Object.values(errors)) {
        if (Array.isArray(value) && value.length > 0) {
            return String(value[0]);
        }
    }

    return null;
}

function quotePayload() {
    const eventType = String(form.value.eventName ?? '').trim();
    const totalHours = toNumber(result.value.hoursDisplay);
    const servicesRequested = String(form.value.servicesRequested ?? '')
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean);
    const addOnServices = resolvedAddOnItems().map((item) => {
        const amount = toNumber(item.amount);

        return amount > 0
            ? `Add-on: ${item.name} ($${amount.toFixed(2)})`
            : `Add-on: ${item.name}`;
    });
    const mergedServices = [...new Set([...servicesRequested, ...addOnServices])].slice(0, 20);

    return {
        name: String(form.value.organizerName ?? '').trim(),
        email: String(form.value.organizerEmail ?? '').trim(),
        phone: String(form.value.organizerPhone ?? '').trim() || null,
        guest_count: toNumber(form.value.guestCount) > 0 ? Math.round(toNumber(form.value.guestCount)) : null,
        package_name: String(form.value.packageName ?? '').trim() || null,
        services_requested: mergedServices.length ? mergedServices : null,
        travel_area: String(form.value.travelArea ?? '').trim() || null,
        venue_type: String(form.value.venueType ?? '').trim() || null,
        heard_about: String(form.value.heardAbout ?? '').trim() || null,
        notes: String(form.value.notes ?? '').trim() || null,
        terms_accepted: Boolean(form.value.termsAccepted),
        event_type: eventType || null,
        event_date: form.value.eventDate || null,
        address: String(form.value.eventAddress ?? '').trim() || null,
        start_time: form.value.startTime || null,
        end_time: form.value.endTime || null,
        total_hours: totalHours > 0 ? totalHours : null,
        calc_payment_type: form.value.paymentType || null,
        calc_base_amount: Number(result.value.baseAmount.toFixed(2)),
        calc_setup_amount: Number(result.value.setupAmount.toFixed(2)),
        calc_travel_amount: Number(result.value.travelAmount.toFixed(2)),
        calc_subtotal: Number(result.value.subtotal.toFixed(2)),
        calc_gst_amount: form.value.includeGST ? Number(result.value.gstAmount.toFixed(2)) : null,
        calc_total_amount: Number(result.value.total.toFixed(2)),
    };
}

async function saveQuote() {
    saveQuoteSuccess.value = '';
    saveQuoteError.value = '';

    if (!showResult.value) {
        saveQuoteError.value = 'Calculate a quote before saving.';
        return;
    }

    if (!canSaveQuote.value) {
        saveQuoteError.value = 'Organizer name and email are required to save a quote.';
        return;
    }

    savingQuote.value = true;

    try {
        const response = await fetchWithCsrfRetry('/admin/quotes', {
            method: 'POST',
            credentials: 'same-origin',
            headers: csrfHeaders(),
            body: JSON.stringify(withCsrfToken(quotePayload())),
        });

        const data = await response.json();

        if (!response.ok) {
            saveQuoteError.value =
                firstValidationError(data.errors) ||
                data.message ||
                data.error ||
                'Failed to save quote.';

            return;
        }

        const savedQuoteId = data?.quote?.id;
        if (savedQuoteId !== undefined && savedQuoteId !== null && savedQuoteId !== '') {
            sourceQuoteId.value = String(savedQuoteId);
        }

        saveQuoteSuccess.value = 'Quote saved to admin quotes.';
    } catch {
        saveQuoteError.value = 'Failed to save quote.';
    } finally {
        savingQuote.value = false;
    }
}

onMounted(async () => {
    await loadCalculatorSettings();
    applyQueryParams();
});
</script>

<template>
    <Head title="Admin Quote Calculator | Sprinkle Fairydust" />

    <main class="mx-auto mt-10 max-w-7xl px-4 pb-10 font-quicksand">
        <section class="rounded-3xl border border-slate-200/90 bg-slate-50/90 p-5 shadow-2xl backdrop-blur-md md:p-6">
            <h1 class="mb-2 text-center text-5xl text-slate-900 drop-shadow-sm font-dancing">Quote Calculator</h1>
            <p class="mb-8 text-center text-slate-600">Create quotes quickly for incoming event requests.</p>

            <AdminMenu />
            <p v-if="settingsLoading" class="mb-5 text-sm font-semibold text-slate-600">Loading calculator defaults...</p>
            <p v-if="settingsError" class="mb-5 text-sm font-semibold text-rose-700">{{ settingsError }}</p>

            <div class="grid gap-6 lg:grid-cols-[1.45fr_1fr]">
                <section class="panel">
                    <div class="mb-6 flex items-center gap-4">
                        <img src="/images/logo.png" alt="Sprinkle Fairydust Face Painting" class="h-16 w-16 rounded-xl border border-slate-200 bg-white object-contain p-1 shadow-sm" />
                        <div>
                            <h2 class="text-xl font-semibold text-sky-700 md:text-2xl">Calculator Inputs</h2>
                            <p class="text-sm text-slate-500">Use event details to estimate totals and prepare quote text.</p>
                        </div>
                    </div>

                    <form class="space-y-6" @submit.prevent="calculateTotal">
                        <details class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <summary class="cursor-pointer select-none text-sm font-semibold text-slate-800">Artist Details</summary>

                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                <label class="field-label">Artist Name
                                    <input v-model="artist.name" type="text" class="input" />
                                </label>
                                <label class="field-label">Artist Email
                                    <input v-model="artist.email" type="email" class="input" />
                                </label>
                                <label class="field-label">Artist Website
                                    <input v-model="artist.website" type="text" class="input" />
                                </label>
                                <label class="field-label">Artist Mobile
                                    <input v-model="artist.mobile" type="text" class="input" />
                                </label>
                            </div>
                        </details>

                        <section class="space-y-4">
                            <h3 class="section-title">Organizer and Event</h3>

                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="field-label">Organizer Name
                                    <input v-model="form.organizerName" type="text" placeholder="e.g. Sarah Thompson" class="input" />
                                </label>
                                <label class="field-label">Organizer Email
                                    <input v-model="form.organizerEmail" type="email" placeholder="e.g. sarah@example.com" class="input" />
                                </label>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="field-label">Organizer Phone
                                    <input v-model="form.organizerPhone" type="text" placeholder="e.g. 021 555 3921" class="input" />
                                </label>
                                <label class="field-label">Guest Count
                                    <input v-model="form.guestCount" type="number" min="0" class="input" />
                                </label>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="field-label">Event Name
                                    <input v-model="form.eventName" type="text" placeholder="e.g. Franklin Summer Fair" class="input" />
                                </label>
                                <label class="field-label">Event Date
                                    <input v-model="form.eventDate" type="date" class="input" />
                                </label>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="field-label">Start Time
                                    <input v-model="form.startTime" type="time" class="input" />
                                </label>
                                <label class="field-label">End Time
                                    <input v-model="form.endTime" type="time" class="input" />
                                </label>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="field-label">Package
                                    <input v-model="form.packageName" type="text" placeholder="e.g. Party Sparkle Package" class="input" />
                                </label>
                                <label class="field-label">Services Requested (comma separated)
                                    <input v-model="form.servicesRequested" type="text" placeholder="Face Painting, Glitter Tattoos" class="input" />
                                </label>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="field-label">Travel Area
                                    <input v-model="form.travelArea" type="text" placeholder="e.g. Pukekohe / Franklin" class="input" />
                                </label>
                                <label class="field-label">Venue Type
                                    <select v-model="form.venueType" class="input">
                                        <option value="">Select venue</option>
                                        <option value="indoor">Indoor</option>
                                        <option value="outdoor">Outdoor</option>
                                        <option value="mixed">Indoor + Outdoor</option>
                                        <option value="unsure">Not sure yet</option>
                                    </select>
                                </label>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="field-label">How They Heard About Us
                                    <input v-model="form.heardAbout" type="text" placeholder="Google, Facebook, referral..." class="input" />
                                </label>
                                <label class="field-label">Event Address
                                    <input v-model="form.eventAddress" type="text" placeholder="Street / suburb" class="input" />
                                </label>
                            </div>

                            <label class="field-label">Notes
                                <textarea v-model="form.notes" rows="3" class="input resize-y" placeholder="Extra event context or requests"></textarea>
                            </label>

                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-800">
                                <input v-model="form.termsAccepted" type="checkbox" class="h-4 w-4 rounded border-slate-400" />
                                Terms accepted
                            </label>
                        </section>

                        <section class="space-y-4">
                            <h3 class="section-title">Pricing</h3>

                            <label class="field-label">Payment Type
                                <select v-model="form.paymentType" class="input">
                                    <option value="hourly">Organizer-Paid (Hourly)</option>
                                    <option value="perface">Pay Per Face</option>
                                    <option value="package">Package</option>
                                </select>
                            </label>

                            <div v-if="form.paymentType === 'hourly'" class="grid gap-4 md:grid-cols-2">
                                <label class="field-label">Hourly Rate ($/hr)
                                    <input v-model="form.rate" type="number" min="0" class="input" />
                                </label>
                                <label class="field-label">Hours Booked
                                    <input v-model="form.hours" type="number" min="0" class="input" />
                                </label>
                            </div>

                            <div v-else-if="form.paymentType === 'perface'" class="grid gap-4 md:grid-cols-2">
                                <label class="field-label">Price per Face ($)
                                    <input v-model="form.pricePerFace" type="number" min="0" class="input" />
                                </label>
                                <label class="field-label">Expected Faces
                                    <input v-model="form.numFaces" type="number" min="0" class="input" />
                                </label>
                            </div>

                            <div v-else class="space-y-4">
                                <label class="field-label">Package Preset
                                    <select
                                        class="input"
                                        :value="selectedPackage?.name || ''"
                                        @change="applyPackagePreset(($event.target && $event.target.value) ? String($event.target.value) : '')"
                                    >
                                        <option value="">Choose from presets</option>
                                        <option v-for="item in packageOptions" :key="item.name" :value="item.name">
                                            {{ item.name }} ({{ item.hours }} hrs, ${{ item.baseAmount.toFixed(2) }})
                                        </option>
                                    </select>
                                </label>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <label class="field-label">Package Name
                                        <input v-model="form.packageName" type="text" class="input" />
                                    </label>
                                    <label class="field-label">Package Base Amount ($)
                                        <input v-model="form.packageBaseAmount" type="number" min="0" step="0.01" class="input" />
                                    </label>
                                    <label class="field-label">Package Hours
                                        <input v-model="form.packageHours" type="number" min="0" step="0.25" class="input" />
                                    </label>
                                </div>

                                <div v-if="selectedPackage" class="rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-slate-700">
                                    <p class="font-semibold text-sky-800">
                                        {{ selectedPackage.name }} · {{ selectedPackage.hours }} hours · {{ selectedPackage.guestGuide }}
                                    </p>
                                    <p class="mt-1 text-slate-600">{{ selectedPackage.summary }}</p>
                                    <ul class="mt-2 list-disc pl-5 text-xs text-slate-600">
                                        <li v-for="feature in selectedPackage.features" :key="`${selectedPackage.name}-${feature}`">{{ feature }}</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-semibold text-slate-800">Preset Add-ons</p>

                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    <button
                                        v-for="addOn in addOnOptions"
                                        :key="addOn.name"
                                        type="button"
                                        class="addon-option"
                                        :class="{ 'addon-option--active': form.selectedAddOns.includes(addOn.name) }"
                                        @click="togglePresetAddOn(addOn.name)"
                                    >
                                        <span class="addon-option__title">{{ addOn.name }}</span>
                                        <span class="addon-option__amount">${{ addOn.amount.toFixed(2) }}</span>
                                        <span class="addon-option__copy">{{ addOn.summary }}</span>
                                    </button>
                                </div>

                                <div class="mt-4 space-y-2">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-sm font-semibold text-slate-800">Custom Add-ons</p>
                                        <button type="button" class="secondary-btn secondary-btn--small" @click="addCustomAddOnRow">
                                            Add Custom Add-on
                                        </button>
                                    </div>

                                    <p v-if="!form.customAddOns.length" class="text-xs text-slate-500">No custom add-ons added yet.</p>

                                    <div v-for="(addOn, index) in form.customAddOns" :key="`custom-addon-${index}`" class="grid gap-2 md:grid-cols-[1fr_160px_auto]">
                                        <input v-model="addOn.name" type="text" class="input" placeholder="Add-on name" />
                                        <input
                                            v-model.number="addOn.amount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="input"
                                            placeholder="Amount"
                                        />
                                        <button type="button" class="secondary-btn secondary-btn--small" @click="removeCustomAddOnRow(index)">
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <label class="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-slate-800">
                                    <input v-model="form.includeSetup" type="checkbox" class="h-4 w-4 rounded border-slate-400" />
                                    Include Setup / Preparation Time
                                </label>

                                <div v-if="form.includeSetup" class="grid gap-4 md:grid-cols-2">
                                    <label class="field-label">Setup Rate ($/hr)
                                        <input v-model="form.setupRate" type="number" min="0" class="input" />
                                    </label>
                                    <label class="field-label">Setup Hours
                                        <input v-model="form.setupHours" type="number" min="0" class="input" />
                                    </label>
                                </div>
                            </div>
                        </section>

                        <section class="space-y-4">
                            <h3 class="section-title">Travel and Notes</h3>

                            <label class="field-label">Travel Method
                                <select v-model="form.travelType" class="input">
                                    <option value="perkm">Per km (round trip)</option>
                                    <option value="flat">Flat Rate</option>
                                </select>
                            </label>

                            <div v-if="form.travelType === 'perkm'" class="grid gap-4 md:grid-cols-2">
                                <label class="field-label">Distance (km, one way)
                                    <input v-model="form.distance" type="number" min="0" class="input" />
                                </label>
                                <label class="field-label">Rate per km ($)
                                    <input v-model="form.travelRate" type="number" min="0" step="0.01" class="input" />
                                </label>
                            </div>

                            <div v-else class="grid gap-4">
                                <label class="field-label">Flat Travel Cost ($)
                                    <input v-model="form.flatTravel" type="number" min="0" class="input" />
                                </label>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <label class="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-slate-800">
                                    <input v-model="form.includePerformance" type="checkbox" class="h-4 w-4 rounded border-slate-400" />
                                    Include Performance Note
                                </label>

                                <div v-if="form.includePerformance" class="grid gap-4 md:grid-cols-2">
                                    <label class="field-label">Faces Painted (last event)
                                        <input v-model="form.perfFaces" type="number" min="0" class="input" />
                                    </label>
                                    <label class="field-label">Hours Worked (last event)
                                        <input v-model="form.perfHours" type="number" min="0" class="input" />
                                    </label>
                                </div>
                            </div>

                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-800">
                                <input v-model="form.includeGST" type="checkbox" class="h-4 w-4 rounded border-slate-400" />
                                Include GST (15%)
                            </label>
                        </section>

                        <div class="flex flex-wrap gap-3 pt-2">
                            <button type="submit" class="primary-btn">Calculate Total</button>
                            <button type="button" class="secondary-btn" @click="resetForm">Reset Form</button>
                        </div>
                    </form>
                </section>

                <section ref="quoteOutputPanelRef" class="panel h-fit lg:sticky lg:top-6">
                    <h2 class="mb-1 text-xl font-semibold text-sky-700">Quote Output</h2>
                    <p class="mb-5 text-sm text-slate-500">Preview totals and copy formatted quote text.</p>

                    <div v-if="showResult" class="space-y-4">
                        <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4">
                            <p class="text-xs font-semibold tracking-[0.14em] text-sky-700 uppercase">Estimated Total</p>
                            <p class="mt-1 text-3xl font-bold text-slate-900">${{ result.total.toFixed(2) }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ result.eventSummary }}</p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-700">
                            <p><strong>Organizer:</strong> {{ form.organizerName || '—' }} ({{ form.organizerEmail || '—' }})</p>
                            <p class="mt-1"><strong>Phone:</strong> {{ form.organizerPhone || '—' }}</p>
                            <p class="mt-1"><strong>Guest Count:</strong> {{ form.guestCount || '—' }}</p>
                            <p class="mt-1"><strong>Package:</strong> {{ form.packageName || '—' }}</p>
                            <p class="mt-1"><strong>Services:</strong> {{ form.servicesRequested || '—' }}</p>
                            <p class="mt-1"><strong>Add-ons:</strong> {{ selectedAddOnSummary || '—' }}</p>
                            <p class="mt-1"><strong>Travel Area:</strong> {{ form.travelArea || '—' }}</p>
                            <p class="mt-1"><strong>Venue Type:</strong> {{ form.venueType || '—' }}</p>
                            <p class="mt-1"><strong>Heard About Us:</strong> {{ form.heardAbout || '—' }}</p>
                            <p class="mt-1"><strong>Address:</strong> {{ form.eventAddress || '—' }}</p>
                            <p class="mt-1"><strong>Notes:</strong> {{ form.notes || '—' }}</p>
                            <p class="mt-1"><strong>Terms Accepted:</strong> {{ form.termsAccepted ? 'Yes' : 'No' }}</p>
                            <p><strong>Base:</strong> {{ result.baseLine }}</p>
                            <p v-if="result.addOnsLine" class="mt-1"><strong>Add-ons:</strong> {{ result.addOnsLine }}</p>
                            <p v-if="result.setupLine" class="mt-1"><strong>Setup:</strong> {{ result.setupLine }}</p>
                            <p class="mt-1"><strong>Travel:</strong> {{ result.travelLine }}</p>
                            <p class="mt-1"><strong>Subtotal:</strong> ${{ result.subtotal.toFixed(2) }}</p>
                            <p v-if="form.includeGST" class="mt-1"><strong>GST (15%):</strong> ${{ result.gstAmount.toFixed(2) }}</p>
                            <p v-else class="mt-1"><strong>GST:</strong> Not included</p>
                            <p class="mt-2"><strong>Start Time:</strong> {{ result.startDisplay }}</p>
                            <p class="mt-1"><strong>End Time:</strong> {{ result.endDisplay }}</p>
                            <p class="mt-1"><strong>Total Hours:</strong> {{ result.hoursDisplay }}</p>
                        </div>

                        <div v-if="form.includePerformance" class="rounded-2xl border border-teal-200 bg-teal-50 p-4 text-xs text-slate-700">
                            <p class="mb-1 font-semibold text-teal-800">Performance Note</p>
                            <p>{{ performanceNoteSentence }}</p>
                        </div>

                        <div>
                            <label for="quote-preview" class="mb-1 block text-sm font-semibold text-slate-800">Quote Text Preview</label>
                            <textarea id="quote-preview" :value="quoteText" class="input h-40 resize-y text-xs" readonly></textarea>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="button" class="primary-btn" @click="copyQuote">Copy Quote</button>
                            <button type="button" class="secondary-btn" @click="generateEmail">Generate Email</button>
                            <button type="button" class="secondary-btn" :disabled="savingQuote" @click="saveQuote">
                                {{ savingQuote ? 'Saving...' : 'Save Quote' }}
                            </button>
                            <Link :href="backToQuoteHref" class="secondary-btn">
                                {{ backToQuoteLabel }}
                            </Link>
                        </div>

                        <p v-if="!canSaveQuote" class="text-xs font-semibold text-amber-700">
                            Add organizer name and email to save this quote.
                        </p>
                        <p v-if="notification" class="text-sm font-semibold text-emerald-700">{{ notification }}</p>
                        <p v-if="saveQuoteSuccess" class="text-sm font-semibold text-emerald-700">{{ saveQuoteSuccess }}</p>
                        <p v-if="saveQuoteError" class="text-sm font-semibold text-rose-700">{{ saveQuoteError }}</p>
                    </div>

                    <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500">
                        Fill out the form and click <strong>Calculate Total</strong> to generate a quote.
                    </div>
                </section>
            </div>
        </section>
    </main>
</template>

<style scoped>
.panel {
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    padding: 1.5rem;
    box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08);
}

.section-title {
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #475569;
}

.field-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: #334155;
}

.input {
    margin-top: 0.35rem;
    width: 100%;
    border-radius: 0.75rem;
    border: 1px solid #cbd5e1;
    padding: 0.65rem 0.8rem;
    background: #fff;
    color: #0f172a;
}

.input:focus {
    outline: none;
    border-color: #38bdf8;
    box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.18);
}

.primary-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    border: 1px solid rgba(224, 247, 255, 0.95);
    background: linear-gradient(135deg, #ecfeff, #bae6fd);
    color: #0f172a;
    font-weight: 700;
    padding: 0.65rem 1rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    box-shadow: 0 8px 16px rgba(2, 6, 23, 0.12);
}

.primary-btn:hover {
    transform: translateY(-1px);
}

.addon-option {
    display: grid;
    gap: 0.25rem;
    border-radius: 0.9rem;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    padding: 0.7rem 0.8rem;
    text-align: left;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.addon-option:hover {
    border-color: #7dd3fc;
}

.addon-option--active {
    border-color: #0ea5e9;
    background: #f0f9ff;
    box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.15);
}

.addon-option__title {
    font-size: 0.84rem;
    font-weight: 700;
    color: #0f172a;
}

.addon-option__amount {
    font-size: 0.78rem;
    font-weight: 700;
    color: #0369a1;
}

.addon-option__copy {
    font-size: 0.74rem;
    line-height: 1.3;
    color: #475569;
}

.secondary-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #334155;
    font-weight: 700;
    padding: 0.65rem 1rem;
    transition: background 0.2s ease, border-color 0.2s ease;
}

.secondary-btn:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}

.secondary-btn--small {
    padding: 0.45rem 0.7rem;
    font-size: 0.74rem;
}
</style>

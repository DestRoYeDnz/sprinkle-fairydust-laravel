<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import AdminMenu from '@/components/admin/AdminMenu.vue';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

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
            eventName: '',
            eventDate: '',
            startTime: '',
            endTime: '',
            paymentType: 'hourly',
            rate: 120,
            hours: 8,
            pricePerFace: 10,
            numFaces: 30,
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

const result = ref({
    baseLine: '',
    setupLine: '',
    travelLine: '',
    subtotal: 0,
    gstAmount: 0,
    total: 0,
    startDisplay: '—',
    endDisplay: '—',
    hoursDisplay: 0,
    eventSummary: '',
});

function toNumber(value) {
    const number = Number(value);
    return Number.isFinite(number) ? number : 0;
}

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
            eventName: String(payloadForm.eventName ?? defaults.form.eventName),
            eventDate: String(payloadForm.eventDate ?? defaults.form.eventDate),
            startTime: String(payloadForm.startTime ?? defaults.form.startTime),
            endTime: String(payloadForm.endTime ?? defaults.form.endTime),
            paymentType: ['hourly', 'perface'].includes(String(payloadForm.paymentType))
                ? String(payloadForm.paymentType)
                : defaults.form.paymentType,
            rate: toNumber(payloadForm.rate ?? defaults.form.rate),
            hours: toNumber(payloadForm.hours ?? defaults.form.hours),
            pricePerFace: toNumber(payloadForm.pricePerFace ?? defaults.form.pricePerFace),
            numFaces: toNumber(payloadForm.numFaces ?? defaults.form.numFaces),
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

    const name = params.get('name') ?? '';
    const email = params.get('email') ?? '';
    const date = params.get('date') ?? '';
    const event = params.get('event') ?? '';
    const type = params.get('type') ?? '';

    const start = params.get('start') ?? '';
    const end = params.get('end') ?? '';
    const hours = params.get('hours') ?? '';

    if (name) {
        form.value.organizerName = name;
    }

    if (email) {
        form.value.organizerEmail = email;
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

    if (event && !form.value.eventName) {
        form.value.eventName = event;
    }

    if (type) {
        const paymentMatch = ['hourly', 'perface'].includes(type.toLowerCase())
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
    const paymentMode = form.value.paymentType;

    let baseTotal = 0;
    let baseLine = '';

    if (paymentMode === 'hourly') {
        const rate = toNumber(form.value.rate);
        const hours = toNumber(form.value.hours);
        baseTotal = rate * hours;
        baseLine = `Hourly: ${hours} hours × $${rate.toFixed(2)} = $${baseTotal.toFixed(2)}`;
    } else {
        const pricePerFace = toNumber(form.value.pricePerFace);
        const numFaces = toNumber(form.value.numFaces);
        baseTotal = pricePerFace * numFaces;
        baseLine = `Per Face: ${numFaces} faces × $${pricePerFace.toFixed(2)} = $${baseTotal.toFixed(2)}`;
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

    const subtotal = baseTotal + setupTotal + travelTotal;
    const gstAmount = form.value.includeGST ? subtotal * 0.15 : 0;
    const total = subtotal + gstAmount;

    const hourlyHours = toNumber(form.value.hours);
    const startDisplay = form.value.startTime || '—';
    const endDisplay = form.value.endTime || '—';

    result.value = {
        baseLine,
        setupLine,
        travelLine,
        subtotal,
        gstAmount,
        total,
        startDisplay,
        endDisplay,
        hoursDisplay: hourlyHours,
        eventSummary:
            paymentMode === 'hourly'
                ? `Hours: ${hourlyHours} · Rate: $${toNumber(form.value.rate).toFixed(2)}/hr`
                : `Per Face: ${toNumber(form.value.numFaces)} faces`,
    };

    showResult.value = true;
}

const quoteText = computed(() => {
    if (!showResult.value) {
        return '';
    }

    const lines = [];

    if (form.value.organizerName || form.value.organizerEmail) {
        lines.push(`Organizer: ${form.value.organizerName} (${form.value.organizerEmail})`.trim());
    }

    if (form.value.eventName || form.value.eventDate) {
        lines.push(`Event: ${form.value.eventName} on ${form.value.eventDate || 'date TBC'}`.trim());
    }

    lines.push(`Start Time: ${result.value.startDisplay}`);
    lines.push(`End Time: ${result.value.endDisplay}`);
    lines.push(`Total Hours: ${result.value.hoursDisplay}`);
    lines.push('');
    lines.push(result.value.baseLine);

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
                        </section>

                        <section class="space-y-4">
                            <h3 class="section-title">Pricing</h3>

                            <label class="field-label">Payment Type
                                <select v-model="form.paymentType" class="input">
                                    <option value="hourly">Organizer-Paid (Hourly)</option>
                                    <option value="perface">Pay Per Face</option>
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

                            <div v-else class="grid gap-4 md:grid-cols-2">
                                <label class="field-label">Price per Face ($)
                                    <input v-model="form.pricePerFace" type="number" min="0" class="input" />
                                </label>
                                <label class="field-label">Expected Faces
                                    <input v-model="form.numFaces" type="number" min="0" class="input" />
                                </label>
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

                <section class="panel h-fit lg:sticky lg:top-6">
                    <h2 class="mb-1 text-xl font-semibold text-sky-700">Quote Output</h2>
                    <p class="mb-5 text-sm text-slate-500">Preview totals and copy formatted quote text.</p>

                    <div v-if="showResult" class="space-y-4">
                        <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4">
                            <p class="text-xs font-semibold tracking-[0.14em] text-sky-700 uppercase">Estimated Total</p>
                            <p class="mt-1 text-3xl font-bold text-slate-900">${{ result.total.toFixed(2) }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ result.eventSummary }}</p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-700">
                            <p><strong>Base:</strong> {{ result.baseLine }}</p>
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
                        </div>

                        <p v-if="notification" class="text-sm font-semibold text-emerald-700">{{ notification }}</p>
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
</style>

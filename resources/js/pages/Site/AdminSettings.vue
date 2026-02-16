<script setup>
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import AdminMenu from '@/components/admin/AdminMenu.vue';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const loading = ref(false);
const saving = ref(false);
const loadError = ref('');
const saveError = ref('');
const saveSuccess = ref('');

const form = ref(defaultSettings());

function defaultSettings() {
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

function toNumber(value, fallback = 0) {
    const number = Number(value);

    return Number.isFinite(number) ? number : fallback;
}

function normalizeSettings(source) {
    const defaults = defaultSettings();
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
            rate: toNumber(payloadForm.rate, defaults.form.rate),
            hours: toNumber(payloadForm.hours, defaults.form.hours),
            pricePerFace: toNumber(payloadForm.pricePerFace, defaults.form.pricePerFace),
            numFaces: toNumber(payloadForm.numFaces, defaults.form.numFaces),
            includeSetup: Boolean(payloadForm.includeSetup ?? defaults.form.includeSetup),
            setupRate: toNumber(payloadForm.setupRate, defaults.form.setupRate),
            setupHours: toNumber(payloadForm.setupHours, defaults.form.setupHours),
            travelType: ['perkm', 'flat'].includes(String(payloadForm.travelType))
                ? String(payloadForm.travelType)
                : defaults.form.travelType,
            distance: toNumber(payloadForm.distance, defaults.form.distance),
            travelRate: toNumber(payloadForm.travelRate, defaults.form.travelRate),
            flatTravel: toNumber(payloadForm.flatTravel, defaults.form.flatTravel),
            includePerformance: Boolean(payloadForm.includePerformance ?? defaults.form.includePerformance),
            perfFaces: toNumber(payloadForm.perfFaces, defaults.form.perfFaces),
            perfHours: toNumber(payloadForm.perfHours, defaults.form.perfHours),
            includeGST: Boolean(payloadForm.includeGST ?? defaults.form.includeGST),
        },
    };
}

async function loadSettings() {
    loading.value = true;
    loadError.value = '';

    try {
        const response = await fetch('/admin/settings/calculator', {
            headers: {
                Accept: 'application/json',
            },
        });

        const data = await response.json();

        if (!response.ok) {
            loadError.value = data.message || data.error || 'Failed to load calculator settings';
            return;
        }

        form.value = normalizeSettings(data);
    } catch {
        loadError.value = 'Failed to load calculator settings';
    } finally {
        loading.value = false;
    }
}

async function saveSettings() {
    saving.value = true;
    saveError.value = '';
    saveSuccess.value = '';

    try {
        const response = await fetch('/admin/settings/calculator', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(form.value),
        });

        const data = await response.json();

        if (!response.ok) {
            saveError.value = data.message || data.error || 'Failed to save calculator settings';
            return;
        }

        form.value = normalizeSettings(data.settings ?? form.value);
        saveSuccess.value = 'Calculator settings updated.';
    } catch {
        saveError.value = 'Failed to save calculator settings';
    } finally {
        saving.value = false;
    }
}

function resetToDefaults() {
    form.value = defaultSettings();
    saveError.value = '';
    saveSuccess.value = '';
}

onMounted(() => {
    loadSettings();
});
</script>

<template>
    <Head title="Calculator Settings | Admin" />

    <main class="mx-auto mt-10 max-w-6xl px-4 pb-10 font-quicksand">
        <section class="rounded-3xl border border-slate-200/90 bg-slate-50/90 p-5 shadow-2xl backdrop-blur-md md:p-6">
            <h1 class="mb-2 text-center text-5xl text-slate-900 drop-shadow-sm font-dancing">Calculator Settings</h1>
            <p class="mb-8 text-center text-slate-600">Set global prefilled values used by the admin calculator.</p>

            <AdminMenu />

            <p v-if="loadError" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-semibold text-rose-700">
                {{ loadError }}
            </p>

            <form class="space-y-6" @submit.prevent="saveSettings">
                <section class="panel">
                    <h2 class="section-title">Performance Note</h2>
                    <label class="field-label mt-3">
                        Prefilled performance note
                        <textarea
                            v-model="form.performance_note_sentence"
                            class="input mt-2 h-32 resize-y"
                            required
                        ></textarea>
                    </label>
                </section>

                <section class="panel">
                    <h2 class="section-title">Artist Defaults</h2>

                    <div class="mt-3 grid gap-4 md:grid-cols-2">
                        <label class="field-label">
                            Name
                            <input v-model="form.artist.name" type="text" class="input" required />
                        </label>
                        <label class="field-label">
                            Email
                            <input v-model="form.artist.email" type="text" class="input" />
                        </label>
                        <label class="field-label">
                            Website
                            <input v-model="form.artist.website" type="text" class="input" />
                        </label>
                        <label class="field-label">
                            Mobile
                            <input v-model="form.artist.mobile" type="text" class="input" />
                        </label>
                    </div>
                </section>

                <section class="panel">
                    <h2 class="section-title">Organizer/Event Defaults</h2>

                    <div class="mt-3 grid gap-4 md:grid-cols-2">
                        <label class="field-label">
                            Organizer Name
                            <input v-model="form.form.organizerName" type="text" class="input" />
                        </label>
                        <label class="field-label">
                            Organizer Email
                            <input v-model="form.form.organizerEmail" type="text" class="input" />
                        </label>
                        <label class="field-label">
                            Event Name
                            <input v-model="form.form.eventName" type="text" class="input" />
                        </label>
                        <label class="field-label">
                            Event Date
                            <input v-model="form.form.eventDate" type="date" class="input" />
                        </label>
                        <label class="field-label">
                            Start Time
                            <input v-model="form.form.startTime" type="time" class="input" />
                        </label>
                        <label class="field-label">
                            End Time
                            <input v-model="form.form.endTime" type="time" class="input" />
                        </label>
                    </div>
                </section>

                <section class="panel">
                    <h2 class="section-title">Pricing Defaults</h2>

                    <div class="mt-3 grid gap-4 md:grid-cols-2">
                        <label class="field-label">
                            Payment Type
                            <select v-model="form.form.paymentType" class="input">
                                <option value="hourly">Organizer-Paid (Hourly)</option>
                                <option value="perface">Pay Per Face</option>
                            </select>
                        </label>
                        <label class="field-label">
                            Hourly Rate ($/hr)
                            <input v-model.number="form.form.rate" type="number" min="0" step="0.01" class="input" />
                        </label>
                        <label class="field-label">
                            Hours Booked
                            <input v-model.number="form.form.hours" type="number" min="0" step="0.25" class="input" />
                        </label>
                        <label class="field-label">
                            Price Per Face ($)
                            <input v-model.number="form.form.pricePerFace" type="number" min="0" step="0.01" class="input" />
                        </label>
                        <label class="field-label">
                            Expected Faces
                            <input v-model.number="form.form.numFaces" type="number" min="0" step="1" class="input" />
                        </label>
                    </div>

                    <label class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <input v-model="form.form.includeGST" type="checkbox" class="h-4 w-4 rounded border-slate-400" />
                        Include GST (15%)
                    </label>
                </section>

                <section class="panel">
                    <h2 class="section-title">Setup Defaults</h2>

                    <label class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <input v-model="form.form.includeSetup" type="checkbox" class="h-4 w-4 rounded border-slate-400" />
                        Include setup / preparation by default
                    </label>

                    <div class="mt-3 grid gap-4 md:grid-cols-2">
                        <label class="field-label">
                            Setup Rate ($/hr)
                            <input v-model.number="form.form.setupRate" type="number" min="0" step="0.01" class="input" />
                        </label>
                        <label class="field-label">
                            Setup Hours
                            <input v-model.number="form.form.setupHours" type="number" min="0" step="0.25" class="input" />
                        </label>
                    </div>
                </section>

                <section class="panel">
                    <h2 class="section-title">Travel Defaults</h2>

                    <div class="mt-3 grid gap-4 md:grid-cols-2">
                        <label class="field-label">
                            Travel Method
                            <select v-model="form.form.travelType" class="input">
                                <option value="perkm">Per km (round trip)</option>
                                <option value="flat">Flat Rate</option>
                            </select>
                        </label>
                        <label class="field-label">
                            Distance (km, one way)
                            <input v-model.number="form.form.distance" type="number" min="0" step="0.1" class="input" />
                        </label>
                        <label class="field-label">
                            Rate per km ($)
                            <input v-model.number="form.form.travelRate" type="number" min="0" step="0.01" class="input" />
                        </label>
                        <label class="field-label">
                            Flat Travel Cost ($)
                            <input v-model.number="form.form.flatTravel" type="number" min="0" step="0.01" class="input" />
                        </label>
                    </div>
                </section>

                <section class="panel">
                    <h2 class="section-title">Performance Defaults</h2>

                    <label class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <input v-model="form.form.includePerformance" type="checkbox" class="h-4 w-4 rounded border-slate-400" />
                        Include performance note by default
                    </label>

                    <div class="mt-3 grid gap-4 md:grid-cols-2">
                        <label class="field-label">
                            Faces Painted
                            <input v-model.number="form.form.perfFaces" type="number" min="0" step="1" class="input" />
                        </label>
                        <label class="field-label">
                            Hours Worked
                            <input v-model.number="form.form.perfHours" type="number" min="0" step="0.25" class="input" />
                        </label>
                    </div>
                </section>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="primary-btn" :disabled="saving || loading">
                        {{ saving ? 'Saving...' : 'Save Calculator Settings' }}
                    </button>
                    <button type="button" class="secondary-btn" :disabled="saving" @click="resetToDefaults">
                        Reset to Defaults
                    </button>
                </div>

                <p v-if="saveSuccess" class="text-sm font-semibold text-emerald-700">{{ saveSuccess }}</p>
                <p v-if="saveError" class="text-sm font-semibold text-rose-700">{{ saveError }}</p>
            </form>
        </section>
    </main>
</template>

<style scoped>
.panel {
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    padding: 1.25rem;
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

.primary-btn:disabled,
.secondary-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
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


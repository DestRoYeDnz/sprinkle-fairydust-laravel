<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { getOrCreateAnonymousId, trackCustomTrackingEvent } from '@/lib/pageTracking';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const packageOptions = [
    {
        name: 'Mini Party Sparkle',
        hours: '2 hours',
        guestGuide: 'Up to 20 kids',
        summary: 'Great for smaller birthday groups and short sessions.',
    },
    {
        name: 'Classic Birthday Magic',
        hours: '3 hours',
        guestGuide: 'Around 30 kids',
        summary: 'Best all-round package for party flow and full variety.',
    },
    {
        name: 'Festival Crowd Package',
        hours: '4+ hours',
        guestGuide: 'Large public events',
        summary: 'Built for schools, markets, fairs, and high-volume lines.',
    },
];

const serviceOptions = [
    'Face Painting',
    'Glitter Tattoos',
    'Festival Bling',
    'Themed Character Looks',
    'Waterproof Festival Designs',
];

const referralOptions = [
    'Google search',
    'Facebook',
    'Instagram',
    'Referral from a friend',
    'Saw us at an event',
    'Returning client',
    'Other',
];

const venueTypeOptions = [
    { value: '', label: 'Select venue type' },
    { value: 'indoor', label: 'Indoor' },
    { value: 'outdoor', label: 'Outdoor' },
    { value: 'mixed', label: 'Indoor + Outdoor' },
    { value: 'unsure', label: 'Not sure yet' },
];

const allTimes = [
    '07:00',
    '07:30',
    '08:00',
    '08:30',
    '09:00',
    '09:30',
    '10:00',
    '10:30',
    '11:00',
    '11:30',
    '12:00',
    '12:30',
    '13:00',
    '13:30',
    '14:00',
    '14:30',
    '15:00',
    '15:30',
    '16:00',
    '16:30',
    '17:00',
    '17:30',
    '18:00',
    '18:30',
    '19:00',
    '19:30',
    '20:00',
    '20:30',
    '21:00',
];

const formSectionRef = ref(null);
const loading = ref(false);
const message = ref('');
const success = ref(false);
const quoteStartTracked = ref(false);

const form = ref({
    name: '',
    email: '',
    phone: '',
    event: '',
    date: '',
    start_time: '',
    end_time: '',
    guest_count: '',
    package_name: '',
    services_requested: [],
    travel_area: '',
    venue_type: '',
    heard_about: '',
    address: '',
    details: '',
    terms_accepted: false,
});

const endTimeOptions = computed(() => {
    if (!form.value.start_time) {
        return [];
    }

    const startIndex = allTimes.indexOf(form.value.start_time);
    const minEndIndex = startIndex + 2;

    return allTimes.slice(minEndIndex);
});

function trackQuoteStartIfNeeded() {
    if (quoteStartTracked.value) {
        return;
    }

    quoteStartTracked.value = true;
    trackCustomTrackingEvent('quote_funnel_start');
}

function scrollToForm() {
    trackQuoteStartIfNeeded();
    formSectionRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function selectPackage(packageName) {
    form.value.package_name = packageName;
    trackQuoteStartIfNeeded();
    scrollToForm();
}

watch(
    () => form.value.package_name,
    (value, previous) => {
        if (value && value !== previous) {
            trackQuoteStartIfNeeded();
            trackCustomTrackingEvent('quote_funnel_package_selected');
        }
    },
);

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const packageName = params.get('package_name');

    if (packageName) {
        form.value.package_name = packageName;
    }
});

function toggleService(service) {
    trackQuoteStartIfNeeded();

    if (form.value.services_requested.includes(service)) {
        form.value.services_requested = form.value.services_requested.filter((item) => item !== service);
        return;
    }

    form.value.services_requested = [...form.value.services_requested, service];
}

function resetForm() {
    form.value = {
        name: '',
        email: '',
        phone: '',
        event: '',
        date: '',
        start_time: '',
        end_time: '',
        guest_count: '',
        package_name: '',
        services_requested: [],
        travel_area: '',
        venue_type: '',
        heard_about: '',
        address: '',
        details: '',
        terms_accepted: false,
    };
}

async function submitForm() {
    loading.value = true;
    message.value = '';
    success.value = false;

    trackQuoteStartIfNeeded();

    if (!form.value.terms_accepted) {
        message.value = 'Please accept the terms before submitting your quote request.';
        loading.value = false;
        return;
    }

    if (form.value.start_time && form.value.end_time) {
        const start = new Date(`1970-01-01T${form.value.start_time}:00`);
        const end = new Date(`1970-01-01T${form.value.end_time}:00`);

        const diffHours = (end - start) / (1000 * 60 * 60);
        if (diffHours < 1) {
            message.value = 'End time must be at least 1 hour after start time.';
            loading.value = false;
            return;
        }
    }

    try {
        const payload = {
            ...form.value,
            guest_count: form.value.guest_count ? Number(form.value.guest_count) : null,
            anonymous_id: getOrCreateAnonymousId(),
        };

        const response = await fetch('/api/quotes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (response.ok) {
            success.value = true;
            message.value = data.message || 'Thank you! Your quote has been submitted.';
            trackCustomTrackingEvent('quote_funnel_submitted');
            resetForm();
        } else {
            message.value = data.error || data.message || 'Something went wrong. Please try again.';
        }
    } catch (error) {
        console.error(error);
        message.value = 'Network error. Please try again later.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <Head title="Get a Face Painting Quote | Auckland Birthday Parties">
        <meta
            head-key="description"
            name="description"
            content="Request a quote for kids face painting for birthday parties in Pukekohe, Papakura, Drury and Auckland."
        />
    </Head>

    <a href="https://www.sprinklefairydust.co.nz/">
        <img
            src="/images/logo.png"
            alt="Sprinkle Fairydust Logo"
            class="floating-logo fade-in-logo"
        />
    </a>

    <main class="hero page-content font-quicksand">
        <div class="mt-16 mb-6 text-center text-shadow-strong">
            <h1 class="text-5xl drop-shadow-xl md:text-6xl font-dancing">Request a Quote</h1>
        </div>

        <p class="tagline mb-8 text-xl text-shadow-strong drop-shadow-md md:text-2xl">
            Tell us your event details and we will send a tailored quote quickly.
        </p>

        <section class="package-grid fade-in">
            <article
                v-for="item in packageOptions"
                :key="item.name"
                class="package-card"
            >
                <h2 class="package-title">{{ item.name }}</h2>
                <p class="package-meta">{{ item.hours }} · {{ item.guestGuide }}</p>
                <p class="package-copy">{{ item.summary }}</p>
                <button type="button" class="mini-cta" @click="selectPackage(item.name)">Choose Package</button>
            </article>
        </section>

        <section
            ref="formSectionRef"
            class="overlay-box mx-auto mt-8 max-w-3xl rounded-2xl p-8 text-left backdrop-blur-md fade-in"
        >
            <form class="quote-form space-y-5" @submit.prevent="submitForm">
                <div class="grid gap-4 md:grid-cols-2">
                    <label>
                        <span class="form-label">Your Name</span>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="form-input"
                            @focus="trackQuoteStartIfNeeded"
                        />
                    </label>

                    <label>
                        <span class="form-label">Email</span>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            class="form-input"
                            @focus="trackQuoteStartIfNeeded"
                        />
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label>
                        <span class="form-label">Phone</span>
                        <input
                            v-model="form.phone"
                            type="text"
                            class="form-input"
                            placeholder="021 555 3921"
                            @focus="trackQuoteStartIfNeeded"
                        />
                    </label>

                    <label>
                        <span class="form-label">Guest Count</span>
                        <input
                            v-model="form.guest_count"
                            type="number"
                            min="1"
                            class="form-input"
                            @focus="trackQuoteStartIfNeeded"
                        />
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label>
                        <span class="form-label">Event Type</span>
                        <input
                            v-model="form.event"
                            type="text"
                            class="form-input"
                            placeholder="Birthday, school fair, market"
                            @focus="trackQuoteStartIfNeeded"
                        />
                    </label>

                    <label>
                        <span class="form-label">Event Date</span>
                        <input
                            v-model="form.date"
                            type="date"
                            class="form-input"
                            @focus="trackQuoteStartIfNeeded"
                        />
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label>
                        <span class="form-label">Start Time</span>
                        <select
                            v-model="form.start_time"
                            class="form-input"
                            @focus="trackQuoteStartIfNeeded"
                        >
                            <option disabled value="">Select start time</option>
                            <option v-for="time in allTimes" :key="time" :value="time">{{ time }}</option>
                        </select>
                    </label>

                    <label>
                        <span class="form-label">End Time</span>
                        <select
                            v-model="form.end_time"
                            class="form-input"
                            :disabled="!form.start_time"
                            @focus="trackQuoteStartIfNeeded"
                        >
                            <option disabled value="">Select end time</option>
                            <option v-for="time in endTimeOptions" :key="time" :value="time">
                                {{ time }}
                            </option>
                        </select>
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label>
                        <span class="form-label">Package</span>
                        <select
                            v-model="form.package_name"
                            class="form-input"
                            @focus="trackQuoteStartIfNeeded"
                        >
                            <option disabled value="">Select package</option>
                            <option v-for="item in packageOptions" :key="item.name" :value="item.name">
                                {{ item.name }}
                            </option>
                        </select>
                    </label>

                    <label>
                        <span class="form-label">Travel Area / Suburb</span>
                        <input
                            v-model="form.travel_area"
                            type="text"
                            class="form-input"
                            placeholder="Pukekohe, Papakura, etc"
                            @focus="trackQuoteStartIfNeeded"
                        />
                    </label>
                </div>

                <div>
                    <span class="form-label">Services Requested</span>
                    <div class="service-grid">
                        <button
                            v-for="service in serviceOptions"
                            :key="service"
                            type="button"
                            class="service-pill"
                            :class="{ 'service-pill--active': form.services_requested.includes(service) }"
                            @click="toggleService(service)"
                        >
                            {{ service }}
                        </button>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <label>
                        <span class="form-label">Venue Type</span>
                        <select
                            v-model="form.venue_type"
                            class="form-input"
                            @focus="trackQuoteStartIfNeeded"
                        >
                            <option
                                v-for="item in venueTypeOptions"
                                :key="item.value || 'placeholder'"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </option>
                        </select>
                    </label>

                    <label>
                        <span class="form-label">How did you hear about us?</span>
                        <select
                            v-model="form.heard_about"
                            class="form-input"
                            @focus="trackQuoteStartIfNeeded"
                        >
                            <option disabled value="">Select one</option>
                            <option v-for="item in referralOptions" :key="item" :value="item">{{ item }}</option>
                        </select>
                    </label>
                </div>

                <label>
                    <span class="form-label">Event Address</span>
                    <input
                        v-model="form.address"
                        type="text"
                        class="form-input"
                        @focus="trackQuoteStartIfNeeded"
                    />
                </label>

                <label>
                    <span class="form-label">Additional Details</span>
                    <textarea
                        v-model="form.details"
                        rows="4"
                        class="form-input resize-none"
                        placeholder="Theme, age range, setup details, parking notes"
                        @focus="trackQuoteStartIfNeeded"
                    ></textarea>
                </label>

                <div class="terms-row">
                    <input id="termsAccepted" v-model="form.terms_accepted" type="checkbox" class="terms-check" />
                    <label for="termsAccepted">
                        I have read and agree to the
                        <Link href="/terms-and-conditions" class="terms-link" target="_blank" @click.stop>
                            Terms and Conditions
                        </Link>
                        and I agree to be contacted about this booking and booking policy.
                    </label>
                </div>

                <p class="policy-note">
                    Booking policy: your date is secured once your quote is confirmed and the 30% booking fee is paid.
                    Final timings and service details can still be refined before the event.
                </p>

                <div class="text-center">
                    <button type="submit" class="cta" :disabled="loading">
                        {{ loading ? 'Sending...' : 'Submit Quote Request' }}
                    </button>

                    <p
                        v-if="message"
                        class="mt-4 font-semibold"
                        :class="success ? 'text-emerald-200' : 'text-rose-200'"
                    >
                        {{ message }}
                    </p>
                </div>
            </form>
        </section>

        <button class="sticky-quote-btn" type="button" @click="scrollToForm">
            Start Quote
        </button>
    </main>
</template>

<style scoped>
.hero {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    flex-grow: 1;
    position: relative;
    z-index: 10;
    padding: 0 1.5rem;
}

.package-grid {
    width: 100%;
    max-width: 1100px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    padding: 0 0.25rem;
}

.package-card {
    background: rgba(4, 15, 30, 0.55);
    border: 1px solid rgba(186, 230, 253, 0.35);
    border-radius: 1rem;
    padding: 1rem;
    text-align: left;
    color: #ecfeff;
}

.package-title {
    margin: 0;
    font-size: 1.2rem;
    color: #bae6fd;
}

.package-meta {
    margin-top: 0.35rem;
    font-size: 0.9rem;
    color: #dbeafe;
}

.package-copy {
    margin-top: 0.6rem;
    font-size: 0.95rem;
    line-height: 1.4;
    color: rgba(236, 254, 255, 0.9);
}

.mini-cta {
    margin-top: 0.8rem;
    border: 1px solid rgba(224, 247, 255, 0.95);
    background: linear-gradient(135deg, #ecfeff, #bae6fd);
    color: #0f172a;
    font-weight: 700;
    border-radius: 999px;
    padding: 0.45rem 0.9rem;
    cursor: pointer;
}

.overlay-box {
    background: rgba(0, 0, 0, 0.55);
    border: 1px solid rgba(255, 255, 255, 0.25);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
    border-radius: 1.5rem;
    color: #fff;
    backdrop-filter: blur(12px);
}

.form-label {
    display: block;
    font-weight: 600;
    color: #ffd6f9;
    margin-bottom: 0.25rem;
}

.form-input {
    width: 100%;
    padding: 0.75rem;
    border-radius: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.4);
    font-size: 1rem;
    background: rgba(255, 255, 255, 0.9);
    color: #333;
    transition: all 0.3s ease;
}

.form-input:focus {
    border-color: #b3e6ff;
    box-shadow: 0 0 10px rgba(179, 230, 255, 0.6);
    outline: none;
}

.service-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.service-pill {
    border: 1px solid rgba(191, 219, 254, 0.45);
    border-radius: 999px;
    background: rgba(14, 165, 233, 0.18);
    color: #e0f2fe;
    padding: 0.4rem 0.8rem;
    font-size: 0.85rem;
    cursor: pointer;
}

.service-pill--active {
    background: rgba(20, 184, 166, 0.35);
    border-color: rgba(153, 246, 228, 0.9);
    color: #ecfeff;
}

.terms-row {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.92);
}

.terms-check {
    margin-top: 0.2rem;
}

.terms-link {
    color: #93c5fd;
    font-weight: 700;
    margin: 0 0.25rem;
    text-decoration: underline;
    text-underline-offset: 2px;
}

.policy-note {
    margin: -0.2rem 0 0;
    border: 1px dashed rgba(191, 219, 254, 0.7);
    border-radius: 0.8rem;
    padding: 0.65rem 0.75rem;
    font-size: 0.82rem;
    line-height: 1.45;
    color: #dbeafe;
    background: rgba(8, 47, 73, 0.28);
}

.cta {
    background: linear-gradient(to right, #b3e6ff, #c7f9cc);
    color: #222;
    font-weight: 700;
    border: none;
    border-radius: 9999px;
    padding: 0.75rem 2rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.cta:hover {
    transform: scale(1.05);
    box-shadow: 0 0 12px rgba(255, 255, 255, 0.6);
}

.cta:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.tagline {
    color: #fff;
}

.sticky-quote-btn {
    position: fixed;
    right: 1rem;
    bottom: 1rem;
    z-index: 70;
    border: 1px solid rgba(224, 247, 255, 0.95);
    background: linear-gradient(135deg, #ecfeff, #bae6fd);
    color: #0f172a;
    border-radius: 999px;
    padding: 0.65rem 1rem;
    font-weight: 700;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.28);
}

.fade-in {
    animation: fadeIn 1s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(12px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.text-shadow-strong {
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.7);
}

@media (min-width: 768px) {
    .sticky-quote-btn {
        display: none;
    }
}
</style>

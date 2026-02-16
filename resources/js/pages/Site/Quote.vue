<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

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

const form = ref({
    name: '',
    email: '',
    event: '',
    date: '',
    start_time: '',
    end_time: '',
    address: '',
    details: '',
});

const loading = ref(false);
const message = ref('');

const endTimeOptions = computed(() => {
    if (!form.value.start_time) {
        return [];
    }

    const startIndex = allTimes.indexOf(form.value.start_time);
    const minEndIndex = startIndex + 2;

    return allTimes.slice(minEndIndex);
});

async function submitForm() {
    loading.value = true;
    message.value = '';

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
        const response = await fetch('/api/quotes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(form.value),
        });

        const data = await response.json();

        if (response.ok) {
            message.value = data.message || 'Thank you! Your quote has been submitted.';
            form.value = {
                name: '',
                email: '',
                event: '',
                date: '',
                start_time: '',
                end_time: '',
                address: '',
                details: '',
            };
        } else {
            message.value = data.error || 'Something went wrong. Please try again.';
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
            Fill in the details below and Melody will get back to you with a sprinkle of magic 💫
        </p>

        <section
            class="overlay-box mx-auto max-w-2xl rounded-2xl p-8 text-center backdrop-blur-md fade-in"
        >
            <p class="mb-6 leading-relaxed text-white/90">
                Interested in <span class="font-semibold text-pink-300">Sprinkle Fairydust</span>
                bringing a little extra sparkle to your next event?
                Please fill out the form below and Melody will get back to you soon with a
                <span class="font-semibold text-yellow-300">personalized quote!</span> ✨
            </p>

            <form class="quote-form space-y-5 text-left" @submit.prevent="submitForm">
                <div>
                    <label for="name" class="form-label">Your Name</label>
                    <input id="name" v-model="form.name" type="text" required class="form-input" />
                </div>

                <div>
                    <label for="email" class="form-label">Email</label>
                    <input id="email" v-model="form.email" type="email" required class="form-input" />
                </div>

                <div>
                    <label for="event" class="form-label">Event Type</label>
                    <input
                        id="event"
                        v-model="form.event"
                        type="text"
                        class="form-input"
                        placeholder="Birthday, Festival, etc."
                    />
                </div>

                <div>
                    <label for="date" class="form-label">Event Date</label>
                    <input id="date" v-model="form.date" type="date" class="form-input" />
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="start_time" class="form-label">Start Time</label>
                        <select id="start_time" v-model="form.start_time" class="form-input">
                            <option disabled value="">Select start time</option>
                            <option v-for="time in allTimes" :key="time" :value="time">{{ time }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="end_time" class="form-label">End Time</label>
                        <select
                            id="end_time"
                            v-model="form.end_time"
                            class="form-input"
                            :disabled="!form.start_time"
                        >
                            <option disabled value="">Select end time</option>
                            <option v-for="time in endTimeOptions" :key="time" :value="time">
                                {{ time }}
                            </option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="address" class="form-label">Event Address</label>
                    <input id="address" v-model="form.address" type="text" class="form-input" />
                </div>

                <div>
                    <label for="details" class="form-label">Additional Details</label>
                    <textarea
                        id="details"
                        v-model="form.details"
                        rows="4"
                        class="form-input resize-none"
                        placeholder="Tell Melody about your event..."
                    ></textarea>
                </div>

                <div class="text-center">
                    <button type="submit" class="cta" :disabled="loading">
                        {{ loading ? 'Sending...' : '✨ Send Request ✨' }}
                    </button>

                    <p v-if="message" class="mt-4 text-white/80">{{ message }}</p>
                </div>
            </form>
        </section>
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

.overlay-box {
    background: rgba(0, 0, 0, 0.55);
    border: 1px solid rgba(255, 255, 255, 0.25);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
    border-radius: 1.5rem;
    padding: 2.5rem;
    color: #fff;
    backdrop-filter: blur(12px);
    transition: transform 0.3s ease;
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

@media (max-width: 768px) {
    .floating-logo {
        position: relative;
        display: block;
        margin: 1rem auto;
        width: 180px;
    }

    .overlay-box {
        padding: 1.5rem;
    }
}
</style>

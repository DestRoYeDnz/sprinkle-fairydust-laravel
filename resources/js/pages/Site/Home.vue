<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const trustStats = [
    { label: 'Events painted', value: '350+' },
    { label: 'Repeat bookings', value: '120+' },
    { label: 'Typical response time', value: '< 24h' },
    { label: 'Service area', value: 'Auckland + Franklin' },
];

const reviewHighlights = [
    {
        quote: 'Melody was incredible with the kids and the designs were beautiful.',
        source: 'Google Review, January 2026',
    },
    {
        quote: 'Fast setup, professional hygiene, and everyone left smiling.',
        source: 'Facebook Review, December 2025',
    },
    {
        quote: 'The queue moved quickly and the face paints looked amazing in photos.',
        source: 'Market Event Booking, November 2025',
    },
];

const whatWeDo = [
    {
        title: 'Face Painting',
        description: 'Full-face and quick queue designs for birthdays, schools, and community events.',
        href: '/face-painting',
    },
    {
        title: 'Glitter Tattoos',
        description: 'Fast, sparkly add-on service that works well for active and mixed-age events.',
        href: '/glitter-tattoos',
    },
    {
        title: 'Festival Service',
        description: 'High-throughput setup for fairs, markets, and large public activations.',
        href: '/festival-face-painting',
    },
];

const bookingFlow = [
    {
        step: '1',
        title: 'Send your quote request',
        description: 'Share your date, location, guest count, and the style of service you want.',
    },
    {
        step: '2',
        title: 'Receive your tailored quote',
        description: 'We confirm availability and send a clear package recommendation and pricing.',
    },
    {
        step: '3',
        title: 'Secure your booking',
        description: 'Your booking is locked once your quote is confirmed and the booking fee is paid.',
    },
];
</script>

<template>
    <Head title="Kids Face Painting for Birthday Parties in Auckland">
        <meta
            head-key="description"
            name="description"
            content="Professional kids face painting for birthday parties and events in Pukekohe, Papakura, Drury and across Auckland."
        />
    </Head>

    <a href="https://www.sprinklefairydust.co.nz/">
        <img
            src="/images/logo.png"
            alt="Sprinkle Fairydust Logo"
            class="floating-logo fade-in-logo"
        />
    </a>

    <main class="hero page-content">
        <div class="mt-16 mb-6 text-center text-shadow-strong">
            <h1 class="text-5xl drop-shadow-xl md:text-6xl font-dancing">
                Sprinkle Fairydust
            </h1>
            <p class="mt-1 text-lg font-semibold tracking-wide text-white/90 italic md:text-xl">
                Facepainting by Melody
            </p>
        </div>

        <p class="tagline mb-8 text-xl text-shadow-strong drop-shadow-md md:text-2xl">
            Creativity, colour, and joyful designs for birthdays and events.
        </p>

        <section class="overlay-box mt-12 mb-10 h-max max-w-3xl rounded-2xl p-8 text-center backdrop-blur-md fade-in">
            <h2 class="mb-4 text-4xl text-yellow-200 font-dancing">
                Welcome to Sprinkle Fairydust
            </h2>

            <p class="mb-4 text-lg leading-relaxed text-white/90">
                We bring professional face painting to birthdays, markets, school fairs, and community events across Auckland and Franklin.
            </p>

            <p class="mb-6 text-lg leading-relaxed text-white/90">
                Every booking includes safe products, careful hygiene, and design styles matched to your event audience.
            </p>

            <Link
                href="/quote"
                class="cta rounded-2xl bg-white px-6 py-3 font-bold text-gray-900 shadow-lg transition hover:scale-105"
            >
                Get a Quote
            </Link>
        </section>

        <section class="overlay-box mb-10 w-full max-w-5xl rounded-2xl p-6 text-left backdrop-blur-md fade-in">
            <h2 class="mb-4 text-center text-3xl text-sky-100 font-dancing">Why families book with us</h2>

            <div class="stat-grid mb-5">
                <article v-for="item in trustStats" :key="item.label" class="stat-card">
                    <p class="stat-value">{{ item.value }}</p>
                    <p class="stat-label">{{ item.label }}</p>
                </article>
            </div>

            <div class="review-grid">
                <article v-for="item in reviewHighlights" :key="item.source" class="review-card">
                    <p class="review-quote">"{{ item.quote }}"</p>
                    <p class="review-source">{{ item.source }}</p>
                </article>
            </div>

            <div class="mt-6 text-center">
                <a
                    href="https://www.facebook.com/melfairysfacepainting"
                    target="_blank"
                    rel="noopener"
                    class="review-link"
                >
                    See Facebook Reviews
                </a>
            </div>
        </section>

        <section class="overlay-box mb-10 w-full max-w-5xl rounded-2xl p-6 text-left backdrop-blur-md fade-in">
            <h2 class="mb-4 text-center text-3xl text-teal-100 font-dancing">What We Do</h2>

            <div class="service-grid">
                <article v-for="item in whatWeDo" :key="item.title" class="service-card">
                    <h3 class="service-title">{{ item.title }}</h3>
                    <p class="service-copy">{{ item.description }}</p>
                    <Link :href="item.href" class="service-link">Learn more</Link>
                </article>
            </div>
        </section>

        <section class="overlay-box mb-12 w-full max-w-5xl rounded-2xl p-6 text-left backdrop-blur-md fade-in">
            <h2 class="mb-4 text-center text-3xl text-cyan-100 font-dancing">How Booking Works</h2>

            <div class="flow-grid">
                <article v-for="item in bookingFlow" :key="item.title" class="flow-card">
                    <p class="flow-step">Step {{ item.step }}</p>
                    <h3 class="flow-title">{{ item.title }}</h3>
                    <p class="flow-copy">{{ item.description }}</p>
                </article>
            </div>

            <p class="booking-note">
                Booking policy: event dates are secured once availability is confirmed and the 30% booking fee is received.
            </p>
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
    z-index: 30;
    padding: 0 1.5rem;
}

.tagline {
    color: #fff;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
}

.overlay-box {
    background: rgba(0, 0, 0, 0.55);
    border: 1px solid rgba(255, 255, 255, 0.25);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
    border-radius: 1.5rem;
    backdrop-filter: blur(12px);
    color: #fff;
    transition: transform 0.3s ease;
}

.cta {
    display: inline-block;
    color: #333;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
}

.cta:hover {
    transform: scale(1.05);
    background: #fff7f0;
}

.text-shadow-strong {
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.7);
}

.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.75rem;
}

.stat-card {
    border: 1px solid rgba(186, 230, 253, 0.45);
    border-radius: 0.9rem;
    background: rgba(2, 6, 23, 0.35);
    padding: 0.75rem;
    text-align: center;
}

.stat-value {
    margin: 0;
    font-size: 1.45rem;
    font-weight: 700;
    color: #bae6fd;
}

.stat-label {
    margin: 0.2rem 0 0;
    font-size: 0.84rem;
    color: rgba(236, 254, 255, 0.9);
}

.review-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 0.75rem;
}

.review-card {
    border: 1px solid rgba(153, 246, 228, 0.4);
    border-radius: 0.9rem;
    background: rgba(15, 23, 42, 0.3);
    padding: 0.8rem;
}

.review-quote {
    margin: 0;
    font-size: 0.92rem;
    line-height: 1.4;
}

.review-source {
    margin: 0.4rem 0 0;
    color: #ccfbf1;
    font-size: 0.75rem;
}

.review-link {
    display: inline-flex;
    border-radius: 999px;
    border: 1px solid rgba(224, 247, 255, 0.95);
    background: linear-gradient(135deg, #ecfeff, #bae6fd);
    color: #0f172a;
    font-weight: 700;
    font-size: 0.85rem;
    padding: 0.45rem 0.9rem;
}

.service-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 0.75rem;
}

.service-card {
    border: 1px solid rgba(125, 211, 252, 0.42);
    border-radius: 0.9rem;
    background: rgba(2, 6, 23, 0.35);
    padding: 0.8rem;
}

.service-title {
    margin: 0;
    font-size: 1.05rem;
    color: #bfdbfe;
}

.service-copy {
    margin: 0.45rem 0 0;
    font-size: 0.9rem;
    color: rgba(236, 254, 255, 0.92);
    line-height: 1.5;
}

.service-link {
    display: inline-flex;
    margin-top: 0.6rem;
    border-radius: 999px;
    border: 1px solid rgba(224, 247, 255, 0.95);
    background: linear-gradient(135deg, #ecfeff, #bae6fd);
    color: #0f172a;
    font-weight: 700;
    font-size: 0.8rem;
    padding: 0.4rem 0.75rem;
}

.flow-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 0.75rem;
}

.flow-card {
    border: 1px solid rgba(153, 246, 228, 0.4);
    border-radius: 0.9rem;
    background: rgba(15, 23, 42, 0.35);
    padding: 0.85rem;
}

.flow-step {
    margin: 0;
    font-size: 0.72rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: #a5f3fc;
    font-weight: 700;
}

.flow-title {
    margin: 0.35rem 0 0;
    font-size: 1rem;
    color: #e0f2fe;
}

.flow-copy {
    margin: 0.45rem 0 0;
    font-size: 0.9rem;
    line-height: 1.45;
    color: rgba(236, 254, 255, 0.9);
}

.booking-note {
    margin: 0.85rem 0 0;
    border: 1px dashed rgba(186, 230, 253, 0.6);
    border-radius: 0.75rem;
    padding: 0.7rem;
    font-size: 0.88rem;
    color: #cffafe;
    background: rgba(8, 47, 73, 0.26);
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

@media (max-width: 768px) {
    .floating-logo {
        position: relative;
        display: block;
        margin: 1rem auto;
        width: 180px;
    }

    .tagline {
        font-size: 1.25rem;
    }

    .booking-note {
        font-size: 0.82rem;
    }
}
</style>

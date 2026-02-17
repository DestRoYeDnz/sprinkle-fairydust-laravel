<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const faqs = [
    {
        question: 'What paints and products do you use?',
        answer: 'Professional cosmetic-grade paints and cosmetic glitter, with hygiene-first brush and sponge rotation.',
        group: 'Safety',
    },
    {
        question: 'What if weather changes for outdoor events?',
        answer: 'We can adapt to sheltered areas or reschedule where possible. Let us know early so we can plan backup options.',
        group: 'Planning',
    },
    {
        question: 'How does cancellation or postponement work?',
        answer: 'Please notify us as early as possible. We aim to transfer bookings to a new date where availability allows.',
        group: 'Booking',
    },
    {
        question: 'How many kids can be painted per hour?',
        answer: 'This depends on design complexity. Fast designs can service larger lines, while premium designs take longer.',
        group: 'Event Day',
    },
    {
        question: 'How do we remove face paint?',
        answer: 'Use warm water and gentle soap with a soft cloth. Avoid harsh scrubbing around sensitive skin.',
        group: 'Aftercare',
    },
];
</script>

<template>
    <Head title="FAQ | Sprinkle Fairydust">
        <meta
            head-key="description"
            name="description"
            content="Frequently asked questions about booking face painting for birthdays and events across Auckland."
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
            <h1 class="text-5xl text-white drop-shadow-xl md:text-6xl font-dancing">FAQ</h1>
        </div>

        <p class="tagline mb-8 text-xl text-shadow-strong drop-shadow-md md:text-2xl">
            Booking policies, safety, and event-day expectations.
        </p>

        <section class="overlay-box mx-auto mb-12 max-w-5xl space-y-8 rounded-2xl p-8 text-left backdrop-blur-md fade-in">
            <div class="rounded-xl border border-white/25 bg-black/20 p-5">
                <h2 class="mb-2 text-3xl text-yellow-200 drop-shadow-sm md:text-4xl font-dancing">
                    Quick answers before you book
                </h2>
                <p class="text-base leading-relaxed text-white/90">
                    If you have a question not listed here, send us your details and we will reply with a tailored answer for your event.
                </p>
            </div>

            <div class="faq-grid">
                <article v-for="item in faqs" :key="item.question" class="faq-card">
                    <p class="faq-tag">{{ item.group }}</p>
                    <h3 class="faq-question">{{ item.question }}</h3>
                    <p class="faq-answer">{{ item.answer }}</p>
                </article>
            </div>

            <div class="faq-links">
                <Link href="/services" class="pill-link">View Services</Link>
                <Link href="/quote" class="cta-link">Request a Quote</Link>
            </div>
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
}

.overlay-box {
    background: rgba(0, 0, 0, 0.55);
    border: 1px solid rgba(255, 255, 255, 0.25);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
    border-radius: 1.5rem;
    color: #fff;
    backdrop-filter: blur(12px);
}

.faq-grid {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
}

.faq-card {
    border: 1px solid rgba(191, 219, 254, 0.35);
    border-radius: 0.9rem;
    padding: 1rem;
    background: rgba(2, 6, 23, 0.35);
}

.faq-tag {
    margin: 0;
    font-size: 0.7rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #7dd3fc;
    font-weight: 700;
}

.faq-question {
    margin: 0.35rem 0 0;
    font-size: 1.05rem;
    color: #e0f2fe;
}

.faq-answer {
    margin: 0.5rem 0 0;
    color: rgba(236, 254, 255, 0.9);
    line-height: 1.5;
}

.faq-links {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.6rem;
}

.pill-link {
    border: 1px solid rgba(224, 247, 255, 0.95);
    background: linear-gradient(135deg, #ecfeff, #bae6fd);
    color: #0f172a;
    border-radius: 999px;
    padding: 0.5rem 0.95rem;
    font-size: 0.9rem;
    font-weight: 700;
}

.cta-link {
    display: inline-flex;
    justify-content: center;
    border-radius: 999px;
    border: 1px solid rgba(224, 247, 255, 0.95);
    background: linear-gradient(135deg, #ecfeff, #bae6fd);
    color: #0f172a;
    font-weight: 700;
    font-size: 0.9rem;
    padding: 0.5rem 0.95rem;
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

.fade-in {
    animation: fadeIn 1s ease-in-out;
}

.text-shadow-strong {
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.7);
}

@media (max-width: 640px) {
    .overlay-box {
        padding: 1rem;
    }

    .faq-question {
        font-size: 1rem;
    }

    .faq-links {
        flex-direction: column;
    }

    .cta-link,
    .pill-link {
        width: 100%;
    }
}
</style>

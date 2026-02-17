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
    },
    {
        question: 'What if weather changes for outdoor events?',
        answer: 'We can adapt to sheltered areas or reschedule where possible. Let us know early so we can plan backup options.',
    },
    {
        question: 'How does cancellation or postponement work?',
        answer: 'Please notify us as early as possible. We aim to transfer bookings to a new date where availability allows.',
    },
    {
        question: 'How many kids can be painted per hour?',
        answer: 'This depends on design complexity. Fast designs can service larger lines, while premium designs take longer.',
    },
    {
        question: 'How do we remove face paint?',
        answer: 'Use warm water and gentle soap with a soft cloth. Avoid harsh scrubbing around sensitive skin.',
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

    <main class="mx-auto mt-8 max-w-4xl px-3 pb-12 sm:mt-10 sm:px-4 font-quicksand">
        <section class="faq-shell">
            <h1 class="text-center text-3xl text-slate-900 sm:text-4xl md:text-5xl font-dancing">FAQ</h1>
            <p class="mt-2 text-center text-sm text-slate-600 sm:text-base">Booking policies, safety, and event-day expectations.</p>

            <div class="mt-6 space-y-3">
                <article v-for="item in faqs" :key="item.question" class="faq-card">
                    <h2 class="faq-question">{{ item.question }}</h2>
                    <p class="faq-answer">{{ item.answer }}</p>
                </article>
            </div>

            <div class="mt-8 text-center">
                <Link href="/quote" class="cta-link">Request a Quote</Link>
            </div>
        </section>
    </main>
</template>

<style scoped>
.faq-shell {
    border-radius: 1.25rem;
    border: 1px solid rgba(148, 163, 184, 0.3);
    background: rgba(248, 250, 252, 0.95);
    box-shadow: 0 18px 30px rgba(15, 23, 42, 0.1);
    padding: 1.5rem;
}

.faq-card {
    border: 1px solid #cbd5e1;
    border-radius: 0.9rem;
    padding: 0.9rem;
    background: #fff;
}

.faq-question {
    margin: 0;
    font-size: 1.05rem;
    color: #0f172a;
}

.faq-answer {
    margin: 0.45rem 0 0;
    color: #475569;
    line-height: 1.5;
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

@media (max-width: 640px) {
    .faq-shell {
        padding: 1rem;
    }

    .faq-question {
        font-size: 1rem;
    }

    .cta-link {
        width: 100%;
    }
}
</style>

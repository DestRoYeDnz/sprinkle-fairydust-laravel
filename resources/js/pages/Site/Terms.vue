<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const sections = [
    {
        title: 'Booking and Confirmation',
        points: [
            'A booking is confirmed once you have accepted your quote and received written confirmation from Sprinkle Fairydust.',
            'All booking details must be accurate at the time of confirmation, including event date, location, and start and finish times.',
            'Requested times are subject to availability until written confirmation is issued.',
        ],
    },
    {
        title: 'Payments',
        points: [
            'Pricing in your quote is based on the event details provided and may change if those details change.',
            'Payment terms and due dates are provided in your quote or invoice.',
            'Late payments may delay or impact service delivery.',
        ],
    },
    {
        title: 'Cancellations and Changes',
        points: [
            'Please provide notice as early as possible if you need to cancel, postpone, or move your event.',
            'Date changes are subject to artist availability.',
            'Travel or setup costs already incurred may still apply where relevant.',
        ],
    },
    {
        title: 'Event Conditions and Safety',
        points: [
            'A safe, clean, and weather-appropriate working area must be provided at the event site.',
            'Children must be supervised by a parent or responsible adult while in the queue and during painting.',
            'Sprinkle Fairydust may refuse service where safety, hygiene, or inappropriate behaviour is a concern.',
        ],
    },
    {
        title: 'Health and Skin Sensitivities',
        points: [
            'Professional cosmetic-grade products are used; however, clients should disclose known allergies or sensitivities before painting.',
            'Face painting is not applied to broken, irritated, or infected skin.',
            'By booking, you accept responsibility for notifying guests of any known skin conditions.',
        ],
    },
    {
        title: 'Photography and Marketing',
        points: [
            'Photos may be taken at events for portfolio and marketing use unless you request otherwise in writing before the event.',
            'You can opt out of photography at any time by advising us directly.',
        ],
    },
    {
        title: 'Liability',
        points: [
            'Sprinkle Fairydust is not liable for delays or non-performance caused by events outside reasonable control, including severe weather, road closures, or emergencies.',
            'Liability is limited to the amount paid for the affected booking, to the extent permitted by law.',
        ],
    },
];
</script>

<template>
    <Head title="Terms and Conditions | Sprinkle Fairydust">
        <meta
            head-key="description"
            name="description"
            content="Read the booking terms and conditions for Sprinkle Fairydust face painting services across Auckland."
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
            <h1 class="text-5xl text-white drop-shadow-xl md:text-6xl font-dancing">Terms and Conditions</h1>
        </div>

        <p class="tagline mb-8 text-xl text-shadow-strong drop-shadow-md md:text-2xl">
            Clear booking terms so your event runs smoothly from quote to paint day.
        </p>

        <section class="overlay-box mx-auto mb-12 w-full max-w-5xl rounded-2xl p-6 text-left backdrop-blur-md fade-in md:p-8">
            <p class="last-updated">Last updated: February 17, 2026</p>

            <div class="terms-grid mt-4">
                <article v-for="section in sections" :key="section.title" class="terms-card">
                    <h2 class="terms-heading">{{ section.title }}</h2>
                    <ul class="terms-list">
                        <li v-for="point in section.points" :key="point">
                            {{ point }}
                        </li>
                    </ul>
                </article>
            </div>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <Link href="/quote" class="cta-link">Return to Quote Form</Link>
                <Link href="/faq" class="cta-link cta-link--alt">View FAQ</Link>
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
    padding: 0 1rem;
}

.tagline {
    color: #fff;
}

.overlay-box {
    background: rgba(0, 0, 0, 0.55);
    border: 1px solid rgba(255, 255, 255, 0.25);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
    color: #fff;
}

.last-updated {
    margin: 0;
    text-align: center;
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #bae6fd;
}

.terms-grid {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(1, minmax(0, 1fr));
}

@media (min-width: 900px) {
    .terms-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

.terms-card {
    border: 1px solid rgba(186, 230, 253, 0.4);
    border-radius: 0.9rem;
    padding: 0.95rem 1rem;
    background: rgba(2, 6, 23, 0.35);
    text-align: left;
}

.terms-heading {
    margin: 0;
    font-size: 1rem;
    color: #bfdbfe;
}

.terms-list {
    margin: 0.55rem 0 0;
    padding-left: 1rem;
    color: rgba(236, 254, 255, 0.94);
    line-height: 1.5;
    font-size: 0.92rem;
}

.terms-list li + li {
    margin-top: 0.5rem;
}

.cta-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    border: 1px solid rgba(224, 247, 255, 0.95);
    background: linear-gradient(135deg, #ecfeff, #bae6fd);
    color: #0f172a;
    font-weight: 700;
    font-size: 0.9rem;
    padding: 0.5rem 0.95rem;
}

.cta-link--alt {
    background: rgba(224, 247, 255, 0.16);
    color: #ecfeff;
    border-color: rgba(186, 230, 253, 0.8);
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

@media (max-width: 640px) {
    .hero {
        padding: 0 0.8rem;
    }

    .tagline {
        font-size: 1.05rem;
    }

    .overlay-box {
        padding: 1rem;
    }

    .cta-link {
        width: 100%;
    }
}
</style>

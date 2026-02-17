<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const packages = [
    {
        name: 'Mini Party Sparkle',
        price: 'From $240',
        duration: '2 hours',
        capacity: 'Up to 20 kids',
        bestFor: 'Home birthdays and tighter party schedules',
        addOns: 'Add glitter tattoos or themed looks',
    },
    {
        name: 'Classic Birthday Magic',
        price: 'From $360',
        duration: '3 hours',
        capacity: 'Around 30 kids',
        bestFor: 'Most birthday parties and school groups',
        addOns: 'Add waterproof or festival style upgrades',
    },
    {
        name: 'Festival Crowd Package',
        price: 'From $480',
        duration: '4+ hours',
        capacity: 'High volume queue events',
        bestFor: 'Markets, fairs, and community days',
        addOns: 'Multi-artist support available by request',
    },
];

const addOnMenu = [
    {
        name: 'Glitter Tattoos Boost',
        detail: 'Add glitter tattoo service to any face painting package.',
        priceHint: 'From $80',
    },
    {
        name: 'Extended Time',
        detail: 'Add extra artist time for larger queues or longer events.',
        priceHint: 'From $90 / additional hour',
    },
    {
        name: 'Festival Queue Setup',
        detail: 'Queue board and menu optimization for high-foot-traffic events.',
        priceHint: 'Quoted per event',
    },
];
</script>

<template>
    <Head title="Face Painting Services for Kids Parties | Auckland">
        <meta
            head-key="description"
            name="description"
            content="Kids face painting services for birthday parties, school events and celebrations in Pukekohe, Papakura, Drury and Auckland."
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
            <h1 class="text-5xl text-white drop-shadow-xl md:text-6xl font-dancing">
                Services and Packages
            </h1>
            <p class="mt-8 text-2xl drop-shadow-md italic md:text-2xl">
                Clear package options to match your event size and timeline.
            </p>
        </div>

        <section class="overlay-box mx-auto max-w-6xl space-y-10 rounded-2xl p-10 text-left backdrop-blur-md fade-in">
            <div class="grid gap-4 md:grid-cols-3">
                <article v-for="item in packages" :key="item.name" class="package-card">
                    <h2 class="package-name">{{ item.name }}</h2>
                    <p class="package-price">{{ item.price }}</p>
                    <ul class="package-list">
                        <li><strong>Duration:</strong> {{ item.duration }}</li>
                        <li><strong>Capacity:</strong> {{ item.capacity }}</li>
                        <li><strong>Best for:</strong> {{ item.bestFor }}</li>
                        <li><strong>Add-ons:</strong> {{ item.addOns }}</li>
                    </ul>

                    <Link
                        :href="`/quote?package_name=${encodeURIComponent(item.name)}`"
                        class="cta mt-4 inline-flex rounded-xl px-4 py-2 text-sm font-bold"
                    >
                        Choose This Package
                    </Link>
                </article>
            </div>

            <div class="rounded-xl border border-white/25 bg-black/20 p-5">
                <h3 class="mb-2 text-3xl text-yellow-200 drop-shadow-sm md:text-4xl font-dancing">
                    What is included
                </h3>
                <p class="text-lg leading-relaxed text-white/90 font-quicksand">
                    Professional skin-safe products, hygienic setup, queue management, and design options tailored to your audience.
                </p>
            </div>

            <div class="rounded-xl border border-white/25 bg-black/20 p-5">
                <h3 class="mb-2 text-3xl text-emerald-200 drop-shadow-sm md:text-4xl font-dancing">
                    Popular add-ons
                </h3>

                <div class="add-on-grid">
                    <article v-for="item in addOnMenu" :key="item.name" class="add-on-card">
                        <h4 class="add-on-name">{{ item.name }}</h4>
                        <p class="add-on-detail">{{ item.detail }}</p>
                        <p class="add-on-price">{{ item.priceHint }}</p>
                    </article>
                </div>

                <p class="mt-4 text-sm text-cyan-100/90">
                    Note: travel outside core Auckland + Franklin zones may incur an additional travel fee.
                </p>
            </div>

            <div class="rounded-xl border border-white/25 bg-black/20 p-5">
                <h3 class="mb-2 text-3xl text-pink-300 drop-shadow-sm md:text-4xl font-dancing">
                    Service pages
                </h3>
                <div class="service-links">
                    <Link href="/face-painting" class="pill-link">Face Painting</Link>
                    <Link href="/glitter-tattoos" class="pill-link">Glitter Tattoos</Link>
                    <Link href="/festival-face-painting" class="pill-link">Festival Face Painting</Link>
                </div>
            </div>

            <div class="rounded-xl border border-cyan-200/35 bg-cyan-950/30 p-5">
                <h3 class="mb-2 text-3xl text-cyan-100 drop-shadow-sm md:text-4xl font-dancing">
                    Booking policy
                </h3>
                <ul class="policy-list">
                    <li>Bookings are secured once your quote is confirmed and your 30% booking fee is paid.</li>
                    <li>Date changes are subject to availability.</li>
                    <li>Final details can be updated closer to your event date.</li>
                </ul>
            </div>

            <div class="border-white/30 pt-6 border-t text-center">
                <h3 class="mb-3 text-3xl text-purple-300 drop-shadow-sm md:text-4xl font-dancing">
                    Need a tailored quote?
                </h3>
                <p class="mb-6 text-lg leading-relaxed text-white/90 font-quicksand">
                    Share your event details and we will recommend the best package for your timing, guest count, and location.
                </p>
                <Link
                    href="/quote"
                    class="cta rounded-2xl bg-white px-6 py-3 font-bold text-gray-900 shadow-lg transition hover:scale-105"
                >
                    Get a Quote
                </Link>
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

.package-card {
    border: 1px solid rgba(191, 219, 254, 0.4);
    border-radius: 1rem;
    padding: 1rem;
    background: rgba(2, 6, 23, 0.35);
}

.package-name {
    margin: 0;
    color: #bae6fd;
    font-size: 1.25rem;
}

.package-price {
    margin-top: 0.35rem;
    font-weight: 700;
    color: #fef08a;
}

.package-list {
    margin-top: 0.7rem;
    font-size: 0.9rem;
    line-height: 1.5;
    color: rgba(255, 255, 255, 0.95);
    padding-left: 1rem;
}

.add-on-grid {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

.add-on-card {
    border: 1px solid rgba(110, 231, 183, 0.35);
    border-radius: 0.85rem;
    background: rgba(2, 6, 23, 0.32);
    padding: 0.75rem;
}

.add-on-name {
    margin: 0;
    font-size: 0.96rem;
    color: #a7f3d0;
}

.add-on-detail {
    margin: 0.35rem 0 0;
    font-size: 0.86rem;
    color: rgba(236, 254, 255, 0.9);
    line-height: 1.45;
}

.add-on-price {
    margin: 0.5rem 0 0;
    font-size: 0.82rem;
    font-weight: 700;
    color: #fde68a;
}

.cta {
    color: #333;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
}

.cta:hover {
    transform: scale(1.05);
    background: #fff7f0;
}

.service-links {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
}

.pill-link {
    border: 1px solid rgba(224, 247, 255, 0.95);
    background: linear-gradient(135deg, #ecfeff, #bae6fd);
    color: #0f172a;
    border-radius: 999px;
    padding: 0.45rem 0.9rem;
    font-size: 0.9rem;
    font-weight: 700;
}

.policy-list {
    margin: 0;
    padding-left: 1rem;
    color: rgba(236, 254, 255, 0.92);
    line-height: 1.5;
}

.policy-list li + li {
    margin-top: 0.45rem;
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
        padding: 1rem;
    }

    .policy-list {
        font-size: 0.9rem;
    }
}
</style>

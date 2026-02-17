<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const highlights = [
    {
        title: 'Why clients choose this',
        items: [
            'Fast throughput for larger guest numbers',
            'Great option for mixed-age events',
            'Perfect add-on with face painting packages',
        ],
    },
    {
        title: 'Design options',
        items: [
            'Sparkle stars, flowers, and butterflies',
            'Festival icons and themed symbols',
            'Custom colour palettes for your event theme',
        ],
    },
    {
        title: 'Wear and care',
        items: [
            'Applied with cosmetic adhesive and glitter',
            'Can last for days with gentle care',
            'Easy to remove with oil-based cleanser',
        ],
    },
];
</script>

<template>
    <Head title="Glitter Tattoos in Auckland | Sprinkle Fairydust">
        <meta
            head-key="description"
            name="description"
            content="Add long-lasting glitter tattoos to your birthday party or event booking in Auckland and Franklin."
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
            <h1 class="text-5xl text-white drop-shadow-xl md:text-6xl font-dancing">Glitter Tattoos</h1>
        </div>

        <p class="tagline mb-8 text-xl text-shadow-strong drop-shadow-md md:text-2xl">
            A fast, fun option for high-throughput events and mixed-age groups.
        </p>

        <section class="overlay-box mx-auto mb-12 max-w-6xl space-y-8 rounded-2xl p-8 text-left backdrop-blur-md fade-in">
            <div class="intro-card">
                <h2 class="intro-title">What this service covers</h2>
                <p class="intro-copy">
                    Glitter tattoos are ideal when you need speed without losing visual impact. This service works as a stand-alone option or as part of a combined package with face painting.
                </p>
            </div>

            <div class="feature-grid">
                <article v-for="item in highlights" :key="item.title" class="feature-card">
                    <h3 class="feature-title">{{ item.title }}</h3>
                    <ul class="feature-list">
                        <li v-for="line in item.items" :key="`${item.title}-${line}`">{{ line }}</li>
                    </ul>
                </article>
            </div>

            <div class="cta-row">
                <Link href="/services" class="pill-link">View Packages</Link>
                <Link href="/quote" class="cta-link">Add Glitter Tattoos</Link>
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

.intro-card {
    border-radius: 0.9rem;
    border: 1px solid rgba(255, 255, 255, 0.25);
    background: rgba(0, 0, 0, 0.2);
    padding: 1rem;
}

.intro-title {
    margin: 0;
    color: #fef08a;
    font-size: 1.5rem;
    font-family: 'Dancing Script', cursive;
}

.intro-copy {
    margin: 0.45rem 0 0;
    color: rgba(236, 254, 255, 0.92);
    line-height: 1.6;
}

.feature-grid {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.feature-card {
    border: 1px solid rgba(191, 219, 254, 0.35);
    border-radius: 0.9rem;
    background: rgba(2, 6, 23, 0.35);
    padding: 0.9rem;
    text-align: left;
}

.feature-title {
    margin: 0;
    color: #bae6fd;
    font-size: 1.05rem;
}

.feature-list {
    margin: 0.45rem 0 0;
    padding-left: 1rem;
    color: rgba(236, 254, 255, 0.9);
    font-size: 0.92rem;
    line-height: 1.45;
}

.feature-list li + li {
    margin-top: 0.3rem;
}

.cta-row {
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

    .cta-row {
        flex-direction: column;
    }

    .pill-link,
    .cta-link {
        width: 100%;
    }
}
</style>

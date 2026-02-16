<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});
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
            Creativity, colour, and a sprinkle of magic in every design ✨
        </p>

        <section
            class="overlay-box mt-12 mb-36 h-max max-w-3xl rounded-2xl p-8 text-center backdrop-blur-md fade-in"
        >
            <h2 class="mb-4 text-4xl text-yellow-200 font-dancing">
                Welcome to Sprinkle Fairydust ✨
            </h2>

            <p class="mb-4 text-lg leading-relaxed text-white/90">
                Where <span class="font-semibold text-pink-300">magic</span>,
                <span class="font-semibold text-blue-300">colour</span>, and
                <span class="font-semibold text-yellow-300">smiles</span> come together!
                Sprinkle Fairydust Facepainting brings a world of imagination to life —
                one brushstroke at a time.
            </p>

            <p class="mb-4 text-lg leading-relaxed text-white/90">
                From birthdays and festivals to school fairs and community events,
                we transform faces into dazzling works of art that spark joy and wonder.
            </p>

            <p class="mb-4 text-lg leading-relaxed text-white/90">
                Using only high-quality, skin-safe paints and a sprinkle of creativity,
                every design is painted with care, laughter, and a little fairy magic. 🧚‍♀️
            </p>

            <p class="mb-6 text-lg leading-relaxed text-white/90">
                Whether it’s <span class="text-blue-300">unicorns</span>,
                <span class="text-green-300">butterflies</span>,
                <span class="text-orange-300">tigers</span>, or
                <span class="text-purple-300">rainbows</span> —
                we’ll make your event sparkle with unforgettable memories!
            </p>

            <Link
                href="/quote"
                class="cta rounded-2xl bg-white px-6 py-3 font-bold text-gray-900 shadow-lg transition hover:scale-105"
            >
                ✨ Get a Quote ✨
            </Link>
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
}
</style>

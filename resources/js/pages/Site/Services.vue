<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});
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
                Our Magical Services
            </h1>
            <p class="mt-8 text-2xl drop-shadow-md italic md:text-2xl">
                Bringing colour, sparkle, and smiles to every occasion 💖
            </p>
        </div>

        <section
            class="overlay-box mx-auto max-w-4xl space-y-10 rounded-2xl p-10 text-left backdrop-blur-md fade-in"
        >
            <div>
                <h3 class="mb-2 text-3xl text-yellow-200 drop-shadow-sm md:text-4xl font-dancing">
                    🎉 Birthday Parties
                </h3>
                <p class="text-lg leading-relaxed text-white/90 font-quicksand">
                    Make your child’s day extra special with custom-painted designs that turn imagination into reality —
                    from <span class="text-pink-300">twinkling fairies</span> and
                    <span class="text-blue-300">brave tigers</span> to
                    <span class="text-yellow-300">dazzling rainbows</span> and
                    <span class="text-green-300">superheroes</span>!
                </p>
            </div>

            <div>
                <h3 class="mb-2 text-3xl text-pink-300 drop-shadow-sm md:text-4xl font-dancing">
                    🏖️ Festivals & Markets
                </h3>
                <p class="text-lg leading-relaxed text-white/90 font-quicksand">
                    Add colour, joy, and a touch of glitter to your event!
                    <span class="text-sky-300">Melody’s vibrant festival looks</span>
                    bring sparkle to every smile and make your crowd shimmer with happiness.
                </p>
            </div>

            <div>
                <h3 class="mb-2 text-3xl text-sky-300 drop-shadow-sm md:text-4xl font-dancing">
                    🎭 Corporate & Community Events
                </h3>
                <p class="text-lg leading-relaxed text-white/90 font-quicksand">
                    From themed family days to local markets and workplace celebrations,
                    <span class="text-yellow-200">Sprinkle Fairydust</span>
                    brings a whimsical touch that both kids and adults adore.
                </p>
            </div>

            <div>
                <h3 class="mb-2 text-3xl text-green-300 drop-shadow-sm md:text-4xl font-dancing">
                    ✨ Custom Designs
                </h3>
                <p class="text-lg leading-relaxed text-white/90 font-quicksand">
                    Have something unique in mind? Melody can create
                    <span class="text-pink-200">personalised, one-of-a-kind</span>
                    designs inspired by your event’s theme, colours, or creative vision.
                </p>
            </div>

            <div class="border-white/30 pt-6 border-t">
                <h3 class="mb-3 text-3xl text-purple-300 drop-shadow-sm md:text-4xl font-dancing">
                    🌟 Safe & Professional
                </h3>
                <p class="text-lg leading-relaxed text-white/90 font-quicksand">
                    All paints are professional-grade, hypoallergenic, and gentle on even the most sensitive skin.
                    <span class="text-yellow-200">Clean brushes, safe products, and smiles guaranteed</span> —
                    it’s all part of the Sprinkle Fairydust experience.
                </p>
            </div>
        </section>

        <section class="overlay-box mx-auto mt-16 max-w-3xl text-center fade-in">
            <h2 class="mb-3 text-4xl text-pink-200 drop-shadow-md md:text-5xl font-dancing">💫 Pricing</h2>

            <p class="mb-6 text-lg leading-relaxed text-white/90 font-quicksand">
                Prices vary depending on event type, location, and duration.
                Melody can tailor a quote to suit your special event perfectly. ✨
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
}
</style>

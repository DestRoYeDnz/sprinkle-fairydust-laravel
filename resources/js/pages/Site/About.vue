<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});
</script>

<template>
    <Head title="About Sprinkle Fairydust | Auckland Face Painter">
        <meta
            head-key="description"
            name="description"
            content="Meet Melody, the creative face painter behind Sprinkle Fairydust, bringing colourful birthday magic to kids across Auckland."
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
            <h1 class="text-5xl drop-shadow-xl md:text-6xl font-dancing">About</h1>
        </div>

        <p class="tagline mb-8 text-xl text-shadow-strong drop-shadow-md md:text-2xl">
            The heart and sparkle behind Sprinkle Fairydust Facepainting 🎨
        </p>

        <section
            class="overlay-box mt-4 mb-12 max-w-2xl rounded-2xl p-8 text-center backdrop-blur-md fade-in"
        >
            <h2 class="mb-6 text-4xl text-yellow-200 drop-shadow-sm font-dancing">
                Meet Melody ✨
            </h2>

            <p class="mb-4 leading-relaxed text-white/90">
                Hello, lovely souls! I’m <span class="font-semibold text-pink-300">Melody</span> —
                the artist, dream-weaver, and smile-maker behind
                <span class="text-yellow-300">Sprinkle Fairydust Facepainting</span>. 💖
                What started as a childhood fascination with colour and imagination has blossomed into a joyful calling —
                turning ordinary moments into glittering memories that sparkle long after the paint fades.
            </p>

            <p class="mb-4 leading-relaxed text-white/90">
                Each time I open my kit, it feels like opening a treasure chest of wonder — brushes poised like wands,
                palettes gleaming with colour, and glitter that dances under the light. With every swirl and shimmer,
                I love watching a child’s eyes widen in delight as they see themselves transform into their favourite
                creature, hero, or mythical being. ✨
            </p>

            <p class="mb-4 leading-relaxed text-white/90">
                I use only the highest-quality, professional, skin-safe paints — gentle enough for little cheeks,
                vibrant enough for big imaginations. My workspace is always filled with laughter, calm energy,
                and a sprinkle of music — a place where creativity and comfort blend beautifully together.
            </p>

            <p class="mb-4 leading-relaxed text-white/90">
                From <span class="text-blue-300">whisper-soft butterflies</span> that flutter across foreheads
                to <span class="text-orange-300">bold jungle tigers</span> ready to roar,
                from <span class="text-green-300">enchanted fairies</span> with sparkling wings
                to <span class="text-purple-300">rainbow princesses</span> crowned in glitter —
                every design is painted with intention, heart, and a touch of magic.
            </p>

            <p class="mb-4 leading-relaxed text-white/90">
                Whether I’m creating joy at a <span class="text-pink-300">birthday celebration</span>,
                adding colour to a <span class="text-sky-300">community festival</span>,
                or sprinkling smiles at a <span class="text-yellow-300">corporate family day</span>,
                my mission is simple — to help every guest feel radiant, unique, and a little more magical than before.
            </p>

            <p class="mt-6 text-white/90 italic opacity-90">
                So come and join me in a world where colour tells stories, laughter paints the air,
                and every brushstroke whispers, “You are extraordinary.” 💫
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
    z-index: 10;
    padding: 0 1.5rem;
    background: transparent;
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
    padding: 2.5rem;
    color: #fff;
    backdrop-filter: blur(12px);
    transition: transform 0.3s ease;
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

    .tagline {
        font-size: 1.25rem;
    }
}
</style>

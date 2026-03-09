<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});
</script>

<template>
    <Head title="About Sprinkle Fairydust | Face Painter in South Auckland and Northern Waikato">
        <meta
            head-key="description"
            name="description"
            content="Meet Melody, the creative face painter behind Sprinkle Fairydust, bringing colourful birthday magic to kids across South Auckland and Northern Waikato."
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
                Hi, I’m <span class="font-semibold text-pink-300">Melody</span>.
            </p>

            <p class="mb-4 leading-relaxed text-white/90">
                Since I was very young, I have been enthralled by the mythical, creative,
                spontaneous, and whimsical world of fairytales and imagination.
            </p>

            <p class="mb-4 leading-relaxed text-white/90">
                I grew up surrounded by creativity. My extended family includes gardeners,
                builders, bakers, weavers, sewers, and costume makers, as well as potters,
                painters, poets, face painters, and body artists.
            </p>

            <p class="mb-4 leading-relaxed text-white/90">
                My interest in face painting comes from growing up in such a creative family
                and being exposed to the magic and whimsy of body art throughout my life.
                I was gifted paints and brushes by family, and over the years I began
                experimenting with them and developing my skills.
            </p>

            <p class="mb-4 leading-relaxed text-white/90">
                I am also a teacher. I have worked with both high school and primary-aged
                children, and I have spent several years supporting learners with additional
                needs. Because of this experience, I am comfortable engaging with children
                of all ages and stages.
            </p>

            <p class="mb-4 leading-relaxed text-white/90">
                I have now been face painting professionally for six years, and I absolutely
                love it. I get so much joy from bringing <span class="text-yellow-300">rainbows</span>,
                <span class="text-blue-300">scales</span>, and
                <span class="text-pink-300">sparkles</span> to people and watching adults and
                teens embrace their inner child.
            </p>

            <p class="mb-4 leading-relaxed text-white/90">
                My favourite moment is seeing someone’s reaction when they look in the mirror
                for the first time. It truly warms my heart.
            </p>

            <p class="mb-4 leading-relaxed text-white/90">
                <span class="text-yellow-300">Face painting and body art is for everyone.</span>
                Age does not matter. If you do not want your face painted, we can paint your
                arm instead and create happy memories to reflect on for years to come.
            </p>

            <p class="mt-6 text-white/90 italic opacity-90">
                When you book me, you get someone who loves bold colour and sparkly glitter,
                but also someone who can go with the flow and be creative on the spot. You
                get someone who is kind, fun, and able to work quickly to ensure you get the
                most value from your booking.
            </p>

            <div class="mt-8 flex justify-center">
                <Link
                    href="/quote"
                    class="cta inline-flex items-center justify-center rounded-2xl bg-white px-6 py-3 font-bold text-gray-900 shadow-lg transition hover:scale-105"
                >
                    ✨ Get a Quote ✨
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

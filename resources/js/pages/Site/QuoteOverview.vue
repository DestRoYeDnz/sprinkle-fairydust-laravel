<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});
</script>

<template>
    <Head title="Quote Submitted | Sprinkle Fairydust">
        <meta
            head-key="description"
            name="description"
            content="Your Sprinkle Fairydust quote request has been submitted and is being processed."
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
            <h1 class="text-5xl drop-shadow-xl md:text-6xl font-dancing">Quote Submitted</h1>
        </div>

        <section class="overlay-box mx-auto mt-8 max-w-3xl rounded-2xl p-8 text-center backdrop-blur-md fade-in">
            <p class="mb-4 text-xl leading-relaxed text-white/95">
                Your quote has been submitted.
            </p>

            <p class="mb-6 text-lg leading-relaxed text-white/90">
                The Sprinkle Fairydust fairies are processing it and will get back to you soon.
            </p>

            <p class="mb-8 text-base leading-relaxed text-white/80">
                Keep an eye on your inbox while we put together the sparkle.
            </p>

            <div class="flex flex-wrap justify-center gap-3">
                <Link
                    href="/"
                    class="cta rounded-2xl bg-white px-6 py-3 font-bold text-gray-900 shadow-lg transition hover:scale-105"
                >
                    Back to Home
                </Link>
                <Link
                    href="/quote"
                    class="cta cta-secondary rounded-2xl px-6 py-3 font-bold text-white shadow-lg transition hover:scale-105"
                >
                    Submit Another Quote
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

.cta-secondary {
    color: #fff;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.26);
}

.cta-secondary:hover {
    background: rgba(255, 255, 255, 0.22);
}

.text-shadow-strong {
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.7);
}
</style>

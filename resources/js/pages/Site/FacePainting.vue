<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});
</script>

<template>
    <Head title="Face Painting in Auckland | Sprinkle Fairydust">
        <meta
            head-key="description"
            name="description"
            content="Professional kids face painting for birthdays, school fairs, and community events across Auckland and Franklin."
        />
    </Head>

    <main class="mx-auto mt-8 max-w-4xl px-3 pb-12 sm:mt-10 sm:px-4 font-quicksand">
        <section class="landing-shell">
            <h1 class="text-center text-3xl text-slate-900 sm:text-4xl md:text-5xl font-dancing">Face Painting</h1>
            <p class="mt-2 text-center text-sm text-slate-600 sm:text-base">Colourful, photo-friendly designs for birthdays and event activations.</p>

            <div class="mt-6 space-y-3 text-slate-700">
                <p>We offer full-face and quick queue-friendly designs depending on your event pace.</p>
                <p>Popular requests include princess looks, superheroes, animals, butterflies, festival motifs, and custom themes.</p>
                <p>All products are professional cosmetic-grade and applied with hygiene-first brush and sponge rotation.</p>
            </div>

            <div class="mt-8 text-center">
                <Link href="/quote" class="cta-link">Book Face Painting</Link>
            </div>
        </section>
    </main>
</template>

<style scoped>
.landing-shell {
    border-radius: 1.25rem;
    border: 1px solid rgba(148, 163, 184, 0.3);
    background: rgba(248, 250, 252, 0.95);
    box-shadow: 0 18px 30px rgba(15, 23, 42, 0.1);
    padding: 1.5rem;
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
    .landing-shell {
        padding: 1rem;
    }

    .cta-link {
        width: 100%;
    }
}
</style>

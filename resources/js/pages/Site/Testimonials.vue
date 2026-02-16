<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const testimonials = ref([]);
const page = ref(1);
const totalPages = ref(1);
const limit = 5;

const lightbox = ref({
    visible: false,
    currentIndex: 0,
    current: '',
    images: [],
});

function normalizeUrls(input) {
    if (Array.isArray(input)) {
        return input;
    }

    if (typeof input === 'string') {
        try {
            const parsed = JSON.parse(input);
            if (Array.isArray(parsed)) {
                return parsed;
            }
        } catch {
            return input
                .split(',')
                .map((item) => item.trim())
                .filter(Boolean);
        }
    }

    return [];
}

function openLightbox(images, index) {
    lightbox.value.visible = true;
    lightbox.value.images = images;
    lightbox.value.currentIndex = index;
    lightbox.value.current = images[index];
}

function closeLightbox() {
    lightbox.value.visible = false;
}

function nextImage() {
    const total = lightbox.value.images.length;
    lightbox.value.currentIndex = (lightbox.value.currentIndex + 1) % total;
    lightbox.value.current = lightbox.value.images[lightbox.value.currentIndex];
}

function prevImage() {
    const total = lightbox.value.images.length;
    lightbox.value.currentIndex = (lightbox.value.currentIndex - 1 + total) % total;
    lightbox.value.current = lightbox.value.images[lightbox.value.currentIndex];
}

async function loadTestimonials(newPage = 1) {
    try {
        const response = await fetch(`/api/testimonials?page=${newPage}&limit=${limit}`);
        const data = await response.json();

        testimonials.value = Array.isArray(data.testimonials)
            ? data.testimonials.map((item) => ({
                  ...item,
                  urls: normalizeUrls(item.urls),
              }))
            : [];

        page.value = Number(data.page || 1);
        totalPages.value = Number(data.totalPages || 1);
    } catch (error) {
        console.error('Error fetching testimonials:', error);
        testimonials.value = [];
    }
}

function nextPage() {
    if (page.value < totalPages.value) {
        loadTestimonials(page.value + 1);
    }
}

function prevPage() {
    if (page.value > 1) {
        loadTestimonials(page.value - 1);
    }
}

onMounted(() => {
    loadTestimonials();
});
</script>

<template>
    <Head title="Face Painting Reviews | Auckland Kids Parties">
        <meta
            head-key="description"
            name="description"
            content="Read reviews from happy parents and families who booked Sprinkle Fairydust for kids parties in Auckland."
        />
    </Head>

    <a href="https://www.sprinklefairydust.co.nz/">
        <img
            v-if="!lightbox.visible"
            src="/images/logo.png"
            alt="Sprinkle Fairydust Logo"
            class="floating-logo fade-in-logo"
        />
    </a>

    <main class="hero page-content font-quicksand">
        <div class="mt-16 mb-6 text-center text-shadow-strong">
            <h1 class="mb-8 text-5xl text-white drop-shadow-xl md:text-6xl font-dancing">
                🧚‍♀️ Words of Sparkle
            </h1>
            <p class="mt-2 text-lg font-semibold text-white/90 italic md:text-xl">
                Kind words and glittering smiles from our magical guests 💖
            </p>

            <div class="mt-6">
                <Link
                    href="/add-testimonial"
                    class="cta rounded-2xl bg-white px-6 py-3 font-bold text-gray-900 shadow-lg transition hover:scale-105"
                >
                    ✨ Add Your Testimonial
                </Link>
            </div>
        </div>

        <section
            v-if="Array.isArray(testimonials) && testimonials.length"
            class="fade-in max-w-7xl px-6 py-8 md:px-10 lg:px-20"
        >
            <div class="flex flex-col gap-8">
                <blockquote
                    v-for="(item, index) in testimonials"
                    :key="item?.id || index"
                    class="testimonial-card w-full rounded-2xl border border-white/20 bg-black/60 p-6 backdrop-blur-md md:p-8"
                >
                    <p class="mb-3 text-left leading-relaxed text-white/90 italic">
                        “{{ item?.testimonial || 'No testimonial text provided.' }}”
                    </p>
                    <cite class="mb-4 block text-left font-semibold text-yellow-300">
                        {{ item?.name || 'Anonymous' }}
                    </cite>

                    <div
                        v-if="item && Array.isArray(item.urls) && item.urls.length"
                        class="grid grid-cols-1 gap-2 md:grid-cols-3"
                    >
                        <img
                            v-for="(url, idx) in item.urls"
                            :key="idx"
                            :src="url"
                            class="h-64 w-full cursor-pointer rounded-lg object-cover shadow-md transition hover:opacity-90"
                            loading="lazy"
                            @click="openLightbox(item.urls, idx)"
                        />
                    </div>
                </blockquote>
            </div>

            <div class="mt-10 flex items-center justify-center space-x-4">
                <button
                    :disabled="page === 1"
                    class="rainbow-btn rounded-xl px-5 py-2 font-semibold transition disabled:opacity-40"
                    @click="prevPage"
                >
                    ← Prev
                </button>
                <span class="font-semibold text-white/80">Page {{ page }} of {{ totalPages }}</span>
                <button
                    :disabled="page === totalPages"
                    class="rainbow-btn rounded-xl px-5 py-2 font-semibold transition disabled:opacity-40"
                    @click="nextPage"
                >
                    Next →
                </button>
            </div>
        </section>

        <section v-else class="mt-20 text-center text-lg font-semibold text-white/70">
            ✨ No testimonials yet — your sparkle could be the first! ✨
        </section>

        <div v-if="lightbox.visible" class="lightbox show" @click.self="closeLightbox">
            <span class="lightbox-close" @click="closeLightbox">×</span>
            <div class="lightbox-content">
                <img class="lightbox-image" :src="lightbox.current" alt="Large View" />
            </div>

            <div class="lightbox-controls">
                <span class="lightbox-prev" @click.stop="prevImage">❮</span>
                <span class="lightbox-next" @click.stop="nextImage">❯</span>
            </div>
        </div>
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

.fade-in-logo {
    animation: fadeIn 0.8s ease-in-out both;
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

.rainbow-btn {
    background-image: linear-gradient(to right, #ffb3e6, #ffc5d8, #ffd9b3, #d4fcb8, #b3e6ff);
    color: #333;
}

.lightbox {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 120;
}

.lightbox-image {
    max-width: 92vw;
    max-height: 80vh;
    border-radius: 1rem;
    box-shadow: 0 0 30px rgba(255, 255, 255, 0.25);
}

.lightbox-close {
    position: absolute;
    top: 1rem;
    right: 1.25rem;
    font-size: 2rem;
    color: #fff;
    cursor: pointer;
}

.lightbox-controls {
    margin-top: 1rem;
    display: flex;
    gap: 2rem;
    font-size: 2rem;
    color: #fff;
}

.lightbox-prev,
.lightbox-next {
    cursor: pointer;
}

@media (max-width: 768px) {
    section.fade-in {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .testimonial-card {
        padding: 1.25rem;
        font-size: 1rem;
        line-height: 1.6;
        width: 100%;
        max-width: none;
        border-width: 1px;
    }

    .testimonial-card p {
        font-size: 1.05rem;
    }

    .testimonial-card cite {
        font-size: 0.95rem;
    }

    .testimonial-card .grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 0.5rem;
    }
}

@media (min-width: 769px) and (max-width: 1024px) {
    .testimonial-card {
        padding: 1.75rem;
    }
}
</style>

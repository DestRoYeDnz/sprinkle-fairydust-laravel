<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const fallbackImages = [
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706431/P_20251213_162601_py2gzu.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706432/P_20251213_162421_dsqqkq.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706432/P_20251213_162401_e1rdk1.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706431/P_20251213_113630_1_mnvkvc.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706430/P_20251213_114333_ddun4g.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706430/P_20251213_114925_uaulph.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706430/P_20251213_174332_k0gt8f.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706430/P_20251213_115422_o2eo0b.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706429/P_20251213_180005_ksb2bs.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706429/P_20251213_120210_rse1ul.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706428/P_20251213_121146_pzlmu9.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706429/P_20251213_120720_xvlrvv.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706428/P_20251213_123126_fiao74.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706428/P_20251213_124908_je7nu4.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706427/P_20251213_130850_gei6sp.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706427/P_20251213_105236_xt8wqs.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706427/P_20251213_123653_glr4f5.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706427/P_20251213_104904_iz5i2s.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706427/P_20251213_125241_irnbld.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706426/P_20251213_110821_bsxfcb.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706426/P_20251213_155133_sdihdx.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706426/P_20251213_131333_xvw5cb.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706425/P_20251213_162357_k2alfg.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706426/P_20251213_111401_j1aswb.jpg',
    'https://res.cloudinary.com/df2fgdx9y/image/upload/v1765706425/P_20251213_113103_kmrell.jpg',
].map((url, index) => ({
    id: `fallback-${index}`,
    url,
    alt_text: 'Sprinkle Fairydust gallery image',
    title: 'Sprinkle Fairydust Gallery',
    description: '',
}));

const apiImages = ref([]);
const lightboxIndex = ref(-1);
const images = computed(() => (apiImages.value.length ? apiImages.value : fallbackImages));

const currentImage = computed(() =>
    lightboxIndex.value >= 0 ? images.value[lightboxIndex.value] : null,
);

function normalizeImage(imageRecord, index) {
    if (typeof imageRecord === 'string') {
        return {
            id: `fallback-${index}`,
            url: imageRecord,
            alt_text: 'Sprinkle Fairydust gallery image',
            title: 'Sprinkle Fairydust Gallery',
            description: '',
            event: null,
        };
    }

    return {
        id: imageRecord?.id ?? `gallery-image-${index}`,
        url: imageRecord?.url ?? '',
        alt_text: imageRecord?.alt_text || imageRecord?.title || 'Sprinkle Fairydust gallery image',
        title: imageRecord?.title || imageRecord?.alt_text || 'Sprinkle Fairydust Gallery',
        description: imageRecord?.description || '',
        event: imageRecord?.event
            ? {
                id: imageRecord.event.id,
                name: imageRecord.event.name || '',
                type: imageRecord.event.type || '',
                address: imageRecord.event.address || '',
                date: imageRecord.event.date || '',
                start_time: imageRecord.event.start_time || '',
                end_time: imageRecord.event.end_time || '',
                description: imageRecord.event.description || '',
            }
            : null,
    };
}

function parseLocalDate(dateString) {
    if (typeof dateString !== 'string' || dateString.length === 0) {
        return null;
    }

    const [year, month, day] = dateString.split('-').map((value) => Number.parseInt(value, 10));

    return new Date(year, (month || 1) - 1, day || 1);
}

function formatDate(dateString) {
    const date = parseLocalDate(dateString);

    if (!date) {
        return '';
    }

    return date.toLocaleDateString('en-NZ', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}

function formatTime(time) {
    if (!time) {
        return '';
    }

    const normal = String(time).slice(0, 5);
    const date = new Date(`1970-01-01T${normal}:00`);

    return date.toLocaleTimeString('en-NZ', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function isEventImage(imageRecord) {
    return Boolean(imageRecord?.event);
}

function getImageEyebrow(imageRecord) {
    return isEventImage(imageRecord) ? 'Event Highlight' : 'Gallery Image';
}

function getImageTitle(imageRecord) {
    if (isEventImage(imageRecord)) {
        return imageRecord.event.name || `${imageRecord.event.type} Event`;
    }

    return imageRecord?.title || 'Sprinkle Fairydust Gallery';
}

function getImageDescription(imageRecord) {
    if (isEventImage(imageRecord)) {
        return imageRecord?.event?.description || '';
    }

    return imageRecord?.description || '';
}

function getImageAltText(imageRecord) {
    return imageRecord?.alt_text || getImageTitle(imageRecord);
}

function getImageKey(imageRecord, index) {
    return imageRecord?.id ?? imageRecord?.url ?? index;
}

function getImageMeta(imageRecord) {
    if (!isEventImage(imageRecord)) {
        return '';
    }

    const parts = [];

    if (imageRecord.event.type) {
        parts.push(imageRecord.event.type);
    }

    if (imageRecord.event.date) {
        parts.push(formatDate(imageRecord.event.date));
    }

    if (imageRecord.event.start_time) {
        let timeLabel = formatTime(imageRecord.event.start_time);

        if (imageRecord.event.end_time) {
            timeLabel = `${timeLabel} - ${formatTime(imageRecord.event.end_time)}`;
        }

        parts.push(timeLabel);
    }

    if (imageRecord.event.address) {
        parts.push(imageRecord.event.address);
    }

    return parts.filter((value) => value && value.length > 0).join(' · ');
}

function openLightbox(index) {
    lightboxIndex.value = index;
}

function closeLightbox() {
    lightboxIndex.value = -1;
}

function nextImage() {
    if (lightboxIndex.value < 0) {
        return;
    }

    lightboxIndex.value = (lightboxIndex.value + 1) % images.value.length;
}

function prevImage() {
    if (lightboxIndex.value < 0) {
        return;
    }

    lightboxIndex.value = (lightboxIndex.value - 1 + images.value.length) % images.value.length;
}

async function loadImages() {
    try {
        const response = await fetch('/api/gallery-images?collection=gallery');
        const data = await response.json();

        if (Array.isArray(data) && data.length) {
            apiImages.value = data
                .map(normalizeImage)
                .filter((imageRecord) => typeof imageRecord.url === 'string' && imageRecord.url.length > 0);
        }
    } catch {
        apiImages.value = [];
    }
}

onMounted(() => {
    loadImages();
});
</script>

<template>
    <Head title="Kids Face Painting Gallery | South Auckland and Northern Waikato">
        <meta
            head-key="description"
            name="description"
            content="Browse our colourful face painting gallery featuring birthday parties and kids events across South Auckland and Northern Waikato."
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
            <h1 class="text-5xl drop-shadow-xl md:text-6xl font-dancing">Our Gallery</h1>
        </div>

        <p class="tagline mb-8 text-xl text-shadow-strong drop-shadow-md md:text-2xl">
            Magic, colour, and glitter — captured in every brushstroke! ✨
        </p>

        <section
            class="overlay-box mx-auto max-w-5xl rounded-2xl p-8 text-center backdrop-blur-md fade-in"
        >
            <h2 class="text-4xl text-yellow-200 font-dancing">Gallery</h2>
            <p class="mb-8 text-lg text-white/90">
                Here are some magical moments captured by Sprinkle Fairydust! ✨
                Click on any image to see it larger.
            </p>

            <div class="gallery-grid">
                <img
                    v-for="(image, index) in images"
                    :key="getImageKey(image, index)"
                    :src="image.url"
                    :alt="getImageAltText(image)"
                    class="gallery-item"
                    @click="openLightbox(index)"
                />
            </div>
        </section>
    </main>

    <div class="lightbox" :class="{ active: currentImage }" @click.self="closeLightbox">
        <span class="lightbox-close" @click="closeLightbox">×</span>
        <div v-if="currentImage" class="lightbox-stage">
            <img class="lightbox-image" :src="currentImage.url" :alt="getImageAltText(currentImage)" />
            <div class="lightbox-overlay">
                <p class="lightbox-eyebrow">{{ getImageEyebrow(currentImage) }}</p>
                <p class="lightbox-title">{{ getImageTitle(currentImage) }}</p>
                <p v-if="getImageMeta(currentImage)" class="lightbox-meta">
                    {{ getImageMeta(currentImage) }}
                </p>
                <p v-if="getImageDescription(currentImage)" class="lightbox-description">
                    {{ getImageDescription(currentImage) }}
                </p>
            </div>
        </div>
        <div class="lightbox-controls">
            <span class="lightbox-prev" @click.stop="prevImage">❮</span>
            <span class="lightbox-next" @click.stop="nextImage">❯</span>
        </div>
    </div>
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
    padding: 2.5rem;
    color: #fff;
    backdrop-filter: blur(12px);
    transition: transform 0.3s ease;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.gallery-item {
    width: 100%;
    border-radius: 1rem;
    object-fit: cover;
    aspect-ratio: 1 / 1;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.gallery-item:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(255, 255, 255, 0.3);
}

.lightbox {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.85);
    z-index: 100;
    justify-content: center;
    align-items: center;
    flex-direction: column;
}

.lightbox.active {
    display: flex;
}

.lightbox-stage {
    position: relative;
    max-width: min(90vw, 960px);
    max-height: 80vh;
}

.lightbox-image {
    display: block;
    max-width: 100%;
    max-height: 80vh;
    border-radius: 1rem;
    box-shadow: 0 0 30px rgba(255, 255, 255, 0.3);
    transition: transform 0.3s ease;
}

.lightbox-overlay {
    position: absolute;
    right: 0;
    bottom: 0;
    left: 0;
    border-radius: 0 0 1rem 1rem;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0) 0%, rgba(15, 23, 42, 0.8) 42%, rgba(15, 23, 42, 0.96) 100%);
    padding: 1.1rem 1.2rem 1.2rem;
    text-align: left;
}

.lightbox-title {
    color: #fff7ed;
    font-size: 1.35rem;
    font-weight: 700;
    line-height: 1.2;
}

.lightbox-eyebrow {
    color: rgba(254, 240, 138, 0.92);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}

.lightbox-meta {
    margin-top: 0.35rem;
    color: rgba(255, 255, 255, 0.78);
    font-size: 0.82rem;
    line-height: 1.45;
}

.lightbox-description {
    margin-top: 0.45rem;
    color: rgba(255, 255, 255, 0.88);
    font-size: 0.95rem;
    line-height: 1.5;
}

.lightbox-close {
    position: absolute;
    top: 2rem;
    right: 2rem;
    color: #fff;
    font-size: 2rem;
    cursor: pointer;
    transition: opacity 0.3s ease;
}

.lightbox-close:hover {
    opacity: 0.7;
}

.lightbox-controls {
    display: flex;
    justify-content: space-between;
    width: 100px;
    margin-top: 1rem;
}

.lightbox-prev,
.lightbox-next {
    color: #fff;
    font-size: 2rem;
    cursor: pointer;
    user-select: none;
    transition: transform 0.3s ease;
}

.lightbox-prev:hover,
.lightbox-next:hover {
    transform: scale(1.2);
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

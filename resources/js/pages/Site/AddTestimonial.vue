<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const fullName = ref('');
const testimonial = ref('');
const files = ref([]);
const previewUrls = ref([]);
const uploading = ref(0);
const submitting = ref(false);
const message = ref('');
const isSuccess = ref(false);

const messageClass = computed(() =>
    isSuccess.value ? 'text-green-200' : 'text-red-300',
);

function handleFiles(event) {
    const selected = Array.from(event.target.files || []).slice(0, 3);
    files.value = selected;
    previewUrls.value = selected.map((file) => URL.createObjectURL(file));
}

function removePhoto(index) {
    files.value.splice(index, 1);
    previewUrls.value.splice(index, 1);
}

async function uploadAllImages() {
    if (files.value.length === 0) {
        return [];
    }

    const uploadedUrls = [];
    uploading.value = 0;

    for (const file of files.value) {
        uploading.value = uploadedUrls.length + 1;

        const formData = new FormData();
        formData.append('file', file);

        const response = await fetch('/api/testimonials/upload-image', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
            },
            body: formData,
        });

        if (!response.ok) {
            throw new Error(`Image upload failed with status ${response.status}`);
        }

        const data = await response.json();
        if (data.url) {
            uploadedUrls.push(data.url);
        }
    }

    return uploadedUrls;
}

async function submitTestimonial() {
    submitting.value = true;
    message.value = '';

    try {
        const uploadedUrls = await uploadAllImages();
        uploading.value = 0;

        const response = await fetch('/api/testimonials', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                name: fullName.value.trim(),
                testimonial: testimonial.value.trim(),
                urls: uploadedUrls,
            }),
        });

        if (!response.ok) {
            throw new Error(`Server returned ${response.status}`);
        }

        const data = await response.json();

        fullName.value = '';
        testimonial.value = '';
        files.value = [];
        previewUrls.value = [];

        isSuccess.value = true;
        message.value = data.message || 'Thanks! Your testimonial was submitted for approval.';
    } catch (error) {
        console.error('Error:', error);
        isSuccess.value = false;
        message.value = '❌ Oops! Something went wrong — please try again later.';
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <Head title="Leave a Review | Sprinkle Fairydust Face Painting">
        <meta
            head-key="description"
            name="description"
            content="Share your experience with Sprinkle Fairydust and help other Auckland families find a great face painter."
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
                Share Your Experience ✨
            </h1>
            <p class="mt-8 text-lg font-semibold text-white/90 italic md:text-xl">
                We’d love to hear your magical story with
                <span class="text-yellow-300">Sprinkle Fairydust</span> 💖
            </p>
        </div>

        <section
            class="overlay-box mx-auto max-w-2xl rounded-2xl p-10 pr-8 text-left backdrop-blur-md fade-in"
        >
            <p class="mb-8 text-center text-lg leading-relaxed text-white/90">
                Tell us about the sparkle we brought to your event — every story helps us
                spread more joy and glitter! ✨
            </p>

            <form class="space-y-6" @submit.prevent="submitTestimonial">
                <div>
                    <label for="fullName" class="mb-2 block text-sm font-semibold tracking-wide text-white/90"
                        >Your Name</label
                    >
                    <input
                        id="fullName"
                        v-model="fullName"
                        type="text"
                        required
                        placeholder="Enter your full name"
                        class="input-field"
                    />
                </div>

                <div>
                    <label for="testimonial" class="mb-2 block text-sm font-semibold tracking-wide text-white/90"
                        >Your Testimonial</label
                    >
                    <textarea
                        id="testimonial"
                        v-model="testimonial"
                        rows="5"
                        required
                        placeholder="Share your magical experience..."
                        class="input-field resize-none"
                    ></textarea>
                </div>

                <div class="mb-4">
                    <label class="mb-2 block font-semibold text-pink-400">Upload up to 3 photos</label>

                    <label
                        class="block cursor-pointer rounded-xl border border-white/30 bg-white/10 p-6 text-center transition hover:bg-white/20"
                    >
                        <p class="font-semibold text-white">📸 Choose up to 3 photos</p>
                        <input type="file" multiple accept="image/*" class="hidden" @change="handleFiles" />
                    </label>

                    <div class="mt-3 grid grid-cols-3 gap-2">
                        <div v-for="(url, index) in previewUrls" :key="index" class="group relative">
                            <img :src="url" class="h-24 w-full rounded-xl object-cover shadow-md" />
                            <button
                                type="button"
                                class="absolute top-1 right-1 rounded-full bg-white/80 p-1 text-xs transition hover:bg-pink-100"
                                @click="removePhoto(index)"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <p v-if="uploading" class="mt-3 text-sm text-gray-300">
                        Uploading {{ uploading }}/{{ files.length }}…
                    </p>
                </div>

                <div class="mt-8 pt-4 text-center">
                    <button
                        type="submit"
                        class="cta rounded-2xl px-6 py-3 font-bold text-gray-900 shadow-lg transition hover:scale-105"
                        :disabled="submitting || uploading > 0"
                    >
                        <span v-if="submitting">Submitting…</span>
                        <span v-else>✨ Submit Testimonial ✨</span>
                    </button>
                </div>

                <p v-if="message" class="text-shadow-strong mt-4 text-center font-semibold" :class="messageClass">
                    {{ message }}
                </p>
            </form>
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
    border-radius: 1.5rem;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
    color: #fff;
    transition: transform 0.3s ease;
}

.input-field {
    width: 100%;
    padding: 0.9rem 1rem;
    border-radius: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.35);
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    font-size: 1rem;
    box-sizing: border-box;
}

.input-field::placeholder {
    color: rgba(255, 255, 255, 0.75);
}

.input-field:focus {
    border-color: #ffd6f6;
    box-shadow: 0 0 15px rgba(255, 200, 255, 0.6);
    background: rgba(255, 255, 255, 0.25);
    outline: none;
}

.cta {
    background: linear-gradient(90deg, #ffb6ec, #ffe7a1, #ffd6f6);
    color: #333;
    font-weight: bold;
    font-size: 1.1rem;
    padding: 0.9rem 2.5rem;
    border-radius: 2rem;
    box-shadow: 0 0 20px rgba(255, 255, 255, 0.35);
    transition: all 0.3s ease-in-out;
    border: none;
}

.cta:hover {
    transform: scale(1.07);
    box-shadow: 0 0 35px rgba(255, 255, 255, 0.7);
    background: linear-gradient(90deg, #ffe3f3, #fff2cc, #ffd6f6);
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
}
</style>

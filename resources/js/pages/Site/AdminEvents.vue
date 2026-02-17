<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminMenu from '@/components/admin/AdminMenu.vue';
import { appendCsrfToken, csrfHeaders, fetchWithCsrfRetry, withCsrfToken } from '@/lib/csrf';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const saved = ref(false);
const saveError = ref('');
const saving = ref(false);
const imageFile = ref(null);
const uploadingImage = ref(false);
const imageUploadError = ref('');
const imageUploadSuccess = ref('');

const event = ref({
    name: '',
    type: '',
    address: '',
    date: '',
    start_time: '',
    end_time: '',
    description: '',
    image_url: '',
});

function handleImageFile(eventInput) {
    imageFile.value = eventInput.target.files?.[0] ?? null;
    imageUploadError.value = '';
    imageUploadSuccess.value = '';
}

async function uploadEventImage() {
    if (!imageFile.value) {
        imageUploadError.value = 'Please choose an image first.';
        return;
    }

    uploadingImage.value = true;
    imageUploadError.value = '';
    imageUploadSuccess.value = '';

    try {
        const formData = new FormData();
        formData.append('file', imageFile.value);
        formData.append('collection', 'events');

        if (event.value.name) {
            formData.append('alt_text', `${event.value.name} event image`);
        }

        const response = await fetchWithCsrfRetry('/admin/images/upload', {
            method: 'POST',
            credentials: 'same-origin',
            headers: csrfHeaders(false),
            body: appendCsrfToken(formData),
        });

        const data = await response.json();

        if (!response.ok || data.error) {
            imageUploadError.value = data.message || data.error || 'Image upload failed';
            return;
        }

        event.value.image_url = data.url;
        imageUploadSuccess.value = 'Image uploaded successfully.';
        imageFile.value = null;
    } catch {
        imageUploadError.value = 'Image upload failed';
    } finally {
        uploadingImage.value = false;
    }
}

async function saveEvent() {
    saving.value = true;
    saveError.value = '';

    try {
        const response = await fetchWithCsrfRetry('/admin/events', {
            method: 'POST',
            credentials: 'same-origin',
            headers: csrfHeaders(),
            body: JSON.stringify(withCsrfToken(event.value)),
        });

        const data = await response.json();

        if (!response.ok) {
            saved.value = false;
            saveError.value = data.message || data.error || 'Failed to save event';
            return;
        }

        saved.value = true;
        imageUploadError.value = '';
        imageUploadSuccess.value = '';
        imageFile.value = null;
        event.value = {
            name: '',
            type: '',
            address: '',
            date: '',
            start_time: '',
            end_time: '',
            description: '',
            image_url: '',
        };
    } catch {
        saved.value = false;
        saveError.value = 'Failed to save event';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <Head title="Add Event | Admin" />

    <main class="mx-auto mt-10 max-w-4xl px-4 pb-10 font-quicksand">
        <section class="rounded-3xl border border-slate-200/90 bg-slate-50/90 p-5 shadow-2xl backdrop-blur-md md:p-6">
            <h1 class="mb-2 text-center text-5xl text-slate-900 drop-shadow-sm font-dancing">Add Event</h1>
            <p class="mb-8 text-center text-slate-600">Create a new public event listing.</p>

            <AdminMenu />

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-md">
                <form class="space-y-4" @submit.prevent="saveEvent">
                    <div>
                        <label for="event-name" class="mb-1 block text-sm font-semibold text-slate-800">Event Name</label>
                        <input id="event-name" v-model="event.name" placeholder="Event Name" class="input" required />
                    </div>

                    <div>
                        <label for="event-type" class="mb-1 block text-sm font-semibold text-slate-800">Event Type</label>
                        <select id="event-type" v-model="event.type" class="input" required>
                            <option disabled value="">Select Type</option>
                            <option>Festival</option>
                            <option>Market</option>
                            <option>Private</option>
                        </select>
                    </div>

                    <div>
                        <label for="event-address" class="mb-1 block text-sm font-semibold text-slate-800">
                            Address (optional for Private events)
                        </label>
                        <input id="event-address" v-model="event.address" placeholder="Address" class="input" />
                    </div>

                    <div>
                        <label for="event-date" class="mb-1 block text-sm font-semibold text-slate-800">Date</label>
                        <input id="event-date" v-model="event.date" type="date" class="input" required />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="event-start" class="mb-1 block text-sm font-semibold text-slate-800">Start Time</label>
                            <input id="event-start" v-model="event.start_time" type="time" class="input" required />
                        </div>

                        <div>
                            <label for="event-end" class="mb-1 block text-sm font-semibold text-slate-800">End Time</label>
                            <input id="event-end" v-model="event.end_time" type="time" class="input" required />
                        </div>
                    </div>

                    <div>
                        <label for="event-description" class="mb-1 block text-sm font-semibold text-slate-800">Description</label>
                        <textarea
                            id="event-description"
                            v-model="event.description"
                            rows="4"
                            placeholder="Description"
                            class="input"
                        ></textarea>
                    </div>

                    <div>
                        <label for="event-image-file" class="mb-1 block text-sm font-semibold text-slate-800">Event Image Upload</label>
                        <div class="grid gap-3 sm:grid-cols-[1fr_auto]">
                            <input
                                id="event-image-file"
                                type="file"
                                accept="image/*"
                                class="input file-input"
                                @change="handleImageFile"
                            />
                            <button
                                type="button"
                                class="secondary-btn"
                                :disabled="uploadingImage || !imageFile"
                                @click="uploadEventImage"
                            >
                                {{ uploadingImage ? 'Uploading...' : 'Upload Image' }}
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Upload stores locally and auto-fills the image URL below.</p>
                        <p v-if="imageUploadSuccess" class="mt-1 text-sm font-semibold text-emerald-700">{{ imageUploadSuccess }}</p>
                        <p v-if="imageUploadError" class="mt-1 text-sm font-semibold text-rose-700">{{ imageUploadError }}</p>
                    </div>

                    <div>
                        <label for="event-image-url" class="mb-1 block text-sm font-semibold text-slate-800">Image URL</label>
                        <input id="event-image-url" v-model="event.image_url" placeholder="https://..." class="input" />
                        <a
                            v-if="event.image_url"
                            :href="event.image_url"
                            target="_blank"
                            rel="noopener"
                            class="mt-2 inline-block text-sm font-semibold text-sky-700 underline"
                        >
                            Preview image
                        </a>
                    </div>

                    <button
                        class="primary-btn"
                        :disabled="saving"
                    >
                        {{ saving ? 'Saving...' : 'Save Event' }}
                    </button>

                    <p v-if="saved" class="font-semibold text-emerald-700">Event saved successfully.</p>
                    <p v-if="saveError" class="font-semibold text-rose-700">{{ saveError }}</p>
                </form>
            </section>
        </section>
    </main>
</template>

<style scoped>
.input {
    width: 100%;
    border-radius: 0.75rem;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #0f172a;
    padding: 0.65rem 0.8rem;
}

.file-input {
    padding: 0.45rem 0.55rem;
}

.input:focus {
    outline: none;
    border-color: #38bdf8;
    box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.18);
}

.primary-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    border: 1px solid rgba(224, 247, 255, 0.95);
    background: linear-gradient(135deg, #ecfeff, #bae6fd);
    color: #0f172a;
    font-weight: 700;
    padding: 0.65rem 1rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    box-shadow: 0 8px 16px rgba(2, 6, 23, 0.12);
}

.primary-btn:hover {
    transform: translateY(-1px);
}

.primary-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.secondary-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #334155;
    font-weight: 700;
    padding: 0.65rem 1rem;
    transition: background 0.2s ease, border-color 0.2s ease;
}

.secondary-btn:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}

.secondary-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

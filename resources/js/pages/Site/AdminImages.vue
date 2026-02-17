<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminMenu from '@/components/admin/AdminMenu.vue';
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue';
import { appendCsrfToken, csrfHeaders, fetchWithCsrfRetry } from '@/lib/csrf';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const file = ref(null);
const imageUrl = ref('');
const uploadError = ref('');
const uploading = ref(false);
const collection = ref('gallery');
const altText = ref('');
const showMissingFileDialog = ref(false);

function handleFile(eventInput) {
    file.value = eventInput.target.files?.[0] ?? null;
}

async function uploadImage() {
    if (!file.value) {
        showMissingFileDialog.value = true;
        return;
    }

    uploading.value = true;
    uploadError.value = '';

    try {
        const formData = new FormData();
        formData.append('file', file.value);
        formData.append('collection', collection.value);

        if (altText.value.trim()) {
            formData.append('alt_text', altText.value.trim());
        }

        const response = await fetchWithCsrfRetry('/admin/images/upload', {
            method: 'POST',
            credentials: 'same-origin',
            headers: csrfHeaders(false),
            body: appendCsrfToken(formData),
        });

        const data = await response.json();

        if (!response.ok || data.error) {
            uploadError.value = data.message || data.error || 'Upload failed';
            return;
        }

        imageUrl.value = data.url;
        altText.value = '';
        file.value = null;
    } catch {
        uploadError.value = 'Upload failed';
    } finally {
        uploading.value = false;
    }
}
</script>

<template>
    <Head title="Upload Image | Admin" />

    <main class="mx-auto mt-10 max-w-4xl px-4 pb-10 font-quicksand">
        <section class="rounded-3xl border border-slate-200/90 bg-slate-50/90 p-5 shadow-2xl backdrop-blur-md md:p-6">
            <h1 class="mb-2 text-center text-5xl text-slate-900 drop-shadow-sm font-dancing">Upload Image</h1>
            <p class="mb-8 text-center text-slate-600">Upload an image and save it to Gallery or Designs.</p>

            <AdminMenu />

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-md">
                <form class="space-y-4" @submit.prevent="uploadImage">
                    <div>
                        <label for="image-collection" class="mb-1 block text-sm font-semibold text-slate-800">Collection</label>
                        <select id="image-collection" v-model="collection" class="input" required>
                            <option value="gallery">Gallery</option>
                            <option value="designs">Designs</option>
                        </select>
                    </div>

                    <div>
                        <label for="image-alt-text" class="mb-1 block text-sm font-semibold text-slate-800">
                            Alt text (optional)
                        </label>
                        <input
                            id="image-alt-text"
                            v-model="altText"
                            type="text"
                            class="input"
                            maxlength="255"
                            placeholder="Short description for accessibility"
                        />
                    </div>

                    <div>
                        <label for="image-file" class="mb-1 block text-sm font-semibold text-slate-800">Image File</label>
                        <input id="image-file" type="file" accept="image/*" class="input file-input" @change="handleFile" required />
                    </div>

                    <button class="primary-btn" :disabled="uploading">
                        {{ uploading ? 'Uploading...' : 'Upload Image' }}
                    </button>

                    <p v-if="imageUrl" class="font-semibold text-emerald-700">
                        Uploaded successfully:
                        <a :href="imageUrl" target="_blank" rel="noopener" class="font-bold text-sky-700 underline">
                            {{ imageUrl }}
                        </a>
                    </p>

                    <p v-if="uploadError" class="font-semibold text-rose-700">{{ uploadError }}</p>
                </form>
            </section>
        </section>
    </main>

    <ConfirmDialog
        v-model:open="showMissingFileDialog"
        title="Image Required"
        message="Please choose an image file before uploading."
        confirm-text="OK"
        cancel-text=""
    />
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
</style>

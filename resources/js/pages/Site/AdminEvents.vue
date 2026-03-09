<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import AdminMenu from '@/components/admin/AdminMenu.vue';
import { adminIndex as listEvents, store as createEvent } from '@/actions/App/Http/Controllers/Api/EventController';
import { store as uploadAdminImage } from '@/actions/App/Http/Controllers/Api/AdminUploadController';
import { appendCsrfToken, csrfHeaders, fetchWithCsrfRetry, withCsrfToken } from '@/lib/csrf';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const saved = ref(false);
const saveError = ref('');
const saving = ref(false);
const loadingEvents = ref(false);
const eventsError = ref('');
const imageFile = ref(null);
const uploadingImage = ref(false);
const imageUploadError = ref('');
const imageUploadSuccess = ref('');
const events = ref([]);
const eventPhotoFiles = ref({});
const eventPhotoInputKeys = ref({});
const eventPhotoStatuses = ref({});

const event = ref({
    name: '',
    type: '',
    visibility: 'public',
    address: '',
    date: '',
    start_time: '',
    end_time: '',
    description: '',
    image_url: '',
});

const allTimes = [
    '07:00',
    '07:30',
    '08:00',
    '08:30',
    '09:00',
    '09:30',
    '10:00',
    '10:30',
    '11:00',
    '11:30',
    '12:00',
    '12:30',
    '13:00',
    '13:30',
    '14:00',
    '14:30',
    '15:00',
    '15:30',
    '16:00',
    '16:30',
    '17:00',
    '17:30',
    '18:00',
    '18:30',
    '19:00',
    '19:30',
    '20:00',
    '20:30',
    '21:00',
];

const futureEvents = computed(() =>
    [...events.value]
        .filter((eventRecord) => !isPastEvent(eventRecord))
        .sort(sortEventsAscending),
);

const pastEvents = computed(() =>
    [...events.value]
        .filter((eventRecord) => isPastEvent(eventRecord))
        .sort(sortEventsDescending),
);

const endTimeOptions = computed(() => {
    if (!event.value.start_time) {
        return [];
    }

    const startIndex = allTimes.indexOf(event.value.start_time);
    const minEndIndex = startIndex + 2;

    return allTimes.slice(minEndIndex);
});

function parseLocalDate(dateString) {
    if (typeof dateString !== 'string' || dateString.length === 0) {
        return new Date(0);
    }

    const [year, month, day] = dateString.split('-').map((value) => Number.parseInt(value, 10));

    return new Date(year, (month || 1) - 1, day || 1);
}

function toMinutes(time) {
    if (typeof time !== 'string' || time.length < 5) {
        return 0;
    }

    const [hours, minutes] = time.slice(0, 5).split(':').map((value) => Number.parseInt(value, 10));

    return ((hours || 0) * 60) + (minutes || 0);
}

function isPastEvent(eventRecord) {
    const today = new Date();

    today.setHours(0, 0, 0, 0);

    return parseLocalDate(eventRecord.date).getTime() < today.getTime();
}

function sortEventsAscending(left, right) {
    const dateDifference = parseLocalDate(left.date).getTime() - parseLocalDate(right.date).getTime();

    if (dateDifference !== 0) {
        return dateDifference;
    }

    return toMinutes(left.start_time) - toMinutes(right.start_time);
}

function sortEventsDescending(left, right) {
    return sortEventsAscending(right, left);
}

function displayName(eventRecord) {
    return eventRecord.name || `${eventRecord.type} Event`;
}

function getEventPhotoCount(eventRecord) {
    return eventRecord.photos.length;
}

function getSelectedEventPhotoCount(eventId) {
    return (eventPhotoFiles.value[eventId] ?? []).length;
}

function formatDate(date) {
    return parseLocalDate(date).toLocaleDateString('en-NZ', {
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

function normalizeEvent(eventRecord) {
    return {
        ...eventRecord,
        visibility: eventRecord.visibility === 'private' ? 'private' : 'public',
        photos: Array.isArray(eventRecord.photos) ? eventRecord.photos.filter((photo) => photo?.url) : [],
    };
}

function formatVisibility(visibility) {
    return visibility === 'private' ? 'Private' : 'Public';
}

function getEventPhotoStatus(eventId) {
    return eventPhotoStatuses.value[eventId] ?? {
        uploading: false,
        error: '',
        success: '',
    };
}

function setEventPhotoStatus(eventId, status) {
    eventPhotoStatuses.value = {
        ...eventPhotoStatuses.value,
        [eventId]: {
            ...getEventPhotoStatus(eventId),
            ...status,
        },
    };
}

function handleImageFile(eventInput) {
    imageFile.value = eventInput.target.files?.[0] ?? null;
    imageUploadError.value = '';
    imageUploadSuccess.value = '';
}

function handlePastEventPhotoFile(eventId, eventInput) {
    eventPhotoFiles.value = {
        ...eventPhotoFiles.value,
        [eventId]: Array.from(eventInput.target.files ?? []),
    };

    setEventPhotoStatus(eventId, {
        error: '',
        success: '',
    });
}

async function loadEvents() {
    loadingEvents.value = true;
    eventsError.value = '';

    try {
        const response = await fetch(listEvents.url(), {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const data = await response.json();

        if (!response.ok || !Array.isArray(data)) {
            events.value = [];
            eventsError.value = 'Failed to load events.';
            return;
        }

        events.value = data.map(normalizeEvent);
    } catch {
        events.value = [];
        eventsError.value = 'Failed to load events.';
    } finally {
        loadingEvents.value = false;
    }
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
            formData.append('alt_text', `${event.value.name} event banner`);
        }

        const response = await fetchWithCsrfRetry(uploadAdminImage.url(), {
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
        imageUploadSuccess.value = 'Banner image uploaded successfully.';
        imageFile.value = null;
    } catch {
        imageUploadError.value = 'Image upload failed';
    } finally {
        uploadingImage.value = false;
    }
}

async function uploadPastEventPhoto(eventRecord) {
    const files = eventPhotoFiles.value[eventRecord.id] ?? [];

    if (files.length === 0) {
        setEventPhotoStatus(eventRecord.id, {
            error: 'Please choose one or more images first.',
            success: '',
        });

        return;
    }

    setEventPhotoStatus(eventRecord.id, {
        uploading: true,
        error: '',
        success: '',
    });

    try {
        let successCount = 0;
        let failureCount = 0;
        let firstErrorMessage = '';

        for (const file of files) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('collection', 'events');
            formData.append('event_id', String(eventRecord.id));
            formData.append('alt_text', `${displayName(eventRecord)} event photo`);

            const response = await fetchWithCsrfRetry(uploadAdminImage.url(), {
                method: 'POST',
                credentials: 'same-origin',
                headers: csrfHeaders(false),
                body: appendCsrfToken(formData),
            });

            const data = await response.json();

            if (!response.ok || data.error) {
                failureCount += 1;
                firstErrorMessage ||= data.message || data.error || 'Photo upload failed.';
                continue;
            }

            successCount += 1;
        }

        if (successCount === 0) {
            setEventPhotoStatus(eventRecord.id, {
                uploading: false,
                error: firstErrorMessage || 'Photo upload failed.',
                success: '',
            });

            return;
        }

        await loadEvents();

        eventPhotoFiles.value = {
            ...eventPhotoFiles.value,
            [eventRecord.id]: [],
        };
        eventPhotoInputKeys.value = {
            ...eventPhotoInputKeys.value,
            [eventRecord.id]: (eventPhotoInputKeys.value[eventRecord.id] ?? 0) + 1,
        };

        setEventPhotoStatus(eventRecord.id, {
            uploading: false,
            error: failureCount > 0 ? `${failureCount} image${failureCount === 1 ? '' : 's'} failed to upload.` : '',
            success: `${successCount} event photo${successCount === 1 ? '' : 's'} uploaded to this event.`,
        });
    } catch {
        setEventPhotoStatus(eventRecord.id, {
            uploading: false,
            error: 'Photo upload failed.',
            success: '',
        });
    }
}

async function saveEvent() {
    saving.value = true;
    saveError.value = '';

    try {
        const response = await fetchWithCsrfRetry(createEvent.url(), {
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
            visibility: 'public',
            address: '',
            date: '',
            start_time: '',
            end_time: '',
            description: '',
            image_url: '',
        };

        await loadEvents();
    } catch {
        saved.value = false;
        saveError.value = 'Failed to save event';
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    loadEvents();
});

watch(
    () => event.value.start_time,
    (startTime) => {
        if (!startTime) {
            event.value.end_time = '';

            return;
        }

        if (!endTimeOptions.value.includes(event.value.end_time)) {
            event.value.end_time = '';
        }
    },
);
</script>

<template>
    <Head title="Manage Events | Admin" />

    <main class="mx-auto mt-10 max-w-6xl px-4 pb-10 font-quicksand">
        <section class="rounded-3xl border border-slate-200/90 bg-slate-50/90 p-5 shadow-2xl backdrop-blur-md md:p-6">
            <h1 class="mb-2 text-center text-5xl text-slate-900 drop-shadow-sm font-dancing">Manage Events</h1>
            <p class="mb-8 text-center text-slate-600">
                Create upcoming events, then add extra photos to completed events.
            </p>

            <AdminMenu />

            <div class="space-y-8">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-md">
                    <div class="mb-5">
                        <h2 class="text-3xl text-slate-900 font-dancing">Add Event</h2>
                        <p class="text-sm text-slate-600">Create a new public or private event listing.</p>
                    </div>

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
                                <option>Party</option>
                            </select>
                        </div>

                        <div>
                            <label for="event-visibility" class="mb-1 block text-sm font-semibold text-slate-800">Event Visibility</label>
                            <select id="event-visibility" v-model="event.visibility" class="input" required>
                                <option value="public">Public</option>
                                <option value="private">Private</option>
                            </select>
                        </div>

                        <div>
                            <label for="event-address" class="mb-1 block text-sm font-semibold text-slate-800">Address (optional)</label>
                            <input id="event-address" v-model="event.address" placeholder="Address" class="input" />
                        </div>

                        <div>
                            <label for="event-date" class="mb-1 block text-sm font-semibold text-slate-800">Date</label>
                            <input id="event-date" v-model="event.date" type="date" class="input" required />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="event-start" class="mb-1 block text-sm font-semibold text-slate-800">Start Time</label>
                                <select id="event-start" v-model="event.start_time" class="input" required>
                                    <option disabled value="">Select start time</option>
                                    <option v-for="time in allTimes" :key="time" :value="time">{{ time }}</option>
                                </select>
                            </div>

                            <div>
                                <label for="event-end" class="mb-1 block text-sm font-semibold text-slate-800">End Time</label>
                                <select
                                    id="event-end"
                                    v-model="event.end_time"
                                    class="input"
                                    :disabled="!event.start_time"
                                    required
                                >
                                    <option disabled value="">Select end time</option>
                                    <option v-for="time in endTimeOptions" :key="time" :value="time">{{ time }}</option>
                                </select>
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
                            <label for="event-image-file" class="mb-1 block text-sm font-semibold text-slate-800">Event Banner Upload</label>
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
                            <p class="mt-1 text-xs text-slate-500">Upload stores locally and uses that image as the event banner.</p>
                            <p v-if="imageUploadSuccess" class="mt-1 text-sm font-semibold text-emerald-700">{{ imageUploadSuccess }}</p>
                            <p v-if="imageUploadError" class="mt-1 text-sm font-semibold text-rose-700">{{ imageUploadError }}</p>

                            <div v-if="event.image_url" class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-semibold text-slate-800">Banner preview</p>
                                <img
                                    :src="event.image_url"
                                    :alt="event.name ? `${event.name} event banner preview` : 'Event banner preview'"
                                    class="mt-3 h-44 w-full rounded-2xl object-cover shadow-sm"
                                />
                            </div>
                        </div>

                        <button class="primary-btn" :disabled="saving">
                            {{ saving ? 'Saving...' : 'Save Event' }}
                        </button>

                        <p v-if="saved" class="font-semibold text-emerald-700">Event saved successfully.</p>
                        <p v-if="saveError" class="font-semibold text-rose-700">{{ saveError }}</p>
                    </form>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-md">
                    <div class="mb-5 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                        <div>
                            <h2 class="text-3xl text-slate-900 font-dancing">Event Timeline</h2>
                            <p class="text-sm text-slate-600">
                                Past events can accept photo uploads so you can keep each event filled out with post-event images.
                            </p>
                        </div>
                        <button type="button" class="secondary-btn" :disabled="loadingEvents" @click="loadEvents">
                            {{ loadingEvents ? 'Refreshing...' : 'Refresh Events' }}
                        </button>
                    </div>

                    <p v-if="eventsError" class="mb-4 font-semibold text-rose-700">{{ eventsError }}</p>

                    <div class="grid gap-6 xl:grid-cols-2">
                        <section class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-2xl text-slate-900 font-dancing">Future Events</h3>
                                <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold tracking-[0.2em] text-sky-700 uppercase">
                                    {{ futureEvents.length }}
                                </span>
                            </div>

                            <article
                                v-for="eventRecord in futureEvents"
                                :key="eventRecord.id"
                                class="event-card"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-2xl text-slate-900 font-dancing">{{ displayName(eventRecord) }}</p>
                                        <p class="text-sm font-semibold text-slate-500 uppercase tracking-[0.2em]">
                                            {{ eventRecord.type }} · {{ formatVisibility(eventRecord.visibility) }}
                                        </p>
                                    </div>
                                    <span class="status-pill status-pill-future">Upcoming</span>
                                </div>

                                <p class="mt-3 text-sm font-semibold text-slate-700">{{ formatDate(eventRecord.date) }}</p>
                                <p class="text-sm text-slate-600">
                                    {{ formatTime(eventRecord.start_time) }}
                                    <span v-if="eventRecord.end_time">- {{ formatTime(eventRecord.end_time) }}</span>
                                </p>
                                <p v-if="eventRecord.address" class="mt-1 text-sm text-slate-600">{{ eventRecord.address }}</p>
                                <p v-if="eventRecord.description" class="mt-3 text-sm leading-relaxed text-slate-700">
                                    {{ eventRecord.description }}
                                </p>

                                <img
                                    v-if="eventRecord.image_url"
                                    :src="eventRecord.image_url"
                                    :alt="`${displayName(eventRecord)} event banner`"
                                    class="mt-4 h-44 w-full rounded-2xl object-cover shadow-sm"
                                />

                                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm font-semibold text-slate-800">Post-event photos</p>
                                    <p class="mt-1 text-sm text-slate-600">
                                        Additional photos can be uploaded once the event has passed.
                                    </p>
                                    <div v-if="getEventPhotoCount(eventRecord)" class="mt-4 grid grid-cols-3 gap-3">
                                        <img
                                            v-for="photo in eventRecord.photos"
                                            :key="photo.id"
                                            :src="photo.url"
                                            :alt="photo.alt_text || `${displayName(eventRecord)} event photo`"
                                            class="h-24 w-full rounded-xl object-cover shadow-sm"
                                        />
                                    </div>
                                </div>
                            </article>

                            <div v-if="!loadingEvents && futureEvents.length === 0" class="empty-state">
                                No future events yet.
                            </div>
                        </section>

                        <section class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-2xl text-slate-900 font-dancing">Past Events</h3>
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold tracking-[0.2em] text-amber-700 uppercase">
                                    {{ pastEvents.length }}
                                </span>
                            </div>

                            <article
                                v-for="eventRecord in pastEvents"
                                :key="eventRecord.id"
                                class="event-card"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-2xl text-slate-900 font-dancing">{{ displayName(eventRecord) }}</p>
                                        <p class="text-sm font-semibold text-slate-500 uppercase tracking-[0.2em]">
                                            {{ eventRecord.type }} · {{ formatVisibility(eventRecord.visibility) }}
                                        </p>
                                    </div>
                                    <span class="status-pill status-pill-past">Completed</span>
                                </div>

                                <p class="mt-3 text-sm font-semibold text-slate-700">{{ formatDate(eventRecord.date) }}</p>
                                <p class="text-sm text-slate-600">
                                    {{ formatTime(eventRecord.start_time) }}
                                    <span v-if="eventRecord.end_time">- {{ formatTime(eventRecord.end_time) }}</span>
                                </p>
                                <p v-if="eventRecord.address" class="mt-1 text-sm text-slate-600">{{ eventRecord.address }}</p>
                                <p v-if="eventRecord.description" class="mt-3 text-sm leading-relaxed text-slate-700">
                                    {{ eventRecord.description }}
                                </p>

                                <img
                                    v-if="eventRecord.image_url"
                                    :src="eventRecord.image_url"
                                    :alt="`${displayName(eventRecord)} event banner`"
                                    class="mt-4 h-44 w-full rounded-2xl object-cover shadow-sm"
                                />

                                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-800">Post-event photos</p>
                                            <p class="mt-1 text-sm text-slate-600">
                                                Upload extra photos from this event after it is finished.
                                            </p>
                                        </div>
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-500">
                                            {{ getEventPhotoCount(eventRecord) }} photos
                                        </span>
                                    </div>

                                    <div class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto]">
                                        <input
                                            :key="eventPhotoInputKeys[eventRecord.id] ?? 0"
                                            :id="`event-photo-${eventRecord.id}`"
                                            type="file"
                                            multiple
                                            accept="image/*"
                                            class="input file-input"
                                            @change="handlePastEventPhotoFile(eventRecord.id, $event)"
                                        />
                                        <button
                                            type="button"
                                            class="secondary-btn"
                                            :disabled="getEventPhotoStatus(eventRecord.id).uploading || !getSelectedEventPhotoCount(eventRecord.id)"
                                            @click="uploadPastEventPhoto(eventRecord)"
                                        >
                                            {{ getEventPhotoStatus(eventRecord.id).uploading ? 'Uploading...' : 'Upload Photos' }}
                                        </button>
                                    </div>

                                    <p v-if="getSelectedEventPhotoCount(eventRecord.id)" class="mt-2 text-sm text-slate-500">
                                        {{ getSelectedEventPhotoCount(eventRecord.id) }} image{{ getSelectedEventPhotoCount(eventRecord.id) === 1 ? '' : 's' }} selected
                                    </p>

                                    <p v-if="getEventPhotoStatus(eventRecord.id).success" class="mt-2 text-sm font-semibold text-emerald-700">
                                        {{ getEventPhotoStatus(eventRecord.id).success }}
                                    </p>
                                    <p v-if="getEventPhotoStatus(eventRecord.id).error" class="mt-2 text-sm font-semibold text-rose-700">
                                        {{ getEventPhotoStatus(eventRecord.id).error }}
                                    </p>

                                    <div v-if="eventRecord.photos.length" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                        <img
                                            v-for="photo in eventRecord.photos"
                                            :key="photo.id"
                                            :src="photo.url"
                                            :alt="photo.alt_text || `${displayName(eventRecord)} event photo`"
                                            class="h-28 w-full rounded-xl object-cover shadow-sm"
                                        />
                                    </div>
                                    <p v-else class="mt-4 text-sm text-slate-500">No event photos uploaded yet.</p>
                                </div>
                            </article>

                            <div v-if="!loadingEvents && pastEvents.length === 0" class="empty-state">
                                No past events yet.
                            </div>
                        </section>
                    </div>
                </section>
            </div>
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

.primary-btn:disabled,
.secondary-btn:disabled {
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

.event-card {
    border-radius: 1.5rem;
    border: 1px solid #e2e8f0;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98));
    padding: 1.25rem;
    box-shadow: 0 16px 30px rgba(15, 23, 42, 0.08);
}

.status-pill {
    display: inline-flex;
    align-items: center;
    border-radius: 9999px;
    padding: 0.35rem 0.8rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}

.status-pill-future {
    background: #e0f2fe;
    color: #0369a1;
}

.status-pill-past {
    background: #fef3c7;
    color: #b45309;
}

.empty-state {
    border-radius: 1.5rem;
    border: 1px dashed #cbd5e1;
    background: #f8fafc;
    color: #64748b;
    padding: 1.25rem;
    text-align: center;
}
</style>

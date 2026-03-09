<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { index as listEvents } from '@/actions/App/Http/Controllers/Api/EventController';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const events = ref([]);
const selectedEvent = ref(null);
const selectedEvents = ref([]);

const visibleEvents = computed(() =>
    events.value.filter((eventRecord) => eventRecord.visibility !== 'private'),
);

const upcomingGroups = computed(() =>
    groupEventsByDate(visibleEvents.value.filter((eventRecord) => !isPastEvent(eventRecord)), 'asc'),
);

const pastGroups = computed(() =>
    groupEventsByDate(visibleEvents.value.filter((eventRecord) => isPastEvent(eventRecord)), 'desc'),
);

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

function isPastEvent(eventRecord) {
    const today = new Date();

    today.setHours(0, 0, 0, 0);

    return parseLocalDate(eventRecord.date).getTime() < today.getTime();
}

function displayName(eventRecord) {
    return eventRecord.name || `${eventRecord.type} Event`;
}

function getEventBanner(eventRecord) {
    return eventRecord?.image_url ?? '';
}

function getEventPhotos(eventRecord) {
    return Array.isArray(eventRecord?.photos) ? eventRecord.photos.filter((photo) => photo?.url) : [];
}

function groupEventsByDate(items, direction = 'asc') {
    const groups = new Map();

    items.forEach((eventRecord) => {
        const key = eventRecord.date;

        if (!groups.has(key)) {
            groups.set(key, []);
        }

        groups.get(key).push(eventRecord);
    });

    const sortedGroups = Array.from(groups.entries()).map(([key, dayEvents]) => ({
        key,
        items: [...dayEvents].sort(sortEventsAscending),
        date: parseLocalDate(key),
    }));

    return sortedGroups.sort((left, right) => {
        const difference = left.date.getTime() - right.date.getTime();

        return direction === 'desc' ? difference * -1 : difference;
    });
}

function openDay(dayEvents) {
    if (dayEvents.length === 1) {
        openEvent(dayEvents[0]);
        return;
    }

    selectedEvents.value = dayEvents;
}

function openEvent(eventRecord) {
    selectedEvent.value = eventRecord;
    selectedEvents.value = [];
}

function closeEvent() {
    selectedEvent.value = null;
    selectedEvents.value = [];
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

onMounted(async () => {
    const response = await fetch(listEvents.url(), {
        method: 'GET',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    const data = await response.json();

    events.value = Array.isArray(data) ? data.map(normalizeEvent) : [];
});
</script>

<template>
    <Head title="Face Painting Events | South Auckland and Northern Waikato">
        <meta
            head-key="description"
            name="description"
            content="Find upcoming face painting events and browse past event highlights from Sprinkle Fairydust across South Auckland and Northern Waikato."
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
        <div class="mt-16 mb-8 text-center text-shadow-strong">
            <h1 class="text-5xl text-white drop-shadow-xl md:text-6xl font-dancing">
                Events
            </h1>
            <p class="mt-2 text-lg font-semibold text-white/90 italic md:text-xl">
                Upcoming sparkle stops and magical moments from past events.
            </p>
        </div>

        <section class="w-full max-w-6xl space-y-8 px-4 pb-10">
            <section class="event-section">
                <div class="mb-5 text-left">
                    <h2 class="text-4xl text-pink-100 drop-shadow-sm font-dancing">Upcoming Events</h2>
                    <p class="mt-1 text-sm text-white/85">
                        Come find Sprinkle Fairydust at the next public event.
                    </p>
                </div>

                <div v-if="upcomingGroups.length" class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="group in upcomingGroups"
                        :key="group.key"
                        class="event-day-card"
                    >
                        <button
                            class="mb-3 w-full text-left text-2xl text-pink-200 drop-shadow-sm font-dancing"
                            @click="openDay(group.items)"
                        >
                            {{ formatDate(group.key) }}
                        </button>

                        <div class="space-y-3">
                            <div
                                v-for="eventRecord in group.items"
                                :key="eventRecord.id"
                                class="event-summary-card"
                                @click="openEvent(eventRecord)"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-xl text-white font-dancing">
                                        {{ displayName(eventRecord) }}
                                    </p>
                                    <span class="status-pill status-pill-future">Upcoming</span>
                                </div>
                                <p class="text-sm text-white/80">
                                    {{ formatTime(eventRecord.start_time) }}
                                    <span v-if="eventRecord.end_time">- {{ formatTime(eventRecord.end_time) }}</span>
                                </p>
                                <p v-if="eventRecord.address" class="text-xs text-white/70 italic">
                                    {{ eventRecord.address }}
                                </p>
                                <p
                                    v-if="getEventBanner(eventRecord) || getEventPhotos(eventRecord).length"
                                    class="mt-2 text-xs font-semibold text-pink-100/90"
                                >
                                    <span v-if="getEventBanner(eventRecord)">Banner image</span>
                                    <span v-if="getEventBanner(eventRecord) && getEventPhotos(eventRecord).length"> + </span>
                                    <span v-if="getEventPhotos(eventRecord).length">
                                        {{ getEventPhotos(eventRecord).length }} post-event photos
                                    </span>
                                </p>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="empty-copy">
                    No upcoming public events right now.
                </div>
            </section>

            <section class="event-section">
                <div class="mb-5 text-left">
                    <h2 class="text-4xl text-pink-100 drop-shadow-sm font-dancing">Past Events</h2>
                    <p class="mt-1 text-sm text-white/85">
                        Browse recent public events and view photo highlights.
                    </p>
                </div>

                <div v-if="pastGroups.length" class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="group in pastGroups"
                        :key="group.key"
                        class="event-day-card"
                    >
                        <button
                            class="mb-3 w-full text-left text-2xl text-pink-200 drop-shadow-sm font-dancing"
                            @click="openDay(group.items)"
                        >
                            {{ formatDate(group.key) }}
                        </button>

                        <div class="space-y-3">
                            <div
                                v-for="eventRecord in group.items"
                                :key="eventRecord.id"
                                class="event-summary-card"
                                @click="openEvent(eventRecord)"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-xl text-white font-dancing">
                                        {{ displayName(eventRecord) }}
                                    </p>
                                    <span class="status-pill status-pill-past">Past</span>
                                </div>
                                <p class="text-sm text-white/80">
                                    {{ formatTime(eventRecord.start_time) }}
                                    <span v-if="eventRecord.end_time">- {{ formatTime(eventRecord.end_time) }}</span>
                                </p>
                                <p v-if="eventRecord.address" class="text-xs text-white/70 italic">
                                    {{ eventRecord.address }}
                                </p>
                                <p
                                    v-if="getEventBanner(eventRecord) || getEventPhotos(eventRecord).length"
                                    class="mt-2 text-xs font-semibold text-pink-100/90"
                                >
                                    <span v-if="getEventBanner(eventRecord)">Banner image</span>
                                    <span v-if="getEventBanner(eventRecord) && getEventPhotos(eventRecord).length"> + </span>
                                    <span v-if="getEventPhotos(eventRecord).length">
                                        {{ getEventPhotos(eventRecord).length }} post-event photos
                                    </span>
                                </p>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="empty-copy">
                    No past public events to show yet.
                </div>
            </section>
        </section>
    </main>

    <transition name="fade">
        <div
            v-if="selectedEvent"
            class="fixed inset-0 z-[120] flex items-center justify-center bg-black/60 px-4 py-6 backdrop-blur-sm"
            @click.self="closeEvent"
        >
            <div
                class="relative max-h-full w-full max-w-3xl overflow-y-auto rounded-2xl border border-white/20 bg-gradient-to-br from-pink-200/20 via-purple-300/20 to-blue-200/20 p-6 text-center text-white shadow-2xl"
            >
                <button
                    class="absolute top-3 right-3 text-xl text-white/70 hover:text-pink-300"
                    @click="closeEvent"
                >
                    x
                </button>

                <h2 class="mb-2 text-3xl text-pink-200 font-dancing">
                    {{ displayName(selectedEvent) }}
                </h2>
                <p class="mb-2 text-white/80 italic">
                    {{ formatDate(selectedEvent.date) }}
                    <span v-if="selectedEvent.start_time">
                        • {{ formatTime(selectedEvent.start_time) }}
                        <span v-if="selectedEvent.end_time">
                            - {{ formatTime(selectedEvent.end_time) }}
                        </span>
                    </span>
                </p>
                <p v-if="selectedEvent.address" class="mb-4 text-white/90">
                    {{ selectedEvent.address }}
                </p>

                <div v-if="getEventBanner(selectedEvent)" class="mb-5">
                    <p class="mb-2 text-left text-sm font-semibold uppercase tracking-[0.2em] text-pink-100/90">
                        Event banner
                    </p>
                    <img
                        :src="getEventBanner(selectedEvent)"
                        :alt="`${displayName(selectedEvent)} event banner`"
                        class="w-full rounded-xl border border-white/10 shadow-md"
                    />
                </div>

                <div v-if="getEventPhotos(selectedEvent).length" class="mb-5">
                    <p class="mb-2 text-left text-sm font-semibold uppercase tracking-[0.2em] text-pink-100/90">
                        Event photos
                    </p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <img
                            v-for="photo in getEventPhotos(selectedEvent)"
                            :key="photo.id"
                            :src="photo.url"
                            :alt="photo.alt_text || `${displayName(selectedEvent)} event photo`"
                            class="h-44 w-full rounded-xl border border-white/10 object-cover shadow-md"
                        />
                    </div>
                </div>

                <p v-if="selectedEvent.description" class="whitespace-pre-line leading-relaxed text-white/90">
                    {{ selectedEvent.description }}
                </p>

                <div class="mt-6">
                    <button
                        class="rainbow-btn rounded-2xl px-6 py-2 font-bold text-gray-900 shadow-lg transition hover:scale-105"
                        @click="closeEvent"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </transition>

    <transition name="fade">
        <div
            v-if="selectedEvents.length"
            class="fixed inset-0 z-[120] flex items-center justify-center bg-black/60 px-4 py-6 backdrop-blur-sm"
            @click.self="closeEvent"
        >
            <div
                class="relative max-h-full w-full max-w-lg overflow-y-auto rounded-2xl border border-white/20 bg-gradient-to-br from-pink-200/20 via-purple-300/20 to-blue-200/20 p-6 text-center text-white shadow-2xl"
            >
                <button
                    class="absolute top-3 right-3 text-xl text-white/70 hover:text-pink-300"
                    @click="closeEvent"
                >
                    x
                </button>

                <h2 class="mb-4 text-3xl text-pink-200 font-dancing">
                    {{ formatDate(selectedEvents[0].date) }}
                </h2>

                <div
                    v-for="eventRecord in selectedEvents"
                    :key="eventRecord.id"
                    class="mb-3 cursor-pointer rounded-xl bg-[rgba(255,255,255,0.1)] p-3 text-left shadow-md transition hover:bg-[rgba(255,255,255,0.2)]"
                    @click="openEvent(eventRecord)"
                >
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-xl text-pink-100 font-dancing">
                            {{ displayName(eventRecord) }}
                        </p>
                        <span
                            class="status-pill"
                            :class="isPastEvent(eventRecord) ? 'status-pill-past' : 'status-pill-future'"
                        >
                            {{ isPastEvent(eventRecord) ? 'Past' : 'Upcoming' }}
                        </span>
                    </div>
                    <p class="text-sm text-white/80">
                        {{ formatTime(eventRecord.start_time) }}
                        <span v-if="eventRecord.end_time">- {{ formatTime(eventRecord.end_time) }}</span>
                    </p>
                    <p v-if="eventRecord.address" class="text-xs text-white/70 italic">
                        {{ eventRecord.address }}
                    </p>
                </div>
            </div>
        </div>
    </transition>
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

.event-section {
    border-radius: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.16);
    background: rgba(15, 23, 42, 0.28);
    padding: 1.5rem;
    box-shadow: 0 18px 30px rgba(15, 23, 42, 0.18);
    backdrop-filter: blur(14px);
}

.event-day-card {
    border-radius: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: linear-gradient(to bottom right, rgba(244, 114, 182, 0.2), rgba(192, 132, 252, 0.18), rgba(96, 165, 250, 0.15));
    padding: 1.25rem;
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.18);
}

.event-summary-card {
    cursor: pointer;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.12);
    padding: 0.85rem;
    transition: background 0.2s ease, transform 0.2s ease;
}

.event-summary-card:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-1px);
}

.empty-copy {
    border-radius: 1.5rem;
    border: 1px dashed rgba(255, 255, 255, 0.22);
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.82);
    padding: 1.25rem;
    text-align: center;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    border-radius: 9999px;
    padding: 0.2rem 0.65rem;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.16em;
    text-transform: uppercase;
}

.status-pill-future {
    background: rgba(224, 242, 254, 0.92);
    color: #0c4a6e;
}

.status-pill-past {
    background: rgba(254, 243, 199, 0.92);
    color: #92400e;
}

.text-shadow-strong {
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.7);
}

.rainbow-btn {
    background: rgba(255, 255, 255, 0.92);
    border: 1px solid rgba(255, 255, 255, 0.45);
    color: #333;
    transition: transform 0.2s ease-in-out;
}

.rainbow-btn:hover {
    transform: scale(1.05);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const events = ref([]);
const selectedEvent = ref(null);
const selectedEvents = ref([]);

const visibleEvents = computed(() =>
    events.value.filter((event) => event.type !== 'Private'),
);

const groupedEvents = computed(() => {
    const groups = new Map();

    visibleEvents.value.forEach((event) => {
        const key = new Date(event.date).toDateString();
        if (!groups.has(key)) {
            groups.set(key, []);
        }

        groups.get(key).push(event);
    });

    return Array.from(groups.entries())
        .map(([key, items]) => ({
            key,
            items,
            date: new Date(items[0].date),
        }))
        .sort((a, b) => a.date - b.date);
});

function openDay(dayEvents) {
    if (dayEvents.length === 1) {
        openEvent(dayEvents[0]);
        return;
    }

    selectedEvents.value = dayEvents;
}

function openEvent(event) {
    selectedEvent.value = event;
    selectedEvents.value = [];
}

function closeEvent() {
    selectedEvent.value = null;
    selectedEvents.value = [];
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-NZ', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
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

onMounted(async () => {
    const response = await fetch('/api/events');
    const data = await response.json();

    data.forEach((event) => {
        if (event.type !== 'Private' && !event.name) {
            event.name = `${event.type} Event`;
        }
    });

    events.value = data;
});
</script>

<template>
    <Head title="Upcoming Face Painting Events | Auckland">
        <meta
            head-key="description"
            name="description"
            content="Find upcoming face painting events and public appearances by Sprinkle Fairydust across Auckland."
        />
    </Head>

    <header
        class="relative z-30 flex flex-col px-6 py-4 md:flex-row md:items-center md:justify-between md:px-12"
    >
        <Link href="/" class="flex justify-center md:justify-start">
            <img
                src="/images/logo.png"
                alt="Sprinkle Fairydust Logo"
                class="w-44 drop-shadow-lg transition-transform duration-300 hover:scale-105 md:w-56"
            />
        </Link>
    </header>

    <main class="hero page-content font-quicksand">
        <div class="mt-8 mb-6 text-center text-shadow-strong md:mt-16">
            <h1 class="text-5xl text-white drop-shadow-xl md:text-6xl font-dancing">
                Upcoming Events ✨
            </h1>
            <p class="mt-2 text-lg font-semibold text-white/90 italic md:text-xl">
                Where the magic happens — come find us at our next sparkle stop! 💖
            </p>
        </div>

        <section class="w-full max-w-6xl px-4 pb-10">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3" v-if="groupedEvents.length">
                <article
                    v-for="group in groupedEvents"
                    :key="group.key"
                    class="rounded-2xl border border-white/10 bg-gradient-to-br from-pink-200/30 via-purple-300/30 to-blue-300/20 p-5 shadow-lg"
                >
                    <button
                        class="mb-3 w-full text-left text-2xl text-pink-200 drop-shadow-sm font-dancing"
                        @click="openDay(group.items)"
                    >
                        {{ formatDate(group.date) }}
                    </button>

                    <div class="space-y-3">
                        <div
                            v-for="event in group.items"
                            :key="event.id"
                            class="cursor-pointer rounded-xl bg-[rgba(255,255,255,0.12)] p-3 transition hover:bg-[rgba(255,255,255,0.2)]"
                            @click="openEvent(event)"
                        >
                            <p class="text-xl text-white font-dancing">
                                {{ event.name || event.type }}
                            </p>
                            <p class="text-sm text-white/80">
                                {{ formatTime(event.start_time) }}
                                <span v-if="event.end_time">– {{ formatTime(event.end_time) }}</span>
                            </p>
                            <p v-if="event.address" class="text-xs text-white/70 italic">
                                📍 {{ event.address }}
                            </p>
                        </div>
                    </div>
                </article>
            </div>

            <div v-else class="mt-8 text-center text-white/80">
                No upcoming public events right now.
            </div>
        </section>

        <transition name="fade">
            <div
                v-if="selectedEvent"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm"
                @click.self="closeEvent"
            >
                <div
                    class="relative w-[90%] max-w-lg rounded-2xl border border-white/20 bg-gradient-to-br from-pink-200/20 via-purple-300/20 to-blue-200/20 p-6 text-center text-white shadow-2xl"
                >
                    <button
                        class="absolute top-3 right-3 text-xl text-white/70 hover:text-pink-300"
                        @click="closeEvent"
                    >
                        ✕
                    </button>

                    <h2 class="mb-2 text-3xl text-pink-200 font-dancing">
                        {{ selectedEvent.name || selectedEvent.type }}
                    </h2>
                    <p class="mb-2 text-white/80 italic">
                        {{ formatDate(selectedEvent.date) }}
                        <span v-if="selectedEvent.start_time">
                            • {{ formatTime(selectedEvent.start_time) }}
                            <span v-if="selectedEvent.end_time">
                                – {{ formatTime(selectedEvent.end_time) }}
                            </span>
                        </span>
                    </p>
                    <p v-if="selectedEvent.address" class="mb-4 text-white/90">
                        📍 {{ selectedEvent.address }}
                    </p>

                    <img
                        v-if="selectedEvent.image_url"
                        :src="selectedEvent.image_url"
                        alt="Event image"
                        class="mb-4 w-full rounded-xl border border-white/10 shadow-md"
                    />

                    <p v-if="selectedEvent.description" class="whitespace-pre-line leading-relaxed text-white/90">
                        {{ selectedEvent.description }}
                    </p>

                    <div class="mt-6">
                        <button
                            class="rainbow-btn rounded-2xl px-6 py-2 font-bold text-gray-900 shadow-lg transition hover:scale-105"
                            @click="closeEvent"
                        >
                            ✨ Close ✨
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <transition name="fade">
            <div
                v-if="selectedEvents.length"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm"
                @click.self="closeEvent"
            >
                <div
                    class="relative w-[90%] max-w-lg rounded-2xl border border-white/20 bg-gradient-to-br from-pink-200/20 via-purple-300/20 to-blue-200/20 p-6 text-center text-white shadow-2xl"
                >
                    <button
                        class="absolute top-3 right-3 text-xl text-white/70 hover:text-pink-300"
                        @click="closeEvent"
                    >
                        ✕
                    </button>

                    <h2 class="mb-4 text-3xl text-pink-200 font-dancing">
                        {{ formatDate(selectedEvents[0].date) }}
                    </h2>

                    <div
                        v-for="event in selectedEvents"
                        :key="event.id"
                        class="mb-3 cursor-pointer rounded-xl bg-[rgba(255,255,255,0.1)] p-3 shadow-md transition hover:bg-[rgba(255,255,255,0.2)]"
                        @click="openEvent(event)"
                    >
                        <p class="text-xl text-pink-100 font-dancing">
                            {{ event.name || event.type }}
                        </p>
                        <p class="text-sm text-white/80">
                            {{ formatTime(event.start_time) }}
                            <span v-if="event.end_time">– {{ formatTime(event.end_time) }}</span>
                        </p>
                        <p v-if="event.address" class="text-xs text-white/70 italic">
                            📍 {{ event.address }}
                        </p>
                    </div>
                </div>
            </div>
        </transition>
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

.text-shadow-strong {
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.7);
}

.rainbow-btn {
    background-image: linear-gradient(to right, #ffb3e6, #ffc5d8, #ffd9b3, #d4fcb8, #b3e6ff);
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

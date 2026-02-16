<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import AdminMenu from '@/components/admin/AdminMenu.vue';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const loadingQuotes = ref(false);
const loadingTestimonials = ref(false);
const quotesError = ref('');
const testimonialsError = ref('');
const quotes = ref([]);
const testimonials = ref([]);

const latestQuotes = computed(() => quotes.value.slice(0, 5));
const latestTestimonials = computed(() => testimonials.value.slice(0, 5));

function formatDateTime(value) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
}

function truncate(value, maxLength = 140) {
    const text = String(value ?? '');

    if (text.length <= maxLength) {
        return text;
    }

    return `${text.slice(0, maxLength)}...`;
}

async function loadQuotes() {
    loadingQuotes.value = true;
    quotesError.value = '';

    try {
        const response = await fetch('/admin/quotes/list', {
            headers: {
                Accept: 'application/json',
            },
        });

        const data = await response.json();

        if (!response.ok) {
            quotesError.value = data.message || data.error || 'Failed to load quotes';
            return;
        }

        quotes.value = Array.isArray(data) ? data : [];
    } catch {
        quotesError.value = 'Failed to load quotes';
    } finally {
        loadingQuotes.value = false;
    }
}

async function loadTestimonials() {
    loadingTestimonials.value = true;
    testimonialsError.value = '';

    try {
        const response = await fetch('/admin/testimonials/list', {
            headers: {
                Accept: 'application/json',
            },
        });

        const data = await response.json();

        if (!response.ok) {
            testimonialsError.value = data.message || data.error || 'Failed to load testimonials';
            return;
        }

        testimonials.value = Array.isArray(data) ? data : [];
    } catch {
        testimonialsError.value = 'Failed to load testimonials';
    } finally {
        loadingTestimonials.value = false;
    }
}

onMounted(() => {
    loadQuotes();
    loadTestimonials();
});
</script>

<template>
    <Head title="Admin | Sprinkle Fairydust" />

    <main class="mx-auto mt-10 max-w-6xl px-4 pb-10 font-quicksand">
        <section class="rounded-3xl border border-slate-200/90 bg-slate-50/90 p-5 shadow-2xl backdrop-blur-md md:p-6">
            <h1 class="mb-2 text-center text-5xl text-slate-900 drop-shadow-sm font-dancing">Admin Dashboard</h1>
            <p class="mb-8 text-center text-slate-600">Latest quotes and testimonials.</p>

            <AdminMenu />

            <section class="mb-6 grid gap-4 sm:grid-cols-2">
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-md">
                    <p class="text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">Quotes</p>
                    <p class="mt-2 text-3xl font-bold text-sky-900">{{ quotes.length }}</p>
                    <p class="text-sm text-slate-600">Total quote records</p>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-md">
                    <p class="text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">Testimonials</p>
                    <p class="mt-2 text-3xl font-bold text-sky-900">{{ testimonials.length }}</p>
                    <p class="text-sm text-slate-600">Total testimonial records</p>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-md">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-2xl text-sky-900 font-dancing">Latest Quotes</h2>
                        <Link href="/admin/quotes" class="secondary-btn">Manage</Link>
                    </div>

                    <p v-if="loadingQuotes" class="text-sm text-slate-600">Loading quotes...</p>
                    <p v-else-if="quotesError" class="text-sm font-semibold text-rose-700">{{ quotesError }}</p>
                    <p v-else-if="latestQuotes.length === 0" class="text-sm text-slate-600">No quotes yet.</p>

                    <ul v-else class="space-y-3">
                        <li
                            v-for="item in latestQuotes"
                            :key="item.id"
                            class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700"
                        >
                            <p><strong>{{ item.name }}</strong> · {{ item.email }}</p>
                            <p class="mt-1">
                                {{ item.event_type || 'Event type not set' }}
                                <span v-if="item.event_date"> on {{ item.event_date }}</span>
                            </p>
                            <p class="mt-1 text-xs text-slate-500">Created {{ formatDateTime(item.created_at) }}</p>
                        </li>
                    </ul>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-md">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-2xl text-sky-900 font-dancing">Latest Testimonials</h2>
                        <Link href="/admin/testimonials" class="secondary-btn">Manage</Link>
                    </div>

                    <p v-if="loadingTestimonials" class="text-sm text-slate-600">Loading testimonials...</p>
                    <p v-else-if="testimonialsError" class="text-sm font-semibold text-rose-700">{{ testimonialsError }}</p>
                    <p v-else-if="latestTestimonials.length === 0" class="text-sm text-slate-600">No testimonials yet.</p>

                    <ul v-else class="space-y-3">
                        <li
                            v-for="item in latestTestimonials"
                            :key="item.id"
                            class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700"
                        >
                            <p><strong>{{ item.name }}</strong></p>
                            <p class="mt-1">{{ truncate(item.testimonial) }}</p>
                            <p class="mt-1 text-xs text-slate-500">Created {{ formatDateTime(item.created_at) }}</p>
                        </li>
                    </ul>
                </section>
            </section>
        </section>
    </main>
</template>

<style scoped>
.secondary-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #334155;
    font-size: 0.875rem;
    font-weight: 700;
    padding: 0.45rem 0.8rem;
    transition: background 0.2s ease, border-color 0.2s ease;
}

.secondary-btn:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}
</style>

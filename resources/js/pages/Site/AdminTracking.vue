<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import AdminMenu from '@/components/admin/AdminMenu.vue';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const loading = ref(false);
const loadError = ref('');
const stats = ref({
    overview: {
        total_page_views: 0,
        unique_visitors: 0,
        gallery_views: 0,
        design_views: 0,
        views_last_24h: 0,
        views_last_7d: 0,
        views_last_30d: 0,
        total_time_seconds: 0,
        average_time_per_visitor_seconds: 0,
        quotes_with_tracking: 0,
    },
    country_views: [],
    page_views: [],
    daily_views: [],
    quote_tracking: [],
});

const overviewCards = computed(() => [
    { label: 'Total Page Views', value: stats.value.overview.total_page_views },
    { label: 'Unique Visitors', value: stats.value.overview.unique_visitors },
    { label: 'Gallery Views', value: stats.value.overview.gallery_views },
    { label: 'Design Views', value: stats.value.overview.design_views },
    { label: 'Views (24h)', value: stats.value.overview.views_last_24h },
    { label: 'Views (7d)', value: stats.value.overview.views_last_7d },
    { label: 'Views (30d)', value: stats.value.overview.views_last_30d },
    { label: 'Total Time On Site', value: stats.value.overview.total_time_seconds, formatter: formatDuration },
    { label: 'Avg Time / Visitor', value: stats.value.overview.average_time_per_visitor_seconds, formatter: formatDuration },
    { label: 'Quotes Linked', value: stats.value.overview.quotes_with_tracking },
]);

function numberValue(value) {
    const number = Number(value);
    return Number.isFinite(number) ? number : 0;
}

function formatNumber(value) {
    return numberValue(value).toLocaleString();
}

function formatDuration(value) {
    const totalSeconds = Math.max(0, numberValue(value));
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }

    if (minutes > 0) {
        return `${minutes}m ${seconds}s`;
    }

    return `${seconds}s`;
}

function formatCardValue(card) {
    if (typeof card.formatter === 'function') {
        return card.formatter(card.value);
    }

    return formatNumber(card.value);
}

function formatDateTime(value) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
}

function normalizedCountries() {
    return (Array.isArray(stats.value.country_views) ? stats.value.country_views : []).map((item) => ({
        country_code: item.country_code || 'UNKNOWN',
        views: numberValue(item.views),
    }));
}

function normalizedPages() {
    return (Array.isArray(stats.value.page_views) ? stats.value.page_views : []).map((item) => ({
        page_key: item.page_key || 'unknown',
        views: numberValue(item.views),
    }));
}

const countryRows = computed(() => normalizedCountries());
const pageRows = computed(() => normalizedPages());

const maxDailyViews = computed(() => {
    const rows = Array.isArray(stats.value.daily_views) ? stats.value.daily_views : [];
    return rows.reduce((max, item) => Math.max(max, numberValue(item.views)), 0);
});

const dailyRows = computed(() =>
    (Array.isArray(stats.value.daily_views) ? stats.value.daily_views : []).map((item) => ({
        date: item.viewed_date ?? '',
        views: numberValue(item.views),
    })),
);

const quoteRows = computed(() =>
    (Array.isArray(stats.value.quote_tracking) ? stats.value.quote_tracking : []).map((item) => ({
        quote_id: numberValue(item.quote_id),
        name: item.name ?? '—',
        email: item.email ?? '—',
        anonymous_id: item.anonymous_id ?? '—',
        page_views: numberValue(item.page_views),
        gallery_views: numberValue(item.gallery_views),
        design_views: numberValue(item.design_views),
        total_time_seconds: numberValue(item.total_time_seconds),
        first_viewed_at: item.first_viewed_at ?? null,
        last_viewed_at: item.last_viewed_at ?? null,
        quote_created_at: item.quote_created_at ?? null,
    })),
);

function barWidth(views) {
    if (maxDailyViews.value <= 0) {
        return 0;
    }

    return Math.round((numberValue(views) / maxDailyViews.value) * 100);
}

async function loadStats() {
    loading.value = true;
    loadError.value = '';

    try {
        const response = await fetch('/admin/tracking/stats', {
            headers: {
                Accept: 'application/json',
            },
        });

        const data = await response.json();

        if (!response.ok) {
            loadError.value = data.message || data.error || 'Failed to load tracking stats.';
            return;
        }

        stats.value = {
            overview: {
                total_page_views: numberValue(data?.overview?.total_page_views),
                unique_visitors: numberValue(data?.overview?.unique_visitors),
                gallery_views: numberValue(data?.overview?.gallery_views),
                design_views: numberValue(data?.overview?.design_views),
                views_last_24h: numberValue(data?.overview?.views_last_24h),
                views_last_7d: numberValue(data?.overview?.views_last_7d),
                views_last_30d: numberValue(data?.overview?.views_last_30d),
                total_time_seconds: numberValue(data?.overview?.total_time_seconds),
                average_time_per_visitor_seconds: numberValue(data?.overview?.average_time_per_visitor_seconds),
                quotes_with_tracking: numberValue(data?.overview?.quotes_with_tracking),
            },
            country_views: Array.isArray(data?.country_views) ? data.country_views : [],
            page_views: Array.isArray(data?.page_views) ? data.page_views : [],
            daily_views: Array.isArray(data?.daily_views) ? data.daily_views : [],
            quote_tracking: Array.isArray(data?.quote_tracking) ? data.quote_tracking : [],
        };
    } catch {
        loadError.value = 'Failed to load tracking stats.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    loadStats();
});
</script>

<template>
    <Head title="Tracking | Admin" />

    <main class="mx-auto mt-10 max-w-6xl px-4 pb-10 font-quicksand">
        <section class="rounded-3xl border border-slate-200/90 bg-slate-50/90 p-5 shadow-2xl backdrop-blur-md md:p-6">
            <h1 class="mb-2 text-center text-5xl text-slate-900 drop-shadow-sm font-dancing">Tracking Stats</h1>
            <p class="mb-8 text-center text-slate-600">Anonymous page views by page and country.</p>

            <AdminMenu />

            <p v-if="loading" class="mb-4 text-sm text-slate-600">Loading tracking stats...</p>
            <p v-if="loadError" class="mb-4 text-sm font-semibold text-rose-700">{{ loadError }}</p>

            <section class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article
                    v-for="card in overviewCards"
                    :key="card.label"
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-md"
                >
                    <p class="text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">{{ card.label }}</p>
                    <p class="mt-2 text-3xl font-bold text-sky-900">{{ formatCardValue(card) }}</p>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-md">
                    <h2 class="mb-3 text-2xl font-dancing text-sky-900">Views by Country</h2>

                    <p v-if="countryRows.length === 0" class="text-sm text-slate-600">No country data yet.</p>

                    <ul v-else class="space-y-2">
                        <li
                            v-for="item in countryRows"
                            :key="`country-${item.country_code}`"
                            class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm"
                        >
                            <span class="font-semibold text-slate-700">{{ item.country_code }}</span>
                            <span class="text-slate-600">{{ formatNumber(item.views) }}</span>
                        </li>
                    </ul>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-md">
                    <h2 class="mb-3 text-2xl font-dancing text-sky-900">Views by Page</h2>

                    <p v-if="pageRows.length === 0" class="text-sm text-slate-600">No page view data yet.</p>

                    <ul v-else class="space-y-2">
                        <li
                            v-for="item in pageRows"
                            :key="`page-${item.page_key}`"
                            class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm"
                        >
                            <span class="font-semibold text-slate-700">{{ item.page_key }}</span>
                            <span class="text-slate-600">{{ formatNumber(item.views) }}</span>
                        </li>
                    </ul>
                </section>
            </section>

            <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-md">
                <h2 class="mb-3 text-2xl font-dancing text-sky-900">Daily Views (Last 14 Days)</h2>

                <p v-if="dailyRows.length === 0" class="text-sm text-slate-600">No recent daily view data yet.</p>

                <ul v-else class="space-y-2">
                    <li
                        v-for="item in dailyRows"
                        :key="`daily-${item.date}`"
                        class="rounded-xl border border-slate-200 bg-slate-50 p-3"
                    >
                        <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                            <span class="font-semibold text-slate-700">{{ item.date }}</span>
                            <span class="text-slate-600">{{ formatNumber(item.views) }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-sky-400 to-teal-400"
                                :style="{ width: `${barWidth(item.views)}%` }"
                            />
                        </div>
                    </li>
                </ul>
            </section>

            <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-md">
                <h2 class="mb-3 text-2xl font-dancing text-sky-900">Quote Linked Tracking</h2>
                <p class="mb-3 text-sm text-slate-600">Anonymous visitor activity linked to submitted quotes.</p>

                <p v-if="quoteRows.length === 0" class="text-sm text-slate-600">No quote-linked tracking data yet.</p>

                <ul v-else class="space-y-3">
                    <li
                        v-for="item in quoteRows"
                        :key="`quote-tracking-${item.quote_id}-${item.anonymous_id}`"
                        class="rounded-xl border border-slate-200 bg-slate-50 p-3"
                    >
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-800">Quote #{{ item.quote_id }} · {{ item.name }}</p>
                            <p class="text-xs text-slate-500">Created {{ formatDateTime(item.quote_created_at) }}</p>
                        </div>

                        <p class="text-xs text-slate-600">Email: {{ item.email }}</p>
                        <p class="text-xs text-slate-600">Anonymous ID: {{ item.anonymous_id }}</p>

                        <div class="mt-2 grid gap-2 text-sm text-slate-700 sm:grid-cols-2 lg:grid-cols-4">
                            <p><strong>Views:</strong> {{ formatNumber(item.page_views) }}</p>
                            <p><strong>Gallery:</strong> {{ formatNumber(item.gallery_views) }}</p>
                            <p><strong>Designs:</strong> {{ formatNumber(item.design_views) }}</p>
                            <p><strong>Time:</strong> {{ formatDuration(item.total_time_seconds) }}</p>
                        </div>

                        <div class="mt-2 grid gap-2 text-xs text-slate-600 sm:grid-cols-2">
                            <p><strong>First Seen:</strong> {{ formatDateTime(item.first_viewed_at) }}</p>
                            <p><strong>Last Seen:</strong> {{ formatDateTime(item.last_viewed_at) }}</p>
                        </div>
                    </li>
                </ul>
            </section>
        </section>
    </main>
</template>

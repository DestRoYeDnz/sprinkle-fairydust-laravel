<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import AdminMenu from '@/components/admin/AdminMenu.vue';
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

function getCsrfToken() {
    const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (metaToken) {
        return metaToken;
    }

    const xsrfCookie = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='))
        ?.split('=')
        .slice(1)
        .join('=');

    return xsrfCookie ? decodeURIComponent(xsrfCookie) : csrfToken;
}

function jsonHeaders(includeContentType = true) {
    const token = getCsrfToken();
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (includeContentType) {
        headers['Content-Type'] = 'application/json';
    }

    if (token) {
        headers['X-CSRF-TOKEN'] = token;
        headers['X-XSRF-TOKEN'] = token;
    }

    return headers;
}

async function parseJson(response) {
    const contentType = response.headers.get('content-type') || '';
    if (!contentType.includes('application/json')) {
        return {};
    }

    try {
        return await response.json();
    } catch {
        return {};
    }
}

const loading = ref(false);
const loadError = ref('');
const createError = ref('');
const createSuccess = ref('');
const creating = ref(false);
const quotes = ref([]);
const quotePendingDelete = ref(null);
const showDeleteDialog = ref(false);
const showCreateForm = ref(false);

const createForm = ref(emptyQuoteForm());

function emptyQuoteForm() {
    return {
        name: '',
        email: '',
        event_type: '',
        event_date: '',
        start_time: '',
        end_time: '',
        total_hours: '',
        calc_payment_type: '',
        calc_base_amount: '',
        calc_setup_amount: '',
        calc_travel_amount: '',
        calc_subtotal: '',
        calc_gst_amount: '',
        calc_total_amount: '',
        address: '',
    };
}

function normalizeDate(value) {
    if (!value) {
        return '';
    }

    return String(value).slice(0, 10);
}

function normalizeTime(value) {
    if (!value) {
        return '';
    }

    return String(value).slice(0, 5);
}

function buildPayload(source) {
    const totalHours = source.total_hours;

    return {
        name: source.name,
        email: source.email,
        event_type: source.event_type || null,
        event_date: source.event_date || null,
        start_time: source.start_time || null,
        end_time: source.end_time || null,
        total_hours: totalHours === '' || totalHours === null ? null : Number(totalHours),
        calc_payment_type: source.calc_payment_type || null,
        calc_base_amount: toNullableNumber(source.calc_base_amount),
        calc_setup_amount: toNullableNumber(source.calc_setup_amount),
        calc_travel_amount: toNullableNumber(source.calc_travel_amount),
        calc_subtotal: toNullableNumber(source.calc_subtotal),
        calc_gst_amount: toNullableNumber(source.calc_gst_amount),
        calc_total_amount: toNullableNumber(source.calc_total_amount),
        address: source.address || null,
    };
}

function toNullableNumber(value) {
    if (value === '' || value === null || value === undefined) {
        return null;
    }

    const number = Number(value);

    return Number.isFinite(number) ? number : null;
}

function normalizeAmount(value) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const number = Number(value);

    return Number.isFinite(number) ? number : null;
}

function formatCurrency(value) {
    const amount = normalizeAmount(value);

    if (amount === null) {
        return '—';
    }

    return `$${amount.toFixed(2)}`;
}

function formatPaymentType(value) {
    if (value === 'hourly') {
        return 'Organizer-Paid (Hourly)';
    }

    if (value === 'perface') {
        return 'Pay Per Face';
    }

    return '—';
}

function hasCalculationBreakdown(item) {
    return [
        item.calc_payment_type,
        item.calc_base_amount,
        item.calc_setup_amount,
        item.calc_travel_amount,
        item.calc_subtotal,
        item.calc_gst_amount,
        item.calc_total_amount,
    ].some((value) => value !== null && value !== '');
}

function formatDateTime(value) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
}

function formatEmailSendStatus(item) {
    if (!item.email_send_status) {
        return 'Not sent';
    }

    const status = item.email_send_status === 'sent' ? 'Sent' : item.email_send_status;
    const attempted = formatDateTime(item.email_send_attempted_at);

    return `${status} (${attempted})`;
}

function formatConfirmationStatus(item) {
    if (!item.client_confirmed_at) {
        return 'Pending confirmation';
    }

    return `Confirmed at ${formatDateTime(item.client_confirmed_at)}`;
}

function formatOpenTrackingStatus(item) {
    if (!item.email_opened_at) {
        return 'Not opened yet';
    }

    const count = Number(item.email_open_count ?? 0);
    const countText = `${count} ${count === 1 ? 'open' : 'opens'}`;
    const lastOpened = formatDateTime(item.email_last_opened_at || item.email_opened_at);

    return `${countText} (last ${lastOpened})`;
}

function emailStatusPillClass(item) {
    if (item.email_send_status === 'sent') {
        return 'status-pill status-pill--success';
    }

    if (item.email_send_status === 'failed') {
        return 'status-pill status-pill--danger';
    }

    return 'status-pill status-pill--pending';
}

function openedStatusPillClass(item) {
    if (item.email_opened_at) {
        return 'status-pill status-pill--success';
    }

    return 'status-pill status-pill--pending';
}

function confirmedStatusPillClass(item) {
    if (item.client_confirmed_at) {
        return 'status-pill status-pill--success';
    }

    return 'status-pill status-pill--pending';
}

function calculatorUrl(source) {
    const params = new URLSearchParams();

    if (source.name) {
        params.set('name', source.name);
    }

    if (source.email) {
        params.set('email', source.email);
    }

    if (source.event_type) {
        params.set('event', source.event_type);
        params.set('type', source.event_type);
    }

    if (source.event_date) {
        params.set('date', source.event_date);
    }

    if (source.start_time) {
        params.set('start', source.start_time);
    }

    if (source.end_time) {
        params.set('end', source.end_time);
    }

    if (source.total_hours !== '' && source.total_hours !== null && source.total_hours !== undefined) {
        params.set('hours', String(source.total_hours));
    }

    const query = params.toString();

    return query ? `/admin/calculator?${query}` : '/admin/calculator';
}

function applyQuoteData(item, quote) {
    item.name = quote.name ?? '';
    item.email = quote.email ?? '';
    item.anonymous_id = quote.anonymous_id ?? '';
    item.event_type = quote.event_type ?? '';
    item.event_date = normalizeDate(quote.event_date);
    item.start_time = normalizeTime(quote.start_time);
    item.end_time = normalizeTime(quote.end_time);
    item.total_hours = quote.total_hours ?? '';
    item.calc_payment_type = quote.calc_payment_type ?? '';
    item.calc_base_amount = normalizeAmount(quote.calc_base_amount);
    item.calc_setup_amount = normalizeAmount(quote.calc_setup_amount);
    item.calc_travel_amount = normalizeAmount(quote.calc_travel_amount);
    item.calc_subtotal = normalizeAmount(quote.calc_subtotal);
    item.calc_gst_amount = normalizeAmount(quote.calc_gst_amount);
    item.calc_total_amount = normalizeAmount(quote.calc_total_amount);
    item.address = quote.address ?? '';
    item.email_send_status = quote.email_send_status ?? '';
    item.email_send_attempted_at = quote.email_send_attempted_at ?? null;
    item.client_confirmed_at = quote.client_confirmed_at ?? null;
    item.email_opened_at = quote.email_opened_at ?? null;
    item.email_last_opened_at = quote.email_last_opened_at ?? null;
    item.email_open_count = Number(quote.email_open_count ?? 0);
    item.created_at = quote.created_at ?? item.created_at;
    item.updated_at = quote.updated_at ?? item.updated_at;
}

function normalizeQuote(quote) {
    return {
        id: quote.id,
        name: quote.name ?? '',
        email: quote.email ?? '',
        anonymous_id: quote.anonymous_id ?? '',
        event_type: quote.event_type ?? '',
        event_date: normalizeDate(quote.event_date),
        start_time: normalizeTime(quote.start_time),
        end_time: normalizeTime(quote.end_time),
        total_hours: quote.total_hours ?? '',
        calc_payment_type: quote.calc_payment_type ?? '',
        calc_base_amount: normalizeAmount(quote.calc_base_amount),
        calc_setup_amount: normalizeAmount(quote.calc_setup_amount),
        calc_travel_amount: normalizeAmount(quote.calc_travel_amount),
        calc_subtotal: normalizeAmount(quote.calc_subtotal),
        calc_gst_amount: normalizeAmount(quote.calc_gst_amount),
        calc_total_amount: normalizeAmount(quote.calc_total_amount),
        address: quote.address ?? '',
        email_send_status: quote.email_send_status ?? '',
        email_send_attempted_at: quote.email_send_attempted_at ?? null,
        client_confirmed_at: quote.client_confirmed_at ?? null,
        email_opened_at: quote.email_opened_at ?? null,
        email_last_opened_at: quote.email_last_opened_at ?? null,
        email_open_count: Number(quote.email_open_count ?? 0),
        created_at: quote.created_at ?? null,
        updated_at: quote.updated_at ?? null,
        _editing: false,
        _saving: false,
        _sending_email: false,
        _error: '',
        _email_success: '',
        _email_error: '',
        _draft: {
            name: quote.name ?? '',
            email: quote.email ?? '',
            event_type: quote.event_type ?? '',
            event_date: normalizeDate(quote.event_date),
            start_time: normalizeTime(quote.start_time),
            end_time: normalizeTime(quote.end_time),
            total_hours: quote.total_hours ?? '',
            calc_payment_type: quote.calc_payment_type ?? '',
            calc_base_amount: normalizeAmount(quote.calc_base_amount),
            calc_setup_amount: normalizeAmount(quote.calc_setup_amount),
            calc_travel_amount: normalizeAmount(quote.calc_travel_amount),
            calc_subtotal: normalizeAmount(quote.calc_subtotal),
            calc_gst_amount: normalizeAmount(quote.calc_gst_amount),
            calc_total_amount: normalizeAmount(quote.calc_total_amount),
            address: quote.address ?? '',
        },
    };
}

function startEdit(item) {
    item._draft = {
        name: item.name,
        email: item.email,
        event_type: item.event_type,
        event_date: item.event_date,
        start_time: item.start_time,
        end_time: item.end_time,
        total_hours: item.total_hours,
        calc_payment_type: item.calc_payment_type,
        calc_base_amount: item.calc_base_amount,
        calc_setup_amount: item.calc_setup_amount,
        calc_travel_amount: item.calc_travel_amount,
        calc_subtotal: item.calc_subtotal,
        calc_gst_amount: item.calc_gst_amount,
        calc_total_amount: item.calc_total_amount,
        address: item.address,
    };

    item._error = '';
    item._editing = true;
}

function cancelEdit(item) {
    item._editing = false;
    item._error = '';
}

async function loadQuotes() {
    loading.value = true;
    loadError.value = '';

    try {
        const response = await fetch('/admin/quotes/list', {
            headers: {
                Accept: 'application/json',
            },
        });

        const data = await parseJson(response);

        if (!response.ok) {
            loadError.value = data.message || data.error || 'Failed to load quotes';
            return;
        }

        quotes.value = Array.isArray(data) ? data.map(normalizeQuote) : [];
    } catch {
        loadError.value = 'Failed to load quotes';
    } finally {
        loading.value = false;
    }
}

async function createQuote() {
    creating.value = true;
    createError.value = '';
    createSuccess.value = '';

    try {
        const response = await fetch('/admin/quotes', {
            method: 'POST',
            credentials: 'same-origin',
            headers: jsonHeaders(),
            body: JSON.stringify(buildPayload(createForm.value)),
        });

        const data = await parseJson(response);

        if (!response.ok) {
            createError.value = response.status === 419
                ? 'Session expired. Refresh and try again.'
                : data.message || data.error || 'Failed to create quote';
            return;
        }

        if (data.quote) {
            quotes.value.unshift(normalizeQuote(data.quote));
        }

        createForm.value = emptyQuoteForm();
        createSuccess.value = 'Quote created successfully.';
    } catch {
        createError.value = 'Failed to create quote';
    } finally {
        creating.value = false;
    }
}

async function saveQuote(item) {
    item._saving = true;
    item._error = '';

    try {
        const response = await fetch(`/admin/quotes/${item.id}`, {
            method: 'PUT',
            credentials: 'same-origin',
            headers: jsonHeaders(),
            body: JSON.stringify(buildPayload(item._draft)),
        });

        const data = await parseJson(response);

        if (!response.ok) {
            item._error = response.status === 419
                ? 'Session expired. Refresh and try again.'
                : data.message || data.error || 'Failed to update quote';
            return;
        }

        if (data.quote) {
            applyQuoteData(item, data.quote);
        }

        item._editing = false;
    } catch {
        item._error = 'Failed to update quote';
    } finally {
        item._saving = false;
    }
}

async function sendQuoteEmail(item) {
    item._sending_email = true;
    item._email_success = '';
    item._email_error = '';

    try {
        const response = await fetch(`/admin/quotes/${item.id}/send-email`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: jsonHeaders(),
            body: JSON.stringify({
                _token: getCsrfToken(),
            }),
        });

        const data = await parseJson(response);

        if (!response.ok || !data.success) {
            item._email_error = response.status === 419
                ? 'Session expired. Refresh and try again.'
                : data.message || data.error || 'Failed to send quote email';
            return;
        }

        item._email_success = data.message || 'Quote email sent successfully.';
    } catch {
        item._email_error = 'Failed to send quote email';
    } finally {
        item._sending_email = false;
    }
}

function requestDeleteQuote(item) {
    quotePendingDelete.value = item;
    showDeleteDialog.value = true;
}

async function deleteQuote() {
    const item = quotePendingDelete.value;
    if (!item) {
        return;
    }

    showDeleteDialog.value = false;
    item._saving = true;
    item._error = '';

    try {
        const response = await fetch(`/admin/quotes/${item.id}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: jsonHeaders(false),
        });

        const data = await parseJson(response);

        if (!response.ok || !data.success) {
            item._error = response.status === 419
                ? 'Session expired. Refresh and try again.'
                : data.message || data.error || 'Failed to delete quote';
            return;
        }

        quotes.value = quotes.value.filter((quote) => quote.id !== item.id);
    } catch {
        item._error = 'Failed to delete quote';
    } finally {
        item._saving = false;
        quotePendingDelete.value = null;
    }
}

onMounted(() => {
    loadQuotes();
});
</script>

<template>
    <Head title="Manage Quotes | Admin" />

    <main class="mx-auto mt-10 max-w-6xl px-4 pb-10 font-quicksand">
        <section class="rounded-3xl border border-slate-200/90 bg-slate-50/90 p-5 shadow-2xl backdrop-blur-md md:p-6">
            <h1 class="mb-2 text-center text-5xl text-slate-900 drop-shadow-sm font-dancing">Manage Quotes</h1>
            <p class="mb-8 text-center text-slate-600">Create, update, and remove quote requests.</p>

            <AdminMenu />

            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-md">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-2xl font-semibold text-sky-900">Add Quote</h2>
                    <button class="secondary-btn" type="button" @click="showCreateForm = !showCreateForm">
                        {{ showCreateForm ? 'Hide Form' : 'Show Form' }}
                    </button>
                </div>

                <p v-if="!showCreateForm" class="text-sm text-slate-600">
                    Form is minimized by default. Select "Show Form" to add a quote.
                </p>

                <form v-else class="space-y-4" @submit.prevent="createQuote">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="field-label">Name
                            <input v-model="createForm.name" type="text" class="input" required />
                        </label>

                        <label class="field-label">Email
                            <input v-model="createForm.email" type="email" class="input" required />
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="field-label">Event Type
                            <input v-model="createForm.event_type" type="text" class="input" />
                        </label>

                        <label class="field-label">Event Date
                            <input v-model="createForm.event_date" type="date" class="input" />
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <label class="field-label">Start Time
                            <input v-model="createForm.start_time" type="time" class="input" />
                        </label>

                        <label class="field-label">End Time
                            <input v-model="createForm.end_time" type="time" class="input" />
                        </label>

                        <label class="field-label">Total Hours
                            <input v-model="createForm.total_hours" type="number" min="0" step="0.25" class="input" />
                        </label>
                    </div>

                    <label class="field-label">Address
                        <input v-model="createForm.address" type="text" class="input" />
                    </label>

                    <button class="primary-btn" :disabled="creating">
                        {{ creating ? 'Creating...' : 'Create Quote' }}
                    </button>

                    <p v-if="createSuccess" class="text-sm font-semibold text-emerald-700">{{ createSuccess }}</p>
                    <p v-if="createError" class="text-sm font-semibold text-rose-700">{{ createError }}</p>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-md">
                <h2 class="mb-4 text-2xl font-semibold text-sky-900">Existing Quotes</h2>

                <p v-if="loading" class="text-slate-600">Loading quotes...</p>
                <p v-else-if="loadError" class="font-semibold text-rose-700">{{ loadError }}</p>
                <p v-else-if="quotes.length === 0" class="text-slate-600">No quotes found.</p>

                <div v-else class="space-y-4">
                    <article
                        v-for="quote in quotes"
                        :key="quote.id"
                        class="rounded-xl border border-slate-200 bg-slate-50 p-4"
                    >
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-700">Quote #{{ quote.id }}</p>
                            <p class="text-xs text-slate-500">Created: {{ formatDateTime(quote.created_at) }}</p>
                        </div>

                        <div v-if="quote._editing" class="space-y-3">
                            <div class="grid gap-3 md:grid-cols-2">
                                <label class="field-label">Name
                                    <input v-model="quote._draft.name" type="text" class="input" required />
                                </label>
                                <label class="field-label">Email
                                    <input v-model="quote._draft.email" type="email" class="input" required />
                                </label>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2">
                                <label class="field-label">Event Type
                                    <input v-model="quote._draft.event_type" type="text" class="input" />
                                </label>
                                <label class="field-label">Event Date
                                    <input v-model="quote._draft.event_date" type="date" class="input" />
                                </label>
                            </div>

                            <div class="grid gap-3 md:grid-cols-3">
                                <label class="field-label">Start Time
                                    <input v-model="quote._draft.start_time" type="time" class="input" />
                                </label>
                                <label class="field-label">End Time
                                    <input v-model="quote._draft.end_time" type="time" class="input" />
                                </label>
                                <label class="field-label">Total Hours
                                    <input v-model="quote._draft.total_hours" type="number" min="0" step="0.25" class="input" />
                                </label>
                            </div>

                            <label class="field-label">Address
                                <input v-model="quote._draft.address" type="text" class="input" />
                            </label>

                            <div class="flex flex-wrap gap-2">
                                <button class="primary-btn" :disabled="quote._saving" @click="saveQuote(quote)">
                                    {{ quote._saving ? 'Saving...' : 'Save' }}
                                </button>
                                <button class="secondary-btn" type="button" :disabled="quote._saving" @click="cancelEdit(quote)">
                                    Cancel
                                </button>
                                <button class="danger-btn" type="button" :disabled="quote._saving" @click="requestDeleteQuote(quote)">
                                    Delete
                                </button>
                            </div>
                        </div>

                        <div v-else class="space-y-1 text-sm text-slate-700">
                            <p><strong>Name:</strong> {{ quote.name }}</p>
                            <p><strong>Email:</strong> {{ quote.email }}</p>
                            <p><strong>Anonymous ID:</strong> {{ quote.anonymous_id || '—' }}</p>
                            <p><strong>Event Type:</strong> {{ quote.event_type || '—' }}</p>
                            <p><strong>Event Date:</strong> {{ quote.event_date || '—' }}</p>
                            <p><strong>Start:</strong> {{ quote.start_time || '—' }}</p>
                            <p><strong>End:</strong> {{ quote.end_time || '—' }}</p>
                            <p><strong>Total Hours:</strong> {{ quote.total_hours ?? '—' }}</p>
                            <div v-if="hasCalculationBreakdown(quote)" class="mt-2 rounded-lg border border-slate-200 bg-white p-3">
                                <p class="mb-1 text-xs font-semibold tracking-[0.12em] text-slate-500 uppercase">Calculation</p>
                                <p><strong>Payment Type:</strong> {{ formatPaymentType(quote.calc_payment_type) }}</p>
                                <p><strong>Base:</strong> {{ formatCurrency(quote.calc_base_amount) }}</p>
                                <p><strong>Setup:</strong> {{ formatCurrency(quote.calc_setup_amount) }}</p>
                                <p><strong>Travel:</strong> {{ formatCurrency(quote.calc_travel_amount) }}</p>
                                <p><strong>Subtotal:</strong> {{ formatCurrency(quote.calc_subtotal) }}</p>
                                <p><strong>GST:</strong> {{ formatCurrency(quote.calc_gst_amount) }}</p>
                                <p><strong>Total:</strong> {{ formatCurrency(quote.calc_total_amount) }}</p>
                            </div>
                            <p><strong>Address:</strong> {{ quote.address || '—' }}</p>
                            <p class="status-line">
                                <strong>Email Status:</strong>
                                <span :class="emailStatusPillClass(quote)">{{ formatEmailSendStatus(quote) }}</span>
                            </p>
                            <p class="status-line">
                                <strong>Opened:</strong>
                                <span :class="openedStatusPillClass(quote)">{{ formatOpenTrackingStatus(quote) }}</span>
                            </p>
                            <p class="status-line">
                                <strong>Confirmed:</strong>
                                <span :class="confirmedStatusPillClass(quote)">{{ formatConfirmationStatus(quote) }}</span>
                            </p>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <Link class="primary-btn" :href="calculatorUrl(quote)">Calculate</Link>
                                <button class="primary-btn" :disabled="quote._sending_email" @click="sendQuoteEmail(quote)">
                                    {{ quote._sending_email ? 'Sending...' : 'Send Quote Email' }}
                                </button>
                                <button class="secondary-btn" @click="startEdit(quote)">Edit</button>
                                <button class="danger-btn" :disabled="quote._saving" @click="requestDeleteQuote(quote)">Delete</button>
                            </div>
                        </div>

                        <p v-if="quote._error" class="mt-2 text-sm font-semibold text-rose-700">{{ quote._error }}</p>
                        <p v-if="quote._email_success" class="mt-2 text-sm font-semibold text-emerald-700">{{ quote._email_success }}</p>
                        <p v-if="quote._email_error" class="mt-2 text-sm font-semibold text-rose-700">{{ quote._email_error }}</p>
                    </article>
                </div>
            </section>
        </section>
    </main>

    <ConfirmDialog
        v-model:open="showDeleteDialog"
        title="Delete Quote"
        :message="
            quotePendingDelete
                ? `Delete quote #${quotePendingDelete.id} from ${quotePendingDelete.name || 'this user'}?`
                : 'Delete this quote?'
        "
        confirm-text="Delete"
        danger
        @confirm="deleteQuote"
    />
</template>

<style scoped>
.field-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: #334155;
}

.input {
    margin-top: 0.35rem;
    width: 100%;
    border-radius: 0.75rem;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #0f172a;
    padding: 0.65rem 0.8rem;
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

.danger-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    border: 1px solid #fecdd3;
    background: #fff1f2;
    color: #9f1239;
    font-weight: 700;
    padding: 0.65rem 1rem;
    transition: background 0.2s ease, border-color 0.2s ease;
}

.danger-btn:hover {
    background: #ffe4e6;
    border-color: #fda4af;
}

.status-line {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.45rem;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    border-radius: 9999px;
    border: 1px solid transparent;
    padding: 0.2rem 0.6rem;
    font-size: 0.72rem;
    font-weight: 700;
    line-height: 1.25;
}

.status-pill--success {
    border-color: #86efac;
    background: #f0fdf4;
    color: #166534;
}

.status-pill--pending {
    border-color: #cbd5e1;
    background: #f8fafc;
    color: #334155;
}

.status-pill--danger {
    border-color: #fda4af;
    background: #fff1f2;
    color: #9f1239;
}
</style>

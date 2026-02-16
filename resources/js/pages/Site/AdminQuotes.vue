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

const loading = ref(false);
const loadError = ref('');
const createError = ref('');
const createSuccess = ref('');
const creating = ref(false);
const quotes = ref([]);
const quotePendingDelete = ref(null);
const showDeleteDialog = ref(false);

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
        address: '',
        details: '',
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
        address: source.address || null,
        details: source.details || null,
    };
}

function formatDateTime(value) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
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
    item.event_type = quote.event_type ?? '';
    item.event_date = normalizeDate(quote.event_date);
    item.start_time = normalizeTime(quote.start_time);
    item.end_time = normalizeTime(quote.end_time);
    item.total_hours = quote.total_hours ?? '';
    item.address = quote.address ?? '';
    item.details = quote.details ?? '';
    item.created_at = quote.created_at ?? item.created_at;
    item.updated_at = quote.updated_at ?? item.updated_at;
}

function normalizeQuote(quote) {
    return {
        id: quote.id,
        name: quote.name ?? '',
        email: quote.email ?? '',
        event_type: quote.event_type ?? '',
        event_date: normalizeDate(quote.event_date),
        start_time: normalizeTime(quote.start_time),
        end_time: normalizeTime(quote.end_time),
        total_hours: quote.total_hours ?? '',
        address: quote.address ?? '',
        details: quote.details ?? '',
        created_at: quote.created_at ?? null,
        updated_at: quote.updated_at ?? null,
        _editing: false,
        _saving: false,
        _error: '',
        _draft: {
            name: quote.name ?? '',
            email: quote.email ?? '',
            event_type: quote.event_type ?? '',
            event_date: normalizeDate(quote.event_date),
            start_time: normalizeTime(quote.start_time),
            end_time: normalizeTime(quote.end_time),
            total_hours: quote.total_hours ?? '',
            address: quote.address ?? '',
            details: quote.details ?? '',
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
        address: item.address,
        details: item.details,
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

        const data = await response.json();

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
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(buildPayload(createForm.value)),
        });

        const data = await response.json();

        if (!response.ok) {
            createError.value = data.message || data.error || 'Failed to create quote';
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
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(buildPayload(item._draft)),
        });

        const data = await response.json();

        if (!response.ok) {
            item._error = data.message || data.error || 'Failed to update quote';
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
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            item._error = data.message || data.error || 'Failed to delete quote';
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
                <h2 class="mb-4 text-2xl font-semibold text-sky-900">Add Quote</h2>

                <form class="space-y-4" @submit.prevent="createQuote">
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

                    <label class="field-label">Details
                        <textarea v-model="createForm.details" rows="3" class="input"></textarea>
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

                            <label class="field-label">Details
                                <textarea v-model="quote._draft.details" rows="3" class="input"></textarea>
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
                            <p><strong>Event Type:</strong> {{ quote.event_type || '—' }}</p>
                            <p><strong>Event Date:</strong> {{ quote.event_date || '—' }}</p>
                            <p><strong>Start:</strong> {{ quote.start_time || '—' }}</p>
                            <p><strong>End:</strong> {{ quote.end_time || '—' }}</p>
                            <p><strong>Total Hours:</strong> {{ quote.total_hours ?? '—' }}</p>
                            <p><strong>Address:</strong> {{ quote.address || '—' }}</p>
                            <p><strong>Details:</strong> {{ quote.details || '—' }}</p>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <Link class="primary-btn" :href="calculatorUrl(quote)">Calculate</Link>
                                <button class="secondary-btn" @click="startEdit(quote)">Edit</button>
                                <button class="danger-btn" :disabled="quote._saving" @click="requestDeleteQuote(quote)">Delete</button>
                            </div>
                        </div>

                        <p v-if="quote._error" class="mt-2 text-sm font-semibold text-rose-700">{{ quote._error }}</p>
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
</style>

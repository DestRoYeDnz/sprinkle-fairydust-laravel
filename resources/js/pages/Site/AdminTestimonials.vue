<script setup>
import { Head } from '@inertiajs/vue3';
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
const testimonials = ref([]);
const testimonialPendingDelete = ref(null);
const showDeleteDialog = ref(false);

const createForm = ref({
    name: '',
    testimonial: '',
    urlsText: '',
    is_approved: true,
});

function parseUrls(text) {
    if (!text) {
        return [];
    }

    return text
        .split(/[\n,]+/)
        .map((value) => value.trim())
        .filter(Boolean);
}

function formatDateTime(value) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
}

function statusLabel(isApproved) {
    return isApproved ? 'Approved' : 'Pending Approval';
}

function normalizeTestimonial(item) {
    const urls = Array.isArray(item.urls) ? item.urls : [];

    return {
        id: item.id,
        name: item.name ?? '',
        testimonial: item.testimonial ?? '',
        urls,
        is_approved: Boolean(item.is_approved),
        approved_at: item.approved_at ?? null,
        created_at: item.created_at ?? null,
        updated_at: item.updated_at ?? null,
        _editing: false,
        _saving: false,
        _error: '',
        _draft: {
            name: item.name ?? '',
            testimonial: item.testimonial ?? '',
            urlsText: urls.join('\n'),
            is_approved: Boolean(item.is_approved),
        },
    };
}

function applyTestimonialData(item, responseItem) {
    const urls = Array.isArray(responseItem.urls) ? responseItem.urls : [];
    item.name = responseItem.name ?? '';
    item.testimonial = responseItem.testimonial ?? '';
    item.urls = urls;
    item.is_approved = Boolean(responseItem.is_approved);
    item.approved_at = responseItem.approved_at ?? null;
    item.created_at = responseItem.created_at ?? item.created_at;
    item.updated_at = responseItem.updated_at ?? item.updated_at;
}

async function loadTestimonials() {
    loading.value = true;
    loadError.value = '';

    try {
        const response = await fetch('/admin/testimonials/list', {
            headers: {
                Accept: 'application/json',
            },
        });

        const data = await response.json();

        if (!response.ok) {
            loadError.value = data.message || data.error || 'Failed to load testimonials';
            return;
        }

        testimonials.value = Array.isArray(data) ? data.map(normalizeTestimonial) : [];
    } catch {
        loadError.value = 'Failed to load testimonials';
    } finally {
        loading.value = false;
    }
}

async function createTestimonial() {
    creating.value = true;
    createError.value = '';
    createSuccess.value = '';

    try {
        const response = await fetch('/admin/testimonials', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                name: createForm.value.name,
                testimonial: createForm.value.testimonial,
                urls: parseUrls(createForm.value.urlsText),
                is_approved: createForm.value.is_approved,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            createError.value = data.message || data.error || 'Failed to create testimonial';
            return;
        }

        if (data.testimonial) {
            testimonials.value.unshift(normalizeTestimonial(data.testimonial));
        }

        createForm.value = {
            name: '',
            testimonial: '',
            urlsText: '',
            is_approved: true,
        };
        createSuccess.value = 'Testimonial created successfully.';
    } catch {
        createError.value = 'Failed to create testimonial';
    } finally {
        creating.value = false;
    }
}

function startEdit(item) {
    item._draft = {
        name: item.name,
        testimonial: item.testimonial,
        urlsText: item.urls.join('\n'),
        is_approved: item.is_approved,
    };
    item._error = '';
    item._editing = true;
}

function cancelEdit(item) {
    item._editing = false;
    item._error = '';
}

async function saveTestimonial(item) {
    item._saving = true;
    item._error = '';

    try {
        const response = await fetch(`/admin/testimonials/${item.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                name: item._draft.name,
                testimonial: item._draft.testimonial,
                urls: parseUrls(item._draft.urlsText),
                is_approved: item._draft.is_approved,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            item._error = data.message || data.error || 'Failed to update testimonial';
            return;
        }

        if (data.testimonial) {
            applyTestimonialData(item, data.testimonial);
        }

        item._editing = false;
    } catch {
        item._error = 'Failed to update testimonial';
    } finally {
        item._saving = false;
    }
}

async function setApproval(item, isApproved) {
    item._saving = true;
    item._error = '';

    try {
        const response = await fetch(`/admin/testimonials/${item.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                name: item.name,
                testimonial: item.testimonial,
                urls: item.urls,
                is_approved: isApproved,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            item._error = data.message || data.error || 'Failed to update approval';
            return;
        }

        if (data.testimonial) {
            applyTestimonialData(item, data.testimonial);
        }
    } catch {
        item._error = 'Failed to update approval';
    } finally {
        item._saving = false;
    }
}

function requestDeleteTestimonial(item) {
    testimonialPendingDelete.value = item;
    showDeleteDialog.value = true;
}

async function deleteTestimonial() {
    const item = testimonialPendingDelete.value;
    if (!item) {
        return;
    }

    showDeleteDialog.value = false;
    item._saving = true;
    item._error = '';

    try {
        const response = await fetch(`/admin/testimonials/${item.id}`, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            item._error = data.message || data.error || 'Failed to delete testimonial';
            return;
        }

        testimonials.value = testimonials.value.filter((testimonial) => testimonial.id !== item.id);
    } catch {
        item._error = 'Failed to delete testimonial';
    } finally {
        item._saving = false;
        testimonialPendingDelete.value = null;
    }
}

onMounted(() => {
    loadTestimonials();
});
</script>

<template>
    <Head title="Manage Testimonials | Admin" />

    <main class="mx-auto mt-10 max-w-6xl px-4 pb-10 font-quicksand">
        <section class="rounded-3xl border border-slate-200/90 bg-slate-50/90 p-5 shadow-2xl backdrop-blur-md md:p-6">
            <h1 class="mb-2 text-center text-5xl text-slate-900 drop-shadow-sm font-dancing">Manage Testimonials</h1>
            <p class="mb-8 text-center text-slate-600">Create, update, and remove testimonials.</p>

            <AdminMenu />

            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-md">
                <h2 class="mb-4 text-2xl font-semibold text-sky-900">Add Testimonial</h2>

                <form class="space-y-4" @submit.prevent="createTestimonial">
                    <label class="field-label">Name
                        <input v-model="createForm.name" type="text" class="input" required />
                    </label>

                    <label class="field-label">Testimonial
                        <textarea v-model="createForm.testimonial" rows="4" class="input" required></textarea>
                    </label>

                    <label class="field-label">Image URLs (optional, one per line or comma-separated)
                        <textarea
                            v-model="createForm.urlsText"
                            rows="3"
                            class="input"
                            placeholder="https://...&#10;https://..."
                        ></textarea>
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <input v-model="createForm.is_approved" type="checkbox" class="h-4 w-4 rounded border-slate-400" />
                        Approve immediately
                    </label>

                    <button class="primary-btn" :disabled="creating">
                        {{ creating ? 'Creating...' : 'Create Testimonial' }}
                    </button>

                    <p v-if="createSuccess" class="text-sm font-semibold text-emerald-700">{{ createSuccess }}</p>
                    <p v-if="createError" class="text-sm font-semibold text-rose-700">{{ createError }}</p>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-md">
                <h2 class="mb-4 text-2xl font-semibold text-sky-900">Existing Testimonials</h2>

                <p v-if="loading" class="text-slate-600">Loading testimonials...</p>
                <p v-else-if="loadError" class="font-semibold text-rose-700">{{ loadError }}</p>
                <p v-else-if="testimonials.length === 0" class="text-slate-600">No testimonials found.</p>

                <div v-else class="space-y-4">
                    <article
                        v-for="item in testimonials"
                        :key="item.id"
                        class="rounded-xl border border-slate-200 bg-slate-50 p-4"
                    >
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-700">Testimonial #{{ item.id }}</p>
                            <div class="flex items-center gap-2">
                                <span :class="['status-pill', item.is_approved ? 'approved' : 'pending']">
                                    {{ statusLabel(item.is_approved) }}
                                </span>
                                <p class="text-xs text-slate-500">Created: {{ formatDateTime(item.created_at) }}</p>
                            </div>
                        </div>

                        <div v-if="item._editing" class="space-y-3">
                            <label class="field-label">Name
                                <input v-model="item._draft.name" type="text" class="input" required />
                            </label>

                            <label class="field-label">Testimonial
                                <textarea v-model="item._draft.testimonial" rows="4" class="input" required></textarea>
                            </label>

                            <label class="field-label">Image URLs (optional)
                                <textarea v-model="item._draft.urlsText" rows="3" class="input"></textarea>
                            </label>

                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-800">
                                <input v-model="item._draft.is_approved" type="checkbox" class="h-4 w-4 rounded border-slate-400" />
                                Approved
                            </label>

                            <div class="flex flex-wrap gap-2">
                                <button class="primary-btn" :disabled="item._saving" @click="saveTestimonial(item)">
                                    {{ item._saving ? 'Saving...' : 'Save' }}
                                </button>
                                <button class="secondary-btn" type="button" :disabled="item._saving" @click="cancelEdit(item)">
                                    Cancel
                                </button>
                                <button class="danger-btn" type="button" :disabled="item._saving" @click="requestDeleteTestimonial(item)">
                                    Delete
                                </button>
                            </div>
                        </div>

                        <div v-else class="space-y-2 text-sm text-slate-700">
                            <p><strong>Name:</strong> {{ item.name }}</p>
                            <p class="whitespace-pre-line"><strong>Testimonial:</strong> {{ item.testimonial }}</p>
                            <p>
                                <strong>Status:</strong> {{ statusLabel(item.is_approved) }}
                                <span v-if="item.approved_at">({{ formatDateTime(item.approved_at) }})</span>
                            </p>

                            <div v-if="item.urls.length" class="space-y-1">
                                <p class="font-semibold text-slate-700">Image URLs:</p>
                                <a
                                    v-for="url in item.urls"
                                    :key="url"
                                    :href="url"
                                    target="_blank"
                                    rel="noopener"
                                    class="block break-all text-sky-700 underline"
                                >
                                    {{ url }}
                                </a>
                            </div>
                            <p v-else><strong>Image URLs:</strong> —</p>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <button class="secondary-btn" @click="startEdit(item)">Edit</button>
                                <button
                                    v-if="!item.is_approved"
                                    class="primary-btn"
                                    :disabled="item._saving"
                                    @click="setApproval(item, true)"
                                >
                                    Approve
                                </button>
                                <button
                                    v-else
                                    class="secondary-btn"
                                    :disabled="item._saving"
                                    @click="setApproval(item, false)"
                                >
                                    Mark Pending
                                </button>
                                <button class="danger-btn" :disabled="item._saving" @click="requestDeleteTestimonial(item)">Delete</button>
                            </div>
                        </div>

                        <p v-if="item._error" class="mt-2 text-sm font-semibold text-rose-700">{{ item._error }}</p>
                    </article>
                </div>
            </section>
        </section>
    </main>

    <ConfirmDialog
        v-model:open="showDeleteDialog"
        title="Delete Testimonial"
        :message="
            testimonialPendingDelete
                ? `Delete testimonial #${testimonialPendingDelete.id} from ${testimonialPendingDelete.name || 'this user'}?`
                : 'Delete this testimonial?'
        "
        confirm-text="Delete"
        danger
        @confirm="deleteTestimonial"
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

.status-pill {
    display: inline-flex;
    align-items: center;
    border-radius: 9999px;
    padding: 0.2rem 0.55rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.approved {
    border: 1px solid #86efac;
    background: #f0fdf4;
    color: #166534;
}

.pending {
    border: 1px solid #fcd34d;
    background: #fffbeb;
    color: #92400e;
}
</style>

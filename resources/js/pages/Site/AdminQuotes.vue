<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { nextTick, onMounted, ref, watch } from 'vue';
import AdminMenu from '@/components/admin/AdminMenu.vue';
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue';
import { csrfHeaders, fetchWithCsrfRetry, withCsrfToken } from '@/lib/csrf';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

function jsonHeaders(includeContentType = true) {
    return csrfHeaders(includeContentType);
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
const quotePendingDecline = ref(null);
const showDeclineDialog = ref(false);
const declineReasonInput = ref('');
const declineDialogRef = ref(null);
const defaultDeclineReason = 'Requested time does not suit our availability. Please reply with another preferred time.';
const focusQuoteId = ref('');

const createForm = ref(emptyQuoteForm());

function emptyQuoteForm() {
    return {
        name: '',
        email: '',
        phone: '',
        guest_count: '',
        package_name: '',
        services_requested_input: '',
        travel_area: '',
        venue_type: '',
        heard_about: '',
        notes: '',
        terms_accepted: false,
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
        phone: source.phone || null,
        guest_count: toNullableInteger(source.guest_count),
        package_name: source.package_name || null,
        services_requested: normalizeServiceListInput(source.services_requested_input),
        travel_area: source.travel_area || null,
        venue_type: source.venue_type || null,
        heard_about: source.heard_about || null,
        notes: source.notes || null,
        terms_accepted: Boolean(source.terms_accepted),
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

function toNullableInteger(value) {
    if (value === '' || value === null || value === undefined) {
        return null;
    }

    const number = Number(value);

    if (!Number.isFinite(number)) {
        return null;
    }

    return Math.max(0, Math.round(number));
}

function normalizeServiceListInput(value) {
    if (typeof value !== 'string') {
        return null;
    }

    const values = value
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean)
        .map((item) => item.slice(0, 80));

    if (!values.length) {
        return null;
    }

    return [...new Set(values)];
}

function serviceListToInput(value) {
    if (Array.isArray(value)) {
        return value.join(', ');
    }

    if (typeof value === 'string') {
        return value;
    }

    return '';
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

    if (value === 'package') {
        return 'Package';
    }

    return '—';
}

function formatVenueType(value) {
    if (value === 'indoor') {
        return 'Indoor';
    }

    if (value === 'outdoor') {
        return 'Outdoor';
    }

    if (value === 'mixed') {
        return 'Indoor + Outdoor';
    }

    if (value === 'unsure') {
        return 'Not sure yet';
    }

    return value || '—';
}

function parsedAddOnsFromQuote(item) {
    const services = Array.isArray(item?.services_requested) ? item.services_requested : [];

    return services
        .map((service) => String(service ?? '').trim())
        .map((service) => {
            const match = service.match(/^add-on:\s*(.+)$/i);

            if (!match) {
                return null;
            }

            const content = String(match[1] ?? '').trim();
            const amountMatch = content.match(/^(.*)\s+\(\$(\d+(?:\.\d{1,2})?)\)\s*$/);

            if (amountMatch) {
                return {
                    name: String(amountMatch[1] ?? '').trim(),
                    amount: Number(amountMatch[2]),
                };
            }

            return {
                name: content,
                amount: null,
            };
        })
        .filter((addOn) => addOn && addOn.name);
}

function formatAddOnBreakdown(item) {
    const addOns = parsedAddOnsFromQuote(item);

    if (!addOns.length) {
        return '—';
    }

    return addOns.map((addOn) => addOn.name).join(', ');
}

function addOnTotalAmount(item) {
    const addOns = parsedAddOnsFromQuote(item);
    const amounts = addOns
        .map((addOn) => addOn.amount)
        .filter((value) => value !== null && Number.isFinite(value));

    if (!amounts.length) {
        return null;
    }

    return amounts.reduce((sum, amount) => sum + Number(amount), 0);
}

function hasAddOnBreakdown(item) {
    return parsedAddOnsFromQuote(item).length > 0;
}

function shouldShowGst(item) {
    const gstAmount = normalizeAmount(item?.calc_gst_amount);

    return gstAmount !== null && gstAmount > 0;
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
    if (item.client_suggested_time_at) {
        return `New time suggested at ${formatDateTime(item.client_suggested_time_at)}`;
    }

    if (item.artist_declined_at) {
        const declinedAt = formatDateTime(item.artist_declined_at);
        const reason = item.artist_decline_reason ? ` (${item.artist_decline_reason})` : '';

        return `Declined at ${declinedAt}${reason}`;
    }

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

    if (item.client_suggested_time_at) {
        return 'status-pill status-pill--pending';
    }

    if (item.artist_declined_at) {
        return 'status-pill status-pill--danger';
    }

    return 'status-pill status-pill--pending';
}

function calculatorUrl(source) {
    const params = new URLSearchParams();

    if (source.id !== '' && source.id !== null && source.id !== undefined) {
        params.set('quote_id', String(source.id));
    }

    if (source.name) {
        params.set('name', source.name);
    }

    if (source.email) {
        params.set('email', source.email);
    }

    if (source.event_type) {
        params.set('event', source.event_type);
    }

    if (source.calc_payment_type) {
        params.set('type', source.calc_payment_type);
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

    if (source.phone) {
        params.set('phone', source.phone);
    }

    if (source.guest_count !== '' && source.guest_count !== null && source.guest_count !== undefined) {
        params.set('guest_count', String(source.guest_count));
    }

    if (source.package_name) {
        params.set('package_name', source.package_name);
    }

    if (source.services_requested_input) {
        params.set('services', source.services_requested_input);
    }

    if (source.travel_area) {
        params.set('travel_area', source.travel_area);
    }

    if (source.venue_type) {
        params.set('venue_type', source.venue_type);
    }

    if (source.heard_about) {
        params.set('heard_about', source.heard_about);
    }

    if (source.address) {
        params.set('address', source.address);
    }

    if (source.notes) {
        params.set('notes', source.notes);
    }

    const query = params.toString();

    return query ? `/admin/calculator?${query}` : '/admin/calculator';
}

function applyQuoteData(item, quote) {
    item.name = quote.name ?? '';
    item.email = quote.email ?? '';
    item.phone = quote.phone ?? '';
    item.guest_count = quote.guest_count ?? '';
    item.package_name = quote.package_name ?? '';
    item.services_requested = Array.isArray(quote.services_requested) ? quote.services_requested : [];
    item.services_requested_input = serviceListToInput(quote.services_requested);
    item.travel_area = quote.travel_area ?? '';
    item.venue_type = quote.venue_type ?? '';
    item.heard_about = quote.heard_about ?? '';
    item.notes = quote.notes ?? '';
    item.terms_accepted = Boolean(quote.terms_accepted);
    item.terms_accepted_at = quote.terms_accepted_at ?? null;
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
    item.artist_declined_at = quote.artist_declined_at ?? null;
    item.artist_decline_reason = quote.artist_decline_reason ?? '';
    item.client_suggested_time_at = quote.client_suggested_time_at ?? null;
    item.client_suggested_event_date = normalizeDate(quote.client_suggested_event_date);
    item.client_suggested_start_time = normalizeTime(quote.client_suggested_start_time);
    item.client_suggested_end_time = normalizeTime(quote.client_suggested_end_time);
    item.client_suggested_time_notes = quote.client_suggested_time_notes ?? '';
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
        phone: quote.phone ?? '',
        guest_count: quote.guest_count ?? '',
        package_name: quote.package_name ?? '',
        services_requested: Array.isArray(quote.services_requested) ? quote.services_requested : [],
        services_requested_input: serviceListToInput(quote.services_requested),
        travel_area: quote.travel_area ?? '',
        venue_type: quote.venue_type ?? '',
        heard_about: quote.heard_about ?? '',
        notes: quote.notes ?? '',
        terms_accepted: Boolean(quote.terms_accepted),
        terms_accepted_at: quote.terms_accepted_at ?? null,
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
        artist_declined_at: quote.artist_declined_at ?? null,
        artist_decline_reason: quote.artist_decline_reason ?? '',
        client_suggested_time_at: quote.client_suggested_time_at ?? null,
        client_suggested_event_date: normalizeDate(quote.client_suggested_event_date),
        client_suggested_start_time: normalizeTime(quote.client_suggested_start_time),
        client_suggested_end_time: normalizeTime(quote.client_suggested_end_time),
        client_suggested_time_notes: quote.client_suggested_time_notes ?? '',
        email_opened_at: quote.email_opened_at ?? null,
        email_last_opened_at: quote.email_last_opened_at ?? null,
        email_open_count: Number(quote.email_open_count ?? 0),
        created_at: quote.created_at ?? null,
        updated_at: quote.updated_at ?? null,
        _editing: false,
        _saving: false,
        _sending_email: false,
        _declining: false,
        _error: '',
        _email_success: '',
        _email_error: '',
        _decline_success: '',
        _decline_error: '',
        _draft: {
            name: quote.name ?? '',
            email: quote.email ?? '',
            phone: quote.phone ?? '',
            guest_count: quote.guest_count ?? '',
            package_name: quote.package_name ?? '',
            services_requested_input: serviceListToInput(quote.services_requested),
            travel_area: quote.travel_area ?? '',
            venue_type: quote.venue_type ?? '',
            heard_about: quote.heard_about ?? '',
            notes: quote.notes ?? '',
            terms_accepted: Boolean(quote.terms_accepted),
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
        phone: item.phone,
        guest_count: item.guest_count,
        package_name: item.package_name,
        services_requested_input: item.services_requested_input,
        travel_area: item.travel_area,
        venue_type: item.venue_type,
        heard_about: item.heard_about,
        notes: item.notes,
        terms_accepted: item.terms_accepted,
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
        await nextTick();
        scrollToFocusedQuote();
    } catch {
        loadError.value = 'Failed to load quotes';
    } finally {
        loading.value = false;
    }
}

function initializeFocusedQuoteFromQuery() {
    const params = new URLSearchParams(window.location.search);
    const requestedQuoteId = String(params.get('quote_id') ?? '').trim();
    const hashQuoteId = window.location.hash.startsWith('#quote-')
        ? window.location.hash.replace('#quote-', '').trim()
        : '';

    focusQuoteId.value = requestedQuoteId || hashQuoteId;
}

function scrollToFocusedQuote(attempt = 0) {
    if (!focusQuoteId.value) {
        return;
    }

    const target = document.getElementById(`quote-${focusQuoteId.value}`);

    if (!target) {
        if (attempt < 5) {
            window.setTimeout(() => {
                scrollToFocusedQuote(attempt + 1);
            }, 120);
        }

        return;
    }

    const scrollTop = Math.max(target.getBoundingClientRect().top + window.scrollY - 92, 0);

    window.scrollTo({
        top: scrollTop,
        behavior: 'smooth',
    });
}

async function createQuote() {
    creating.value = true;
    createError.value = '';
    createSuccess.value = '';

    try {
        const response = await fetchWithCsrfRetry('/admin/quotes', {
            method: 'POST',
            credentials: 'same-origin',
            headers: jsonHeaders(),
            body: JSON.stringify(withCsrfToken(buildPayload(createForm.value))),
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
        const response = await fetchWithCsrfRetry(`/admin/quotes/${item.id}`, {
            method: 'PUT',
            credentials: 'same-origin',
            headers: jsonHeaders(),
            body: JSON.stringify(withCsrfToken(buildPayload(item._draft))),
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
        const response = await fetchWithCsrfRetry(`/admin/quotes/${item.id}/send-email`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: jsonHeaders(),
            body: JSON.stringify(withCsrfToken({})),
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

function requestDeclineQuote(item) {
    if (item.client_confirmed_at) {
        item._decline_error = 'Confirmed quotes cannot be declined.';

        return;
    }

    quotePendingDecline.value = item;
    declineReasonInput.value = item.artist_decline_reason || defaultDeclineReason;
    showDeclineDialog.value = true;
}

function closeDeclineDialog() {
    showDeclineDialog.value = false;
}

function handleDeclineDialogCancel(event) {
    event.preventDefault();
    closeDeclineDialog();
}

function handleDeclineDialogClose() {
    showDeclineDialog.value = false;
    quotePendingDecline.value = null;
    declineReasonInput.value = '';
}

function handleDeclineDialogBackdropClick(event) {
    if (event.target === declineDialogRef.value) {
        closeDeclineDialog();
    }
}

async function confirmDeclineQuote() {
    const item = quotePendingDecline.value;
    if (!item) {
        return;
    }

    const reason = declineReasonInput.value.trim() || defaultDeclineReason;

    closeDeclineDialog();

    item._declining = true;
    item._decline_success = '';
    item._decline_error = '';

    try {
        const response = await fetchWithCsrfRetry(`/admin/quotes/${item.id}/decline`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: jsonHeaders(),
            body: JSON.stringify(withCsrfToken({
                reason,
            })),
        });

        const data = await parseJson(response);

        if (!response.ok || !data.success) {
            item._decline_error = response.status === 419
                ? 'Session expired. Refresh and try again.'
                : data.message || data.error || 'Failed to decline quote';

            return;
        }

        if (data.quote) {
            applyQuoteData(item, data.quote);
        }

        item._decline_success = data.message || 'Quote marked as declined.';
    } catch {
        item._decline_error = 'Failed to decline quote';
    } finally {
        item._declining = false;
    }
}

watch(
    () => showDeclineDialog.value,
    (open) => {
        if (!declineDialogRef.value) {
            return;
        }

        if (open && !declineDialogRef.value.open) {
            declineDialogRef.value.showModal();
            return;
        }

        if (!open && declineDialogRef.value.open) {
            declineDialogRef.value.close();
        }
    },
);

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
        const response = await fetchWithCsrfRetry(`/admin/quotes/${item.id}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: jsonHeaders(),
            body: JSON.stringify(withCsrfToken({})),
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
    initializeFocusedQuoteFromQuery();
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
                        <label class="field-label">Phone
                            <input v-model="createForm.phone" type="text" class="input" />
                        </label>
                        <label class="field-label">Guest Count
                            <input v-model="createForm.guest_count" type="number" min="1" class="input" />
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="field-label">Package
                            <input v-model="createForm.package_name" type="text" class="input" />
                        </label>
                        <label class="field-label">Services (comma separated)
                            <input v-model="createForm.services_requested_input" type="text" class="input" />
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="field-label">Travel Area
                            <input v-model="createForm.travel_area" type="text" class="input" />
                        </label>
                        <label class="field-label">Venue Type
                            <select v-model="createForm.venue_type" class="input">
                                <option value="">Select venue</option>
                                <option value="indoor">Indoor</option>
                                <option value="outdoor">Outdoor</option>
                                <option value="mixed">Indoor + Outdoor</option>
                                <option value="unsure">Not sure yet</option>
                            </select>
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="field-label">How they heard about us
                            <input v-model="createForm.heard_about" type="text" class="input" />
                        </label>
                        <label class="field-label">Terms Accepted
                            <select v-model="createForm.terms_accepted" class="input">
                                <option :value="false">No</option>
                                <option :value="true">Yes</option>
                            </select>
                        </label>
                    </div>

                    <label class="field-label">Notes
                        <textarea v-model="createForm.notes" class="input min-h-[90px] resize-y"></textarea>
                    </label>

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
                        :id="`quote-${quote.id}`"
                        :class="[
                            'rounded-xl border border-slate-200 bg-slate-50 p-3 md:p-4',
                            String(quote.id) === focusQuoteId ? 'ring-2 ring-sky-300 ring-offset-2' : '',
                        ]"
                    >
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
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
                                <label class="field-label">Phone
                                    <input v-model="quote._draft.phone" type="text" class="input" />
                                </label>
                                <label class="field-label">Guest Count
                                    <input v-model="quote._draft.guest_count" type="number" min="1" class="input" />
                                </label>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2">
                                <label class="field-label">Package
                                    <input v-model="quote._draft.package_name" type="text" class="input" />
                                </label>
                                <label class="field-label">Services (comma separated)
                                    <input v-model="quote._draft.services_requested_input" type="text" class="input" />
                                </label>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2">
                                <label class="field-label">Travel Area
                                    <input v-model="quote._draft.travel_area" type="text" class="input" />
                                </label>
                                <label class="field-label">Venue Type
                                    <select v-model="quote._draft.venue_type" class="input">
                                        <option value="">Select venue</option>
                                        <option value="indoor">Indoor</option>
                                        <option value="outdoor">Outdoor</option>
                                        <option value="mixed">Indoor + Outdoor</option>
                                        <option value="unsure">Not sure yet</option>
                                    </select>
                                </label>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2">
                                <label class="field-label">How they heard about us
                                    <input v-model="quote._draft.heard_about" type="text" class="input" />
                                </label>
                                <label class="field-label">Terms Accepted
                                    <select v-model="quote._draft.terms_accepted" class="input">
                                        <option :value="false">No</option>
                                        <option :value="true">Yes</option>
                                    </select>
                                </label>
                            </div>

                            <label class="field-label">Notes
                                <textarea v-model="quote._draft.notes" class="input min-h-[90px] resize-y"></textarea>
                            </label>

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

                        <div v-else class="space-y-3 text-sm text-slate-700">
                            <dl class="quote-grid">
                                <div class="quote-item"><dt>Name</dt><dd>{{ quote.name || '—' }}</dd></div>
                                <div class="quote-item"><dt>Email</dt><dd>{{ quote.email || '—' }}</dd></div>
                                <div class="quote-item"><dt>Phone</dt><dd>{{ quote.phone || '—' }}</dd></div>
                                <div class="quote-item"><dt>Guest Count</dt><dd>{{ quote.guest_count || '—' }}</dd></div>
                                <div class="quote-item"><dt>Package</dt><dd>{{ quote.package_name || '—' }}</dd></div>
                                <div class="quote-item"><dt>Services</dt><dd>{{ quote.services_requested_input || '—' }}</dd></div>
                                <div class="quote-item"><dt>Travel Area</dt><dd>{{ quote.travel_area || '—' }}</dd></div>
                                <div class="quote-item"><dt>Venue Type</dt><dd>{{ formatVenueType(quote.venue_type) }}</dd></div>
                                <div class="quote-item"><dt>Heard About</dt><dd>{{ quote.heard_about || '—' }}</dd></div>
                                <div class="quote-item"><dt>Event Type</dt><dd>{{ quote.event_type || '—' }}</dd></div>
                                <div class="quote-item"><dt>Event Date</dt><dd>{{ quote.event_date || '—' }}</dd></div>
                                <div class="quote-item"><dt>Start / End</dt><dd>{{ quote.start_time || '—' }} - {{ quote.end_time || '—' }}</dd></div>
                                <div class="quote-item"><dt>Total Hours</dt><dd>{{ quote.total_hours ?? '—' }}</dd></div>
                                <div class="quote-item"><dt>Address</dt><dd>{{ quote.address || '—' }}</dd></div>
                                <div class="quote-item"><dt>Terms</dt><dd>{{ quote.terms_accepted ? 'Yes' : 'No' }}</dd></div>
                                <div class="quote-item"><dt>Terms At</dt><dd>{{ formatDateTime(quote.terms_accepted_at) }}</dd></div>
                                <div class="quote-item"><dt>Anonymous ID</dt><dd>{{ quote.anonymous_id || '—' }}</dd></div>
                                <div class="quote-item quote-item--full"><dt>Notes</dt><dd class="whitespace-pre-line break-words">{{ quote.notes || '—' }}</dd></div>
                            </dl>

                            <div v-if="hasCalculationBreakdown(quote)" class="quote-subcard">
                                <p class="quote-subcard-title">Calculation</p>
                                <dl class="quote-grid quote-grid--calc">
                                    <div class="quote-item"><dt>Payment Type</dt><dd>{{ formatPaymentType(quote.calc_payment_type) }}</dd></div>
                                    <div class="quote-item"><dt>Base</dt><dd>{{ formatCurrency(quote.calc_base_amount) }}</dd></div>
                                    <div v-if="hasAddOnBreakdown(quote)" class="quote-item quote-item--full"><dt>Add-ons</dt><dd>{{ formatAddOnBreakdown(quote) }}</dd></div>
                                    <div v-if="addOnTotalAmount(quote) !== null" class="quote-item"><dt>Add-on Total</dt><dd>{{ formatCurrency(addOnTotalAmount(quote)) }}</dd></div>
                                    <div class="quote-item"><dt>Setup</dt><dd>{{ formatCurrency(quote.calc_setup_amount) }}</dd></div>
                                    <div class="quote-item"><dt>Travel</dt><dd>{{ formatCurrency(quote.calc_travel_amount) }}</dd></div>
                                    <div class="quote-item"><dt>Subtotal</dt><dd>{{ formatCurrency(quote.calc_subtotal) }}</dd></div>
                                    <div v-if="shouldShowGst(quote)" class="quote-item"><dt>GST</dt><dd>{{ formatCurrency(quote.calc_gst_amount) }}</dd></div>
                                    <div class="quote-item"><dt>Total</dt><dd>{{ formatCurrency(quote.calc_total_amount) }}</dd></div>
                                </dl>
                            </div>

                            <p v-if="quote.artist_decline_reason" class="compact-note"><strong>Decline Reason:</strong> {{ quote.artist_decline_reason }}</p>
                            <p v-if="quote.client_suggested_time_at" class="compact-note">
                                <strong>Suggested New Time:</strong>
                                {{ quote.client_suggested_event_date || '—' }}
                                {{ quote.client_suggested_start_time || '—' }} - {{ quote.client_suggested_end_time || '—' }}
                                (submitted {{ formatDateTime(quote.client_suggested_time_at) }})
                            </p>
                            <p v-if="quote.client_suggested_time_notes" class="compact-note"><strong>Suggestion Notes:</strong> {{ quote.client_suggested_time_notes }}</p>

                            <p class="status-line">
                                <strong>Email Status:</strong>
                                <span :class="emailStatusPillClass(quote)">{{ formatEmailSendStatus(quote) }}</span>
                            </p>
                            <p class="status-line">
                                <strong>Opened:</strong>
                                <span :class="openedStatusPillClass(quote)">{{ formatOpenTrackingStatus(quote) }}</span>
                            </p>
                            <p class="status-line">
                                <strong>Booking Status:</strong>
                                <span :class="confirmedStatusPillClass(quote)">{{ formatConfirmationStatus(quote) }}</span>
                            </p>

                            <div class="mt-2 flex flex-wrap gap-2">
                                <Link class="primary-btn" :href="calculatorUrl(quote)">Calculate</Link>
                                <button class="primary-btn" :disabled="quote._sending_email" @click="sendQuoteEmail(quote)">
                                    {{ quote._sending_email ? 'Sending...' : 'Send Quote Email' }}
                                </button>
                                <button
                                    class="danger-btn"
                                    :disabled="quote._declining || Boolean(quote.client_confirmed_at)"
                                    @click="requestDeclineQuote(quote)"
                                >
                                    {{ quote._declining ? 'Declining...' : 'Decline (Time Unavailable)' }}
                                </button>
                                <button class="secondary-btn" @click="startEdit(quote)">Edit</button>
                                <button class="danger-btn" :disabled="quote._saving" @click="requestDeleteQuote(quote)">Delete</button>
                            </div>
                        </div>

                        <p v-if="quote._error" class="mt-2 text-sm font-semibold text-rose-700">{{ quote._error }}</p>
                        <p v-if="quote._email_success" class="mt-2 text-sm font-semibold text-emerald-700">{{ quote._email_success }}</p>
                        <p v-if="quote._email_error" class="mt-2 text-sm font-semibold text-rose-700">{{ quote._email_error }}</p>
                        <p v-if="quote._decline_success" class="mt-2 text-sm font-semibold text-emerald-700">{{ quote._decline_success }}</p>
                        <p v-if="quote._decline_error" class="mt-2 text-sm font-semibold text-rose-700">{{ quote._decline_error }}</p>
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

    <dialog
        ref="declineDialogRef"
        class="decline-dialog"
        @cancel="handleDeclineDialogCancel"
        @close="handleDeclineDialogClose"
        @click="handleDeclineDialogBackdropClick"
    >
        <section class="decline-panel">
            <h3 class="decline-title">Decline Quote</h3>
            <p class="decline-message">
                {{
                    quotePendingDecline
                        ? `Add a reason for declining quote #${quotePendingDecline.id} (${quotePendingDecline.name || quotePendingDecline.email}).`
                        : 'Add a reason for declining this quote.'
                }}
            </p>

            <label class="decline-label" for="declineReason">Decline reason</label>
            <textarea
                id="declineReason"
                v-model="declineReasonInput"
                class="decline-textarea"
                rows="4"
                maxlength="1000"
                placeholder="Requested time does not suit our availability. Please reply with another preferred time."
            ></textarea>

            <div class="decline-actions">
                <button type="button" class="secondary-btn" @click="closeDeclineDialog">Cancel</button>
                <button
                    type="button"
                    class="danger-btn"
                    :disabled="Boolean(quotePendingDecline && quotePendingDecline._declining)"
                    @click="confirmDeclineQuote"
                >
                    {{
                        quotePendingDecline && quotePendingDecline._declining
                            ? 'Declining...'
                            : 'Decline Quote'
                    }}
                </button>
            </div>
        </section>
    </dialog>
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

.decline-dialog {
    max-width: 34rem;
    width: calc(100% - 2rem);
    border: none;
    padding: 0;
    background: transparent;
}

.decline-dialog::backdrop {
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(2px);
}

.decline-panel {
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    padding: 1rem;
    box-shadow: 0 18px 42px rgba(15, 23, 42, 0.2);
}

.decline-title {
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
}

.decline-message {
    margin-top: 0.5rem;
    color: #475569;
    line-height: 1.5;
}

.decline-label {
    display: block;
    margin-top: 0.9rem;
    font-size: 0.85rem;
    font-weight: 700;
    color: #334155;
}

.decline-textarea {
    margin-top: 0.35rem;
    width: 100%;
    border-radius: 0.75rem;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #0f172a;
    padding: 0.65rem 0.8rem;
    min-height: 6.5rem;
    resize: vertical;
}

.decline-textarea:focus {
    outline: none;
    border-color: #f43f5e;
    box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.16);
}

.decline-actions {
    margin-top: 1rem;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

.quote-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 0.4rem 0.9rem;
}

.quote-grid--calc {
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
}

.quote-item {
    display: grid;
    grid-template-columns: 96px minmax(0, 1fr);
    align-items: start;
    gap: 0.35rem;
    line-height: 1.3;
}

.quote-item dt {
    font-weight: 700;
    color: #475569;
}

.quote-item dd {
    margin: 0;
    color: #0f172a;
    min-width: 0;
    word-break: break-word;
}

.quote-item--full {
    grid-column: 1 / -1;
}

.quote-subcard {
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    padding: 0.65rem 0.75rem;
}

.quote-subcard-title {
    margin: 0 0 0.4rem 0;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #64748b;
}

.compact-note {
    margin: 0;
    line-height: 1.35;
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

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirm',
    },
    message: {
        type: String,
        default: '',
    },
    confirmText: {
        type: String,
        default: 'Confirm',
    },
    cancelText: {
        type: String,
        default: 'Cancel',
    },
    danger: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:open', 'confirm']);

const dialogRef = ref(null);

function closeDialog() {
    emit('update:open', false);
}

function handleCancel(event) {
    event.preventDefault();
    closeDialog();
}

function handleClose() {
    emit('update:open', false);
}

function handleBackdropClick(event) {
    if (event.target === dialogRef.value) {
        closeDialog();
    }
}

function handleConfirm() {
    emit('confirm');
    closeDialog();
}

watch(
    () => props.open,
    (open) => {
        if (!dialogRef.value) {
            return;
        }

        if (open && !dialogRef.value.open) {
            dialogRef.value.showModal();
            return;
        }

        if (!open && dialogRef.value.open) {
            dialogRef.value.close();
        }
    },
);
</script>

<template>
    <dialog
        ref="dialogRef"
        class="confirm-dialog"
        @cancel="handleCancel"
        @close="handleClose"
        @click="handleBackdropClick"
    >
        <section class="panel">
            <h3 class="title">{{ title }}</h3>
            <p class="message">{{ message }}</p>

            <div class="actions">
                <button
                    v-if="cancelText"
                    type="button"
                    class="secondary-btn"
                    @click="closeDialog"
                >
                    {{ cancelText }}
                </button>
                <button
                    type="button"
                    :class="danger ? 'danger-btn' : 'primary-btn'"
                    @click="handleConfirm"
                >
                    {{ confirmText }}
                </button>
            </div>
        </section>
    </dialog>
</template>

<style scoped>
.confirm-dialog {
    max-width: 32rem;
    width: calc(100% - 2rem);
    border: none;
    padding: 0;
    background: transparent;
}

.confirm-dialog::backdrop {
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(2px);
}

.panel {
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    padding: 1rem;
    box-shadow: 0 18px 42px rgba(15, 23, 42, 0.2);
}

.title {
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
}

.message {
    margin-top: 0.5rem;
    color: #475569;
    line-height: 1.5;
}

.actions {
    margin-top: 1rem;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

.primary-btn,
.secondary-btn,
.danger-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    font-weight: 700;
    padding: 0.55rem 0.9rem;
    transition: background 0.2s ease, border-color 0.2s ease;
}

.primary-btn {
    border: 1px solid rgba(224, 247, 255, 0.95);
    background: linear-gradient(135deg, #ecfeff, #bae6fd);
    color: #0f172a;
}

.secondary-btn {
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #334155;
}

.secondary-btn:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}

.danger-btn {
    border: 1px solid #fecdd3;
    background: #fff1f2;
    color: #9f1239;
}

.danger-btn:hover {
    background: #ffe4e6;
    border-color: #fda4af;
}
</style>

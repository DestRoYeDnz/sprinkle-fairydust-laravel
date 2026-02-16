<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="auth-shell">
        <div class="auth-stars"></div>
        <div class="auth-orb auth-orb-one"></div>
        <div class="auth-orb auth-orb-two"></div>

        <div class="auth-content">
            <Link :href="home()" class="auth-logo-link" aria-label="Go to home">
                <img src="/images/logo.png" alt="Sprinkle Fairydust Logo" class="auth-logo" />
            </Link>

            <section class="auth-card">
                <header class="auth-header">
                    <h1 v-if="title" class="auth-title font-dancing">{{ title }}</h1>
                    <p v-if="description" class="auth-description">
                        {{ description }}
                    </p>
                </header>

                <div class="auth-form">
                    <slot />
                </div>
            </section>
        </div>
    </div>
</template>

<style scoped>
.auth-shell {
    position: relative;
    min-height: 100svh;
    padding: 2.25rem 1rem 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: linear-gradient(
        120deg,
        #1a4f7a 0%,
        #2b7db1 34%,
        #4bb7c8 66%,
        #9edff2 100%
    );
    background-size: 280% 280%;
    animation: authSkyDrift 20s ease infinite;
}

.auth-stars {
    position: absolute;
    inset: 0;
    pointer-events: none;
    background-image:
        radial-gradient(circle at 12% 18%, rgba(255, 255, 255, 0.45) 0 1.1px, transparent 1.1px),
        radial-gradient(circle at 72% 28%, rgba(255, 255, 255, 0.34) 0 1px, transparent 1px),
        radial-gradient(circle at 34% 72%, rgba(255, 255, 255, 0.3) 0 1px, transparent 1px),
        radial-gradient(circle at 84% 80%, rgba(255, 255, 255, 0.28) 0 1px, transparent 1px);
    background-size: 190px 190px, 230px 230px, 240px 240px, 210px 210px;
    opacity: 0.55;
}

.auth-orb {
    position: absolute;
    border-radius: 999px;
    pointer-events: none;
    filter: blur(2px);
}

.auth-orb-one {
    width: 320px;
    height: 320px;
    top: -130px;
    right: -80px;
    background: radial-gradient(
        circle,
        rgba(191, 242, 255, 0.5) 0%,
        rgba(191, 242, 255, 0) 72%
    );
}

.auth-orb-two {
    width: 380px;
    height: 380px;
    left: -140px;
    bottom: -140px;
    background: radial-gradient(
        circle,
        rgba(167, 229, 241, 0.45) 0%,
        rgba(167, 229, 241, 0) 74%
    );
}

.auth-content {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 430px;
}

.auth-logo-link {
    display: block;
    width: fit-content;
    margin: 0 auto 1rem;
}

.auth-logo {
    width: clamp(168px, 34vw, 218px);
    filter: drop-shadow(0 8px 20px rgba(4, 27, 54, 0.35));
    transition: transform 0.2s ease;
}

.auth-logo-link:hover .auth-logo {
    transform: scale(1.03);
}

.auth-card {
    border-radius: 1.2rem;
    border: 1px solid rgba(255, 255, 255, 0.34);
    padding: 1.15rem;
    backdrop-filter: blur(11px);
    background: linear-gradient(
        150deg,
        rgba(255, 255, 255, 0.2),
        rgba(186, 230, 253, 0.1)
    );
    box-shadow:
        0 16px 34px rgba(4, 27, 54, 0.24),
        inset 0 1px 0 rgba(255, 255, 255, 0.26);
}

.auth-header {
    margin-bottom: 1rem;
    text-align: center;
}

.auth-title {
    margin: 0;
    font-size: clamp(1.75rem, 3.6vw, 2.2rem);
    color: #f4fcff;
    text-shadow: 0 3px 10px rgba(5, 31, 58, 0.36);
    font-weight: 700;
}

.auth-description {
    margin: 0.45rem auto 0;
    max-width: 32ch;
    font-size: 0.95rem;
    line-height: 1.4;
    color: rgba(234, 250, 255, 0.9);
}

.auth-form :deep(label) {
    color: #edf9ff;
    font-weight: 600;
    font-family: 'Quicksand', sans-serif;
}

.auth-form :deep([data-slot='input']) {
    height: 2.65rem;
    border-color: rgba(255, 255, 255, 0.38);
    background: rgba(255, 255, 255, 0.18);
    color: #fff;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.15);
}

.auth-form :deep([data-slot='input']::placeholder) {
    color: rgba(229, 247, 255, 0.7);
}

.auth-form :deep([data-slot='input']:focus-visible) {
    border-color: rgba(186, 230, 253, 0.9);
    box-shadow:
        0 0 0 3px rgba(125, 211, 252, 0.35),
        inset 0 1px 0 rgba(255, 255, 255, 0.18);
}

.auth-form :deep([data-slot='checkbox']) {
    border-color: rgba(255, 255, 255, 0.5);
    background: rgba(255, 255, 255, 0.2);
}

.auth-form :deep([data-slot='checkbox'][data-state='checked']) {
    border-color: rgba(191, 242, 255, 0.95);
    background: rgba(14, 116, 144, 0.72);
}

.auth-form :deep([data-slot='button']) {
    border: 1px solid rgba(255, 255, 255, 0.75);
    color: #07445e;
    background: linear-gradient(
        135deg,
        rgba(240, 253, 255, 0.98),
        rgba(207, 250, 254, 0.95)
    );
    box-shadow:
        0 8px 18px rgba(5, 31, 58, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.55);
}

.auth-form :deep([data-slot='button']:hover) {
    transform: translateY(-1px);
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.99),
        rgba(224, 247, 255, 0.96)
    );
}

.auth-form :deep([data-slot='button']:disabled) {
    opacity: 0.7;
}

.auth-form :deep([data-slot='input-otp-slot']) {
    border-color: rgba(255, 255, 255, 0.45);
    background: rgba(255, 255, 255, 0.18);
    color: #fff;
}

.auth-form :deep(a) {
    color: #e4f9ff;
    text-decoration-color: rgba(228, 249, 255, 0.7);
}

.auth-form :deep(.text-foreground) {
    color: #e4f9ff;
}

.auth-form :deep(a:hover) {
    color: #fff;
    text-decoration-color: rgba(255, 255, 255, 1);
}

.auth-form :deep(.text-muted-foreground) {
    color: rgba(234, 250, 255, 0.86);
}

.auth-form :deep(.text-green-600) {
    color: #bff9da;
}

.auth-form :deep(.text-red-600),
.auth-form :deep(.dark\:text-red-500) {
    color: #ffd3e1;
}

@keyframes authSkyDrift {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

@media (max-width: 640px) {
    .auth-shell {
        padding-top: 1.5rem;
    }

    .auth-card {
        border-radius: 1rem;
        padding: 1rem;
    }
}
</style>

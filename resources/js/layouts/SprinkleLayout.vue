<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const page = usePage();

const links = [
    { label: 'Home', href: '/' },
    { label: 'About', href: '/about' },
    { label: 'FAQ', href: '/faq' },
    { label: 'Terms', href: '/terms-and-conditions' },
    { label: 'Services', href: '/services' },
    { label: 'Events', href: '/events' },
    { label: 'Gallery', href: '/gallery' },
    { label: 'Our Designs', href: '/designs' },
    { label: 'Testimonials', href: '/testimonials' },
    { label: 'Get a Quote', href: '/quote' },
];

const currentPath = computed(() => page.url.split('?')[0]);
const isAdminPage = computed(() => currentPath.value === '/admin' || currentPath.value.startsWith('/admin/'));
const showMobileQuoteCta = computed(() => !isAdminPage.value && currentPath.value !== '/quote');
const starsRef = ref(null);
const navOpen = ref(false);
const publicNavId = 'public-site-nav';

function isActive(href) {
    return currentPath.value === href;
}

function closeNav() {
    navOpen.value = false;
}

function toggleNav() {
    navOpen.value = !navOpen.value;
}

function handleNavLinkClick() {
    closeNav();
}

function handleViewportChange() {
    if (typeof window === 'undefined') {
        return;
    }

    if (window.innerWidth > 1024) {
        closeNav();
    }
}

function renderStars() {
    if (!starsRef.value) {
        return;
    }

    starsRef.value.innerHTML = '';

    const numStars = 200;

    for (let i = 0; i < numStars; i += 1) {
        const star = document.createElement('div');
        star.classList.add('star');
        star.style.left = `${Math.random() * 100}vw`;
        star.style.top = `${Math.random() * -100}vh`;
        star.style.animationDuration = `${6 + Math.random() * 10}s`;
        star.style.animationDelay = `${Math.random() * 3}s`;
        starsRef.value.appendChild(star);
    }
}

onMounted(() => {
    document.documentElement.classList.add('sprinkle-theme');
    document.body.classList.add('sprinkle-theme');
    renderStars();
    window.addEventListener('resize', handleViewportChange);
});

watch(
    () => page.url,
    () => {
        renderStars();
        closeNav();
    },
);

onBeforeUnmount(() => {
    document.documentElement.classList.remove('sprinkle-theme');
    document.body.classList.remove('sprinkle-theme');
    window.removeEventListener('resize', handleViewportChange);
    if (starsRef.value) {
        starsRef.value.innerHTML = '';
    }
});
</script>

<template>
    <div class="sprinkle-shell">
        <div ref="starsRef" class="stars"></div>

        <div v-if="!isAdminPage" class="nav-wrap" @keydown.esc="closeNav">
            <button
                type="button"
                class="nav-toggle"
                :class="{ 'is-open': navOpen }"
                :aria-controls="publicNavId"
                :aria-expanded="navOpen ? 'true' : 'false'"
                aria-label="Toggle navigation menu"
                @click="toggleNav"
            >
                <span class="nav-toggle__bars" aria-hidden="true">
                    <span class="nav-toggle__bar"></span>
                    <span class="nav-toggle__bar"></span>
                    <span class="nav-toggle__bar"></span>
                </span>
                <span class="nav-toggle__text">{{ navOpen ? 'Close' : 'Menu' }}</span>
            </button>

            <nav :id="publicNavId" class="nav" :class="{ 'nav--open': navOpen }">
                <Link
                    v-for="link in links"
                    :key="link.href"
                    :href="link.href"
                    :class="{ active: isActive(link.href) }"
                    @click="handleNavLinkClick"
                >
                    {{ link.label }}
                </Link>
            </nav>
        </div>

        <div class="sprinkle-content">
            <slot />
        </div>

        <Link v-if="showMobileQuoteCta" href="/quote" class="mobile-quote-cta">
            Get Quote
        </Link>

        <footer class="footer-glow">
            <p>
                © {{ new Date().getFullYear() }}
                <span class="brand">Sprinkle Fairydust</span>
                · Designed with love by Melody ✨
            </p>

            <p class="footer-links">
                <Link href="/terms-and-conditions">Terms and Conditions</Link>
            </p>

            <div class="social-links">
                <a
                    href="https://www.facebook.com/melfairysfacepainting"
                    target="_blank"
                    rel="noopener"
                    aria-label="Facebook"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M22.675 0h-21.35C.597 0 0 .6 0 1.333v21.334C0 23.4.597 24 1.325 24h11.495v-9.333H9.692V11.33h3.128V8.413c0-3.1 1.894-4.788 4.659-4.788 1.325 0 2.463.1 2.796.144v3.246h-1.922c-1.506 0-1.798.718-1.798 1.772v2.348h3.588l-.467 3.337h-3.121V24h6.116C23.403 24 24 23.4 24 22.667V1.333C24 .6 23.403 0 22.675 0z"
                        />
                    </svg>
                </a>

                <a
                    href="https://www.instagram.com/sprinkle_fairydust_facepaint/"
                    target="_blank"
                    rel="noopener"
                    aria-label="Instagram"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M12 2.163c3.204 0 3.584.012 4.85.07 1.366.062 2.633.333 3.608 1.308.975.975 1.246 2.242 1.308 3.608.058 1.266.07 1.646.07 4.85s-.012 3.584-.07 4.85c-.062 1.366-.333 2.633-1.308 3.608-.975.975-2.242 1.246-3.608 1.308-1.266.058-1.646.07-4.85.07s-3.584-.012-4.85-.07c-1.366-.062-2.633-.333-3.608-1.308-.975-.975-1.246-2.242-1.308-3.608C2.175 15.584 2.163 15.204 2.163 12s.012-3.584.07-4.85c.062-1.366.333-2.633 1.308-3.608C4.516 2.566 5.783 2.295 7.15 2.233 8.416 2.175 8.796 2.163 12 2.163zm0 1.837c-3.18 0-3.555.012-4.805.07-1.047.048-1.617.218-1.99.39-.484.223-.83.49-1.197.857-.367.367-.634.713-.857 1.197-.172.373-.342.943-.39 1.99-.058 1.25-.07 1.625-.07 4.805s.012 3.555.07 4.805c.048 1.047.218 1.617.39 1.99.223.484.49.83.857 1.197.367.367.713.634 1.197.857.373.172.943.342 1.99.39 1.25.058 1.625.07 4.805.07s3.555-.012 4.805-.07c1.047-.048 1.617-.218 1.99-.39.484-.223.83-.49 1.197-.857.367-.367.634-.713.857-1.197.172-.373.342-.943.39-1.99.058-1.25.07-1.625.07-4.805s-.012-3.555-.07-4.805c-.048-1.047-.218-1.617-.39-1.99a3.232 3.232 0 0 0-.857-1.197 3.232 3.232 0 0 0-1.197-.857c-.373-.172-.943-.342-1.99-.39-1.25-.058-1.625-.07-4.805-.07zm0 3.784a5.216 5.216 0 1 1 0 10.432 5.216 5.216 0 0 1 0-10.432zm0 8.6a3.384 3.384 0 1 0 0-6.768 3.384 3.384 0 0 0 0 6.768zm6.406-9.847a1.218 1.218 0 1 1-2.437 0 1.218 1.218 0 0 1 2.437 0z"
                        />
                    </svg>
                </a>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.sprinkle-shell {
    min-height: 100svh;
    display: flex;
    flex-direction: column;
}

.sprinkle-content {
    flex: 1 0 auto;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.footer-glow {
    margin-top: auto;
    text-align: center;
    padding: 1rem;
    color: #fff;
    font-family: 'Quicksand', sans-serif;
    font-size: 0.9rem;
    background: linear-gradient(
        90deg,
        rgba(26, 79, 122, 0.36),
        rgba(43, 125, 177, 0.32),
        rgba(75, 183, 200, 0.3),
        rgba(158, 223, 242, 0.28)
    );
    background-size: 400% 400%;
    animation: rainbowFlow 12s ease infinite;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(6px);
    z-index: 30;
    position: relative;
}

.brand {
    color: #dbeafe;
    font-weight: 600;
}

.footer-links {
    margin-top: 0.4rem;
}

.footer-links a {
    color: #dbeafe;
    font-weight: 600;
    text-decoration: underline;
    text-underline-offset: 2px;
}

.social-links {
    display: flex;
    justify-content: center;
    align-items: center;
    column-gap: 14px;
    margin-top: 0.75rem;
}

.social-links a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.15);
    transition: transform 0.3s ease, background 0.3s ease;
}

.social-links a:hover {
    transform: scale(1.15);
    background: rgba(255, 255, 255, 0.3);
}

.social-links svg {
    width: 16px;
    height: 16px;
    fill: white;
    display: block;
    transition: transform 0.3s ease, fill 0.3s ease;
}

.social-links a:hover svg {
    transform: scale(1.1);
    fill: #dff7ff;
}

.mobile-quote-cta {
    position: fixed;
    right: 1rem;
    bottom: 1rem;
    z-index: 80;
    border-radius: 999px;
    border: 1px solid rgba(224, 247, 255, 0.95);
    background: linear-gradient(135deg, #ecfeff, #bae6fd);
    color: #0f172a;
    font-weight: 700;
    font-size: 0.85rem;
    padding: 0.6rem 0.95rem;
    box-shadow: 0 12px 24px rgba(2, 6, 23, 0.25);
}

@media (min-width: 768px) {
    .mobile-quote-cta {
        display: none;
    }
}

@keyframes rainbowFlow {
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
</style>

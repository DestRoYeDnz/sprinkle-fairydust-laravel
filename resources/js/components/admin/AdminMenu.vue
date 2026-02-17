<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { logout } from '@/routes';

const page = usePage();

const menuItems = [
    { label: 'Dashboard', href: '/admin' },
    { label: 'Add Event', href: '/admin/events' },
    { label: 'Upload Image', href: '/admin/images' },
    { label: 'Quotes', href: '/admin/quotes' },
    { label: 'Testimonials', href: '/admin/testimonials' },
    { label: 'Add User', href: '/admin/users/create' },
    { label: 'Calculator', href: '/admin/calculator' },
    { label: 'Settings', href: '/admin/settings' },
    { label: 'Tracking', href: '/admin/tracking' },
];

const currentPath = computed(() => page.url.split('?')[0]);
const mobileMenuOpen = ref(false);

function isActive(href) {
    if (href === '/admin') {
        return currentPath.value === '/admin';
    }

    return currentPath.value.startsWith(href);
}

const activeMenuLabel = computed(() =>
    menuItems.find((item) => isActive(item.href))?.label ?? 'Menu',
);

function toggleMobileMenu() {
    mobileMenuOpen.value = !mobileMenuOpen.value;
}

watch(currentPath, () => {
    mobileMenuOpen.value = false;
});
</script>

<template>
    <section
        class="mb-8 rounded-2xl border border-slate-200/90 bg-slate-50/95 p-4 shadow-xl backdrop-blur-md"
    >
        <div class="mb-3 flex items-center justify-between gap-3">
            <p class="text-xs font-bold tracking-[0.14em] text-slate-500 uppercase">Admin Menu</p>
            <div class="flex items-center gap-2">
                <Link
                    href="/"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-sky-200 hover:bg-sky-50"
                >
                    Home
                </Link>
                <Link
                    :href="logout()"
                    as="button"
                    class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100"
                >
                    Log out
                </Link>
            </div>
        </div>

        <button
            type="button"
            class="mb-3 inline-flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-sky-200 hover:bg-sky-50 sm:hidden"
            :aria-expanded="mobileMenuOpen ? 'true' : 'false'"
            @click="toggleMobileMenu"
        >
            <span>{{ activeMenuLabel }}</span>
            <span class="text-xs">{{ mobileMenuOpen ? 'Hide' : 'Show' }}</span>
        </button>

        <nav :class="['gap-2 sm:grid sm:grid-cols-2 lg:grid-cols-9', mobileMenuOpen ? 'grid' : 'hidden']">
            <Link
                v-for="item in menuItems"
                :key="item.href"
                :href="item.href"
                :class="[
                    'rounded-xl border px-3 py-2 text-center text-sm font-semibold transition',
                    isActive(item.href)
                        ? 'border-sky-200 bg-sky-100 text-sky-900 shadow-md'
                        : 'border-slate-200 bg-white text-slate-700 hover:border-sky-200 hover:bg-sky-50',
                ]"
            >
                {{ item.label }}
            </Link>
        </nav>
    </section>
</template>

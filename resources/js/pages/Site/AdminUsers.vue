<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminMenu from '@/components/admin/AdminMenu.vue';
import SprinkleLayout from '../../layouts/SprinkleLayout.vue';

defineOptions({
    layout: SprinkleLayout,
});

const addUserForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    is_admin: false,
});

function createUser() {
    addUserForm.post('/admin/users', {
        preserveScroll: true,
        onSuccess: () => {
            addUserForm.reset();
            addUserForm.clearErrors();
        },
    });
}
</script>

<template>
    <Head title="Add User | Admin" />

    <main class="mx-auto mt-10 max-w-4xl px-4 pb-10 font-quicksand">
        <section class="rounded-3xl border border-slate-200/90 bg-slate-50/90 p-5 shadow-2xl backdrop-blur-md md:p-6">
            <h1 class="mb-2 text-center text-5xl text-slate-900 drop-shadow-sm font-dancing">Add User</h1>
            <p class="mb-8 text-center text-slate-600">Create a new user account from admin.</p>

            <AdminMenu />

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-md">
                <form class="space-y-4" @submit.prevent="createUser">
                    <div>
                        <label for="user-name" class="mb-1 block text-sm font-semibold text-slate-800">Full Name</label>
                        <input id="user-name" v-model="addUserForm.name" placeholder="Full name" class="input" required />
                        <p v-if="addUserForm.errors.name" class="mt-1 text-sm text-rose-700">{{ addUserForm.errors.name }}</p>
                    </div>

                    <div>
                        <label for="user-email" class="mb-1 block text-sm font-semibold text-slate-800">Email Address</label>
                        <input
                            id="user-email"
                            v-model="addUserForm.email"
                            type="email"
                            placeholder="Email address"
                            class="input"
                            required
                        />
                        <p v-if="addUserForm.errors.email" class="mt-1 text-sm text-rose-700">{{ addUserForm.errors.email }}</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="user-password" class="mb-1 block text-sm font-semibold text-slate-800">Password</label>
                            <input
                                id="user-password"
                                v-model="addUserForm.password"
                                type="password"
                                placeholder="Password"
                                class="input"
                                required
                            />
                        </div>

                        <div>
                            <label for="user-password-confirm" class="mb-1 block text-sm font-semibold text-slate-800">
                                Confirm Password
                            </label>
                            <input
                                id="user-password-confirm"
                                v-model="addUserForm.password_confirmation"
                                type="password"
                                placeholder="Confirm password"
                                class="input"
                                required
                            />
                        </div>
                    </div>

                    <p v-if="addUserForm.errors.password" class="text-sm text-rose-700">{{ addUserForm.errors.password }}</p>

                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <input v-model="addUserForm.is_admin" type="checkbox" class="h-4 w-4 rounded border-slate-400" />
                        <span>Grant admin access</span>
                    </label>

                    <button class="primary-btn" :disabled="addUserForm.processing">
                        {{ addUserForm.processing ? 'Creating...' : 'Create User' }}
                    </button>

                    <p v-if="addUserForm.recentlySuccessful" class="font-semibold text-emerald-700">User created successfully.</p>
                </form>
            </section>
        </section>
    </main>
</template>

<style scoped>
.input {
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
</style>

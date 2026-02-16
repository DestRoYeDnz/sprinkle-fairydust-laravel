<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
</script>

<template>
    <AuthBase
        title="Registration disabled"
        description="Accounts are created by an administrator."
    >
        <Head title="Register" />

        <div class="text-center text-sm text-muted-foreground">
            Need access?
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                :tabindex="1"
                >Go to log in</TextLink
            >
        </div>
    </AuthBase>
</template>

<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';
import { useSiteSettings } from '@/composables/useSiteSettings';

const { siteName, siteLogo } = useSiteSettings();

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link :href="home()" class="flex flex-col items-center gap-2 font-medium">
                        <div class="mb-2 flex h-16 w-16 items-center justify-center rounded-md">
                            <img v-if="siteLogo" :src="siteLogo" :alt="siteName" class="h-16 w-16 object-contain" />
                            <AppLogoIcon v-else class="size-12 fill-current text-[var(--foreground)] dark:text-white" />
                        </div>
                        <span class="text-xl font-bold">{{ siteName }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">{{ description }}</p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useSiteSettings } from '@/composables/useSiteSettings';

const { siteName, siteLogo } = useSiteSettings();
</script>

<template>
    <div class="flex aspect-square size-12 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
        <img v-if="siteLogo" :src="siteLogo" :alt="siteName" class="size-12 object-contain rounded-md" />
        <AppLogoIcon v-else class="size-8 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-2 grid flex-1 text-left text-base">
        <span class="mb-0.5 truncate leading-tight font-bold">{{ siteName }}</span>
    </div>
</template>

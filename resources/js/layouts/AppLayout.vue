<script setup lang="ts">
import VerificationBanner from '@/components/VerificationBanner.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <div class="flex flex-col min-h-screen">
        <VerificationBanner />
        <AppLayout :breadcrumbs="breadcrumbs" class="flex-1">
            <slot />
        </AppLayout>
    </div>
</template>

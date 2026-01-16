<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import RankForm from './Partials/RankForm.vue';

defineProps<{
    permissions: any[];
}>();
</script>

<template>
    <Head title="Create Rank" />

    <AppLayout title="Create Rank">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Create Rank
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <RankForm 
                        :permissions="permissions" 
                        :submit-route="route('ranks.store')" 
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

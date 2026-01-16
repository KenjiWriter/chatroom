<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import RankForm from './Partials/RankForm.vue';

defineProps<{
    rank: any;
    permissions: any[];
}>();
</script>

<template>
    <Head title="Edit Rank" />

    <AppLayout title="Edit Rank">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Edit Rank
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <RankForm 
                        :rank="rank"
                        :permissions="permissions" 
                        :submit-route="route('ranks.update', rank.id)" 
                        method="put"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

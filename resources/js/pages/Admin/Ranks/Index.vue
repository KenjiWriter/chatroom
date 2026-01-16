<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import RankedUserLabel from '@/components/RankedUserLabel.vue';

defineProps<{
    ranks: any[];
}>();

const deleteRank = (id: number) => {
    if (confirm('Are you sure you want to delete this rank?')) {
        // In a real app, use router.delete or form helper
        // Using Inertia Link as button wrapper or logical delete
    }
}
</script>

<template>
    <Head title="Manage Ranks" />

    <AppLayout title="Manage Ranks">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Manage Ranks
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex justify-end mb-4">
                    <Link 
                        :href="route('ranks.create')" 
                        class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700"
                    >
                        Create New Rank
                    </Link>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name/Preview</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Effects</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="rank in ranks" :key="rank.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ rank.priority }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <RankedUserLabel :rank="rank" :name="rank.name" />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <div class="flex gap-1">
                                        <span v-if="rank.effects?.glow" class="px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-800">Glow</span>
                                        <span v-if="rank.effects?.rainbow" class="px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-800">Rainbow</span>
                                        <span v-if="rank.effects?.bold" class="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-800">Bold</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link :href="route('ranks.edit', rank.id)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</Link>
                                    <Link :href="route('ranks.destroy', rank.id)" method="delete" as="button" class="text-red-600 hover:text-red-900">Delete</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

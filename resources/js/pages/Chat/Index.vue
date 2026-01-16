<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    rooms: any[];
}>();
</script>

<template>
    <Head title="Chat Rooms" />

    <AppLayout title="Chat Rooms">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Chat Rooms
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="room in rooms" :key="room.id" class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ room.name }}</h3>
                            <span v-if="room.min_level > 0" class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                                Lvl {{ room.min_level }}+
                            </span>
                            <span v-else-if="room.required_rank" class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">
                                {{ room.required_rank.name }}
                            </span>
                        </div>
                        
                        <p class="text-gray-600 dark:text-gray-300 text-sm mb-6 min-h-[40px]">
                            {{ room.description }}
                        </p>

                        <div class="mt-auto">
                            <Link 
                                :href="route('chat.room', room.slug)" 
                                class="inline-flex items-center justify-center w-full px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 disabled:opacity-25 transition"
                            >
                                Join Room
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

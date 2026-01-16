<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { resolveAsset } from '@/lib/utils';
import { route } from 'ziggy-js';
import { MessageSquare } from 'lucide-vue-next';

defineProps<{
    conversations: any[];
}>();
</script>

<template>
    <Head title="Direct Messages" />

    <AppLayout title="Direct Messages">
        <template #header>
            <h2 class="font-semibold text-xl text-foreground px-4 py-6">
                Direct Messages
            </h2>
        </template>

        <div class="max-w-4xl mx-auto p-4 space-y-4">
            <div v-if="conversations.length === 0" class="flex flex-col items-center justify-center py-20 text-muted-foreground bg-card rounded-xl border border-dashed">
                <MessageSquare class="w-12 h-12 opacity-20 mb-4" />
                <p>No active conversations yet.</p>
                <p class="text-sm">Find friends in chat rooms to start messaging!</p>
            </div>

            <Link 
                v-for="conv in conversations" 
                :key="conv.id"
                :href="route('conversations.show', conv.id)"
                class="flex items-center gap-4 p-4 bg-card rounded-xl border border-border hover:border-primary/50 transition-all hover:shadow-md"
            >
                <div class="relative flex-shrink-0">
                    <Avatar class="h-14 w-14 border border-border">
                        <AvatarImage :src="resolveAsset(conv.users[0]?.avatar_url, 'avatar', conv.users[0]?.name) as string" />
                        <AvatarFallback>{{ conv.users[0]?.name.substring(0,2).toUpperCase() }}</AvatarFallback>
                    </Avatar>
                    <span v-if="conv.unread_count > 0" 
                          class="absolute -top-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white border-2 border-background"
                    >
                        {{ conv.unread_count }}
                    </span>
                </div>
                
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-start mb-1">
                        <h3 class="font-bold text-lg truncate">
                            {{ conv.users[0]?.name }}
                        </h3>
                        <span v-if="conv.last_message_at" class="text-xs text-muted-foreground whitespace-nowrap">
                            {{ new Date(conv.last_message_at).toLocaleDateString() }}
                        </span>
                    </div>
                    <p class="text-sm text-muted-foreground line-clamp-1 opacity-80">
                         {{ conv.lastMessage?.sender_id === $page.props.auth.user.id ? 'You: ' : '' }}
                         {{ conv.lastMessage?.body || 'No messages yet.' }}
                    </p>
                </div>
            </Link>
        </div>
    </AppLayout>
</template>

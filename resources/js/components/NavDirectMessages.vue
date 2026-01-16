<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { 
    SidebarGroup, 
    SidebarGroupLabel, 
    SidebarMenu, 
    SidebarMenuButton, 
    SidebarMenuItem 
} from '@/components/ui/sidebar';
import { resolveAsset } from '@/lib/utils';
import { route } from 'ziggy-js';

const page = usePage();
// We use a local ref to allow real-time updates without full Inertia reload if possible, 
// though Inertia might refresh it anyway.
const conversations = computed(() => (page.props as any).conversations || []);
const currentUser = computed(() => page.props.auth.user);

onMounted(() => {
    // @ts-ignore
    window.Echo.private(`conversations.${currentUser.value.id}`)
        .listen('DirectMessageSent', (e: any) => {
            // When a new DM arrives, we reload the conversations prop
            // using router.reload to get fresh counts and last message.
            import('@inertiajs/vue3').then(({ router }) => {
                router.reload({ only: ['conversations'] });
            });
        });
});

onUnmounted(() => {
    if (currentUser.value) {
        // @ts-ignore
        window.Echo.leave(`conversations.${currentUser.value.id}`);
    }
});
</script>

<template>
    <SidebarGroup v-if="conversations.length > 0">
        <SidebarGroupLabel>Direct Messages</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="conv in conversations" :key="conv.id">
                <SidebarMenuButton as-child :active="route().current('conversations.show', conv.id)" class="py-6">
                    <Link :href="route('conversations.show', conv.id)" class="flex items-center gap-3 w-full">
                        <div class="relative flex-shrink-0">
                            <Avatar class="h-8 w-8">
                                <AvatarImage :src="resolveAsset(conv.users[0]?.avatar_url, 'avatar', conv.users[0]?.name) as string" />
                                <AvatarFallback>{{ conv.users[0]?.name.substring(0,2).toUpperCase() }}</AvatarFallback>
                            </Avatar>
                            <span v-if="conv.unread_count > 0" 
                                  class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white border-2 border-background"
                            >
                                {{ conv.unread_count > 9 ? '9+' : conv.unread_count }}
                            </span>
                        </div>
                        <div class="flex flex-col min-w-0 flex-1 overflow-hidden">
                            <span class="text-sm font-medium truncate">{{ conv.users[0]?.name }}</span>
                            <span v-if="conv.last_message" class="text-xs text-muted-foreground truncate opacity-70">
                                {{ conv.last_message.sender_id === currentUser.id ? 'You: ' : '' }}{{ conv.last_message.body }}
                            </span>
                        </div>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>

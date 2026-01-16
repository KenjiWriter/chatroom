<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue';
import RankedUserLabel from '@/components/RankedUserLabel.vue';
import UserPopOver from '@/components/UserPopOver.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import axios from 'axios';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';
import { resolveAsset } from '@/lib/utils';
import { ArrowLeft } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    conversation: any;
    initialMessages: any[];
}>();

const messages = ref<any[]>([...props.initialMessages]);
const newMessage = ref('');
const messagesContainer = ref<HTMLElement | null>(null);
const sending = ref(false);

const page = usePage();
const currentUser = page.props.auth.user;

const otherUser = computed(() => props.conversation.users[0]);

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const sendMessage = async () => {
    if (!newMessage.value.trim() || sending.value) return;

    sending.value = true;
    const body = newMessage.value;
    const tempId = 'temp-' + Date.now();

    const optimisticMessage = {
        id: tempId,
        body: body,
        sender_id: currentUser.id,
        sender: currentUser,
        created_at: new Date().toISOString(),
        is_sending: true,
    };
    
    messages.value.push(optimisticMessage);
    newMessage.value = '';
    scrollToBottom();

    try {
        const response = await axios.post(route('conversations.messages.store', props.conversation.id), {
            body: body
        });

        const realMessage = response.data.message;
        const index = messages.value.findIndex(m => m.id === tempId);
        if (index !== -1) {
            messages.value[index] = realMessage;
        }
    } catch (error: any) {
        messages.value = messages.value.filter(m => m.id !== tempId);
        toast.error('Failed to send message.');
    } finally {
        sending.value = false;
    }
};

onMounted(() => {
    scrollToBottom();

    // Listen for DM Events
    // @ts-ignore
    window.Echo.private(`conversations.room.${props.conversation.id}`)
        .listen('DirectMessageSent', (e: any) => {
            if (e.message.sender_id !== currentUser.id) {
                messages.value.push(e.message);
                scrollToBottom();
                
                // Mark as read immediately if viewing
                axios.post(route('conversations.messages.store', props.conversation.id), { 
                    // This is handled by 'show' route, but for real-time we might need an explicit mark-as-read?
                    // Actually, the show controller marks them, but we need to mark new ones too.
                    // Let's add a separate mark-as-read route or handle it silently.
                });
            }
        });
});

onUnmounted(() => {
    // @ts-ignore
    window.Echo.leave(`conversations.${props.conversation.id}`);
});
</script>

<template>
    <Head :title="'Chat with ' + otherUser.name" />

    <AppLayout :title="'Chat with ' + otherUser.name">
        <template #header>
            <div class="flex items-center h-16 px-4 border-b bg-card gap-4">
                <Link :href="route('dashboard')" class="p-2 hover:bg-muted rounded-full transition-colors">
                    <ArrowLeft class="w-5 h-5" />
                </Link>
                
                <div class="flex items-center gap-3">
                    <Avatar class="h-10 w-10 border border-border">
                        <AvatarImage :src="resolveAsset(otherUser.avatar_url, 'avatar', otherUser.name) as string" />
                        <AvatarFallback>{{ otherUser.name.substring(0,2).toUpperCase() }}</AvatarFallback>
                    </Avatar>
                    <div class="flex flex-col">
                        <RankedUserLabel 
                            :rank="otherUser.rank_data" 
                            :name="otherUser.name"
                            :user-id="otherUser.id"
                            class="font-bold text-lg"
                        />
                        <span class="text-[10px] text-muted-foreground uppercase tracking-wider font-semibold">
                            Private Conversation
                        </span>
                    </div>
                </div>
            </div>
        </template>

        <div class="flex h-[calc(100vh-8.5rem)] overflow-hidden bg-background">
            <div class="flex-1 flex flex-col min-w-0 bg-background relative">
                
                <div 
                    ref="messagesContainer" 
                    class="flex-1 overflow-y-auto p-4 space-y-6 scroll-smooth pb-4"
                >
                    <div v-for="msg in messages" :key="msg.id" class="w-full">
                        <div 
                             class="flex gap-4 max-w-4xl"
                             :class="msg.sender_id === currentUser.id ? 'ml-auto flex-row-reverse' : ''"
                        >
                            <div class="flex-shrink-0">
                                <Avatar class="h-10 w-10 border border-border">
                                    <AvatarImage :src="resolveAsset(msg.sender.avatar_url, 'avatar', msg.sender.name) as string" />
                                    <AvatarFallback>{{ msg.sender.name.substring(0,2).toUpperCase() }}</AvatarFallback>
                                </Avatar>
                            </div>

                            <div class="flex flex-col min-w-0 max-w-[85%] sm:max-w-[75%]"
                                 :class="msg.sender_id === currentUser.id ? 'items-end' : 'items-start'"
                            >
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-semibold text-foreground">
                                        {{ msg.sender.name }}
                                    </span>
                                    <span class="text-[10px] text-muted-foreground opacity-70">
                                        {{ new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                                    </span>
                                </div>

                                <div 
                                    class="relative px-5 py-3 text-sm shadow-sm leading-relaxed"
                                    :class="[
                                        msg.sender_id === currentUser.id 
                                            ? 'bg-primary text-primary-foreground rounded-2xl rounded-tr-sm' 
                                            : 'bg-muted/80 text-foreground border border-border/50 rounded-2xl rounded-tl-sm',
                                        msg.is_sending ? 'opacity-70' : ''
                                    ]"
                                >
                                    {{ msg.body }}
                                </div>
                                <span v-if="msg.is_sending" class="text-[10px] text-muted-foreground mt-1">Sending...</span>
                            </div>
                        </div>
                    </div>
                </div>

                 <div class="p-4 bg-background border-t border-border z-10 w-full max-w-5xl mx-auto">
                    <form @submit.prevent="sendMessage" class="relative flex items-center gap-2">
                        <Input 
                            v-model="newMessage" 
                            type="text" 
                            placeholder="Type a private message..."
                            class="flex-1 py-6 pl-5 pr-12 rounded-full border-muted-foreground/20 bg-muted/30 focus-visible:ring-offset-2 focus-visible:ring-primary/20 shadow-sm"
                            @keydown.enter.prevent="sendMessage"
                            :disabled="sending"
                        />
                        <Button 
                            type="submit" 
                            size="icon"
                            class="absolute right-2 h-10 w-10 rounded-full shadow-md"
                            :disabled="sending || !newMessage.trim()"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-0.5"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                        </Button>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

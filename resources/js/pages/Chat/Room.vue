<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import RankedUserLabel from '@/components/RankedUserLabel.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import axios from 'axios';
import { toast } from 'vue-sonner';

const props = defineProps<{
    room: any;
    initialMessages: any[];
}>();

const messages = ref<any[]>([...props.initialMessages]);
const newMessage = ref('');
const onlineUsers = ref<any[]>([]);
const typingUsers = ref<any[]>([]);
const messagesContainer = ref<HTMLElement | null>(null);
const sending = ref(false);

const page = usePage();
const currentUser = page.props.auth.user;

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

// Optimistic UUID generator
const generateTempId = () => 'temp-' + Date.now() + Math.random().toString(36).substr(2, 9);

const sendMessage = async () => {
    if (!newMessage.value.trim() || sending.value) return;

    sending.value = true;
    const content = newMessage.value;
    const tempId = generateTempId();

    // 1. Optimistic Update
    const optimisticMessage = {
        id: tempId,
        content: content,
        user_id: currentUser.id,
        user: currentUser, // Shared Inertia prop
        created_at: new Date().toISOString(),
        is_sending: true,
    };
    
    messages.value.push(optimisticMessage);
    newMessage.value = '';
    scrollToBottom();

    try {
        const response = await axios.post(route('chat.message', props.room.slug), {
            content: content
        });

        // 2. Replace Optimistic with Real
        const realMessage = response.data.message;
        const index = messages.value.findIndex(m => m.id === tempId);
        if (index !== -1) {
            messages.value[index] = realMessage;
        }

        // 3. Handle XP / Level Up
        if (response.data.xp_result?.leveled_up) {
            // Using flash message structure or direct response
            toast.success(`Level Up! You reached level ${response.data.xp_result.new_level}!`);
        }

    } catch (error) {
        console.error('Failed to send message', error);
        // Remove optimistic message on failure or show error state
        messages.value = messages.value.filter(m => m.id !== tempId);
        toast.error('Failed to send message.');
    } finally {
        sending.value = false;
    }
};

const sendTypingWhisper = () => {
    // @ts-ignore
    window.Echo.private(`chat.room.${props.room.id}`)
        .whisper('typing', {
            id: currentUser.id,
            name: currentUser.name
        });
};

// Throttle typing events
let typingTimer: any = null;
const handleTyping = () => {
    if (!typingTimer) {
        sendTypingWhisper();
        typingTimer = setTimeout(() => {
            typingTimer = null;
        }, 1000);
    }
};

onMounted(() => {
    scrollToBottom();

    // @ts-ignore
    window.Echo.join(`chat.room.presence.${props.room.id}`)
        .here((users: any[]) => {
            onlineUsers.value = users;
        })
        .joining((user: any) => {
            onlineUsers.value.push(user);
        })
        .leaving((user: any) => {
            onlineUsers.value = onlineUsers.value.filter((u: any) => u.id !== user.id);
        })
        .error((error: any) => {
            console.error('Presence channel error:', error);
        });

    // @ts-ignore
    window.Echo.private(`chat.room.${props.room.id}`)
        .listen('MessageSent', (e: any) => {
            // Deduplication (if event comes faster than axios response)
            // But we depend on axios replacing the temp ID. 
            // If this comes first, we might have a duplicate temporarily until axios response resolves?
            // Actually, best practice: if we find a matching temp message (impossible to match strictly without UUID from server),
            // usually optimistic UI assumes "my" messages come from Axios response first.
            // We ignore our own messages from Broadcast to avoid race conditions/flicker.
            
            if (e.message.user_id !== currentUser.id) {
                messages.value.push(e.message);
                scrollToBottom();
            }
        })
        .listenForWhisper('typing', (e: any) => {
            if (!typingUsers.value.find(u => u.id === e.id)) {
                typingUsers.value.push(e);
                setTimeout(() => {
                    typingUsers.value = typingUsers.value.filter(u => u.id !== e.id);
                }, 2000); // Clear after 2s of no typing
            }
        });
});

onUnmounted(() => {
    // @ts-ignore
    window.Echo.leave(`chat.room.presence.${props.room.id}`);
    // @ts-ignore
    window.Echo.leave(`chat.room.${props.room.id}`);
});
</script>

<template>
    <Head :title="room.name" />

    <AppLayout :title="room.name">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ room.name }}
                </h2>
                <span class="text-sm text-gray-500">
                    {{ onlineUsers.length }} Online
                </span>
            </div>
        </template>

        <div class="flex h-[calc(100vh-10rem)] max-w-7xl mx-auto sm:px-6 lg:px-8 py-6 gap-6">
            <!-- Chat Area -->
            <div class="flex flex-col flex-1 bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg overflow-hidden">
                <!-- Messages -->
                <div 
                    ref="messagesContainer" 
                    class="flex-1 overflow-y-auto p-4 space-y-4 scroll-smooth"
                >
                    <div 
                        v-for="msg in messages" 
                        :key="msg.id" 
                        class="flex flex-col"
                        :class="msg.user_id === currentUser.id ? 'items-end' : 'items-start'"
                    >
                        <RankedUserLabel 
                            :rank="msg.user.rank_data" 
                            :name="msg.user.name"
                            :message="msg.content" 
                            class="mb-1"
                        />
                        <div 
                            class="px-4 py-2 rounded-lg max-w-[80%] break-words shadow-sm"
                            :class="[
                                msg.user_id === currentUser.id 
                                    ? 'bg-indigo-600 text-white rounded-br-none' 
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-bl-none',
                                msg.is_sending ? 'opacity-70' : ''
                            ]"
                        >
                           {{ msg.content }}
                        </div>
                        <span v-if="msg.is_sending" class="text-xs text-gray-400 mt-1">Sending...</span>
                    </div>
                </div>
                
                <!-- Typing Indicator -->
                <div v-if="typingUsers.length > 0" class="px-4 py-2 text-xs text-gray-500 italic">
                    {{ typingUsers.map(u => u.name).join(', ') }} is typing...
                </div>

                <!-- Input -->
                <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    <form @submit.prevent="sendMessage" class="flex gap-2">
                        <Input 
                            v-model="newMessage" 
                            type="text" 
                            placeholder="Type your message..." 
                            class="flex-1"
                            @input="handleTyping"
                            :disabled="sending"
                        />
                        <Button type="submit" :disabled="sending || !newMessage.trim()">
                            Send
                        </Button>
                    </form>
                </div>
            </div>

            <!-- Online Users Sidebar (Desktop) -->
            <div class="hidden md:flex flex-col w-64 bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-gray-200">
                    Online Users
                </div>
                <div class="flex-1 overflow-y-auto p-4 space-y-3">
                    <div v-for="user in onlineUsers" :key="user.id" class="flex items-center">
                        <div class="flex flex-col">
                             <RankedUserLabel 
                                :rank="user.rank_data" 
                                :name="user.name"
                                :show-message="false"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

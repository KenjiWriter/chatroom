<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue';
import RankedUserLabel from '@/components/RankedUserLabel.vue';
import ModerationModal from '@/components/ModerationModal.vue';
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
const isMuted = ref(false);
const muteExpiresAt = ref<Date | null>(null);

const page = usePage();
const currentUser = page.props.auth.user;
// Helper for permission check - assuming shared props has permission structure
const canModerate = computed(() => {
    // Basic check: if user has any mod permission. 
    // Ideally use page.props.auth.user.permissions generic check or specific ones.
    const perms = (page.props.auth.user as any).permissions || [];
    return perms.some((p: any) => ['kick_user', 'mute_user', 'ban_user'].includes(p.slug));
});

const modModalOpen = ref(false);
const targetUser = ref<any>(null);

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const generateTempId = () => 'temp-' + Date.now() + Math.random().toString(36).substr(2, 9);

const sendMessage = async () => {
    if (!newMessage.value.trim() || sending.value || isMuted.value) return;

    sending.value = true;
    const content = newMessage.value;
    const tempId = generateTempId();

    const optimisticMessage = {
        id: tempId,
        content: content,
        user_id: currentUser.id,
        user: currentUser,
        created_at: new Date().toISOString(),
        is_sending: true,
        is_system_message: false,
    };
    
    messages.value.push(optimisticMessage);
    newMessage.value = '';
    scrollToBottom();

    try {
        const response = await axios.post(route('chat.message', props.room.slug), {
            content: content
        });

        const realMessage = response.data.message;
        const index = messages.value.findIndex(m => m.id === tempId);
        if (index !== -1) {
            messages.value[index] = realMessage;
        }

        if (response.data.xp_result?.leveled_up) {
            toast.success(`Level Up! You reached level ${response.data.xp_result.new_level}!`);
        }

    } catch (error: any) {
        console.error('Failed to send message', error);
        messages.value = messages.value.filter(m => m.id !== tempId);
        
        if (error.response?.status === 403) {
             toast.error('You are muted or unauthorized.');
        } else {
             toast.error('Failed to send message.');
        }
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

let typingTimer: any = null;
const handleTyping = () => {
    if (!typingTimer && !isMuted.value) {
        sendTypingWhisper();
        typingTimer = setTimeout(() => {
            typingTimer = null;
        }, 1000);
    }
};

const openModModal = (user: any) => {
    // Don't moderate self
    if (user.id === currentUser.id) return;
    targetUser.value = user;
    modModalOpen.value = true;
};

const handleModAction = async (payload: any) => {
    const { userId, minutes, reason, roomId } = payload;
    let url = '';
    let data: any = { reason };

    // Mapping action events to endpoints
    // Note: Since Modal emits specific action name, we rely on parent logic?
    // Oh, the modal emits 'mute', 'kick' etc.
    // Need to update Modal to pass the action type or bind handleModAction to varying listeners?
    // Let's create specific handlers for clarity.
};

const submitKick = async (payload: any) => {
    try {
        await axios.post(route('admin.kick', { room: props.room.id, user: payload.userId }), {
            reason: payload.reason
        });
        toast.success('User kicked.');
        modModalOpen.value = false;
    } catch (e) {
        toast.error('Kick failed.');
    }
};

const submitMute = async (payload: any) => {
    try {
        await axios.post(route('admin.mute', { user: payload.userId }), {
            reason: payload.reason,
            duration: payload.minutes,
            room_id: payload.roomId
        });
        toast.success('User muted.');
        modModalOpen.value = false;
    } catch (e) {
        toast.error('Mute failed.');
    }
};

const submitBan = async (payload: any) => {
     // TODO: Implement Ban endpoint + logic
};

// Countdown Logic
const muteTimeRemaining = computed(() => {
    if (!muteExpiresAt.value) return 'Permanently';
    // Simplified, real countdown would use ticking ref
    return 'Until ' + muteExpiresAt.value.toLocaleTimeString(); 
});

onMounted(() => {
    scrollToBottom();

    // Listen for Personal Restrictions (Mutes/Bans)
    // @ts-ignore
    window.Echo.private(`App.Models.User.${currentUser.id}`)
        .listen('UserRestricted', (e: any) => {
            if (e.type === 'mute') {
                isMuted.value = true;
                muteExpiresAt.value = e.expiresAt ? new Date(e.expiresAt) : null;
                toast.warning(`You have been muted: ${e.reason}`);
            } else if (e.type === 'ban') {
                // Force redirect
                router.visit('/');
            }
        });

    // Listen for Room Events
    // @ts-ignore
    window.Echo.private(`chat.room.${props.room.id}`)
        .listen('MessageSent', (e: any) => {
            if (e.message.user_id !== currentUser.id || e.message.is_system_message) {
                messages.value.push(e.message);
                scrollToBottom();
            }
        })
        .listen('UserKicked', (e: any) => {
            if (e.userId === currentUser.id) {
                toast.error(`You were kicked: ${e.reason}`);
                router.visit(route('dashboard'));
            }
        })
        .listenForWhisper('typing', (e: any) => {
            if (!typingUsers.value.find(u => u.id === e.id)) {
                typingUsers.value.push(e);
                setTimeout(() => {
                    typingUsers.value = typingUsers.value.filter(u => u.id !== e.id);
                }, 2000);
            }
        });
        
    // Presence
    // @ts-ignore
    window.Echo.join(`chat.room.presence.${props.room.id}`)
        .here((users: any[]) => { onlineUsers.value = users; })
        .joining((user: any) => { onlineUsers.value.push(user); })
        .leaving((user: any) => { onlineUsers.value = onlineUsers.value.filter((u: any) => u.id !== user.id); });
});

onUnmounted(() => {
    // @ts-ignore
    // Cleanup Echo listeners
    window.Echo.leave(`chat.room.${props.room.id}`);
    window.Echo.leave(`chat.room.presence.${props.room.id}`);
    window.Echo.leave(`App.Models.User.${currentUser.id}`);
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
                <div class="text-sm text-gray-500">
                    <span class="mr-2">{{ onlineUsers.length }} Online</span>
                    <span v-if="isMuted" class="text-red-500 font-bold">MUTED {{ muteTimeRemaining }}</span>
                </div>
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
                    >
                        <!-- System Message -->
                        <div v-if="msg.is_system_message" class="flex justify-center my-2">
                             <div class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-xs text-gray-500 rounded-full">
                                 {{ msg.content }}
                             </div>
                        </div>

                        <!-- User Message -->
                        <div 
                            v-else
                            class="flex flex-col"
                            :class="msg.user_id === currentUser.id ? 'items-end' : 'items-start'"
                        >
                            <RankedUserLabel 
                                :rank="msg.user.rank_data" 
                                :name="msg.user.name"
                                :message="msg.content" 
                                :show-moderation-tools="canModerate && msg.user_id !== currentUser.id"
                                @moderate="openModModal(msg.user)"
                                class="mb-1"
                            />
                            <!-- Content Bubble (Only visual backup, text is in Label now mostly? 
                                 Wait, RankedUserLabel displays message? Yes. 
                                 But normally bubble style is separate.
                                 If RankedUserLabel handles message, we duplicate?
                                 Check implementation of RankedUserLabel.
                                 It has 'message' prop which renders text.
                                 If we use that, we lose the bubble styling unless we style the label?
                                 Let's keep the bubble for message body and NOT pass message to label here
                                 OR rely on Label for full display?
                                 Previous walkthrough used Label for name + bubble for text.
                                 Let's revert to: Label for Name, Bubble for Text.
                                 So don't pass :message to RankedUserLabel here.
                            -->
                            <div 
                                class="px-4 py-2 rounded-lg max-w-[80%] break-words shadow-sm mt-1"
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
                </div>
                
                <!-- Typing & Input -->
                <div v-if="typingUsers.length > 0" class="px-4 py-2 text-xs text-gray-500 italic">
                    {{ typingUsers.map(u => u.name).join(', ') }} is typing...
                </div>

                <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    <form @submit.prevent="sendMessage" class="flex gap-2">
                        <Input 
                            v-model="newMessage" 
                            type="text" 
                            :placeholder="isMuted ? 'You are muted.' : 'Type your message...'"
                            class="flex-1"
                            @input="handleTyping"
                            :disabled="sending || isMuted"
                        />
                        <Button type="submit" :disabled="sending || !newMessage.trim() || isMuted">
                            Send
                        </Button>
                    </form>
                </div>
            </div>

            <!-- Online Users -->
            <div class="hidden md:flex flex-col w-64 bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-gray-200">
                    Online Users
                </div>
                <div class="flex-1 overflow-y-auto p-4 space-y-3">
                    <div v-for="user in onlineUsers" :key="user.id" class="flex items-center">
                        <RankedUserLabel 
                            :rank="user.rank_data" 
                            :name="user.name"
                            :show-message="false"
                            :show-moderation-tools="canModerate && user.id !== currentUser.id"
                            @moderate="openModModal(user)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <ModerationModal 
            :is-open="modModalOpen" 
            :user="targetUser"
            :room-id="room.id"
            @close="modModalOpen = false"
            @kick="submitKick"
            @mute="submitMute"
            @ban="submitBan"
        />
    </AppLayout>
</template>

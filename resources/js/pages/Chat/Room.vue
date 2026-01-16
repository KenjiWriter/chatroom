<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue';
import RankedUserLabel from '@/components/RankedUserLabel.vue';
import UserPopOver from '@/components/UserPopOver.vue';
import ModerationModal from '@/components/ModerationModal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import axios from 'axios';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';
import { resolveAsset } from '@/lib/utils';
import GifPicker from '@/components/GifPicker.vue';
import { Smile } from 'lucide-vue-next';
import { onClickOutside } from '@vueuse/core';

const props = defineProps<{
    room: any;
    initialMessages: any[];
}>();

const page = usePage();
const currentUser = page.props.auth.user;

const messages = ref<any[]>([...props.initialMessages]);
const newMessage = ref('');
const onlineUsers = ref<any[]>([]);
const typingUsers = ref<any[]>([]);
const messagesContainer = ref<HTMLElement | null>(null);
const sending = ref(false);

const user = computed(() => page.props.auth.user as any);
const isMuted = ref(user.value?.mute_data ? true : false);
const muteData = ref(user.value?.mute_data || null);

const showGifPicker = ref(false);
const gifPickerRef = ref(null);
onClickOutside(gifPickerRef, () => showGifPicker.value = false);

// Helper for permission check
const canModerate = computed(() => {
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

const sendGif = async (gif: any) => {
    if (sending.value || isMuted.value) return;
    
    showGifPicker.value = false;
    sending.value = true;
    
    const tempId = generateTempId();
    const optimisticMessage = {
        id: tempId,
        content: gif.url,
        type: 'gif',
        metadata: { width: gif.width, height: gif.height, title: gif.title },
        user_id: currentUser.id,
        user: currentUser,
        created_at: new Date().toISOString(),
        is_sending: true,
        is_system_message: false,
    };

    messages.value.push(optimisticMessage);
    scrollToBottom();

    try {
        const response = await axios.post(route('chat.message', props.room.slug), {
            content: gif.url,
            type: 'gif',
            metadata: { width: gif.width, height: gif.height, title: gif.title }
        });

        const index = messages.value.findIndex(m => m.id === tempId);
        if (index !== -1) {
            messages.value[index] = response.data.message;
        }
    } catch (error) {
        messages.value = messages.value.filter(m => m.id !== tempId);
        toast.error('Failed to send GIF.');
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
    if (!muteData.value) return '';
    if (!muteData.value.expires_at) return 'Permanently';
    
    const expiry = new Date(muteData.value.expires_at);
    if (expiry < new Date()) {
        isMuted.value = false;
        return '';
    }
    
    return 'Until ' + expiry.toLocaleTimeString() + (muteData.value.reason ? ` (${muteData.value.reason})` : '');
});

onMounted(() => {
    scrollToBottom();

    // Listen for Personal Restrictions (Mutes/Bans)
    // @ts-ignore
    window.Echo.private(`user-notifications.${currentUser.id}`)
        .listen('UserPunished', (e: any) => {
            if (e.type === 'mute') {
                isMuted.value = true;
                muteData.value = {
                    reason: e.reason,
                    expires_at: e.expiresAt || null, // Ensure backend sends ISO string
                    room_id: e.roomId || null
                };
            } else if (e.type === 'ban') {
                // AppLayout handles the overlay, so we don't need to do much here, 
                // but we could redirect if we wanted to be double sure.
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
        .listen('UserPunished', (e: any) => {
            if (e.userId === currentUser.id && e.type === 'kick') {
                // AppLayout handles the toast and redirect, we just stop local processing
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
            <div class="flex justify-between items-center h-16 px-4 border-b bg-card">
                <div class="flex flex-col">
                    <div class="flex items-center gap-3">
                        <h2 class="font-bold text-xl tracking-tight text-foreground">
                            {{ room.name }}
                        </h2>
                        <span v-if="room.required_rank" 
                              class="text-xs font-bold px-2 py-0.5 rounded-full bg-secondary"
                              :style="{ color: room.required_rank.color_name }"
                        >
                            {{ room.required_rank.name }}+
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground">{{ room.description }}</p>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2 text-sm text-muted-foreground bg-muted/50 px-3 py-1.5 rounded-full">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                        </span>
                        <span class="font-medium">{{ onlineUsers.length }} Online</span>
                    </div>
                    <span v-if="isMuted" class="text-xs font-bold text-red-500 bg-red-100 dark:bg-red-900/30 px-2 py-1 rounded border border-red-200 dark:border-red-800">
                        MUTED: {{ muteTimeRemaining }}
                    </span>
                </div>
            </div>
        </template>

        <!-- Main Chat Container -->
        <div class="flex h-[calc(100vh-8.5rem)] overflow-hidden bg-background">
            
            <!-- Chat Area -->
            <div class="flex-1 flex flex-col min-w-0 bg-background relative">
                
                <!-- Messages List -->
                <div 
                    ref="messagesContainer" 
                    class="flex-1 overflow-y-auto p-4 space-y-6 scroll-smooth pb-4"
                >
                    <div v-for="msg in messages" :key="msg.id" class="w-full">
                        
                        <!-- System Message -->
                        <div v-if="msg.is_system_message" class="flex justify-center my-4">
                            <span class="px-4 py-1.5 bg-muted/50 text-xs font-medium text-muted-foreground rounded-full border border-border/50 shadow-sm">
                                {{ msg.content }}
                            </span>
                        </div>

                        <!-- User Message -->
                        <div v-else 
                             class="flex gap-4 max-w-4xl"
                             :class="msg.user_id === currentUser.id ? 'ml-auto flex-row-reverse' : ''"
                        >
                            <!-- Avatar -->
                            <div class="flex-shrink-0">
                                <UserPopOver 
                                    v-if="msg.user_id !== currentUser.id" 
                                    :user-id="msg.user_id"
                                    :name="msg.user.name"
                                    :room-id="room.id"
                                >
                                    <Avatar class="h-10 w-10 border border-border cursor-pointer hover:ring-2 hover:ring-ring transition-all">
                                        <AvatarImage :src="resolveAsset(msg.user.avatar_url, 'avatar', msg.user.name) as string" />
                                        <AvatarFallback>{{ msg.user.name.substring(0,2).toUpperCase() }}</AvatarFallback>
                                    </Avatar>
                                </UserPopOver>
                                <Avatar v-else class="h-10 w-10 border border-border">
                                    <AvatarImage :src="resolveAsset(msg.user.avatar_url, 'avatar', msg.user.name) as string" />
                                    <AvatarFallback>{{ msg.user.name.substring(0,2).toUpperCase() }}</AvatarFallback>
                                </Avatar>
                            </div>

                            <!-- Message Content -->
                            <div class="flex flex-col min-w-0 max-w-[85%] sm:max-w-[75%]"
                                 :class="msg.user_id === currentUser.id ? 'items-end' : 'items-start'"
                            >
                                <div class="flex items-center gap-2 mb-1">
                                    <RankedUserLabel 
                                        :rank="msg.user.rank_data" 
                                        :name="msg.user.name"
                                        :user-id="msg.user_id"
                                        :room-id="room.id"
                                        :show-moderation-tools="canModerate && msg.user_id !== currentUser.id"
                                        @moderate="openModModal(msg.user)"
                                        class="text-sm font-semibold"
                                    />
                                    <span class="text-[10px] text-muted-foreground opacity-70">
                                        {{ new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                                    </span>
                                </div>

                                <div 
                                    class="relative text-sm shadow-sm leading-relaxed overflow-hidden"
                                    :class="[
                                        msg.user_id === currentUser.id 
                                            ? 'bg-primary text-primary-foreground rounded-2xl rounded-tr-sm' 
                                            : 'bg-muted/80 text-foreground border border-border/50 rounded-2xl rounded-tl-sm',
                                        msg.is_sending ? 'opacity-70' : '',
                                        msg.type === 'gif' ? 'p-1' : 'px-5 py-3'
                                    ]"
                                >
                                    <template v-if="msg.type === 'gif'">
                                        <img 
                                            :src="msg.content" 
                                            :alt="msg.metadata?.title || 'GIF'"
                                            class="rounded-xl max-w-full h-auto max-h-[300px] object-contain"
                                            loading="lazy"
                                        />
                                    </template>
                                    <template v-else>
                                        {{ msg.content }}
                                    </template>
                                </div>
                                <span v-if="msg.is_sending" class="text-[10px] text-muted-foreground mt-1">Sending...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Typing Indicator & Input Area -->
                 <div class="p-4 bg-background border-t border-border z-10 w-full max-w-5xl mx-auto">
                    <!-- Typing Indicator -->
                    <div class="h-6 mb-2 flex items-center">
                        <div v-if="typingUsers.length > 0" class="flex items-center gap-2 text-xs text-muted-foreground animate-pulse">
                            <span class="flex gap-0.5">
                                <span class="h-1 w-1 bg-current rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                                <span class="h-1 w-1 bg-current rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                                <span class="h-1 w-1 bg-current rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                            </span>
                            {{ typingUsers.map(u => u.name).join(', ') }} is typing...
                        </div>
                    </div>

                    <form @submit.prevent="sendMessage" class="relative flex items-center gap-2">
                        <div class="relative flex-1">
                            <Input 
                                v-model="newMessage" 
                                type="text" 
                                :placeholder="isMuted ? (muteData?.reason ? `Muted: ${muteData.reason}` : 'You are temporarily muted.') : 'Type a message...'"
                                class="w-full py-6 pl-5 pr-24 rounded-full border-muted-foreground/20 bg-muted/30 focus-visible:ring-offset-2 focus-visible:ring-primary/20 shadow-sm"
                                @input="handleTyping"
                                :disabled="sending || isMuted"
                            />
                            
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1">
                                <div ref="gifPickerRef" class="relative">
                                    <Button 
                                        type="button"
                                        variant="ghost" 
                                        size="icon" 
                                        class="h-9 w-9 rounded-full text-muted-foreground hover:text-primary transition-colors"
                                        @click="showGifPicker = !showGifPicker"
                                        :disabled="sending || isMuted"
                                    >
                                        <Smile class="h-5 w-5" />
                                    </Button>

                                    <div v-if="showGifPicker" class="absolute bottom-full right-0 mb-4 z-50 animate-in fade-in slide-in-from-bottom-2 duration-200">
                                        <GifPicker @select="sendGif" />
                                    </div>
                                </div>

                                <Button 
                                    type="submit" 
                                    size="icon"
                                    class="h-9 w-9 rounded-full shadow-md transition-all hover:scale-105 active:scale-95"
                                    :disabled="sending || !newMessage.trim() || isMuted"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-0.5"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                                    <span class="sr-only">Send</span>
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Sidebar: Online Users -->
            <div class="hidden lg:flex w-80 border-l border-border bg-card/50 flex-col">
                <div class="p-4 border-b border-border bg-card/80 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                    <h3 class="font-semibold text-sm tracking-wide text-foreground uppercase opacity-80">
                        Online Users
                    </h3>
                </div>
                <div class="flex-1 overflow-y-auto p-2 space-y-1">
                    <div v-for="user in onlineUsers" 
                         :key="user.id" 
                         class="group flex items-center p-2 rounded-lg hover:bg-muted/50 transition-colors"
                    >
                         <div class="relative mr-3">
                             <UserPopOver :user-id="user.id" :name="user.name" :room-id="room.id">
                                 <Avatar class="h-9 w-9 border border-border cursor-pointer">
                                     <AvatarImage :src="resolveAsset(user.avatar_url, 'avatar', user.name) as string" />
                                     <AvatarFallback>{{ user.name.substring(0,2).toUpperCase() }}</AvatarFallback>
                                 </Avatar>
                             </UserPopOver>
                             <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-green-500 ring-2 ring-background"></span>
                         </div>
                         <div class="flex-1 min-w-0">
                             <RankedUserLabel 
                                 :rank="user.rank_data" 
                                 :name="user.name"
                                 :user-id="user.id"
                                 :room-id="room.id"
                                 :show-message="false"
                                 :show-moderation-tools="canModerate && user.id !== currentUser.id"
                                 @moderate="openModModal(user)"
                                 class="text-sm font-medium truncate"
                             />
                             <!-- Optional status text -->
                             <p class="text-[10px] text-muted-foreground truncate">
                                 {{ user.bio || 'Online' }}
                             </p>
                         </div>
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

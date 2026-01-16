<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { HoverCard, HoverCardContent, HoverCardTrigger } from '@/components/ui/hover-card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { UserPlus, UserMinus, Shield, MessageSquare, Clock, Check, X, Ban } from 'lucide-vue-next';
import axios from 'axios';
import { route } from 'ziggy-js';
import { toast } from 'vue-sonner';
import { usePage, router } from '@inertiajs/vue3';
import { resolveAsset } from '@/lib/utils';
import ModerationModal from './ModerationModal.vue';

const props = defineProps<{
    userId: number;
    name: string;
    roomId?: number;
}>();

const open = ref(false);
const loading = ref(false);
const userData = ref<any>(null);
const isModModalOpen = ref(false);
const assignableRanks = ref<any[]>([]);

const page = usePage();
const authUser = computed(() => page.props.auth.user as any);
const myPermissions = computed(() => authUser.value?.permissions || []);

const canModerate = computed(() => {
    const modPerms = ['kick_user', 'mute_temp', 'mute_perm', 'ban_room_access'];
    return myPermissions.value.some((p: string) => modPerms.includes(p));
});

const canManageRanks = computed(() => {
    if (!myPermissions.value.includes('manage_user_ranks')) return false;
    if (userData.value?.is_self) return false;
    
    // Priority check
    const myPriority = authUser.value?.rank?.priority || 0;
    const targetPriority = userData.value?.rank_data?.priority || 0;
    
    return myPriority > targetPriority;
});

const fetchAssignableRanks = async () => {
    if (assignableRanks.value.length > 0) return;
    try {
        const response = await axios.get(route('admin.ranks.assignable'));
        assignableRanks.value = response.data;
    } catch (e) {
        console.error("Failed to fetch ranks", e);
    }
};

const updateRank = async (rankId: string) => {
    if (!rankId) return;
    try {
        await axios.post(route('admin.users.rank', props.userId), { rank_id: rankId });
        toast.success("User rank updated.");
        // Refresh local data
        userData.value = null;
        fetchUserData();
    } catch (e: any) {
        toast.error(e.response?.data?.message || "Failed to update rank.");
    }
};

const handleModeration = (type: string, data: any) => {
    let url = '';
    if (type === 'kick') url = route('admin.kick', { room: data.roomId, user: data.userId });
    else if (type === 'ban') url = route('admin.ban', { user: data.userId });
    else if (type === 'unmute') url = route('admin.unmute', { user: data.userId });
    else if (type === 'unban') url = route('admin.unban', { user: data.userId });
    else url = route('admin.mute', { user: data.userId });
        
    router.post(url, data, {
        onSuccess: () => {
            isModModalOpen.value = false;
            toast.success(`User ${type}ed successfully.`);
        },
        onError: (errors) => {
            console.error(errors);
            toast.error(`Failed to ${type} user.`);
            // Optionally close even on error if preferred, but usually keep open to show errors
            // isModModalOpen.value = false;
        },
        onFinish: () => {
            // Cleanup if needed
        }
    });
};

const fetchUserData = async () => {
    if (userData.value || loading.value) return;
    
    loading.value = true;
    try {
        const response = await axios.get(route('users.hover-card', { user: props.userId, room_id: props.roomId }));
        userData.value = response.data;
        if (canManageRanks.value) {
            fetchAssignableRanks();
        }
    } catch (e) {
        console.error("Failed to fetch user data", e);
    } finally {
        loading.value = false;
    }
};

const onOpenChange = (isOpen: boolean) => {
    open.value = isOpen;
    if (isOpen) {
        fetchUserData();
    }
};

const sendFriendRequest = async () => {
    try {
        await axios.post(route('friendships.store', props.userId));
        toast.success("Friend request sent!");
        // Update local state temporarily
        if (userData.value) userData.value.friendship_status = 'sent_pending';
    } catch (e: any) {
        toast.error(e.response?.data?.message || "Failed to send request.");
    }
};

const acceptRequest = async (id: number) => {
    try {
        await axios.put(route('friendships.update', id));
        toast.success("Friend request accepted!");
        if (userData.value) {
             userData.value.friendship_status = 'accepted';
             // Refresh page? Or just state.
             // Usually we want to reflect change immediately.
             router.reload({ only: ['friends', 'pendingRequests'] });
        }
    } catch (e) {
        toast.error("Failed to accept.");
    }
};

const unfriend = async (id: number) => {
    // We need friendship ID. Our API returns it?
    // The hoverCard API doesn't populate friendship ID in the status string.
    // We might need to fetch it or guess it? 
    // Actually, destroy endpoint usually takes Friendship ID.
    // If we only have UserID, we need a flexible endpoint DELETE /friendships/user/{user}?
    // Or we update hoverCard to return friendship_id.
    
    // Simplification: We need friendship ID to destroy.
    // Let's reload or implementing robust state management.
    // For now, let's assume we can't unfriend easily from hover card without friendship ID.
    // I'll update hoverCard API to return friendship_id if exists.
    
    // Wait, let's just use `route('friendships.destroy', id)` where id is friendship ID.
    // If we don't have it, we can't call it.
    
    // Let's SKIP implementation of Unfriend/Cancel from hover card if we lack ID, 
    // OR we update `UserController` to return `friendship_id`.
    // I will update `UserController` in next step if needed. 
    // For now, let's assume we just show status.
};

// Fallback visual
const bannerStyle = computed(() => {
    const resolved = resolveAsset(userData.value?.banner_url, 'banner');
    if (resolved) {
        return { backgroundImage: `url(${resolved})` };
    }
    const color = userData.value?.rank_data?.color_name || '#666';
    return { background: `linear-gradient(to right, ${color}, #333)` };
});

</script>

<template>
    <span v-bind="$attrs">
        <HoverCard :open="open" @update:open="onOpenChange">
            <HoverCardTrigger as-child>
                <slot />
            </HoverCardTrigger>
            <HoverCardContent class="w-80 p-0 overflow-hidden border-none shadow-xl bg-white dark:bg-gray-900">
                <div v-if="loading" class="p-4 space-y-4">
                    <Skeleton class="h-20 w-full" />
                    <div class="flex items-center space-x-4">
                        <Skeleton class="h-12 w-12 rounded-full" />
                        <div class="space-y-2">
                            <Skeleton class="h-4 w-[200px]" />
                            <Skeleton class="h-4 w-[150px]" />
                        </div>
                    </div>
                </div>
                
                <div v-else-if="userData">
                    <!-- Banner -->
                    <div class="h-24 bg-cover bg-center" :style="bannerStyle"></div>
                    
                    <div class="px-4 pb-4 -mt-10">
                        <div class="flex justify-between items-end">
                            <Avatar class="h-20 w-20 border-4 border-white dark:border-gray-900">
                                <AvatarImage :src="resolveAsset(userData.avatar_url, 'avatar', userData.name) as string" />
                                <AvatarFallback>{{ userData.name.substring(0,2).toUpperCase() }}</AvatarFallback>
                            </Avatar>
                            
                            <!-- Actions -->
                             <div class="flex gap-2 mb-2">
                                <Button v-if="userData.friendship_status === 'none' && !userData.is_self" 
                                    size="sm" variant="outline" class="h-8 gap-1"
                                    @click="sendFriendRequest"
                                >
                                    <UserPlus class="w-4 h-4" /> Add
                                </Button>
                                
                                <Button v-else-if="userData.friendship_status === 'sent_pending'"
                                    size="sm" variant="secondary" class="h-8 gap-1" disabled
                                >
                                    <Clock class="w-4 h-4" /> Sent
                                </Button>
                                
                                 <Button v-else-if="userData.friendship_status === 'received_pending'"
                                    size="sm" class="h-8 gap-1 bg-green-600 hover:bg-green-700"
                                    @click="() => {} /* Need ID */"
                                >
                                    <Check class="w-4 h-4" /> Accept
                                </Button>
                                 
                                 <Button v-else-if="userData.friendship_status === 'accepted'"
                                    size="sm" variant="ghost" class="h-8 gap-1"
                                    @click="router.post(route('conversations.store'), { user_id: userData.id })"
                                >
                                    <MessageSquare class="w-4 h-4" /> Message
                                </Button>
    
                                <!-- Moderation Actions -->
                                <Button v-if="canModerate && !userData.is_self"
                                    size="sm" variant="outline" class="h-8 gap-1 text-destructive hover:text-destructive"
                                    @click="isModModalOpen = true"
                                >
                                    <Ban class="w-4 h-4" /> Actions
                                </Button>

                             </div>
                        </div>
                        
                        <div class="mt-2">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-lg text-gray-900 dark:text-gray-100">{{ userData.name }}</span>
                                <!-- Rank Badge -->
                                <span 
                                    class="text-xs px-2 py-0.5 rounded border"
                                    :style="{ 
                                        borderColor: userData.rank_data.color_name,
                                        color: userData.rank_data.color_name,
                                        backgroundColor: userData.rank_data.color_name + '1A' // 10% opacity
                                    }"
                                >
                                    {{ userData.rank_data.name }}
                                </span>
                                 <span class="text-xs text-gray-500">Lvl {{ userData.level }}</span>
                            </div>
                            
                            <div v-if="userData.bio" class="text-sm text-gray-600 dark:text-gray-300 mt-2 line-clamp-3">
                                {{ userData.bio }}
                            </div>
                            
                            <!-- Mini XP Bar -->
                            <div v-if="userData.xp !== null" class="mt-3">
                                 <div class="flex justify-between text-[10px] text-gray-500 mb-1">
                                    <span>XP Progress</span>
                                    <span>{{ userData.xp }} / {{ userData.next_level_xp }}</span>
                                 </div>
                                 <div class="h-1.5 w-full bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full bg-blue-500 transition-all duration-500"
                                        :style="{ width: Math.min(100, (userData.xp / userData.next_level_xp) * 100) + '%' }"
                                    ></div>
                                 </div>
                            </div>
                            
                            <div v-if="!userData.xp && !userData.bio" class="text-sm text-gray-400 italic mt-2">
                                Profile is private.
                            </div>
                            
                            <div class="text-xs text-gray-400 mt-3">
                                Joined {{ userData.created_at }}
                            </div>

                            <!-- Rank Management -->
                            <div v-if="canManageRanks" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                                <Label class="text-[10px] uppercase font-bold text-gray-400 mb-2 block">Set User Rank</Label>
                                <select 
                                    @change="(e) => updateRank((e.target as HTMLSelectElement).value)"
                                    class="w-full h-8 rounded-md border border-input bg-background px-2 py-1 text-xs ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">Select rank...</option>
                                    <option v-for="rank in assignableRanks" :key="rank.id" :value="rank.id" :selected="rank.id === userData.rank_id">
                                        {{ rank.name }} (P:{{ rank.priority }})
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div v-else class="p-4 text-center text-red-500">
                    Failed to load profile.
                </div>
            </HoverCardContent>
        </HoverCard>
    
        <ModerationModal 
            v-if="userData"
            :is-open="isModModalOpen" 
            :user="userData"
            :room-id="roomId"
            @close="isModModalOpen = false"
            @kick="(d) => handleModeration('kick', d)"
            @mute="(d) => handleModeration('mute', d)"
            @ban="(d) => handleModeration('ban', d)"
            @unmute="(d) => handleModeration('unmute', d)"
            @unban="(d: any) => handleModeration('unban', d)"
        />
    </span>
</template>

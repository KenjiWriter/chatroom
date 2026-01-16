<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { route } from 'ziggy-js';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import RankedUserLabel from '@/components/RankedUserLabel.vue';
import { Clock, MessageSquare, Check, X, User as UserIcon } from 'lucide-vue-next';
import axios from 'axios';
import { toast } from 'vue-sonner';

const props = defineProps<{
    progress: any;
    rooms: any[];
    friends: any[];
    pendingRequests: any[];
    roomHistory: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
];

const onlineFriendIds = ref<number[]>([]);

onMounted(() => {
    // Listen for global online status
    // @ts-ignore
    window.Echo.join('online')
        .here((users: any[]) => {
            onlineFriendIds.value = users.map(u => u.id);
        })
        .joining((user: any) => {
            if (!onlineFriendIds.value.includes(user.id)) {
                onlineFriendIds.value.push(user.id);
            }
        })
        .leaving((user: any) => {
            onlineFriendIds.value = onlineFriendIds.value.filter(id => id !== user.id);
        });
});

onUnmounted(() => {
    // @ts-ignore
    window.Echo.leave('online');
});

const friendsWithStatus = computed(() => {
    return props.friends.map(f => ({
        ...f,
        is_online: onlineFriendIds.value.includes(f.id)
    })).sort((a, b) => (b.is_online === a.is_online) ? 0 : b.is_online ? 1 : -1);
});

const acceptRequest = async (id: number) => {
    try {
        await axios.put(route('friendships.update', id));
        toast.success("Request accepted.");
        router.reload();
    } catch (e) {
        toast.error("Failed to accept.");
    }
};

const declineRequest = async (id: number) => {
    try {
        await axios.delete(route('friendships.destroy', id));
        toast.success("Request declined.");
        router.reload();
    } catch (e) {
        toast.error("Failed to decline.");
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <!-- Rank & XP Card -->
            <Card>
                 <CardHeader>
                    <CardTitle>Welcome Back!</CardTitle>
                    <CardDescription>Level {{ progress.next_level_xp ? 'Progress' : 'Maxed' }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center gap-4 mb-2">
                        <div class="font-bold text-2xl">{{ progress.current_xp }} XP</div>
                         <div class="text-sm text-gray-500"> Level {{ Math.floor(progress.current_xp / 100) }} (Approximation) </div>
                    </div>
                    <div class="h-4 w-full bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div 
                            class="h-full bg-indigo-500 transition-all duration-1000"
                            :style="{ width: progress.percent + '%' }"
                        ></div>
                    </div>
                     <div class="flex justify-between text-xs text-gray-500 mt-1">
                        <span>Lvl {{ Math.floor(progress.current_xp / 100) }}</span> 
                        <!-- Simplification for display, real logic is in backend progress obj -->
                        <span>{{ progress.needed_for_next }} XP to next level</span>
                    </div>
                </CardContent>
            </Card>

            <Tabs default-value="overview" class="w-full">
                <TabsList class="grid w-full grid-cols-2 lg:w-[400px]">
                    <TabsTrigger value="overview">Overview</TabsTrigger>
                    <TabsTrigger value="social">Social</TabsTrigger>
                </TabsList>
                
                <TabsContent value="overview" class="space-y-4">
                     <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                         <!-- Rooms -->
                         <Card v-for="room in rooms" :key="room.id" class="hover:bg-accent/50 transition cursor-pointer" @click="router.visit(route('chat.room', room.slug))">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">
                                    {{ room.name }}
                                </CardTitle>
                                <MessageSquare class="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-xs text-muted-foreground">
                                    {{ room.description || 'Join the conversation.' }}
                                </div>
                                <div v-if="room.required_rank" class="mt-2 text-xs font-bold" :style="{ color: room.required_rank.color_name }">
                                    Requires {{ room.required_rank.name }}
                                </div>
                                <div v-else class="mt-2 text-xs text-green-600">
                                    Open to all
                                </div>
                            </CardContent>
                         </Card>
                     </div>
                     
                     <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                        <!-- Recent History -->
                        <Card class="col-span-4">
                            <CardHeader>
                                <CardTitle>Recent Activity</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div v-for="visit in roomHistory" :key="visit.id" class="flex items-center">
                                         <Avatar class="h-9 w-9">
                                            <AvatarFallback><Clock class="h-4 w-4" /></AvatarFallback>
                                        </Avatar>
                                        <div class="ml-4 space-y-1">
                                            <p class="text-sm font-medium leading-none">{{ visit.room.name }}</p>
                                            <p class="text-xs text-muted-foreground">
                                                Visited {{ new Date(visit.last_visited_at).toLocaleDateString() }}
                                            </p>
                                        </div>
                                         <div class="ml-auto font-medium">
                                            <Button variant="ghost" size="sm" as-child>
                                                <a :href="route('chat.room', visit.room.slug)">Join</a>
                                            </Button>
                                        </div>
                                    </div>
                                    <div v-if="roomHistory.length === 0" class="text-sm text-gray-500">
                                        No recent room visits.
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                     </div>
                </TabsContent>
                
                <TabsContent value="social" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <!-- Friends -->
                        <Card class="col-span-1">
                            <CardHeader>
                                <CardTitle>Friends ({{ friends.length }})</CardTitle>
                                <CardDescription>Online status updates automatically.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4 h-[300px] overflow-y-auto">
                                    <div v-for="friend in friendsWithStatus" :key="friend.id" class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="relative">
                                                <Avatar>
                                                    <AvatarImage :src="friend.avatar_url" />
                                                    <AvatarFallback>{{ friend.name.substring(0,2).toUpperCase() }}</AvatarFallback>
                                                </Avatar>
                                                <span 
                                                    class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white dark:border-gray-950"
                                                    :class="friend.is_online ? 'bg-green-500' : 'bg-gray-300'"
                                                ></span>
                                            </div>
                                            <div>
                                                <RankedUserLabel :rank="friend.rank_data" :name="friend.name" :user-id="friend.id" />
                                            </div>
                                        </div>
                                        
                                        <!-- Actions (e.g. Message) could go here -->
                                        <!-- For now usually standard is hover card handles interaction or separate Msg button -->
                                    </div>
                                    <div v-if="friends.length === 0" class="text-sm text-gray-500 text-center py-4">
                                        No friends yet. Hover over users in chat to add them!
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                        
                        <!-- Pending Requests -->
                        <Card class="col-span-1">
                             <CardHeader>
                                <CardTitle>Friend Requests ({{ pendingRequests.length }})</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div v-for="req in pendingRequests" :key="req.id" class="flex items-center justify-between bg-muted/50 p-2 rounded-lg">
                                        <div class="flex items-center gap-3">
                                            <Avatar class="h-9 w-9">
                                                <AvatarImage :src="req.user.avatar_url" />
                                                <AvatarFallback>{{ req.user.name.substring(0,2) }}</AvatarFallback>
                                            </Avatar>
                                            <div class="flex flex-col">
                                                <span class="font-medium text-sm">{{ req.user.name }}</span>
                                                <span class="text-xs text-muted-foreground">{{ req.created_at }}</span>
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <Button size="icon" variant="outline" class="h-8 w-8 text-green-600 hover:text-green-700" @click="acceptRequest(req.id)">
                                                <Check class="h-4 w-4" />
                                            </Button>
                                            <Button size="icon" variant="outline" class="h-8 w-8 text-red-600 hover:text-red-700" @click="declineRequest(req.id)">
                                                <X class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                     <div v-if="pendingRequests.length === 0" class="text-sm text-gray-500 text-center py-4">
                                        No pending requests.
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>

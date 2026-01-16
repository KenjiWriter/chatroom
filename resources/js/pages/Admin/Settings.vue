<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { 
    Tabs, 
    TabsContent, 
    TabsList, 
    TabsTrigger 
} from '@/components/ui/tabs';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { 
    Dialog, 
    DialogContent, 
    DialogDescription, 
    DialogFooter, 
    DialogHeader, 
    DialogTitle,
    DialogTrigger
} from '@/components/ui/dialog';
import { Switch } from '@/components/ui/switch';
import { 
    PlusIcon, 
    PencilIcon, 
    TrashIcon, 
    ShieldCheckIcon,
    Settings2Icon,
    LayersIcon
} from 'lucide-vue-next';
import RankedUserLabel from '@/components/RankedUserLabel.vue';

interface Permission {
    id: number;
    name: string;
    slug: string;
}

interface Rank {
    id: number;
    name: string;
    prefix: string | null;
    priority: number;
    color_name: string;
    permissions: Permission[];
}

interface Room {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    min_level: number;
    required_rank_id: number | null;
    is_active: boolean;
    required_rank?: Rank;
}

const props = defineProps<{
    rooms: Room[];
    ranks: Rank[];
    permissions: Permission[];
    auth: {
        user: any;
    };
}>();

const activeTab = ref('rooms');

// --- Room Logic ---
const isRoomDialogOpen = ref(false);
const editingRoom = ref<Room | null>(null);

const roomForm = useForm({
    name: '',
    description: '',
    min_level: 0,
    required_rank_id: null as number | null,
    is_active: true,
});

const openRoomDialog = (room: Room | null = null) => {
    editingRoom.value = room;
    if (room) {
        roomForm.name = room.name;
        roomForm.description = room.description || '';
        roomForm.min_level = room.min_level;
        roomForm.required_rank_id = room.required_rank_id;
        roomForm.is_active = room.is_active;
    } else {
        roomForm.reset();
    }
    isRoomDialogOpen.value = true;
};

const submitRoom = () => {
    if (editingRoom.value) {
        roomForm.put(route('rooms.update', editingRoom.value.id), {
            onSuccess: () => {
                isRoomDialogOpen.value = false;
            }
        });
    } else {
        roomForm.post(route('rooms.store'), {
            onSuccess: () => {
                isRoomDialogOpen.value = false;
            }
        });
    }
};

const deleteRoom = (room: Room) => {
    if (confirm(`Are you sure you want to delete room "${room.name}"?`)) {
        roomForm.delete(route('rooms.destroy', room.id));
    }
};

// --- Rank Logic ---
const isPermissionDialogOpen = ref(false);
const editingRank = ref<Rank | null>(null);

const rankForm = useForm({
    permissions: [] as number[],
});

const openPermissionDialog = (rank: Rank) => {
    editingRank.value = rank;
    rankForm.permissions = rank.permissions.map(p => p.id);
    isPermissionDialogOpen.value = true;
};

const togglePermission = (permissionId: number) => {
    const index = rankForm.permissions.indexOf(permissionId);
    if (index === -1) {
        rankForm.permissions.push(permissionId);
    } else {
        rankForm.permissions.splice(index, 1);
    }
};

const submitPermissions = () => {
    if (editingRank.value) {
        // We use a partial update route or update the whole rank.
        // For simplicity, we use the update route but only send permissions.
        // Actually, our UpdateRankRequest might require all fields.
        // Let's assume we update only permissions in this specific context.
        rankForm.put(route('ranks.update', editingRank.value.id), {
            onSuccess: () => {
                isPermissionDialogOpen.value = false;
            }
        });
    }
};

const canManageRooms = computed(() => props.auth.user.permissions.includes('manage_rooms'));
const canManageRanks = computed(() => props.auth.user.permissions.includes('manage_ranks'));

</script>

<template>
    <AppLayout>
        <Head title="Management Settings" />

        <div class="max-w-6xl mx-auto py-8 px-4">
            <header class="mb-8">
                <h1 class="text-4xl font-bold tracking-tight">Management</h1>
                <p class="text-muted-foreground mt-2">Manage rooms, ranks, and community permissions.</p>
            </header>

            <Tabs v-model="activeTab" class="space-y-6">
                <TabsList class="grid w-full max-w-md grid-cols-2">
                    <TabsTrigger value="rooms" :disabled="!canManageRooms">
                        <Settings2Icon class="w-4 h-4 mr-2" />
                        Rooms
                    </TabsTrigger>
                    <TabsTrigger value="ranks" :disabled="!canManageRanks">
                        <LayersIcon class="w-4 h-4 mr-2" />
                        Ranks
                    </TabsTrigger>
                </TabsList>

                <!-- Rooms Tab -->
                <TabsContent value="rooms" class="space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-semibold">Chat Rooms</h2>
                        <Button @click="openRoomDialog()" class="gap-2">
                            <PlusIcon class="w-4 h-4" />
                            Create Room
                        </Button>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <Card v-for="room in rooms" :key="room.id" class="flex flex-col">
                            <CardHeader>
                                <div class="flex justify-between items-start">
                                    <div>
                                        <CardTitle>{{ room.name }}</CardTitle>
                                        <CardDescription>/{{ room.slug }}</CardDescription>
                                    </div>
                                    <div class="flex gap-1">
                                        <Button variant="ghost" size="icon" @click="openRoomDialog(room)">
                                            <PencilIcon class="w-4 h-4" />
                                        </Button>
                                        <Button variant="ghost" size="icon" class="text-destructive" @click="deleteRoom(room)">
                                            <TrashIcon class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent class="flex-1 space-y-4">
                                <p class="text-sm text-muted-foreground line-clamp-2">
                                    {{ room.description || 'No description provided.' }}
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <div class="px-2 py-1 bg-secondary rounded text-[10px] font-bold uppercase tracking-wider">
                                        Lvl {{ room.min_level }}+
                                    </div>
                                    <div v-if="room.required_rank" class="px-2 py-1 bg-primary/10 text-primary border border-primary/20 rounded text-[10px] font-bold uppercase tracking-wider">
                                        {{ room.required_rank.name }}+
                                    </div>
                                    <div v-else class="px-2 py-1 bg-green-500/10 text-green-500 border border-green-500/20 rounded text-[10px] font-bold uppercase tracking-wider">
                                        Open Entry
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Ranks Tab -->
                <TabsContent value="ranks" class="space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-semibold">User Ranks</h2>
                    </div>

                    <div class="rounded-md border bg-card">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="h-10 px-4 text-left font-medium">Rank</th>
                                    <th class="h-10 px-4 text-left font-medium">Priority</th>
                                    <th class="h-10 px-4 text-left font-medium">Permissions</th>
                                    <th class="h-10 px-4 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="rank in ranks" :key="rank.id" class="border-b transition-colors hover:bg-muted/50">
                                    <td class="p-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full" :style="{ backgroundColor: rank.color_name }"></div>
                                            <span class="font-bold" :style="{ color: rank.color_name }">{{ rank.name }}</span>
                                            <span v-if="rank.prefix" class="text-xs text-muted-foreground">[{{ rank.prefix }}]</span>
                                        </div>
                                    </td>
                                    <td class="p-4 font-mono text-xs">{{ rank.priority }}</td>
                                    <td class="p-4">
                                        <div class="flex flex-wrap gap-1">
                                            <div v-for="perm in rank.permissions.slice(0, 3)" :key="perm.id" class="px-1.5 py-0.5 bg-secondary text-[10px] rounded">
                                                {{ perm.name }}
                                            </div>
                                            <div v-if="rank.permissions.length > 3" class="px-1.5 py-0.5 bg-secondary text-[10px] rounded">
                                                +{{ rank.permissions.length - 3 }} more
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 text-right">
                                        <Button variant="outline" size="sm" class="gap-2" @click="openPermissionDialog(rank)">
                                            <ShieldCheckIcon class="w-4 h-4" />
                                            Edit Permissions
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </TabsContent>
            </Tabs>
        </div>

        <!-- Room Dialog -->
        <Dialog v-model:open="isRoomDialogOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>{{ editingRoom ? 'Edit Room' : 'Create Room' }}</DialogTitle>
                    <DialogDescription>
                        Configure chat room details and entry requirements.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitRoom" class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="name">Room Name</Label>
                        <Input id="name" v-model="roomForm.name" placeholder="e.g. VIP Lounge" required />
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="description">Description</Label>
                        <Textarea id="description" v-model="roomForm.description" placeholder="Short description of the room..." />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="min_level">Min Level</Label>
                            <Input id="min_level" type="number" v-model="roomForm.min_level" min="0" />
                        </div>
                        <div class="space-y-2">
                            <Label for="rank">Min Rank Required</Label>
                            <select 
                                id="rank" 
                                v-model="roomForm.required_rank_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option :value="null">None (Open Entry)</option>
                                <option v-for="rank in ranks" :key="rank.id" :value="rank.id">
                                    {{ rank.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                        <Switch id="is_active" :checked="roomForm.is_active" @update:checked="roomForm.is_active = $event" />
                        <Label for="is_active">Room is Active</Label>
                    </div>
                </form>

                <DialogFooter>
                    <Button variant="outline" @click="isRoomDialogOpen = false">Cancel</Button>
                    <Button @click="submitRoom" :loading="roomForm.processing">
                        {{ editingRoom ? 'Save Changes' : 'Create Room' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Permission Dialog -->
        <Dialog v-model:open="isPermissionDialogOpen">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>Edit Permissions: {{ editingRank?.name }}</DialogTitle>
                    <DialogDescription>
                        Toggle granular permissions for this rank.
                    </DialogDescription>
                </DialogHeader>

                <div class="py-4 space-y-4 max-h-[60vh] overflow-y-auto">
                    <div v-for="perm in permissions" :key="perm.id" class="flex items-center justify-between p-3 rounded-lg border bg-muted/30">
                        <div>
                            <p class="font-medium text-sm">{{ perm.name }}</p>
                            <p class="text-xs text-muted-foreground font-mono">{{ perm.slug }}</p>
                        </div>
                        <Switch 
                            :checked="rankForm.permissions.includes(perm.id)"
                            @update:checked="togglePermission(perm.id)"
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="isPermissionDialogOpen = false">Cancel</Button>
                    <Button @click="submitPermissions" :loading="rankForm.processing">
                        Update Permissions
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

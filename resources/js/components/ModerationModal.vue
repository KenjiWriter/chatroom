<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    isOpen: boolean;
    user: any; // The target user
    roomId?: number; // Context room
}>();

const emit = defineEmits(['close', 'kick', 'mute', 'ban', 'unmute']);

const page = usePage();
const myPermissions = computed(() => (page.props.auth.user as any)?.permissions || []);

const availableActions = computed(() => {
    const actions: string[] = [];
    if (!props.user) return actions;
    if (myPermissions.value.includes('mute_temp') || myPermissions.value.includes('mute_perm')) actions.push('mute');
    if (myPermissions.value.includes('unmute_user') && props.user.is_muted) actions.push('unmute');
    if (myPermissions.value.includes('kick_user')) actions.push('kick');
    if (myPermissions.value.includes('ban_room_access')) actions.push('ban');
    return actions;
});

const action = ref<'kick' | 'mute' | 'ban' | 'unmute'>('mute');

// Reset action if current one becomes unavailable
watch(availableActions, (newActions) => {
    if (newActions.length > 0 && !newActions.includes(action.value)) {
        action.value = newActions[0] as any;
    }
}, { immediate: true });

const duration = ref<number | ''>(10); // Minutes
const reason = ref('');

const isPermanent = ref(false);

const canDoPermMute = computed(() => myPermissions.value.includes('mute_perm'));

const submit = () => {
    emit(action.value, {
        userId: props.user.id,
        duration: (isPermanent.value && canDoPermMute.value) ? null : duration.value,
        reason: reason.value,
        roomId: props.roomId
    });
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-xl w-96 animate-in fade-in zoom-in duration-200">
            <h3 class="text-lg font-bold mb-4 dark:text-white">Moderate {{ user.name }}</h3>
            
            <div class="flex gap-2 mb-4">
                <button 
                    v-for="a in availableActions" 
                    :key="a"
                    @click="action = a as any"
                    class="flex-1 py-1 rounded capitalize transition-colors"
                    :class="action === a ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 dark:text-gray-300'"
                >
                    {{ a }}
                </button>
            </div>

            <div class="space-y-4">
                <div v-if="action !== 'kick'">
                    <Label>Duration (Minutes)</Label>
                    <div class="flex gap-2 items-center">
                        <Input 
                            v-model="duration" 
                            type="number" 
                            :disabled="isPermanent" 
                            placeholder="Min" 
                        />
                        <label class="flex items-center gap-2 whitespace-nowrap text-sm dark:text-gray-300">
                            <input type="checkbox" v-model="isPermanent"> Permanent
                        </label>
                    </div>
                </div>

                <div>
                    <Label>Reason</Label>
                    <Input v-model="reason" placeholder="Spam, Rude, etc." />
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <Button variant="secondary" @click="$emit('close')">Cancel</Button>
                <Button variant="destructive" @click="submit">Confirm {{ action }}</Button>
            </div>
        </div>
    </div>
</template>

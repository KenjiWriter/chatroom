<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { 
    Dialog, 
    DialogContent, 
    DialogHeader, 
    DialogTitle, 
    DialogDescription,
    DialogFooter
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { AlertTriangle, ShieldAlert, Gavel, Volume2 } from 'lucide-vue-next';

const props = defineProps<{
    punishment?: any;
    isOpen?: boolean;
}>();

const emit = defineEmits(['close']);

const show = ref(false);

const playAlert = () => {
    const audio = new Audio('/sounds/error.mp3');
    audio.play().catch(() => {});
};

watch(() => props.punishment, (newVal) => {
    if (newVal) {
        show.value = true;
        playAlert();
    }
}, { immediate: true });

// If isOpen prop is provided, use it to control the initial state of 'show'
// and allow external control.
watch(() => props.isOpen, (newVal) => {
    if (newVal !== undefined) {
        show.value = newVal;
    }
}, { immediate: true });

const displayPunishment = computed(() => props.punishment);

const title = computed(() => {
    switch (displayPunishment.value?.type) {
        case 'kick': return 'Zostałeś wyrzucony!';
        case 'mute': return 'Zostałeś wyciszony!';
        case 'ban': return 'Zostałeś zbanowany!';
        default: return 'Powiadomienie o karze';
    }
});

const themeColor = computed(() => {
    switch (displayPunishment.value?.type) {
        case 'kick': return 'text-orange-500';
        case 'mute': return 'text-yellow-500';
        case 'ban': return 'text-red-500';
        default: return 'text-indigo-500';
    }
});

const close = () => {
    show.value = false;
    emit('close');
};
</script>

<template>
    <Dialog :open="show" @update:open="(v) => !v && close()">
        <DialogContent class="sm:max-w-[425px] border-2" :class="displayPunishment?.type === 'ban' ? 'border-red-600' : 'border-orange-500'">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-2xl font-black uppercase italic" :class="themeColor">
                    <ShieldAlert v-if="displayPunishment?.type === 'ban'" class="w-8 h-8" />
                    <AlertTriangle v-else class="w-8 h-8" />
                    {{ title }}
                </DialogTitle>
                <DialogDescription class="text-gray-600 dark:text-gray-400 font-medium">
                    Twoje konto zostało poddane moderacji. Poniżej znajdują się szczegóły podjętej akcji.
                </DialogDescription>
            </DialogHeader>

            <div v-if="displayPunishment" class="py-4 space-y-4">
                <div class="grid grid-cols-3 gap-2 text-sm">
                    <div class="text-gray-500 uppercase font-bold text-[10px]">Administrator</div>
                    <div class="col-span-2 font-semibold">{{ displayPunishment.adminName || 'System' }}</div>
                    
                    <div class="text-gray-500 uppercase font-bold text-[10px]">Powód</div>
                    <div class="col-span-2 text-red-600 dark:text-red-400 font-bold italic">"{{ displayPunishment.reason }}"</div>
                    
                    <div v-if="displayPunishment.roomName" class="text-gray-500 uppercase font-bold text-[10px]">Miejsce</div>
                    <div v-if="displayPunishment.roomName" class="col-span-2">{{ displayPunishment.roomName }}</div>
                    
                    <div v-if="displayPunishment.duration" class="text-gray-500 uppercase font-bold text-[10px]">Czas trwania</div>
                    <div v-if="displayPunishment.duration" class="col-span-2 text-orange-600 font-black">{{ displayPunishment.duration }}</div>
                </div>

                <div class="p-3 bg-gray-100 dark:bg-gray-800 rounded-md text-xs text-center border border-gray-200 dark:border-gray-700">
                    Pamiętaj o przestrzeganiu regulaminu chatroomu. Kolejne naruszenia mogą skutkować trwałym zablokowaniem konta.
                </div>
            </div>

            <DialogFooter>
                <Button type="button" :variant="displayPunishment?.type === 'ban' ? 'destructive' : 'default'" class="w-full font-bold uppercase transition-all hover:scale-105" @click="close">
                    Rozumiem
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

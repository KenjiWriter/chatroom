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

const page = usePage();
const punishment = computed(() => (page.props as any).flash?.punishment);
const show = ref(false);

const playAlert = () => {
    const audio = new Audio('/sounds/error.mp3');
    audio.play().catch(() => {});
};

watch(punishment, (newVal) => {
    if (newVal) {
        show.value = true;
        playAlert();
    }
}, { immediate: true });

const title = computed(() => {
    switch (punishment.value?.type) {
        case 'kick': return 'Zostałeś wyrzucony!';
        case 'mute': return 'Zostałeś wyciszony!';
        case 'ban': return 'Zostałeś zbanowany!';
        default: return 'Powiadomienie o karze';
    }
});

const themeColor = computed(() => {
    switch (punishment.value?.type) {
        case 'kick': return 'text-orange-500';
        case 'mute': return 'text-yellow-500';
        case 'ban': return 'text-red-500';
        default: return 'text-indigo-500';
    }
});

const close = () => {
    show.value = false;
    // We don't necessarily need to clear the flash here as Inertia usually handles it 
    // but if we want to be sure it doesn't pop again on another redirect:
    // router.reload({ only: [] }); // Not ideal. 
    // Flash messages are usually single-use in Inertia.
};
</script>

<template>
    <Dialog :open="show" @update:open="(v) => !v && close()">
        <DialogContent class="sm:max-w-[425px] border-2" :class="punishment?.type === 'ban' ? 'border-red-600' : 'border-orange-500'">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-2xl font-black uppercase italic" :class="themeColor">
                    <ShieldAlert v-if="punishment?.type === 'ban'" class="w-8 h-8" />
                    <AlertTriangle v-else class="w-8 h-8" />
                    {{ title }}
                </DialogTitle>
                <DialogDescription class="text-gray-600 dark:text-gray-400 font-medium">
                    Twoje konto zostało poddane moderacji. Poniżej znajdują się szczegóły podjętej akcji.
                </DialogDescription>
            </DialogHeader>

            <div v-if="punishment" class="py-4 space-y-4">
                <div class="grid grid-cols-3 gap-2 text-sm">
                    <div class="text-gray-500 uppercase font-bold text-[10px]">Administrator</div>
                    <div class="col-span-2 font-semibold">{{ punishment.admin }}</div>
                    
                    <div class="text-gray-500 uppercase font-bold text-[10px]">Powód</div>
                    <div class="col-span-2 text-red-600 dark:text-red-400 font-bold italic">"{{ punishment.reason }}"</div>
                    
                    <div v-if="punishment.room" class="text-gray-500 uppercase font-bold text-[10px]">Miejsce</div>
                    <div v-if="punishment.room" class="col-span-2">{{ punishment.room }}</div>
                    
                    <div v-if="punishment.duration" class="text-gray-500 uppercase font-bold text-[10px]">Czas trwania</div>
                    <div v-if="punishment.duration" class="col-span-2 text-orange-600 font-black">{{ punishment.duration }}</div>
                </div>

                <div class="p-3 bg-gray-100 dark:bg-gray-800 rounded-md text-xs text-center border border-gray-200 dark:border-gray-700">
                    Pamiętaj o przestrzeganiu regulaminu chatroomu. Kolejne naruszenia mogą skutkować trwałym zablokowaniem konta.
                </div>
            </div>

            <DialogFooter>
                <Button type="button" :variant="punishment?.type === 'ban' ? 'destructive' : 'default'" class="w-full font-bold uppercase transition-all hover:scale-105" @click="close">
                    Rozumiem
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

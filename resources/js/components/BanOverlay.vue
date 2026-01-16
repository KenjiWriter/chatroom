<script setup lang="ts">
import { computed } from 'vue';
import { ShieldAlert, Gavel, LogOut } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    banData: {
        reason: string;
        expires_at: string | null;
        moderator: string;
    }
}>();

const formattedExpiry = computed(() => {
    if (!props.banData.expires_at) return 'Permanent';
    return new Date(props.banData.expires_at).toLocaleString();
});

const handleLogout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="fixed inset-0 z-[9999] flex items-center justify-center p-4 backdrop-blur-xl bg-black/80 animate-in fade-in duration-500">
        <div class="max-w-md w-full bg-card border-none shadow-2xl rounded-2xl overflow-hidden animate-in zoom-in-95 duration-300">
            <div class="bg-destructive p-8 flex justify-center">
                <div class="bg-white/20 p-4 rounded-full">
                    <ShieldAlert class="w-16 h-16 text-white" />
                </div>
            </div>
            
            <div class="p-8 text-center">
                <h1 class="text-3xl font-black tracking-tighter mb-2">ACCESS DENIED</h1>
                <p class="text-muted-foreground mb-6 uppercase tracking-widest text-xs font-bold">Your account has been suspended</p>
                
                <div class="space-y-4 text-left bg-muted/50 p-6 rounded-xl border border-border">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-muted-foreground block mb-1">Reason</span>
                        <p class="text-sm font-medium leading-relaxed">{{ banData.reason }}</p>
                    </div>
                    
                    <div class="flex justify-between border-t border-border pt-4">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-muted-foreground block mb-1">Moderator</span>
                            <p class="text-sm font-semibold flex items-center gap-1.5">
                                <Gavel class="w-3.5 h-3.5 text-destructive" />
                                {{ banData.moderator }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] uppercase font-bold text-muted-foreground block mb-1">Ends</span>
                            <p class="text-sm font-semibold">{{ formattedExpiry }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8">
                    <Button 
                        variant="ghost" 
                        class="w-full gap-2 text-muted-foreground hover:text-foreground"
                        @click="handleLogout"
                    >
                        <LogOut class="w-4 h-4" /> Sign Out
                    </Button>
                </div>
                
                <p class="mt-8 text-[10px] text-muted-foreground uppercase tracking-widest leading-loose">
                    If you believe this is an error,<br>please contact the administration.
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Prevent any interaction with elements behind the overlay */
.fixed {
    pointer-events: auto;
}
</style>

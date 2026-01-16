<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';
import VerificationBanner from '@/components/VerificationBanner.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import BanOverlay from '@/components/BanOverlay.vue';
import PunishmentModal from '@/components/PunishmentModal.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const user = computed(() => page.props.auth.user as any);

const isBanned = ref(user.value?.is_banned || false);
const banData = ref(user.value?.ban_data || null);
const activePunishment = ref<any>(null);
const kickCountdown = ref(0);

const playNotificationSound = () => {
    try {
        const audio = new Audio('/sounds/notification.mp3'); // Assuming standard location
        audio.play().catch(() => {}); // Browser might block un-interacted audio
    } catch (e) {}
};

onMounted(() => {
    if (!user.value) return;

    // Listen for Punishments
    // @ts-ignore
    window.Echo.private(`user-notifications.${user.value.id}`)
        .listen('UserPunished', (e: any) => {
            playNotificationSound();
            
            // Set active punishment to show modal
            activePunishment.value = e;

            if (e.type === 'ban') {
                isBanned.value = true;
                banData.value = {
                    reason: e.reason,
                    expires_at: e.expiresAt,
                    moderator: e.adminName
                };
            } else if (e.type === 'kick') {
                kickCountdown.value = 5;
                toast.error(`Zostałeś wyrzucony: ${e.reason}`, {
                    description: "Przekierowanie do panelu głównego za 5 sekund...",
                    duration: 5000,
                });
                
                const timer = setInterval(() => {
                    kickCountdown.value--;
                    if (kickCountdown.value <= 0) {
                        clearInterval(timer);
                        activePunishment.value = null; // Close modal before redirect
                        router.visit(route('dashboard'));
                    }
                }, 1000);
            } else if (e.type === 'mute') {
                toast.warning(`Zostałeś wyciszony: ${e.reason}`, {
                    description: `Czas trwania: ${e.duration || 'Nieznany'}`,
                });
                // Optionally reload to update auth user state if needed immediately
                // router.reload({ only: ['auth'] });
            } else if (e.type === 'unmute') {
                activePunishment.value = null; // Close any open punishment modal
                toast.success('Zostałeś odwyciszony', {
                    description: 'Możesz ponownie pisać na czacie.',
                    duration: 5000,
                });
                router.reload(); // Reload to refresh permissions/state
            }
        });
});
</script>

<template>
    <div class="flex flex-col min-h-screen">
        <BanOverlay v-if="isBanned && banData" :ban-data="banData" />
        
        <VerificationBanner />
        <AppSidebarLayout :breadcrumbs="breadcrumbs" class="flex-1">
            <slot />
        </AppSidebarLayout>
        <PunishmentModal 
            :punishment="activePunishment"
            :is-open="!!activePunishment"
            @close="activePunishment = null"
        />
    </div>
</template>

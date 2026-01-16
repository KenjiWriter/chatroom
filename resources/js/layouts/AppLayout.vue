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
                if (e.roomName) {
                    // Room Ban
                    toast.error(`Zostałeś zbanowany z pokoju: ${e.roomName}`, {
                        description: `Powód: ${e.reason}`,
                        duration: 5000,
                    });
                    
                    // If currently in this room, redirect
                    if (window.location.pathname.includes(`/chat/${e.roomSlug}`)) { // Ideally use ID or slug check if available
                        // Or just checking if we act on "chat" route.
                        // Simple fallback: if we are in a chat room, reload or redirect.
                        // But verifying room is hard without slug in payload.
                        // For now, let's just show Toast. Middleware handles access on move.
                        // Actually, if we want immediate kick-out, we need `roomSlug` or ID in event.
                        router.visit(route('dashboard'));
                    }
                } else {
                    // Global Ban
                    isBanned.value = true;
                    banData.value = {
                        reason: e.reason,
                        expires_at: e.expiresAt,
                        moderator: e.adminName
                    };
                }
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
                activePunishment.value = null;
                toast.success('Zostałeś odwyciszony', {
                    description: 'Możesz ponownie pisać na czacie.',
                    duration: 5000,
                });
                router.reload();
            } else if (e.type === 'unban') {
                activePunishment.value = null; 
                toast.success('Blokada została zdjęta', {
                    description: e.roomName ? `Dostęp do pokoju ${e.roomName} przywrócony.` : 'Twoje konto zostało odblokowane.',
                    duration: 5000,
                });
                if (!e.roomName) {
                     isBanned.value = false; // Clear global ban overlay
                }
                router.reload();
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

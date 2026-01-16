<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';
import VerificationBanner from '@/components/VerificationBanner.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import BanOverlay from '@/components/BanOverlay.vue';
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
            
            if (e.type === 'ban') {
                isBanned.value = true;
                banData.value = {
                    reason: e.reason,
                    expires_at: e.duration === 'permanently' ? null : e.expiresAt, // Need to ensure event sends expiresAt if possible, or duration string
                    moderator: 'Administrative Action'
                };
            } else if (e.type === 'kick') {
                kickCountdown.value = 5;
                toast.error(`You have been kicked: ${e.reason}`, {
                    description: "Redirecting to dashboard in 5 seconds...",
                    duration: 5000,
                });
                
                const timer = setInterval(() => {
                    kickCountdown.value--;
                    if (kickCountdown.value <= 0) {
                        clearInterval(timer);
                        router.visit(route('dashboard'));
                    }
                }, 1000);
            } else if (e.type === 'mute') {
                toast.warning(`You have been muted: ${e.reason}`, {
                    description: `Duration: ${e.duration || 'Unknown'}`,
                });
                // State is tracked primarily via Inertia shared props for persistence, 
                // but event triggers immediate feedback.
                // Re-fetch or update shared data? 
                // Inertia automatically updates page.props.auth.user if we use standard partial reload or just trust the next visit.
                // For real-time sync, we might need a global store or just trust the next backend validation.
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
    </div>
</template>

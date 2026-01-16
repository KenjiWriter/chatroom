<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { AlertTriangle } from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth.user);
const verificationSent = ref(false);

const isUnverified = computed(() => {
    return user.value && !user.value.email_verified_at;
});
</script>

<template>
    <div v-if="isUnverified" class="bg-yellow-500/10 border-b border-yellow-500/20 text-yellow-600 dark:text-yellow-400 px-4 py-3">
        <div class="container mx-auto flex flex-col sm:flex-row items-center justify-between gap-3 text-sm">
            <div class="flex items-center gap-2">
                <AlertTriangle class="h-4 w-4" />
                <span class="font-medium">
                    Your account is not verified! You currently have limited access.
                </span>
            </div>
            
            <Link
                :href="route('verification.send')"
                method="post"
                as="button"
                @success="verificationSent = true"
                class="whitespace-nowrap font-bold hover:underline"
            >
                {{ verificationSent ? 'Verification Link Sent!' : 'Resend Verification Email' }}
            </Link>
        </div>
    </div>
</template>

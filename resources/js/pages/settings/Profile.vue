<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { route } from 'ziggy-js';
import { type BreadcrumbItem } from '@/types';
import { toast } from 'vue-sonner';
import { Trash2, Upload } from 'lucide-vue-next';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: route('profile.edit'),
    },
];

const page = usePage();
const user = page.props.auth.user as any; // Cast to any to avoid strict TS issues with dynamic props

const form = useForm({
    _method: 'PATCH',
    name: user.name,
    email: user.email,
    bio: user.bio || '',
    avatar: null as File | null,
    banner: null as File | null,
    delete_avatar: false,
    delete_banner: false,
    is_private: !!user.is_private,
    preferences: {
        show_online_status: user.preferences?.show_online_status ?? true,
        show_current_room: user.preferences?.show_current_room ?? true,
    }
});

// Previews
const avatarPreview = ref<string | null>(user.avatar_url);
const bannerPreview = ref<string | null>(user.banner_url);

const handleAvatarChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        const file = input.files[0];
        form.avatar = file;
        form.delete_avatar = false; // Reset delete flag
        
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const handleBannerChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        const file = input.files[0];
        form.banner = file;
        form.delete_banner = false;
        
        const reader = new FileReader();
        reader.onload = (e) => {
            bannerPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const deleteAvatar = () => {
    form.avatar = null;
    form.delete_avatar = true;
    avatarPreview.value = null; // Or show placeholder
};

const deleteBanner = () => {
    form.banner = null;
    form.delete_banner = true;
    bannerPreview.value = null;
};

// Trigger file inputs via refs
const avatarInput = ref<HTMLInputElement | null>(null);
const bannerInput = ref<HTMLInputElement | null>(null);

const triggerAvatarUpload = () => avatarInput.value?.click();
const triggerBannerUpload = () => bannerInput.value?.click();

const submit = () => {
    form.post(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Profile updated successfully.');
            // Update initial previews just in case (though page reload/prop update handles it usually)
        },
        onError: () => {
             toast.error('Failed to update profile.');
        }
    });
};
const getBannerUrl = (url: string | null) => {
    if (!url) return null;
    if (url.startsWith('data:') || url.startsWith('http') || url.startsWith('/storage')) return url;
    return `/storage/${url}`;
};

const displayBanner = computed(() => {
    return getBannerUrl(bannerPreview.value);
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <h1 class="sr-only">Profile Settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-8">
                <HeadingSmall
                    title="Profile information"
                    description="Update your profile details and privacy settings."
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Media Uploads -->
                    <div class="space-y-6">
                        <!-- Banner -->
                        <div class="space-y-2">
                             <Label>Profile Banner</Label>
                             <div 
                                class="relative h-32 md:h-48 w-full rounded-xl bg-muted overflow-hidden group border border-input"
                                :style="{ 
                                    backgroundImage: displayBanner ? `url('${displayBanner}')` : undefined,
                                    background: !displayBanner ? `linear-gradient(to right, ${user.rank_data?.color_name || '#666'}, #1a1a1a)` : undefined,
                                    backgroundSize: 'cover',
                                    backgroundPosition: 'center',
                                    backgroundRepeat: 'no-repeat'
                                }"
                             >
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                    <Button type="button" variant="secondary" size="sm" @click="triggerBannerUpload">
                                        <Upload class="w-4 h-4 mr-2" /> Change Banner
                                    </Button>
                                    <Button v-if="bannerPreview" type="button" variant="destructive" size="sm" @click="deleteBanner">
                                        <Trash2 class="w-4 h-4" />
                                    </Button>
                                </div>
                             </div>
                             <input 
                                ref="bannerInput"
                                type="file" 
                                class="hidden" 
                                accept="image/jpeg,image/png,image/jpg,image/gif"
                                @change="handleBannerChange"
                             />
                             <InputError :message="form.errors.banner" />
                        </div>

                        <!-- Avatar -->
                        <div class="flex items-center gap-6">
                            <div class="relative group">
                                <Avatar class="h-24 w-24 border-4 border-background shadow-sm">
                                    <AvatarImage :src="avatarPreview || ''" />
                                    <AvatarFallback class="text-xl">{{ user.name.substring(0,2).toUpperCase() }}</AvatarFallback>
                                </Avatar>
                                <div class="absolute inset-0 rounded-full bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                     <Button type="button" variant="ghost" size="icon" class="text-white hover:text-white hover:bg-white/20 rounded-full" @click="triggerAvatarUpload">
                                        <Upload class="w-5 h-5" />
                                    </Button>
                                </div>
                            </div>
                            
                            <div class="flex flex-col gap-2">
                                <Label>Avatar</Label>
                                <div class="text-xs text-muted-foreground max-w-[200px]">
                                    Click the avatar to upload. Max 2MB.
                                </div>
                                <div v-if="avatarPreview" class="mt-1">
                                    <Button type="button" variant="outline" size="xs" class="h-7 text-xs text-red-500 hover:text-red-600" @click="deleteAvatar">
                                        Remove Avatar
                                    </Button>
                                </div>
                            </div>
                            
                            <input 
                                ref="avatarInput"
                                type="file" 
                                class="hidden" 
                                accept="image/jpeg,image/png,image/jpg,image/gif"
                                @change="handleAvatarChange"
                             />
                        </div>
                        <InputError :message="form.errors.avatar" />
                    </div>

                    <!-- Basic Info -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                required
                                autocomplete="name"
                                placeholder="Full name"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                v-model="form.email"
                                required
                                autocomplete="username"
                                placeholder="Email address"
                            />
                            <InputError :message="form.errors.email" />
                        </div>
                    </div>
                    
                    <!-- Bio -->
                    <div class="space-y-2">
                        <Label for="bio">Bio</Label>
                        <Textarea
                            id="bio"
                            v-model="form.bio"
                            placeholder="Tell us about yourself..."
                            class="h-24"
                        />
                         <div class="text-xs text-muted-foreground flex justify-end">
                            {{ form.bio.length }} / 500
                        </div>
                        <InputError :message="form.errors.bio" />
                    </div>

                    <!-- Privacy & Preferences -->
                    <div class="space-y-4 pt-4 border-t">
                        <h3 class="font-medium text-sm">Privacy & Preferences</h3>
                        
                        <div class="flex items-center justify-between rounded-lg border p-4">
                            <div class="space-y-0.5">
                                <Label class="text-base">Private Profile</Label>
                                <div class="text-sm text-muted-foreground">
                                    Hide your bio and XP progress from non-friends.
                                </div>
                            </div>
                            <Switch v-model:checked="form.is_private" />
                        </div>

                        <div class="flex items-center justify-between rounded-lg border p-4">
                            <div class="space-y-0.5">
                                <Label class="text-base">Show Online Status</Label>
                                <div class="text-sm text-muted-foreground">
                                    Allow friends to see when you are online.
                                </div>
                            </div>
                            <Switch v-model:checked="form.preferences.show_online_status" />
                        </div>
                        
                         <div class="flex items-center justify-between rounded-lg border p-4">
                            <div class="space-y-0.5">
                                <Label class="text-base">Show Current Room</Label>
                                <div class="text-sm text-muted-foreground">
                                    Allow friends to see which room you are in.
                                </div>
                            </div>
                            <Switch v-model:checked="form.preferences.show_current_room" />
                        </div>
                    </div>

                    <div v-if="props.mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Click here to resend the verification email.
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            A new verification link has been sent to your email address.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save Changes</Button>
                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                        </Transition>
                    </div>
                </form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>

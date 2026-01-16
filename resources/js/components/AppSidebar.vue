<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, MessageSquare, Shield } from 'lucide-vue-next';
import { computed } from 'vue';
import { route } from 'ziggy-js';

import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavDirectMessages from '@/components/NavDirectMessages.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';

import AppLogo from './AppLogo.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const mainNavItems = computed<NavItem[]>(() => {
    const items = [
        {
            title: 'Dashboard',
            href: route('dashboard'),
            icon: LayoutGrid,
        },
        {
            title: 'Chat Rooms',
            href: route('chat.index'),
            icon: MessageSquare,
        },
    ];

    const perms = (user.value as any)?.permissions || [];
    if (perms.includes('manage_rooms') || perms.includes('manage_ranks')) {
        items.push({
            title: 'Management',
            href: route('admin.settings'),
            icon: Shield,
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];

import { ref, onMounted, onUnmounted } from 'vue';

const totalOnline = ref(0);

onMounted(() => {
    // @ts-ignore
    window.Echo.join('online')
        .here((users: any[]) => totalOnline.value = users.length)
        .joining(() => totalOnline.value++)
        .leaving(() => totalOnline.value = Math.max(0, totalOnline.value - 1));
});

onUnmounted(() => {
    // @ts-ignore
    window.Echo.leave('online');
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <div class="flex items-center justify-between px-2 mb-2">
                        <SidebarMenuButton size="lg" as-child class="flex-1">
                            <Link :href="route('dashboard')">
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                        
                        <div v-if="totalOnline > 0" class="flex items-center gap-1.5 px-2 py-1 rounded-md bg-green-500/10 text-green-600 dark:text-green-400 border border-green-500/20 ml-2 animate-in fade-in duration-500">
                            <span class="relative flex h-1.5 w-1.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-500 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-green-500"></span>
                            </span>
                            <span class="text-[10px] font-black tabular-nums">{{ totalOnline }}</span>
                        </div>
                    </div>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavDirectMessages />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

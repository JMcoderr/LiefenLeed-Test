<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { AdminAuth, type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Library, ClipboardPlus, User2, Users2 } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage<SharedData>();
const admin = (page.props.auth.user.admin as AdminAuth) ?? false;

const mainNavItems: NavItem[] = [
    {
        title: 'Aanvragen',
        href: route('requests'),
        icon: ClipboardPlus,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Beoordelen',
        href: route('admin.requests.index'),
        icon: BookOpen,
    },
];

const superNavItems: NavItem[] = [
    {
        title: 'Type aanvragen',
        href: route('admin.events.index'),
        icon: Library,
    },
    {
        title: 'Leden',
        href: route('admin.members.index'),
        icon: Users2,
    },
    {
        title: 'Admins',
        href: route('admin.admins.index'),
        icon: User2,
    },
];

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Github Repo',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: Folder,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits#vue',
    //     icon: BookOpen,
    // },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('admin.requests.index')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain title="Gebruiker" :items="mainNavItems" />
            <NavMain v-if="admin.isAdmin" title="Admin" :items="adminNavItems" />
            <NavMain v-if="admin.isSuper" title="Super" :items="superNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

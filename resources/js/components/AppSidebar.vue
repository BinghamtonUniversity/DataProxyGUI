<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Users, Database, File, Calendar, History, Building2, Globe } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Environments',
        href: '/environments',
        icon: Building2,
    },
    {
        title: 'API Accounts',
        href: '/users',
        icon: Users,
    },
    {
        title: 'APIS',
        href: '/apis',
        icon: Folder,
    },
    {
        title: 'API Instances',
        href: '/api_instances',
        icon: Database,
    },
    {
        title: 'Resources',
        href: '/resources',
        icon: File,
    },
    {
        title: 'Schedules',
        href: '/schedules',
        icon: Calendar,
    },{
        title: 'Activity Logs',
        href: '/activity-logs',
        icon: History,
    },

    // {
    //     title: 'Editor',
    //     href: '/editor',
    //     icon: BookOpen,
    // },

];

const footerNavItems: NavItem[] = [
    {
        title: 'Development',
        href: '#',
        icon: Folder,
        children: [
            {
                title: 'CAS Users',
                href: '/cas_users',
                icon: Users,
            },
            {
                title: 'DataGrid Example',
                href: '/datagrid-example',
                icon: LayoutGrid,
            },
            {
                title: 'Types Example',
                href: '/types-example',
                icon: Folder,
            },
            {
                title: 'FormViewer Example',
                href: '/formviewer-example',
                icon: BookOpen,
            },
            {
                title: 'Formbuilder Example',
                href: '/formbuilder-example',
                icon: LayoutGrid,
            },
        ]
    },
    {
        title: 'Github Repo',
        href: 'https://github.com/BinghamtonUniversity/DataProxyGUI',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

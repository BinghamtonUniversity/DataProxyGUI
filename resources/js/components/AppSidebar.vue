<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Users, Database, File, Calendar, History, Building2, Globe, ShieldCheckIcon, ShieldCheck, TestTube, CheckCircle } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { useProxyServer } from '@/composables/useProxyServer';
import NoServerAvailable from '@/pages/NoServerAvailable.vue';

const { buildUrl, serverSlug } = useProxyServer();

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: buildUrl('dashboard'),
        icon: LayoutGrid,
    },
    {
        title: 'Environments',
        href: buildUrl('environments'),
        icon: Building2,
    },
    {
        title: 'API Accounts',
        href: buildUrl('api_accounts'),
        icon: Users,
    },
    {
        title: 'Users',
        href: buildUrl('users'),
        icon: ShieldCheck,
    },
    {
        title: 'APIS',
        href: buildUrl('apis'),
        icon: Folder,
    },
    {
        title: 'API Instances',
        href: buildUrl('api_instances'),
        icon: Database,
    },
    {
        title: 'Resources',
        href: buildUrl('resources'),
        icon: File,
    },
    {
        title: 'Schedules',
        href: buildUrl('schedules'),
        icon: Calendar,
    },{
        title: 'Activity Logs',
        href: buildUrl('activity_log'),
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
                title: 'Users',
                href: '/users',
                icon: Users,
            },
            // {
            //     title: 'Unit Tests',
            //     href: '/unit-tests',
            //     icon: CheckCircle,
            // },
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
                        <Link :href="buildUrl('environments')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>
        <SidebarContent v-if="!serverSlug">
           <NoServerAvailable />
        </SidebarContent>
        <SidebarContent v-else>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

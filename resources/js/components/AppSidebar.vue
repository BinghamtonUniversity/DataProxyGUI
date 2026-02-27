<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Folder, LayoutGrid, Database, File, Calendar, History, Building2, ShieldCheck, Users } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { useProxyServer } from '@/composables/useProxyServer';
import NoServerAvailable from '@/pages/NoServerAvailable.vue';

const { buildUrl, serverSlug } = useProxyServer();
const page = usePage();

const can = computed(() => page.props.can as { server_admin: boolean});

const allNavItems: NavItem[] = [
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
    },
    {
        title: 'Activity Logs',
        href: buildUrl('activity_log'),
        icon: History,
    },
];

const mainNavItems = computed(() =>
    can.value.server_admin
        ? allNavItems
        : allNavItems.filter(
              (item) => item.title !== 'Environments' 
              && item.title !== 'Activity Logs' 
              && item.title !== 'Resources' 
              && item.title !== 'Users'
          )
);

</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="serverSlug ? `/${serverSlug}/dashboard` : '#'">
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
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

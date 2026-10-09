<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { LayoutGrid, Database, File, Calendar, History, Building2, Network, Users, UserPlus, SquareCode} from 'lucide-vue-next';
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
        icon: UserPlus,
    },
    {
        title: 'Users',
        href: buildUrl('users'),
        icon: Users,
    },
    {
        title: 'APIS',
        href: buildUrl('apis'),
        icon: SquareCode,
    },
    {
        title: 'API Instances',
        href: buildUrl('api_instances'),
        icon: Network,
    },
    {
        title: 'Resources',
        href: buildUrl('resources'),
        icon: Database,
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
                    <SidebarMenuButton size="lg" class="h-14! group-data-[collapsible=icon]:size-8!" as-child>
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

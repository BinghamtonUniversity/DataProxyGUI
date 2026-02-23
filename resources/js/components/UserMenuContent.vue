<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import type { User, NavItem } from '@/types';
import { router } from '@inertiajs/vue3';
import { LogOut, Settings, Server, BookOpen, Folder, LayoutGrid, Users, ChevronDown, ChevronRight } from 'lucide-vue-next';
import { ref, onMounted, computed } from 'vue';
import { useProxyServer } from '@/composables/useProxyServer';

const footerNavItems: NavItem[] = [
    {
        title: 'Development',
        href: '#',
        icon: Folder,
        children: [
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

const isExternalLink = (href: string) => href.startsWith('http');

/** Resolve href: add proxy server slug prefix when there is an active server and link is internal. */
const resolveHref = (href: string) => {
    if (isExternalLink(href)) return href;
    if (serverSlug.value) return buildUrl(href);
    return href;
};

const expandedSections = ref<Set<string>>(new Set());
const toggleSection = (title: string) => {
    if (expandedSections.value.has(title)) {
        expandedSections.value.delete(title);
    } else {
        expandedSections.value.add(title);
    }
    expandedSections.value = new Set(expandedSections.value);
};


interface Props {
    user: User;
}

interface ProxyServer {
    id: number;
    name: string;
    slug: string;
    server: string;
}

const proxyServers = ref<ProxyServer[]>([]);
const { buildUrl, serverSlug } = useProxyServer();

// Use the composable's serverSlug instead of fetching it
const currentServer = computed(() => serverSlug.value);

const fetchProxyServers = async () => {
    try {
        const response = await fetch('/api/proxy-servers');
        const data = await response.json();
        proxyServers.value = data.servers;

    } catch (error) {
        console.error('Failed to fetch proxy servers:', error);
    }
};

const selectServer = (slug: string) => {
    const path = typeof window !== 'undefined' ? window.location.pathname : '';
    const currentSlug = serverSlug.value;
    let newPath: string;
    if (currentSlug && path.startsWith(`/${currentSlug}`)) {
        const rest = path.slice(`/${currentSlug}`.length) || '';
        newPath = `/${slug}${rest === '' || rest === '/' ? '/dashboard' : rest}`;
    } else {
        newPath = `/${slug}/dashboard`;
    }
    router.visit(newPath);
};

const handleSettings = () => {
    // Use the route helper with optional server parameter
    const params = currentServer.value ? { server_slug: currentServer.value } : {};
    router.visit(route('profile.edit', params));
};

const handleLogout = () => {
    router.post(route('logout'));
};

defineProps<Props>();

onMounted(() => {
    fetchProxyServers();
});
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>

    <DropdownMenuSeparator />

    <!-- Proxy Servers Section -->
    <DropdownMenuGroup v-if="proxyServers.length > 0">
        <DropdownMenuLabel class="px-2 py-1.5 text-xs font-semibold text-muted-foreground">
            Proxy Servers
        </DropdownMenuLabel>
        <DropdownMenuItem
            v-for="server in proxyServers"
            :key="server.id"
            :as-child="true"
        >
            <button
                class="flex w-full items-center text-left"
                :class="{ 'bg-accent': currentServer === server.slug }"
                @click="selectServer(server.slug)"
            >
                <Server class="mr-2 h-4 w-4" />
                {{ server.name }}
                <span v-if="currentServer === server.slug" class="ml-auto text-xs text-muted-foreground">
                    (Active)
                </span>
            </button>
        </DropdownMenuItem>
    </DropdownMenuGroup>

    <DropdownMenuSeparator v-if="proxyServers.length > 0" />

    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <button
                class="flex w-full items-center text-left"
                @click="handleSettings"
            >
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </button>
        </DropdownMenuItem>
    </DropdownMenuGroup>

    <DropdownMenuSeparator />

    <!-- Footer nav items (Development, Github, Documentation) -->
    <DropdownMenuGroup v-for="item in footerNavItems" :key="item.title">
        <template v-if="item.children?.length">
            <DropdownMenuItem :as-child="true" @select.prevent="toggleSection(item.title)">
                <button
                    type="button"
                    class="flex w-full cursor-pointer items-center px-2 py-1.5 text-left text-xs font-semibold text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                >
                    <component :is="item.icon" class="mr-2 h-4 w-4 shrink-0" />
                    <span class="flex-1">{{ item.title }}</span>
                    <ChevronDown
                        v-if="expandedSections.has(item.title)"
                        class="h-4 w-4 shrink-0"
                    />
                    <ChevronRight
                        v-else
                        class="h-4 w-4 shrink-0"
                    />
                </button>
            </DropdownMenuItem>
            <template v-if="expandedSections.has(item.title)">
                <DropdownMenuItem
                    v-for="child in item.children"
                    :key="child.title"
                    :as-child="true"
                >
                    <a
                        :href="resolveHref(child.href)"
                        class="flex w-full items-center pl-6 text-left text-sm"
                        :target="isExternalLink(child.href) ? '_blank' : undefined"
                        :rel="isExternalLink(child.href) ? 'noopener noreferrer' : undefined"
                    >
                        <component :is="child.icon" class="mr-2 h-4 w-4" />
                        {{ child.title }}
                    </a>
                </DropdownMenuItem>
            </template>
        </template>
        <DropdownMenuItem v-else :as-child="true">
            <a
                :href="resolveHref(item.href)"
                class="flex w-full items-center text-left"
                :target="isExternalLink(item.href) ? '_blank' : undefined"
                :rel="isExternalLink(item.href) ? 'noopener noreferrer' : undefined"
            >
                <component :is="item.icon" class="mr-2 h-4 w-4" />
                {{ item.title }}
            </a>
        </DropdownMenuItem>
    </DropdownMenuGroup>

    <DropdownMenuSeparator />

    <DropdownMenuItem :as-child="true">
        <button class="flex w-full items-center text-left" @click="handleLogout">
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </button>
    </DropdownMenuItem>
</template>
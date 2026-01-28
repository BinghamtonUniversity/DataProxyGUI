<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import type { User } from '@/types';
import { router } from '@inertiajs/vue3';
import { LogOut, Settings, Server } from 'lucide-vue-next';
import { ref, onMounted, computed } from 'vue';
import { useProxyServer } from '@/composables/useProxyServer';


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
    // Redirect to the server's API page
    router.visit(`/${slug}/apis`);
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
                @click="$inertia.visit(route('profile.edit'))"
            >
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </button>
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
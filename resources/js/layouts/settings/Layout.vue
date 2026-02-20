<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { User, Palette, Server, Users } from 'lucide-vue-next';
import Toaster from '@/components/toaster/Toaster.vue';
import { useProxyServer } from '@/composables/useProxyServer';
import { computed } from 'vue';

const { serverSlug } = useProxyServer();

const page = usePage();
const isSuperAdmin = computed(() => (page.props.auth?.user as { super_admin?: boolean } | undefined)?.super_admin ?? false);

const sidebarNavItems = computed(() => {
    const baseItems: NavItem[] = [
        {
            title: 'Profile',
            href: serverSlug.value ? `/${serverSlug.value}/settings/profile` : '/settings/profile',
            icon: User,
        },
        {
            title: 'Appearance',
            href: serverSlug.value ? `/${serverSlug.value}/settings/appearance` : '/settings/appearance',
            icon: Palette,
        },
        ...(isSuperAdmin.value
            ? [
                  {
                      title: 'Servers',
                      href: serverSlug.value ? `/${serverSlug.value}/settings/servers` : '/settings/servers',
                      icon: Server,
                  },
                  {
                      title: 'Internal Users',
                      href: serverSlug.value ? `/${serverSlug.value}/settings/users` : '/settings/users',
                      icon: Users,
                  },
              ]
            : []),
    ];
    return baseItems;
});

const currentPath = page.props.ziggy?.location ? new URL(page.props.ziggy.location).pathname : '';
</script>

<template>
    <div class="px-4 py-6">
        <Heading title="Settings" description="Manage your profile and account settings" />

        <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-y-0 lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-1 space-x-0">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="item.href"
                        variant="ghost"
                        :class="['w-full justify-start', { 'bg-muted': currentPath === item.href }]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" class="w-4 h-4 mr-2 text-w-600 dark:text-white-400" />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 md:hidden" />

            <div class="flex-1 ">
                <section class="w-full space-y-12">
                    <slot />
                </section>
            </div>
        </div>
        <!-- Global Toaster -->
        <Toaster />
    </div>
</template>

<script setup lang="ts">
import { SidebarGroup, SidebarGroupContent, SidebarMenu, SidebarMenuButton, SidebarMenuItem, SidebarMenuSub, SidebarMenuSubButton, SidebarMenuSubItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { ref } from 'vue';
import { ChevronDown, ChevronRight } from 'lucide-vue-next';

interface Props {
    items: NavItem[];
    class?: string;
}

defineProps<Props>();

const expandedItems = ref<Set<string>>(new Set());

const toggleExpanded = (title: string) => {
    if (expandedItems.value.has(title)) {
        expandedItems.value.delete(title);
    } else {
        expandedItems.value.add(title);
    }
};
</script>

<template>
    <SidebarGroup :class="`group-data-[collapsible=icon]:p-0 ${$props.class || ''}`">
        <SidebarGroupContent>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in items" :key="item.title">
                    <!-- Parent item with children -->
                    <template v-if="item.children && item.children.length > 0">
                        <SidebarMenuButton 
                            class="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100" 
                            @click="toggleExpanded(item.title)"
                        >
                            <component :is="item.icon" />
                            <span class="group-data-[collapsible=icon]:hidden">{{ item.title }}</span>
                            <ChevronDown 
                                v-if="expandedItems.has(item.title)" 
                                class="ml-auto h-4 w-4 group-data-[collapsible=icon]:hidden"
                            />
                            <ChevronRight 
                                v-else 
                                class="ml-auto h-4 w-4 group-data-[collapsible=icon]:hidden"
                            />
                        </SidebarMenuButton>
                        
                        <SidebarMenuSub v-if="expandedItems.has(item.title)">
                            <SidebarMenuSubItem v-for="child in item.children" :key="child.title">
                                <SidebarMenuSubButton as-child>
                                    <a :href="child.href" target="_blank" rel="noopener noreferrer">
                                        <component :is="child.icon" />
                                        <span>{{ child.title }}</span>
                                    </a>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </template>
                    
                    <!-- Regular item without children -->
                    <template v-else>
                        <SidebarMenuButton class="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100" as-child>
                            <a :href="item.href" target="_blank" rel="noopener noreferrer">
                                <component :is="item.icon" />
                                <span class="group-data-[collapsible=icon]:hidden">{{ item.title }}</span>
                            </a>
                        </SidebarMenuButton>
                    </template>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>

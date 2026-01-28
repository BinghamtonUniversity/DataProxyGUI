// resources/js/composables/useProxyServer.js
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useProxyServer() {
    const page = usePage();
    
    const serverSlug = computed(() => page.props.server_slug);
    
    const buildUrl = (path: string) => {
        // Remove leading slash if present
        const cleanPath = path.replace(/^\//, '');
        return `/${serverSlug.value}/${cleanPath}`;
    };
    
    return {
        serverSlug,
        buildUrl
    };
}
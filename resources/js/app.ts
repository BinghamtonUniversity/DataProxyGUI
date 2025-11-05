import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import { install as VueMonacoEditorPlugin, loader } from '@guolao/vue-monaco-editor';
import { FontAwesomeIcon } from './lib/fontawesome';
import Toaster from './components/toaster/Toaster.vue';
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const isDev = import.meta.env.DEV;

// Wrap Monaco initialization in async function
async function initializeMonaco() {
    if (isDev) {
        // Development: Use CDN to avoid CORS/rebuild issues
        loader.config({
            paths: {
                vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/vs'
            }
        });
    } else {
        // Production: Use npm package with local workers
        const monaco = await import('monaco-editor');
        const editorWorker = await import('monaco-editor/esm/vs/editor/editor.worker?worker');
        const jsonWorker = await import('monaco-editor/esm/vs/language/json/json.worker?worker');

        self.MonacoEnvironment = {
            getWorker(_, label) {
                if (label === "json") {
                    return new jsonWorker.default();
                }
                return new editorWorker.default();
            }
        };

        loader.config({ monaco: monaco.default });
    }
}

// Initialize Monaco and then create the app
initializeMonaco().then(() => {
    createInertiaApp({
        title: (title) => (title ? `${title} - ${appName}` : appName),
        resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
        setup({ el, App, props, plugin }) {
            createApp({ render: () => h(App, props) })
                .use(plugin)
                .use(ZiggyVue)
                .use(VueMonacoEditorPlugin)
                .component('font-awesome-icon', FontAwesomeIcon)
                .component('Toaster', Toaster)
                .mount(el);
        },
        progress: {
            color: '#4B5563',
        },
    });

    initializeTheme();
});
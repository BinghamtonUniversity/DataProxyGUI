import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import { install as VueMonacoEditorPlugin, loader } from '@guolao/vue-monaco-editor';
import { FontAwesomeIcon } from './lib/fontawesome';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const isDev = import.meta.env.DEV;

// Configure Monaco based on environment
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
    // const cssWorker = await import('monaco-editor/esm/vs/language/css/css.worker?worker');
    // const htmlWorker = await import('monaco-editor/esm/vs/language/html/html.worker?worker');
    // const tsWorker = await import('monaco-editor/esm/vs/language/typescript/ts.worker?worker');

    self.MonacoEnvironment = {
        getWorker(_, label) {
            if (label === "json") {
                return new jsonWorker.default();
            }
            // if (label === "css" || label === "scss" || label === "less") {
            //     return new cssWorker.default();
            // }
            // if (label === "html" || label === "handlebars" || label === "razor") {
            //     return new htmlWorker.default();
            // }
            // if (label === "typescript" || label === "javascript") {
            //     return new tsWorker.default();
            // }
            return new editorWorker.default();
        }
    };

    loader.config({ monaco: monaco.default });
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(VueMonacoEditorPlugin)
            .component('font-awesome-icon', FontAwesomeIcon)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
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
import { registerPhpSnippetLanguage } from './lib/monacoPhpSnippet';
const appName = import.meta.env.VITE_APP_NAME || 'BITS Proxy';
const isDev = import.meta.env.DEV;



// Wrap Monaco initialization in async function
// async function initializeMonaco() {
//     if (isDev) {
//         // Development: Use CDN to avoid CORS/rebuild issues
//         loader.config({
//             paths: {
//                 vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/vs'
//             }
//         });
//     } else {
//         // Production: Use npm package with local workers
//         const monaco = await import('monaco-editor');
//         const editorWorker = await import('monaco-editor/esm/vs/editor/editor.worker?worker');
//         const jsonWorker = await import('monaco-editor/esm/vs/language/json/json.worker?worker');

//         self.MonacoEnvironment = {
//             getWorker(_, label) {
//                 if (label === "json") {
//                     return new jsonWorker.default();
//                 }
//                 return new editorWorker.default();
//             }
//         };

//         loader.config({ monaco: (monaco as any).default ?? monaco });
//     }
// }

async function initializeMonaco() {
    const monaco = await import('monaco-editor');

    if (import.meta.env.DEV) {
        // Laravel (:8000) and the Vite dev server (:5173) are different origins, and
        // browsers block cross-origin Worker scripts. Start each worker from a
        // same-origin blob that imports the real worker module from Vite.
        const { default: editorWorkerUrl } = await import('monaco-editor/esm/vs/editor/editor.worker?worker&url');
        const { default: jsonWorkerUrl } = await import('monaco-editor/esm/vs/language/json/json.worker?worker&url');

        const devWorker = (url: string) => {
            const absolute = new URL(url, import.meta.url).href;
            const blob = new Blob([`import ${JSON.stringify(absolute)};`], { type: 'text/javascript' });
            return new Worker(URL.createObjectURL(blob), { type: 'module' });
        };

        self.MonacoEnvironment = {
            getWorker: (_, label) => devWorker(label === 'json' ? jsonWorkerUrl : editorWorkerUrl),
        };
    } else {
        const { default: EditorWorker } = await import('monaco-editor/esm/vs/editor/editor.worker?worker');
        const { default: JsonWorker } = await import('monaco-editor/esm/vs/language/json/json.worker?worker');

        self.MonacoEnvironment = {
            getWorker: (_, label) => (label === 'json' ? new JsonWorker() : new EditorWorker()),
        };
    }

    // loader.config({ monaco: (monaco as any).default ?? monaco });
    const monacoInstance = (monaco as any).default ?? monaco;
    loader.config({ monaco: monacoInstance });
    await registerPhpSnippetLanguage(monacoInstance);
    (window as any).monaco = monacoInstance;
}

// Initialize Monaco and then create the app
initializeMonaco().then(() => {
    createInertiaApp({
        title: (title) => (title ? `${title} - ${appName}` : appName),
        resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
        setup({ el, App, props, plugin }) {
            const app = createApp({ render: () => h(App, props) })
                .use(plugin)
                .use(ZiggyVue)
                .use(VueMonacoEditorPlugin)
                .component('font-awesome-icon', FontAwesomeIcon)
                // .component('Toaster', Toaster)
                .mount(el);
            
            // Add role to the app container for accessibility
            if (el && !el.getAttribute('role')) {
                el.setAttribute('role', 'application');
            }
        },
        progress: {
            color: '#4B5563',
        },
    });

    initializeTheme();
});
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js'; // Assuming you have the type definitions for ziggy-js or it works as is
// import { initializeTheme } from './composables/useAppearance'; // Assuming this is correctly typed

// Import vue-toastification
import Toast, { POSITION, type PluginOptions } from 'vue-toastification';
// Import the CSS or use your own custom styling
import 'vue-toastification/dist/index.css';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T = DefineComponent>(pattern: string) => Record<string, () => Promise<T>>; // Made T default to DefineComponent
    }
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Define options for vue-toastification
const toastOptions: PluginOptions = {
    position: POSITION.TOP_RIGHT,
    timeout: 5000, // Default timeout in ms
    closeOnClick: true,
    pauseOnFocusLoss: true,
    pauseOnHover: true,
    draggable: true,
    draggablePercent: 0.6,
    showCloseButtonOnHover: false,
    hideProgressBar: false,
    closeButton: "button", // Or true (default icon), "button" (styled button), "fontawesome" (if using FA)
    icon: true, // Show default icons, or provide your own
    rtl: false,
    transition: "Vue-Toastification__fade", // Default transition
    maxToasts: 20,
    newestOnTop: true
    // You can add more default options here:
    // filterBeforeCreate: (toast, toasts) => {
    //   if (toasts.filter(t => t.type === toast.type).length !== 0) {
    //     // Returning false discards the toast
    //     return false;
    //   }
    //   // You can modify the toast here
    //   return toast;
    // }
};

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue) // Assuming Ziggy is globally available or passed correctly
            .use(Toast, toastOptions) // <<< --- ADD THIS LINE TO USE THE TOAST PLUGIN
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
// initializeTheme(); // Uncomment if you are using this
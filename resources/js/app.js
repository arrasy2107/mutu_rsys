import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import VueApexCharts from 'vue3-apexcharts';
import Swal from 'sweetalert2';
import notify, { toast, alert, confirm, dangerConfirm, promptInput } from './Utils/notify';
import NotionIcon from './Components/NotionIcon.vue';

window.Swal = Swal;
window.notify = notify;
window.Toast = {
    fire: ({ icon = 'info', title = '', text = '' }) => {
        if (icon === 'success') toast.success(title, text);
        else if (icon === 'error') toast.error(title, text);
        else if (icon === 'warning') toast.warning(title, text);
        else toast.info(title, text);
    }
};

const appName = import.meta.env.VITE_APP_NAME || 'MUTU RSYS';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(VueApexCharts)
            .component('NotionIcon', NotionIcon)
            .mount(el);
    },
    progress: {
        color: '#4F46E5',
    },
});

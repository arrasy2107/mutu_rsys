import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

const normalizeBasePath = (basePath) => {
    const path = basePath.replace(/^\/+|\/+$/g, '');

    return path ? `/${path}/` : '/';
};

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), 'VITE_');

    return {
        base: normalizeBasePath(env.VITE_BASE_PATH || '/'),
        plugins: [
            laravel({
                input: 'resources/js/app.js',
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        server: {
            host: '0.0.0.0',
            hmr: {
                host: '192.168.0.75',
            },
        },
    };
});

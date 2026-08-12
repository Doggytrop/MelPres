import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/superadmin.css',
                'resources/js/app.js',
                'resources/js/superadmin.js',
            ],
            refresh: true,
        }),
    ],
});

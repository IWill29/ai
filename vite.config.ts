import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

const wayfinderCommand =
    process.env.WAYFINDER_COMMAND ??
    (process.platform === 'win32'
        ? 'docker compose exec -T app php artisan wayfinder:generate'
        : 'php artisan wayfinder:generate');

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/landing.css',
                'resources/js/app.tsx',
                'resources/js/marketing.tsx',
            ],
            refresh: true,
        }),
        inertia(),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
            command: wayfinderCommand,
        }),
    ],
});

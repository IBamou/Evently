import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const vitePort = Number(process.env.VITE_PORT ?? 5173);
const viteHmrHost = process.env.VITE_HMR_HOST ?? 'localhost';

export default defineConfig({
    server: {
        strictPort: true,
        cors: true,
        hmr: {
            host: viteHmrHost,
            clientPort: vitePort,
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // Booking helpers are loaded only by event detail and checkout.
                'resources/js/booking.js',
                // QR helpers are loaded only by views that render QR codes.
                'resources/js/qr.js',
            ],
            refresh: true,
        }),
    ],
});

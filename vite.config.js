import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        // Bind IPv4 loopback explicitly: the PHP dev server listens on
        // 127.0.0.1:8000, and the default Vite 'localhost' binding resolves to
        // [::1] (IPv6-only) on this machine — 127.0.0.1:5173 then refuses
        // connections and the hot file records an origin some browsers cannot
        // reach. Pinning 127.0.0.1 keeps `public/hot` reachable from the same
        // host that serves the app.
        host: '127.0.0.1',
        port: 5173,
        hmr: {
            host: '127.0.0.1',
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

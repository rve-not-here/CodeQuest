import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

const root = dirname(fileURLToPath(import.meta.url));
const projectRoot = resolve(root, '../../..');

/**
 * Mirrors Blade @include for the static prototype:
 * <!-- @include header active="learn" --> inlines partials/header.html
 * and marks the matching nav link with aria-current="page".
 */
function includePartials() {
    return {
        name: 'codequest-prototype-partials',
        transformIndexHtml: {
            order: 'pre',
            handler(html) {
                return html.replace(/<!--\s*@include\s+([\w-]+)(?:\s+active="([\w-]+)")?\s*-->/g, (_, name, active) => {
                    let partial = readFileSync(resolve(root, 'partials', `${name}.html`), 'utf8');

                    if (active) {
                        partial = partial.replaceAll(`data-nav="${active}"`, `data-nav="${active}" aria-current="page"`);
                    }

                    return partial;
                });
            },
        },
        handleHotUpdate({ file, server }) {
            if (file.includes('/partials/')) {
                server.ws.send({ type: 'full-reload' });
            }
        },
    };
}

export default defineConfig({
    root,
    plugins: [includePartials(), tailwindcss()],
    server: {
        host: '0.0.0.0',
        port: 4173,
        strictPort: true,
        fs: { allow: [projectRoot] },
    },
    build: {
        outDir: resolve(root, 'dist'),
        emptyOutDir: true,
        rollupOptions: {
            input: {
                dashboard: resolve(root, 'index.html'),
                learn: resolve(root, 'learn.html'),
                missions: resolve(root, 'missions.html'),
                challenge: resolve(root, 'challenge.html'),
                system: resolve(root, 'design-system.html'),
            },
        },
    },
});

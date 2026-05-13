import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import fs from 'node:fs';
import path from 'node:path';

function normalizeLaravelManifestKeys() {
    return {
        name: 'normalize-laravel-manifest-keys',
        closeBundle() {
            const manifestPath = path.resolve('public/build/manifest.json');

            if (!fs.existsSync(manifestPath)) {
                return;
            }

            const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
            const normalized = { ...manifest };

            for (const [key, value] of Object.entries(manifest)) {
                const normalizedKey = normalizeResourcePath(key);
                const normalizedSrc = normalizeResourcePath(value.src);

                if (normalizedKey) {
                    normalized[normalizedKey] = {
                        ...value,
                        src: normalizedSrc ?? normalizedKey,
                    };
                }

                if (normalizedSrc) {
                    normalized[normalizedSrc] = {
                        ...value,
                        src: normalizedSrc,
                    };
                }
            }

            fs.writeFileSync(manifestPath, `${JSON.stringify(normalized, null, 2)}\n`);
        },
    };
}

function normalizeResourcePath(value) {
    if (typeof value !== 'string') {
        return null;
    }

    const normalized = value.replaceAll('\\', '/');
    const index = normalized.indexOf('resources/');

    return index === -1 ? null : normalized.slice(index);
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        normalizeLaravelManifestKeys(),
    ],
});

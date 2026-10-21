import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    optimizeDeps: {
        // maplibre-gl instantiates its own internal Web Worker. Vite's esbuild
        // dependency pre-bundling breaks the worker's URL resolution in dev
        // mode ("Worker failed to load"), which silently kills all GeoJSON
        // source processing (clustering). Exclude it so the browser loads
        // maplibre-gl's own already-correct ESM build directly.
        exclude: ['maplibre-gl'],
    },
});

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
    build: {
        // Ensure unique filenames for every build
        rollupOptions: {
            output: {
                // Add timestamp to chunk names for cache busting
                chunkFileNames: (chunkInfo) => {
                    const facadeModuleId = chunkInfo.facadeModuleId ? chunkInfo.facadeModuleId.split('/').pop() : 'chunk';
                    return `js/[name]-[hash].js`;
                },
                // Add timestamp to asset names for cache busting
                assetFileNames: (assetInfo) => {
                    const info = assetInfo.name.split('.');
                    const ext = info[info.length - 1];
                    if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(ext)) {
                        return `images/[name]-[hash].[ext]`;
                    }
                    if (/css/i.test(ext)) {
                        return `css/[name]-[hash].[ext]`;
                    }
                    return `assets/[name]-[hash].[ext]`;
                },
            },
        },
        // Generate manifest with hashed filenames
        manifest: true,
        // Ensure assets are properly hashed
        assetsInlineLimit: 0,
    },
});

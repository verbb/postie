import { resolve } from 'node:path';

import { defineConfig } from 'vite';

const root = resolve(import.meta.dirname, 'src/web/assets/cp');
const modes = new Set(['css', 'js', 'plugin-kit']);

export default defineConfig(({ mode }) => {
    if (!modes.has(mode)) {
        throw new Error(`Unknown asset build mode: ${mode}`);
    }

    const isCssBuild = mode === 'css';

    // Plugin Kit's web components need native class fields, so its bundle keeps Vite's modern default target and
    // loads only on the pages that show them.
    const isPluginKitBuild = mode === 'plugin-kit';

    const input = {
        css: 'src/postie.css',
        js: 'src/postie.js',
        'plugin-kit': 'src/plugin-kit.js',
    }[mode];

    const output = isCssBuild ? {
        assetFileNames: '[name][extname]',
    } : {
        // Plugin Kit's styles come out as one file, so the asset bundle can load them in the page head.
        assetFileNames: isPluginKitBuild ? 'plugin-kit[extname]' : '[name][extname]',
        codeSplitting: false,
        entryFileNames: '[name].js',
        format: 'iife',
    };

    return {
        root,
        publicDir: resolve(root, 'public'),
        input: resolve(root, input),
        resolve: {
            // One copy of Lit and one custom-element registry, whether Plugin Kit comes from npm or a local checkout.
            dedupe: ['lit', 'lit-element', 'lit-html', '@lit/reactive-element'],
        },
        build: {
            outDir: resolve(root, 'dist'),
            emptyOutDir: isCssBuild,
            assetsDir: '',
            cssCodeSplit: !isPluginKitBuild,
            cssMinify: 'esbuild',
            cssTarget: ['chrome61', 'safari10'],
            minify: 'oxc',
            sourcemap: !isCssBuild && !isPluginKitBuild,
            target: isPluginKitBuild ? undefined : 'es2015',
            rolldownOptions: {
                output,
            },
        },
    };
});

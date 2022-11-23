import { defineConfig } from 'astro/config';

// https://astro.build/config
export default defineConfig({
    // site: 'https://hendrikprinsza.github.io',
    base: '/laravel-kata',
    srcDir: './client/src',
    publicDir: './client/public',
    outDir: './client/dist'
});

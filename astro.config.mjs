import { defineConfig } from 'astro/config';

// https://astro.build/config
export default defineConfig({
    srcDir: './client/src',
    publicDir: './client/public',
    outDir: './client/dist'
});

import { defineConfig } from 'astro/config';
import vue from "@astrojs/vue";
import svelte from "@astrojs/svelte";

import * as dotenv from 'dotenv'
dotenv.config()

let site = null;
if (process.env.CI_MODE === 'local') {
  site = 'http://localhost/laravel-kata';
}

// https://astro.build/config
export default defineConfig({
  site: site,
  base: '/laravel-kata',
  srcDir: './client/src',
  publicDir: './client/public',
  outDir: './client/dist',
  integrations: [
    vue(),
    svelte()
  ]
});

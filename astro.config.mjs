import { defineConfig } from 'astro/config';
import vue from "@astrojs/vue";
import svelte from "@astrojs/svelte";

import * as dotenv from 'dotenv'
dotenv.config()

let site = null;

// https://astro.build/config
let config = {
    base: '/laravel-kata',
    srcDir: './client/src',
    publicDir: './client/public',
    outDir: './client/dist',
    integrations: [
      vue(),
      svelte()
    ]
};

if (process.env.CI_MODE === 'local') {
  config.site = 'http://localhost/laravel-kata';
}

export default defineConfig(config);

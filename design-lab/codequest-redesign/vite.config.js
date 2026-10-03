import { defineConfig } from 'vite';
import { fileURLToPath } from 'node:url';
export default defineConfig({root:fileURLToPath(new URL('.',import.meta.url)),server:{host:'127.0.0.1',port:4175,strictPort:true},build:{outDir:'/tmp/codequest-redesign-v3-build',emptyOutDir:true}});

import fs from 'fs';
import { defineConfig } from 'vite';
import laravel, { refreshPaths } from 'laravel-vite-plugin';

const laragonCert = 'D:/WebServer/laragon/etc/ssl/laragon.crt';
const laragonKey = 'D:/WebServer/laragon/etc/ssl/laragon.key';
const hasLaragonSsl = fs.existsSync(laragonCert) && fs.existsSync(laragonKey);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: [
                ...refreshPaths,
                'app/Livewire/**',
            ],
        }),
    ],
    server: {
        host: hasLaragonSsl ? 'wa-gateway.lol' : '127.0.0.1',
        port: 5173,
        cors: true,
        ...(hasLaragonSsl
            ? {
                  https: {
                      cert: fs.readFileSync(laragonCert),
                      key: fs.readFileSync(laragonKey),
                  },
                  hmr: {
                      host: 'wa-gateway.lol',
                  },
              }
            : {
                  hmr: {
                      host: '127.0.0.1',
                  },
              }),
    },
});

# WA-Gateway (Laravel + Livewire + Alpine + Tailwind + Baileys v7 + PM2)

Aplikasi WhatsApp Gateway terpadu yang menggabungkan dashboard/API **Laravel 10 + Livewire 3 + Alpine.js + Tailwind CSS** dan engine **Baileys v7 (Node.js)** dalam **1 folder project, 1 file `.env`, dan 1 database (MySQL Laravel)**.

## Struktur Folder Utama

```text
wa-gateway/
├── app/
│   ├── Http/Controllers/         # Auth, API masuk, Webhook Baileys v7
│   ├── Livewire/                 # Dashboard, Message, Device, Token, Group, User
│   └── Services/                 # BaileysService, WhatsAppService, TelegramService
├── baileys/                      # Engine WhatsApp Node.js (@whiskeysockets/baileys v7)
│   ├── index.js                  # Membaca ../.env (tanpa DB terpisah)
│   ├── scheduler.js              # Scheduler Windows-safe (tanpa popup cmd.exe)
│   └── package.json
├── resources/views/              # Layout responsif & komponen Blade + Tailwind + Alpine
├── storage/
│   ├── app/baileys-sessions/     # Penyimpanan kredensial multi-device WhatsApp
│   └── logs/                     # Log Laravel, webhook Baileys, dan PM2
├── ecosystem.config.cjs          # Konfigurasi PM2 (wa-baileys & wa-scheduler)
├── .env                          # Satu konfigurasi terpusat untuk Laravel & Baileys
└── package.json                  # Script shortcut PM2 & Vite
```

## Konfigurasi `.env` Terpusat

Pastikan variabel berikut sesuai dengan environment Anda:

```dotenv
APP_URL=http://wa-gateway.lol
WHATSAPP_API="baileys-v7"

BAILEYS_V7_HOST=127.0.0.1
BAILEYS_V7_PORT=3000
BAILEYS_V7_API_URL="http://127.0.0.1:3000"
BAILEYS_V7_API_KEY=isi_token_rahasia_anda
BAILEYS_V7_WEBHOOK_URL="http://wa-gateway.lol/webhook/baileys-v7"
BAILEYS_V7_WEBHOOK_INSECURE=true
BAILEYS_V7_SESSIONS_DIR="../storage/app/baileys-sessions"
```

> Untuk production dengan SSL valid, ubah `BAILEYS_V7_WEBHOOK_INSECURE=false` dan arahkan `APP_URL` serta `BAILEYS_V7_WEBHOOK_URL` ke domain HTTPS production.

## Menjalankan di Development (Windows 11 + Laragon + PM2)

1. **Web Server (Laragon):**
   - Pastikan Laragon sudah berjalan dan reload Apache agar virtual host `http://wa-gateway.lol` mengarah ke `wa-gateway/public`.
   - Alternatif tanpa Laragon: jalankan `php artisan serve --port=8000` (dan ubah `APP_URL` & `BAILEYS_V7_WEBHOOK_URL` ke `http://127.0.0.1:8000`).

2. **Install Dependensi (jika clone baru):**
   ```powershell
   composer install
   npm run wa:install
   npm install -g pm2
   ```

3. **Jalankan Baileys & Scheduler via PM2:**
   ```powershell
   npm run wa:start
   ```
   Perintah ini menjalankan 2 proses di background (tanpa popup terminal):
   - `wa-baileys` — Engine Baileys v7 di `http://127.0.0.1:3000`
   - `wa-scheduler` — Wrapper Node.js (`baileys/scheduler.js`) yang memanggil `php artisan message:send-to-api` tiap menit (dan `message:delete` jam 02:25) dengan `windowsHide: true, shell: false` sehingga **tidak memunculkan popup `cmd.exe` di Windows 11** (berbeda dengan `php artisan schedule:work` yang memicu `cmd.exe` tiap menit).

4. **Perintah PM2 Harian:**
   ```powershell
   npm run wa:status    # Cek status proses
   npm run wa:logs      # Lihat log Baileys real-time
   npm run wa:restart   # Restart setelah ubah .env atau baileys/index.js
   npm run wa:stop      # Hentikan proses
   npm run wa:delete    # Hapus dari daftar PM2
   ```

5. **Cek Health Endpoint:**
   Buka `http://127.0.0.1:3000/health` untuk memastikan service Baileys aktif dan mengarah ke webhook yang benar.

## Catatan Auto-Start di Windows 11 vs Linux Production

- **Windows 11 (Development):** Cukup jalankan `npm run wa:start` saat mulai ngoding. Jika ingin otomatis jalan saat login Windows, simpan daftar proses dengan `pm2 save` lalu buat **Task Scheduler** (Trigger: *At log on*, Action: `pm2 resurrect`).
- **Linux / VPS / Plesk (Production):**
  - Jalankan hanya `wa-baileys` lewat PM2 (`pm2 start ecosystem.config.cjs --only wa-baileys --env production && pm2 save && pm2 startup`).
  - Jalankan scheduler Laravel lewat **Cron Job** sistem (`* * * * * php /path/to/wa-gateway/artisan schedule:run >> /dev/null 2>&1`).

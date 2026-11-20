# WA-Gateway (Laravel + Livewire + Alpine + Tailwind + Baileys v7 + PM2)

Aplikasi WhatsApp Gateway terpadu yang menggabungkan dashboard/API **Laravel 10 + Livewire 3 + Alpine.js + Tailwind CSS** dan engine **Baileys v7 (Node.js)**.

## Struktur Folder Utama

```text
wa-gateway/
├── app/
│   ├── Http/Controllers/         # Auth, API masuk, Webhook Baileys v7
│   ├── Livewire/                 # Dashboard, Message, Device, Token, Group, User, Troubleshoot
│   └── Services/                 # BaileysService, Pm2Service, TroubleshootService, TelegramService
├── baileys/                      # Engine WhatsApp Node.js (@whiskeysockets/baileys v7)
│   ├── index.js                  # Engine WebSocket Baileys v7 (Port 3000)
│   ├── scheduler.js              # Scheduler Windows-safe (background loop)
│   └── package.json
├── resources/views/              # Layout responsif & komponen Blade + Tailwind + Alpine
├── storage/
│   ├── app/baileys-sessions/     # Penyimpanan kredensial multi-device WhatsApp
│   └── logs/                     # Log Laravel, webhook Baileys, dan PM2
├── ecosystem.config.cjs          # Konfigurasi PM2 (wa-baileys & wa-scheduler)
├── .env                          # Satu konfigurasi terpusat untuk Laravel & Baileys
└── package.json                  # Script shortcut PM2 & Vite
```

---

## Persyaratan Server (Server Requirements)

Sebelum menginstal di server production (Linux / Ubuntu / Debian / Plesk / cPanel / VPS):

| Komponen | Versi Minimal / Rekomendasi | Keterangan |
| :--- | :--- | :--- |
| **Sistem Operasi** | Linux (Ubuntu 20.04/22.04/24.04, Debian, AlmaLinux) atau Windows Server | Disarankan Linux untuk server production |
| **PHP** | **PHP >= 8.1** (Direkomendasikan **PHP 8.2** atau **8.3**) | Wajib sesuai Laravel 10 |
| **Ekstensi PHP** | `pdo_mysql`, `curl`, `mbstring`, `openssl`, `fileinfo`, `json`, `tokenizer`, `xml`, `bcmath` | Ekstensi inti Laravel & WhatsApp API |
| **Database** | **MySQL >= 5.7 / 8.0** atau **MariaDB >= 10.3** | Karakter charset utf8mb4 |
| **Web Server** | **Nginx** atau **Apache** | Document Root **wajib** mengarah ke folder `/public` |
| **Node.js** | **Node.js >= 18.x LTS** (Direkomendasikan **Node.js v20.x**) | Runtime engine Baileys v7 |
| **PM2** | Versi terbaru (`npm install -g pm2`) | Daemon process manager untuk menjaga engine tetap online |
| **Composer** | Composer 2.x | Manajer paket PHP |

---

## Panduan Instalasi Server Production

Ikuti panduan berikut untuk menjalankan Whatsapp Gateway:

### 1. Upload / Clone Kode Aplikasi ke Server
Masuk ke terminal/SSH server dan clone atau letakkan source code proyek di direktori web server (contoh: `/var/www/wa-gateway` atau direktori vhost domain Anda):
```bash
cd /var/www
git clone https://github.com/amuadib/wa-gateway
cd wa-gateway
```

### 2. Atur Document Root Web Server ke `/public`
> [!IMPORTANT]
> **Document Root web server wajib diarahkan ke subfolder `/public`, BUKAN ke root proyek!**
- **Nginx:** `root /var/www/wa-gateway/public;`
- **Apache / Plesk:** Atur Document Root domain Anda ke `wa-gateway/public` (atau `httpdocs/public`).

### 3. Install Dependensi PHP & Node.js
Jalankan instalasi paket Composer untuk PHP dan npm untuk engine Baileys:
```bash
# 1. Install dependensi PHP
composer install --no-dev --optimize-autoloader

# 2. Install dependensi Baileys v7
npm run wa:install

# 3. Install PM2 secara global jika belum ada di server
npm install -g pm2
```

### 4. Konfigurasi Environment (`.env`)
Salin file konfigurasi contoh dan buat application key:
```bash
cp .env.example .env
php artisan key:generate
```
Buka file `.env` (misal via `nano .env`) dan sesuaikan variabel utama:
```dotenv
APP_NAME="WA Gateway"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://wa.domainanda.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=user_database
DB_PASSWORD=password_database

BAILEYS_V7_HOST=127.0.0.1
BAILEYS_V7_PORT=3000
BAILEYS_V7_API_URL="http://127.0.0.1:3000"
BAILEYS_V7_API_KEY="buat_token_rahasia_acak_disini"
BAILEYS_V7_WEBHOOK_URL="https://wa.domainanda.com/webhook/baileys-v7"
BAILEYS_V7_WEBHOOK_INSECURE=false
BAILEYS_V7_SESSIONS_DIR="../storage/app/baileys-sessions"
```

### 5. Jalankan Migrasi Database & Seeder Admin Default
Jalankan migrasi tabel database sekaligus buat akun Administrator default:
```bash
php artisan migrate --seed --force
```

> [!NOTE]
> **Kredensial Akun Administrator Default:**
> - **URL Login:** `https://wa.domainanda.com/login`
> - **Email:** `admin@admin.com`
> - **Password:** `password`
> - **Role:** `admin` (Hak akses penuh: Troubleshoot, Kelola Pengguna, Token API, Device, Kill Engine)
>
> *(Sangat disarankan segera mengganti password Anda di menu **Pengguna** setelah login pertama kali).*

### 6. Atur Izin Direktori (Permissions)
Pastikan web server dapat membaca dan menulis ke folder storage dan cache:
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```
*(Ganti `www-data:www-data` dengan user domain Anda jika menggunakan panel Plesk atau cPanel, misal: `chown -R userdomain:psacln storage bootstrap/cache`).*

### 7. Jalankan Baileys v7 Engine via PM2
Jalankan service Node.js Baileys di background agar selalu online dan otomatis restart jika crash atau server reboot:
```bash
# Start engine Baileys di port 3000
pm2 start ecosystem.config.cjs --only wa-baileys --env production

# Simpan daftar proses PM2
pm2 save

# Aktifkan auto-start saat server reboot
pm2 startup
```
*Cek status engine dengan perintah `pm2 status`. Service `wa-baileys` harus berstatus `online`.*

### 8. Konfigurasi Cron Job / Scheduler
Antrian pengiriman pesan otomatis (`message:send-to-api`) memerlukan cron job sistem untuk berjalan setiap menit:

- **Di Linux Crontab (`crontab -e`):**
  Tambahkan baris berikut di paling bawah:
  ```bash
  * * * * * cd /var/www/wa-gateway && php artisan schedule:run >> /dev/null 2>&1
  ```
- **Di Panel Plesk (Websites & Domains -> Scheduled Tasks):**
  1. Klik **Add Task**.
  2. Pilih jenis task: **Run a command**.
  3. Perintah: `cd /var/www/vhosts/domain.com/httpdocs && /opt/plesk/php/8.2/bin/php artisan schedule:run >> /dev/null 2>&1`
  4. Jadwal (*Run*): **Every minute** (`* * * * *`).
  5. Klik **OK**.

### 9. Verifikasi via Menu Troubleshoot
1. Buka browser dan login ke `https://wa.domainanda.com/login` menggunakan akun default:
   - Email: `admin@admin.com`
   - Password: `password`
2. Buka menu **Troubleshoot** di sidebar (`https://wa.domainanda.com/troubleshoot`).
3. Pastikan **19 checklist requirement sistem** (Baileys HTTP port 3000, Database, PHP extensions, izin direktori, dan Heartbeat cron) berstatus hijau / **Lolos**.
4. Buka menu **Device**, klik **Tambah Device**, lalu scan QR WhatsApp untuk menautkan nomor Anda.

---

## Menjalankan di Development Lokal (Windows + Laragon / Apache + PM2)

1. **Web Server:**
   - Pastikan virtual host `http://wa-gateway.local` sudah mengarah ke folder `wa-gateway/public`.
   - Alternatif tanpa Apache: jalankan `php artisan serve --port=8000`.

2. **Install Dependensi & Setup Database:**
   ```powershell
   composer install
   npm run wa:install
   npm install -g pm2
   cp .env.example .env
   php artisan key:generate
   php artisan migrate --seed
   ```

3. **Jalankan Baileys & Scheduler via PM2:**
   ```powershell
   npm run wa:start
   ```
   Perintah ini menjalankan 2 proses di background (tanpa memunculkan popup `cmd.exe` di Windows 11):
   - `wa-baileys` — Engine Baileys v7 di `http://127.0.0.1:3000`
   - `wa-scheduler` — Wrapper Node.js (`baileys/scheduler.js`) yang memanggil `php artisan message:send-to-api` tiap menit.

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

---

## Peringatan Penggunaan

> [!WARNING]
> **Otomasi WhatsApp dengan Baileys bukan merupakan API resmi dari WhatsApp / Meta.**

Harap perhatikan beberapa hal berikut sebelum menggunakan aplikasi ini:

- **Risiko Pemblokiran Akun:** Library `@whiskeysockets/baileys` menggunakan protokol WhatsApp Web secara tidak resmi (*unofficial*). Aktivitas seperti pengiriman pesan massal (*blast*/spam), pengiriman terlalu cepat, atau laporan dari penerima berisiko menyebabkan nomor dibatasi sementara hingga **diblokir permanen** oleh WhatsApp.
- **Gunakan Secara Bijak:** Disarankan hanya untuk notifikasi transaksional atau informasi yang memang diharapkan oleh penerima, dengan jeda pengiriman yang wajar, serta menggunakan nomor khusus (bukan nomor pribadi/utama yang krusial).
- **Perubahan Protokol:** Sewaktu-waktu WhatsApp dapat memperbarui protokolnya sehingga koneksi Baileys terganggu sampai tersedia pembaruan library.
- **Alternatif Resmi:** Untuk kebutuhan bisnis skala besar atau operasional kritis yang membutuhkan SLA resmi, gunakan **WhatsApp Business Cloud API (Meta)**.
- **Tanggung Jawab:** Segala risiko penggunaan—termasuk pemblokiran nomor atau gangguan layanan—sepenuhnya menjadi tanggung jawab pengguna. Proyek ini tidak berafiliasi dengan, disponsori, atau didukung oleh WhatsApp LLC maupun Meta Platforms, Inc.

## Lisensi

WA-Gateway adalah perangkat lunak open-source yang dirilis di bawah [MIT License](LICENSE) dengan harapan bisa bermanfaat bagi banyak orang.

## Sponsor

Buy me a coffee (or tea, or snacks) :) [Ko-Fi](https://ko-fi.com/amuadib)

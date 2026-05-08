# 💰 Finance AI WhatsApp Assistant

> Aplikasi keuangan pribadi cerdas berbasis Laravel 12 yang terintegrasi dengan WhatsApp dan AI Grok untuk pencatatan transaksi otomatis.

![Laravel](https://img.shields.io/badge/Laravel-12-red) ![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3-blue) ![PHP](https://img.shields.io/badge/PHP-8.2-purple) ![MySQL](https://img.shields.io/badge/MySQL-8-orange)

---

## ✨ Fitur Utama

- 🤖 **AI Grok Integration** — Parse transaksi dari teks natural, foto struk, dan voice note
- 📱 **WhatsApp Gateway** — Input transaksi langsung dari chat WhatsApp
- 📸 **Receipt Scanner** — Scan struk/nota otomatis via foto WhatsApp
- 🎤 **Voice Note** — Catat transaksi via rekaman suara
- 💳 **Multi Wallet** — BCA, BRI, Mandiri, Gopay, Dana, OVO, dll.
- 📊 **Dashboard Analitik** — Chart interaktif, AI insight, laporan bulanan
- 📄 **Export PDF & Excel** — Laporan keuangan lengkap
- ⚙️ **Admin Panel** — Konfigurasi API key tanpa edit kode
- 🔒 **Security** — API key terenkripsi, webhook signature, rate limiting

---

## 🚀 Instalasi Cepat

### Prasyarat
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+

### Langkah Instalasi

```bash
# 1. Clone atau extract project
cd finance-ai-whatsapp-assistant

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Setup environment
cp .env.example .env
php artisan key:generate

# 5. Buat database MySQL
mysql -u root -e "CREATE DATABASE finance_ai;"

# 6. Update .env dengan kredensial database Anda
# DB_DATABASE=finance_ai
# DB_USERNAME=root
# DB_PASSWORD=yourpassword

# 7. Jalankan migrasi & seeder
php artisan migrate --seed

# 8. Build assets
npm run build

# 9. Buat symlink storage
php artisan storage:link

# 10. Jalankan aplikasi
php artisan serve
```

Akses: http://localhost:8000

### Demo Login
| Role  | Email                  | Password  |
|-------|------------------------|-----------|
| Admin | admin@financeai.app    | password  |
| User  | demo@financeai.app     | password  |

---

## ⚙️ Setup Grok AI (dari Admin Panel)

**TIDAK PERLU edit `.env`!** Semua konfigurasi dilakukan dari Admin Panel.

1. Login sebagai admin
2. Buka **Admin → API Credentials**
3. Klik **+ Tambah Credential**
4. Isi:
   - **Nama**: Grok AI Production
   - **Provider**: `grok`
   - **API Key**: Dapatkan dari [console.x.ai](https://console.x.ai)
   - **Endpoint URL**: `https://api.x.ai/v1`
   - **Model**: `grok-2-vision-1212`
5. Centang **Aktif** dan **Default**
6. Simpan, lalu klik tombol **Test Koneksi**

---

## 📱 Setup WhatsApp Gateway

1. Daftar ke salah satu provider:
   - [Fonnte](https://fonnte.com) — Recommended
   - [Wablas](https://wablas.com)
   - [Whacenter](https://whacenter.com)

2. Buka **Admin → WhatsApp Gateway**

3. Klik **+ Tambah Gateway** dan isi:
   - Provider, Base URL, API Key, Nomor Pengirim

4. **Webhook URL** (daftarkan ke provider):
   ```
   https://yourdomain.com/webhook/whatsapp
   ```

5. Test koneksi dari admin panel

---

## 💬 Cara Penggunaan WhatsApp

### Input Transaksi via Teks
```
beli kopi 25rb pakai gopay
gaji masuk 5 juta ke bca
transfer 100rb dari bri ke dana
tarik tunai 500rb dari bca
isi bensin 50rb cash
bayar listrik 350 ribu pake bca
```

### Kirim Foto Struk
Kirim foto struk/nota ke nomor WhatsApp terdaftar.
AI akan otomatis membaca dan mencatat transaksi.

### Kirim Voice Note
Rekam suara: *"Catat ya, tadi beli bensin lima puluh ribu pakai cash"*
AI akan mentranskrip dan mencatat otomatis.

### Commands WhatsApp
```
/saldo          - Lihat semua saldo wallet
/laporan        - Laporan bulan ini
/bulanini       - Sama dengan /laporan
/topkategori    - Top kategori pengeluaran
/wallet         - Daftar wallet
/help           - Bantuan lengkap
```

### Tanya ke AI
```
bulan ini saya paling boros dimana?
berapa total pengeluaran makan?
buat ringkasan keuangan bulan ini
berapa saldo BRI saya?
```

---

## 📊 Contoh Payload Webhook WhatsApp

### Text Message
```json
{
  "sender": "628123456789",
  "message": "beli kopi 25rb pakai gopay",
  "type": "text",
  "id": "msg_123abc"
}
```

### Image (Struk)
```json
{
  "sender": "628123456789",
  "type": "image",
  "file": "https://cdn.gateway.com/media/image.jpg",
  "mimetype": "image/jpeg",
  "id": "msg_456def"
}
```

### Voice Note
```json
{
  "sender": "628123456789",
  "type": "voice",
  "file": "https://cdn.gateway.com/media/voice.ogg",
  "mimetype": "audio/ogg",
  "id": "msg_789ghi"
}
```

---

## 🗂️ Struktur Folder

```
app/
├── Console/Commands/       # Artisan commands (SendMonthlyReports)
├── Exports/                # Excel exports
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # Admin panel controllers
│   │   ├── Auth/           # Auth controllers
│   │   └── ...             # Feature controllers
│   └── Middleware/         # Custom middleware
├── Jobs/                   # Queue jobs
├── Models/                 # Eloquent models
├── Providers/              # Service providers
└── Services/               # Business logic services
    ├── AppSettingService.php
    ├── GrokAIService.php
    ├── TransactionParserService.php
    ├── ReceiptScannerService.php
    ├── VoiceNoteTranscriptionService.php
    ├── WhatsAppService.php
    └── WhatsAppWebhookService.php

database/
├── migrations/             # 12 migration files
├── seeders/                # Demo data seeders
└── factories/              # Model factories

resources/views/
├── admin/                  # Admin panel views
├── auth/                   # Login, Register, Forgot
├── dashboard/              # Dashboard utama
├── layouts/                # App & Auth layouts
├── profile/                # Profile settings
├── reports/                # Laporan & PDF
├── transactions/           # CRUD transaksi
└── wallets/                # CRUD wallet
```

---

## 🔧 Queue Worker (untuk production)

```bash
# Jalankan queue worker
php artisan queue:work --queue=whatsapp,notifications,default

# Atau dengan supervisor (recommended)
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

---

## 🌐 Konfigurasi Production

```bash
# Optimasi
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 📌 Tech Stack

| Layer      | Teknologi                    |
|------------|------------------------------|
| Backend    | Laravel 12, PHP 8.2          |
| Database   | MySQL 8.0                    |
| Frontend   | Blade, TailwindCSS 3, Alpine.js |
| Charts     | Chart.js 4                   |
| AI         | Grok API (xAI)               |
| Queue      | Laravel Queue (Database)     |
| PDF        | DomPDF (barryvdh)            |
| Excel      | Laravel Excel (Maatwebsite)  |

---

## 📝 License

MIT License — Bebas digunakan dan dimodifikasi.

---

Made with ❤️ by Finance AI Team

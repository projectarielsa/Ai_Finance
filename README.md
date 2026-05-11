# 💰 Finance AI — Telegram Bot Assistant

> Aplikasi keuangan pribadi cerdas berbasis Laravel 12 yang terintegrasi dengan **Telegram Bot** dan **Groq AI** untuk pencatatan transaksi otomatis via chat.

![Laravel](https://img.shields.io/badge/Laravel-12-red) ![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3-blue) ![PHP](https://img.shields.io/badge/PHP-8.2-purple) ![MySQL](https://img.shields.io/badge/MySQL-8-orange)

---

## ✨ Fitur Utama

- 🤖 **Groq AI Integration** — Parse transaksi dari teks natural, foto struk, dan voice note
- 📱 **Telegram Bot** — Input transaksi langsung dari chat Telegram
- 📸 **Receipt Scanner** — Scan struk/nota otomatis via foto Telegram
- 🎤 **Voice Note** — Catat transaksi via rekaman suara
- 💳 **Multi Wallet** — BCA, BRI, Mandiri, Gopay, Dana, OVO, dll.
- 📊 **Dashboard Analitik** — Chart interaktif, AI insight, laporan bulanan
- 📄 **Export PDF & Excel** — Laporan keuangan lengkap
- ⚙️ **Admin Panel** — Konfigurasi API key tanpa edit kode
- 🔒 **Security** — API key terenkripsi, webhook signature, race-condition-safe balance updates

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
cd finance-ai

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

## ⚙️ Setup Groq AI (dari Admin Panel)

**TIDAK PERLU edit `.env`!** Semua konfigurasi dilakukan dari Admin Panel.

1. Login sebagai admin
2. Buka **Admin → API Credentials**
3. Klik **+ Tambah Credential**
4. Isi:
   - **Nama**: Groq AI Production
   - **Provider**: `groq`
   - **API Key**: Dapatkan dari [console.groq.com/keys](https://console.groq.com/keys) (GRATIS!)
   - **Endpoint URL**: `https://api.groq.com/openai/v1`
   - **Model**: `llama-3.3-70b-versatile`
5. Centang **Aktif** dan **Default**
6. Simpan, lalu klik tombol **Test Koneksi**

### Model yang Digunakan

| Fungsi | Model | Keterangan |
|--------|-------|------------|
| Parse transaksi teks | `llama-3.3-70b-versatile` | Model utama, cepat & akurat |
| Scan foto struk (Vision) | `meta-llama/llama-4-scout-17b-16e-instruct` | Multimodal, bisa baca gambar |
| Transcribe voice note | `whisper-large-v3` | Whisper via Groq (super cepat) |

---

## 📱 Setup Telegram Bot

### 1. Buat Bot di BotFather
1. Chat ke [@BotFather](https://t.me/BotFather) di Telegram
2. Kirim `/newbot` dan ikuti instruksinya
3. Salin **Bot Token** yang diberikan

### 2. Konfigurasi `.env`
```env
TELEGRAM_BOT_TOKEN=your_bot_token_from_botfather
TELEGRAM_BOT_USERNAME=YourBotUsername
TELEGRAM_WEBHOOK_SECRET=random_secret_string_here
```

### 3. Daftarkan Webhook
Setelah deploy ke server dengan HTTPS:
```bash
# Via artisan (setelah login admin)
# Atau akses URL:
https://yourdomain.com/telegram/setup-webhook
```

Atau dari **Admin Panel → Telegram → Set Webhook**.

---

## 💬 Cara Penggunaan Telegram Bot

### Hubungkan Akun
```
/link email@anda.com
```

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
Kirim foto struk/nota ke bot. AI akan otomatis membaca dan mencatat transaksi.

### Kirim Voice Note
Rekam suara: *"Catat ya, tadi beli bensin lima puluh ribu pakai cash"*

### Commands Bot
```
/start          - Salam pembuka
/saldo          - Lihat semua saldo wallet
/laporan        - Ringkasan bulan ini
/rekap          - Rekapan lengkap bulan ini
/rekap 4 2026   - Rekap bulan April 2026
/topkategori    - Top kategori pengeluaran
/wallet         - Daftar wallet
/link email     - Hubungkan/ganti akun
/unlink         - Putuskan akun dari bot
/help           - Bantuan lengkap
```

### Tanya ke AI
```
bulan ini saya paling boros dimana?
berapa total pengeluaran makan?
buat ringkasan keuangan bulan ini
analisa keuangan saya
```

---

## 🗂️ Struktur Folder

```
app/
├── Console/Commands/       # SendMonthlyReports (Telegram)
├── Exports/                # Excel exports
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # Admin panel controllers
│   │   ├── Auth/           # Auth controllers
│   │   └── ...             # Feature controllers
│   └── Middleware/         # AdminMiddleware
├── Jobs/                   # Queue jobs (Telegram, MonthlyReport)
├── Models/                 # Eloquent models
├── Providers/              # Service providers
└── Services/               # Business logic
    ├── AppSettingService.php
    ├── GrokAIService.php
    ├── TransactionParserService.php
    ├── ReceiptScannerService.php
    ├── VoiceNoteTranscriptionService.php
    ├── TelegramBotService.php
    ├── TelegramWebhookService.php
    └── ...

database/
├── migrations/             # DB migrations
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
php artisan queue:work --queue=notifications,default --sleep=3 --tries=3
```

---

## 🌐 Konfigurasi Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 📌 Tech Stack

| Layer      | Teknologi                           |
|------------|-------------------------------------|
| Backend    | Laravel 12, PHP 8.2                 |
| Database   | MySQL 8.0                           |
| Frontend   | Blade, TailwindCSS 3, Alpine.js     |
| Charts     | Chart.js 4                          |
| Bot        | Telegram Bot API                    |
| AI         | Groq API (Llama 3.3 + Llama 4 Scout Vision) |
| Audio      | Groq Whisper (whisper-large-v3)     |
| Queue      | Laravel Queue (Database)            |
| PDF        | DomPDF (barryvdh)                   |
| Excel      | Laravel Excel (Maatwebsite)         |

---

## 📝 License

MIT License — Bebas digunakan dan dimodifikasi.

---

Made with ❤️ — Finance AI Team

<h1 align="center">KPI Laravel</h1>

<p align="center">
  Sistem manajemen KPI berbasis web untuk monitoring kinerja pegawai secara real-time.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/Vue.js-3-4FC08D?style=flat-square&logo=vue.js&logoColor=white" alt="Vue 3">
  <img src="https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Vite-8-646CFF?style=flat-square&logo=vite&logoColor=white" alt="Vite">
  <img src="https://img.shields.io/badge/Pinia-2-FFD859?style=flat-square&logo=pinia&logoColor=black" alt="Pinia">
</p>

<p align="center">
  <a href="#tentang-proyek">Tentang</a> •
  <a href="#fitur">Fitur</a> •
  <a href="#peran-pengguna">Peran</a> •
  <a href="#instalasi">Instalasi</a> •
  <a href="#cicd">CI/CD</a>
</p>

---

## Tentang Proyek

**KPI Laravel** adalah aplikasi manajemen Key Performance Indicator (KPI) yang dibangun dengan Laravel 13 dan Vue.js 3. Sistem ini dirancang untuk membantu organisasi memantau, melaporkan, dan mengevaluasi kinerja pegawai secara terstruktur dalam satu platform terpadu — mulai dari input pekerjaan harian, pelaporan KPI, hingga review dan analitik oleh manajemen.

---

## Fitur

### Manajemen KPI
- Buat dan kelola indikator KPI per jabatan
- Mapping KPI ke pegawai berdasarkan departemen
- Perhitungan skor KPI otomatis berdasarkan nilai aktual vs target
- Laporan KPI dengan upload evidence (PDF, gambar, dokumen)

### Manajemen Tugas
- Penugasan tugas manual dari HR ke pegawai
- Pembaruan status tugas oleh pegawai (Pending → Dalam Proses → Selesai)
- Upload evidence per tugas
- Pencatatan nilai aktual untuk perhitungan skor

### Dashboard & Analitik
- Dashboard ringkasan per peran (Pegawai, HR, Direktur)
- Grafik progres KPI, tren kinerja, dan leaderboard
- Filter berdasarkan periode, departemen, dan jabatan
- Export laporan

### Lainnya
- Multi-tenant — satu instalasi untuk banyak organisasi
- Notifikasi real-time (FCM)
- Audit log setiap aktivitas
- Autentikasi berbasis peran (RBAC)
- Responsive & mobile-friendly

---

## Peran Pengguna

| Peran | Akses |
|-------|-------|
| **Admin** | Kelola tenant, user, template KPI, dan assignment |
| **HR** | Kelola pegawai, tugas, SLA, review laporan KPI |
| **Direktur** | Lihat dashboard analitik, ranking, dan laporan seluruh pegawai |
| **Pegawai** | Input pekerjaan, laporan KPI, update status tugas |

---

## Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 13, PHP 8.3 |
| Frontend | Vue.js 3, Vite 8, Pinia 2 |
| Styling | Tailwind CSS 4, Lucide Icons |
| Database | MySQL / MariaDB |
| Storage | Laravel Storage (public disk) |
| Notifikasi | Firebase Cloud Messaging (FCM) |

---

## Instalasi

### Prasyarat

- PHP >= 8.3
- Composer
- Node.js >= 18
- MySQL / MariaDB

### Langkah Setup

```bash
# 1. Clone repository
git clone <repo-url>
cd kpi-laravel

# 2. Install dependency PHP
composer install

# 3. Install dependency Node.js
npm install

# 4. Salin file environment dan isi konfigurasi
cp .env.example .env

# 5. Generate app key
php artisan key:generate

# 6. Jalankan migrasi & seeder
php artisan migrate
php artisan db:seed

# 7. Buat symlink storage
php artisan storage:link

# 8. Build aset frontend
npm run build

# 9. Jalankan server
php artisan serve
npm run dev
```

---

## CI/CD

Proyek ini sudah disiapkan dengan GitHub Actions untuk CI dan deployment otomatis.

### Pipeline CI

Berjalan otomatis saat `push` dan `pull_request`:

- Install dependency PHP dan Node.js
- Generate Laravel app key
- Jalankan `composer test`
- Build aset Vite dengan `npm run build`

### Pipeline Deploy

Berjalan saat `push` ke branch `main` atau dijalankan manual via `workflow_dispatch`.

#### GitHub Secrets yang Diperlukan

| Secret | Keterangan |
|--------|------------|
| `DEPLOY_HOST` | Host server production |
| `DEPLOY_PORT` | Port SSH (biasanya `22`) |
| `DEPLOY_USERNAME` | User SSH untuk deploy |
| `DEPLOY_SSH_KEY` | Private key SSH |
| `DEPLOY_PATH` | Root folder app di server, contoh `/var/www/kpi_laravel` |
| `DEPLOY_RELOAD_COMMAND` | Opsional, contoh: `sudo systemctl reload php8.3-fpm` |

#### Struktur Folder di Server

```
$DEPLOY_PATH/
├── current/              ← symlink ke release aktif
├── releases/
│   └── <commit-sha>/     ← tiap deploy buat folder baru
└── shared/
    ├── .env              ← environment production
    └── storage/          ← storage persisten
```

> Workflow menyimpan 5 release terakhir untuk kemudahan rollback.

---

## Struktur Proyek

```
├── app/
│   ├── Http/Controllers/Api/   ← API controllers
│   ├── Models/                 ← Eloquent models
│   └── Services/               ← Business logic
├── resources/js/
│   ├── pages/                  ← Vue pages (per role)
│   ├── stores/                 ← Pinia stores
│   ├── components/             ← Shared UI components
│   └── modules/                ← Feature modules (kpi-reports, dll)
└── routes/
    └── api.php                 ← API routes
```

---

<br>

<p align="center">
  Built by <strong>Reisan Adrefa</strong> — Internship(SMK Bina Informatika) <strong>PT BASS</strong> 2026
</p>

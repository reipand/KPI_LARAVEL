# Dokumentasi API dan Command

Dokumen ini merangkum seluruh API utama dan command operasional pada project `KPI_LARAVEL` agar maintenance lebih mudah.

## Ringkasan Teknis

- Framework: Laravel 13
- PHP: `^8.3`
- Auth API: Laravel Sanctum
- Export file: `barryvdh/laravel-dompdf`
- Frontend build: Vite + Vue 3 + Pinia
- Base API URL lokal saat `php artisan serve`: `http://127.0.0.1:8000/api`

## Konvensi API

### Format Response Sukses

Sebagian besar endpoint mengembalikan format:

```json
{
  "success": true,
  "data": {},
  "message": "Berhasil"
}
```

Untuk endpoint paginasi:

```json
{
  "success": true,
  "data": {
    "items": [],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 0
    }
  },
  "message": "Berhasil"
}
```

### Format Response Error

```json
{
  "success": false,
  "message": "Pesan error",
  "errors": {}
}
```

### Autentikasi

- Login menggunakan `nip` + `nama`.
- Setelah login, client menerima Bearer token Sanctum.
- Endpoint yang butuh login memakai middleware `auth:sanctum`.
- Expired token default di config: `480` menit (`SANCTUM_EXPIRATION`).

Header yang dipakai:

```http
Authorization: Bearer <token>
Accept: application/json
```

### Role

Role user yang digunakan:

- `pegawai`
- `hr_manager`
- `direktur`

Aturan umum:

- `pegawai` hanya bisa mengakses data miliknya sendiri pada beberapa modul.
- `hr_manager` dan `direktur` dianggap bisa mengelola seluruh data (`canManageAllData()`).
- Beberapa endpoint dibatasi khusus `hr_manager`.

## Daftar API

## 1. Auth

### `POST /api/auth/login`

Login user berdasarkan `nip` dan `nama`.

Request body:

```json
{
  "nip": "12345",
  "nama": "Nama Pegawai",
  "device_name": "web-chrome"
}
```

Validasi:

- `nip`: required, string, max 50
- `nama`: required, string, max 255
- `device_name`: nullable, string, max 100

Catatan:

- Rate limit: 5 percobaan per IP.
- Jika gagal terus, blokir sementara 15 menit.
- Sistem menjaga maksimal token aktif terbaru.

Response penting:

- `token`
- `token_type`
- `expires_at`
- `user`

### `POST /api/auth/logout`

Menghapus token aktif saat ini.

Auth:

- `auth:sanctum`

### `GET /api/auth/me`

Mengambil profil user yang sedang login.

Auth:

- `auth:sanctum`

## 2. Employees

### `GET /api/employees`

Mengambil daftar pegawai.

Auth:

- `auth:sanctum`

Query parameter:

- `jabatan`
- `departemen`
- `per_page` default `15`

### `POST /api/employees`

Membuat pegawai baru.

Auth:

- `auth:sanctum`
- role: `hr_manager`, `direktur`

Request body utama:

```json
{
  "nip": "12345",
  "nama": "Nama Pegawai",
  "jabatan": "Staff",
  "departemen": "Operasional",
  "status_karyawan": "Tetap",
  "tanggal_masuk": "2026-04-01",
  "no_hp": "08123456789",
  "email": "pegawai@example.com",
  "role": "pegawai",
  "division_id": 1,
  "department_id": 1,
  "position_id": 1
}
```

Validasi:

- `nip`: required, unique
- `nama`: required
- `jabatan`: required
- `departemen`: required
- `status_karyawan`: required
- `tanggal_masuk`: required, date
- `no_hp`: nullable
- `email`: nullable, email, unique
- `role`: `pegawai|hr_manager|direktur`
- `division_id`: nullable, exists
- `department_id`: nullable, exists
- `position_id`: nullable, exists

Catatan:

- Password dibentuk otomatis dari kombinasi `nip|nama` yang di-hash.

### `PUT /api/employees/{employee}`

Update data pegawai.

Auth:

- `auth:sanctum`
- role: `hr_manager`, `direktur`

Validasi sama seperti create.

Catatan:

- Jika `nip` atau `nama` berubah, password ikut dibangkitkan ulang.

### `DELETE /api/employees/{employee}`

Hapus pegawai dan seluruh token Sanctum miliknya.

Auth:

- `auth:sanctum`
- role: `hr_manager`, `direktur`

## 3. Tasks

### `GET /api/tasks`

Mengambil daftar task.

Auth:

- `auth:sanctum`

Query parameter:

- `user_id`
- `bulan`
- `tahun`
- `status`
- `per_page` default `15`

Catatan:

- Jika role `pegawai`, hasil otomatis dibatasi ke task milik sendiri.

### `POST /api/tasks`

Membuat task untuk user login.

Auth:

- `auth:sanctum`

Request body:

```json
{
  "tanggal": "2026-04-08",
  "judul": "Input data KPI",
  "jenis_pekerjaan": "Administrasi",
  "status": "Selesai",
  "waktu_mulai": "08:00",
  "waktu_selesai": "10:00",
  "ada_delay": false,
  "ada_error": false,
  "ada_komplain": false,
  "deskripsi": "Pekerjaan selesai tepat waktu"
}
```

Validasi:

- `tanggal`: required, date
- `judul`: required
- `jenis_pekerjaan`: required
- `status`: `Selesai|Dalam Proses|Pending`
- `waktu_mulai`: nullable, format `H:i`
- `waktu_selesai`: nullable, format `H:i`
- `ada_delay`: nullable, boolean
- `ada_error`: nullable, boolean
- `ada_komplain`: nullable, boolean
- `deskripsi`: nullable

### `PUT /api/tasks/{task}`

Update task.

Auth:

- `auth:sanctum`

Catatan:

- Otorisasi via policy: pemilik task atau role yang bisa kelola semua data.

### `DELETE /api/tasks/{task}`

Hapus task.

Auth:

- `auth:sanctum`

Catatan:

- Otorisasi via policy: pemilik task atau role yang bisa kelola semua data.

### `PUT /api/tasks/{task}/mapping`

Mapping task ke komponen KPI.

Auth:

- `auth:sanctum`
- role: `hr_manager`, `direktur`

Request body:

```json
{
  "kpi_component_id": 10,
  "manual_score": 4.5
}
```

Validasi:

- `kpi_component_id`: required, exists
- `manual_score`: nullable, numeric, min 0, max 5

Efek:

- Mengisi `kpi_component_id`
- Mengisi `manual_score`
- Menyimpan `mapped_by` dan `mapped_at`

## 4. KPI

### `GET /api/kpi/me`

Mengambil skor KPI user login.

Auth:

- `auth:sanctum`

Query parameter:

- `bulan`
- `tahun`

### `GET /api/kpi/{user}`

Mengambil skor KPI user tertentu.

Auth:

- `auth:sanctum`
- role: `hr_manager`, `direktur`

Query parameter:

- `bulan`
- `tahun`

### `GET /api/kpi/ranking`

Mengambil ranking KPI seluruh pegawai.

Auth:

- `auth:sanctum`

Query parameter:

- `bulan`
- `tahun`

## 5. KPI Components

### `GET /api/kpi-components`

Daftar komponen KPI.

Auth:

- `auth:sanctum`

Query parameter:

- `jabatan`
- `per_page` default `15`

### `POST /api/kpi-components`

Membuat komponen KPI.

Auth:

- `auth:sanctum`
- role: `hr_manager`

Request body utama:

```json
{
  "jabatan": "Staff",
  "division_id": 1,
  "position_id": 1,
  "objectives": "Ketepatan waktu pekerjaan",
  "strategy": "Zero delay",
  "bobot": 0.2,
  "target": 100,
  "satuan": "%",
  "tipe": "achievement",
  "kpi_type": "percentage",
  "period": "monthly",
  "catatan": "KPI utama",
  "is_active": true
}
```

Validasi:

- `jabatan`: required
- `division_id`: nullable, exists
- `position_id`: nullable, exists
- `objectives`: required
- `strategy`: required
- `bobot`: required, numeric, min 0, max 1
- `target`: nullable, numeric
- `satuan`: nullable
- `tipe`: `zero_delay|zero_error|zero_complaint|achievement|csi`
- `kpi_type`: `number|percentage|boolean`
- `period`: `daily|weekly|monthly`
- `catatan`: nullable
- `is_active`: nullable, boolean

### `PUT /api/kpi-components/{kpiComponent}`

Update komponen KPI.

Auth:

- `auth:sanctum`
- role: `hr_manager`

Validasi sama seperti create.

### `DELETE /api/kpi-components/{kpiComponent}`

Hapus komponen KPI.

Auth:

- `auth:sanctum`
- role: `hr_manager`

## 6. SLA

### `GET /api/sla`

Daftar SLA.

Auth:

- `auth:sanctum`

Query parameter:

- `jabatan`
- `per_page` default `15`

### `POST /api/sla`

Membuat SLA.

Auth:

- `auth:sanctum`
- role: `hr_manager`

Request body:

```json
{
  "nama_pekerjaan": "Verifikasi dokumen",
  "jabatan": "Staff",
  "position_id": 1,
  "durasi_jam": 8,
  "keterangan": "Maksimal 1 hari kerja"
}
```

Validasi:

- `nama_pekerjaan`: required
- `jabatan`: required
- `position_id`: nullable, exists
- `durasi_jam`: required, integer, min 1
- `keterangan`: nullable

### `PUT /api/sla/{sla}`

Update SLA.

Auth:

- `auth:sanctum`
- role: `hr_manager`

### `DELETE /api/sla/{sla}`

Hapus SLA.

Auth:

- `auth:sanctum`
- role: `hr_manager`

## 7. Settings

### `GET /api/settings`

Mengambil seluruh settings.

Auth:

- `auth:sanctum`

### `PUT /api/settings`

Update satu atau banyak setting sekaligus.

Auth:

- `auth:sanctum`
- role: `hr_manager`, `direktur`

Request body:

```json
{
  "settings": {
    "kpi_minimum_score": 3,
    "notif_review_enabled": true
  }
}
```

Validasi:

- `settings`: required, array, minimal 1 item
- `settings.*`: nullable

## 8. Activity Logs

### `GET /api/logs`

Mengambil activity log.

Auth:

- `auth:sanctum`
- role: `hr_manager`, `direktur`

Query parameter:

- `user_id`
- `action`
- `per_page` default `20`

## 9. Reference Data

### Departments

#### `GET /api/departments`

Daftar departemen.

Auth:

- `auth:sanctum`

Query parameter:

- `active_only` boolean
- `division_id`

#### `POST /api/departments`

Buat departemen.

Auth:

- `auth:sanctum`
- role: `hr_manager`, `direktur`

Request body:

```json
{
  "nama": "Finance",
  "kode": "FIN",
  "division_id": 1,
  "deskripsi": "Departemen keuangan",
  "is_active": true
}
```

Validasi:

- `nama`: required, string, max 100
- `kode`: required, unique
- `division_id`: nullable, exists
- `deskripsi`: nullable
- `is_active`: nullable, boolean

#### `PUT /api/departments/{department}`

Update departemen.

Auth:

- `auth:sanctum`
- role: `hr_manager`, `direktur`

#### `DELETE /api/departments/{department}`

Hapus departemen.

Auth:

- `auth:sanctum`
- role: `hr_manager`, `direktur`

### Divisions

#### `GET /api/divisions`

Daftar divisi.

Auth:

- `auth:sanctum`

Query parameter:

- `active_only` boolean

#### `POST /api/divisions`

Buat divisi.

Auth:

- `auth:sanctum`
- role: `hr_manager`, `direktur`

Request body:

```json
{
  "nama": "Operasional",
  "kode": "OPS",
  "deskripsi": "Divisi operasional",
  "is_active": true
}
```

Validasi:

- `nama`: required
- `kode`: required, unique
- `deskripsi`: nullable
- `is_active`: nullable, boolean

#### `PUT /api/divisions/{division}`

Update divisi.

Auth:

- `auth:sanctum`
- role: `hr_manager`, `direktur`

#### `DELETE /api/divisions/{division}`

Hapus divisi.

Auth:

- `auth:sanctum`
- role: `hr_manager`, `direktur`

### Positions

#### `GET /api/positions`

Daftar posisi/jabatan referensi.

Auth:

- `auth:sanctum`

Query parameter:

- `active_only` boolean
- `department_id`

## 10. KPI Reports

### `GET /api/kpi-reports`

Daftar laporan KPI.

Auth:

- `auth:sanctum`

Query parameter:

- `user_id`
- `kpi_component_id`
- `bulan`
- `tahun`
- `status`
- `per_page` default `20`

Catatan:

- `pegawai` hanya melihat laporannya sendiri.

### `POST /api/kpi-reports`

Membuat laporan KPI.

Auth:

- `auth:sanctum`

Request body:

```json
{
  "user_id": 1,
  "kpi_component_id": 2,
  "period_type": "monthly",
  "tanggal": "2026-04-08",
  "period_label": "April 2026",
  "nilai_target": 100,
  "nilai_aktual": 95,
  "catatan": "Target hampir tercapai",
  "status": "submitted"
}
```

Validasi:

- `user_id`: nullable, exists
- `kpi_component_id`: required, exists
- `period_type`: `daily|weekly|monthly`
- `tanggal`: required, date
- `period_label`: required, string, max 100
- `nilai_target`: nullable, numeric, min 0
- `nilai_aktual`: required, numeric, min 0
- `catatan`: nullable
- `status`: `draft|submitted|approved|rejected`

Catatan:

- Untuk `pegawai`, `user_id` otomatis dipaksa ke user login.
- `persentase` dihitung otomatis dari `nilai_aktual / nilai_target`.
- `score_label` dihitung otomatis:
  - `excellent` jika `> 100`
  - `good` jika `>= 80`
  - `average` jika `>= 50`
  - `bad` jika `< 50`
- Jika status `submitted`, sistem mengisi `submitted_by` dan `submitted_at`.

### `PUT /api/kpi-reports/{kpiReport}`

Update laporan KPI.

Auth:

- `auth:sanctum`

Catatan:

- Hanya author laporan atau role manager/direktur yang bisa update.
- Persentase dan `score_label` akan dihitung ulang.

### `DELETE /api/kpi-reports/{kpiReport}`

Hapus laporan KPI.

Auth:

- `auth:sanctum`

Catatan:

- Jika ada file evidence, file lama di storage public ikut dihapus.
- Hanya author atau role manager/direktur yang bisa menghapus.

### `POST /api/kpi-reports/{kpiReport}/evidence`

Upload file evidence untuk laporan KPI.

Auth:

- `auth:sanctum`

Form-data:

- `file`

Validasi file:

- required
- type: `pdf,png,jpg,jpeg,doc,docx,xlsx`
- max: `5120 KB`

Catatan:

- File disimpan ke `storage/app/public/kpi-evidence/{tahun}/{bulan}`.
- Jika evidence lama ada, file lama dihapus.

### `PATCH /api/kpi-reports/{kpiReport}/review`

Review laporan KPI.

Auth:

- `auth:sanctum`
- role: `hr_manager`, `direktur`

Request body:

```json
{
  "status": "approved",
  "catatan": "Sudah sesuai"
}
```

Validasi:

- `status`: required, `approved|rejected`
- `catatan`: nullable, string, max 1000

## 11. Analytics

Seluruh endpoint analytics memerlukan:

- `auth:sanctum`
- role: `hr_manager`, `direktur`

### `GET /api/analytics/trend`

Data tren KPI bulanan untuk chart.

Query parameter:

- `tahun` default tahun berjalan
- `division_id`

Mengembalikan:

- `labels` bulan Jan-Dec
- dataset rata-rata pencapaian persen
- dataset rata-rata skor KPI berbasis task

### `GET /api/analytics/per-division`

Rata-rata KPI per divisi.

Query parameter:

- `tahun` default tahun berjalan
- `bulan`

Mengembalikan:

- label nama divisi
- dataset pencapaian persen
- dataset skor KPI 0-5

### `GET /api/analytics/distribution`

Distribusi kategori nilai KPI.

Query parameter:

- `tahun` default tahun berjalan
- `bulan`
- `division_id`

Mengembalikan:

- `report_based`
- `task_based`

### `GET /api/analytics/overview`

Ringkasan statistik analytics.

Query parameter:

- `tahun` default tahun berjalan
- `bulan` default bulan berjalan

Mengembalikan:

- total pegawai
- total divisi
- total report
- rata-rata achievement
- jumlah excellent/good/average/bad

## 12. Export

Seluruh endpoint export memerlukan:

- `auth:sanctum`
- role: `hr_manager`, `direktur`

### `GET /api/export/kpi/{user}/pdf`

Export KPI user tertentu ke PDF.

Query parameter:

- `bulan` default bulan berjalan
- `tahun` default tahun berjalan

Output:

- file download PDF

### `GET /api/export/ranking/csv`

Export ranking KPI ke CSV.

Query parameter:

- `bulan` default bulan berjalan
- `tahun` default tahun berjalan

Output:

- file download CSV

### `GET /api/export/reports/csv`

Export laporan KPI ke CSV.

Query parameter:

- `bulan` default bulan berjalan
- `tahun` default tahun berjalan
- `division_id`

Output:

- file download CSV

## 13. Notifications

### `GET /api/notifications`

Mengambil maksimal 50 notifikasi user login.

Auth:

- `auth:sanctum`

Response:

- `items`
- `unread_count`

### `PUT /api/notifications/{notification}/read`

Menandai satu notifikasi sebagai terbaca.

Auth:

- `auth:sanctum`

Catatan:

- User hanya bisa membaca notifikasi miliknya sendiri.

### `PUT /api/notifications/read-all`

Menandai semua notifikasi user login sebagai terbaca.

Auth:

- `auth:sanctum`

## Command Operasional

## 1. Composer Script

### `composer setup`

Setup project dari nol.

Yang dijalankan:

1. `composer install`
2. copy `.env.example` ke `.env` jika belum ada
3. `php artisan key:generate`
4. `php artisan migrate --force`
5. `npm install --ignore-scripts`
6. `npm run build`

Pakai untuk:

- pertama kali clone project
- environment baru

### `composer dev`

Menjalankan environment development lengkap secara paralel:

- `php artisan serve`
- `php artisan queue:listen --tries=1 --timeout=0`
- `php artisan pail --timeout=0`
- `npm run dev`

Pakai untuk:

- development harian

### `composer test`

Menjalankan test aplikasi.

Yang dijalankan:

1. `php artisan config:clear --ansi`
2. `php artisan test`

## 2. NPM Script

### `npm run dev`

Menjalankan Vite dev server.

### `npm run build`

Build frontend production.

## 3. Artisan Command yang Paling Relevan

### Environment dan Serve

- `php artisan serve`
- `php artisan about`
- `php artisan env`
- `php artisan down`
- `php artisan up`

### Database

- `php artisan migrate`
- `php artisan migrate:status`
- `php artisan migrate:rollback`
- `php artisan migrate:refresh`
- `php artisan migrate:fresh`
- `php artisan db:seed`
- `php artisan schema:dump`

Catatan:

- `migrate:fresh` menghapus seluruh tabel, jangan dipakai di environment yang berisi data penting.

### Queue dan Scheduler

- `php artisan queue:listen`
- `php artisan queue:work`
- `php artisan queue:restart`
- `php artisan schedule:list`
- `php artisan schedule:run`
- `php artisan schedule:work`

Catatan:

- Saat ini file `routes/console.php` hanya mendefinisikan command `inspire`.
- Tidak ada custom schedule yang terdaftar di dokumen yang saya baca.

### Cache dan Optimasi

- `php artisan optimize`
- `php artisan optimize:clear`
- `php artisan cache:clear`
- `php artisan config:clear`
- `php artisan config:cache`
- `php artisan route:clear`
- `php artisan route:cache`
- `php artisan view:clear`
- `php artisan view:cache`

### Debugging dan Maintenance

- `php artisan route:list`
- `php artisan test`
- `php artisan tinker`
- `php artisan pail`
- `php artisan storage:link`

### Command Kustom Project

- `php artisan inspire`

Deskripsi:

- menampilkan quote bawaan Laravel

## Command Referensi Lengkap yang Tersedia Saat Ini

Hasil `php artisan list --raw` menunjukkan command berikut tersedia di project ini:

```text
about
clear-compiled
completion
db
docs
down
env
help
inspire
list
migrate
optimize
pail
reload
serve
test
tinker
up
auth:clear-resets
boost:add-skill
boost:install
boost:mcp
boost:update
cache:clear
cache:forget
cache:prune-stale-tags
channel:list
config:cache
config:clear
config:publish
config:show
db:monitor
db:seed
db:show
db:table
db:wipe
env:decrypt
env:encrypt
event:cache
event:clear
event:list
install:api
install:broadcasting
key:generate
lang:publish
make:cache-table
make:cast
make:channel
make:class
make:command
make:component
make:config
make:controller
make:enum
make:event
make:exception
make:factory
make:interface
make:job
make:job-middleware
make:listener
make:mail
make:mcp-prompt
make:mcp-resource
make:mcp-server
make:mcp-tool
make:middleware
make:migration
make:model
make:notification
make:notifications-table
make:observer
make:policy
make:provider
make:queue-batches-table
make:queue-failed-table
make:queue-table
make:request
make:resource
make:rule
make:scope
make:seeder
make:session-table
make:test
make:trait
make:view
mcp:inspector
mcp:start
migrate:fresh
migrate:install
migrate:refresh
migrate:reset
migrate:rollback
migrate:status
model:prune
model:show
optimize:clear
package:discover
queue:clear
queue:failed
queue:flush
queue:forget
queue:listen
queue:monitor
queue:pause
queue:prune-batches
queue:prune-failed
queue:restart
queue:resume
queue:retry
queue:retry-batch
queue:work
roster:scan
route:cache
route:clear
route:list
sanctum:prune-expired
schedule:clear-cache
schedule:interrupt
schedule:list
schedule:pause
schedule:resume
schedule:run
schedule:test
schedule:work
schema:dump
storage:link
storage:unlink
stub:publish
vendor:publish
view:cache
view:clear
```

## File Penting untuk Maintenance

- `routes/api.php`: seluruh definisi endpoint API
- `routes/console.php`: custom artisan command
- `app/Http/Controllers/Api`: logika endpoint API
- `app/Http/Requests`: validasi request
- `app/Http/Resources`: format output API
- `app/Models`: relasi dan business data utama
- `app/Services/KpiCalculatorService.php`: perhitungan KPI
- `resources/js/stores`: konsumsi API di frontend
- `composer.json`: script Composer
- `package.json`: script frontend

## Saran Maintenance

Saat menambah fitur baru, minimal update file berikut agar dokumentasi tetap sinkron:

1. `routes/api.php` jika ada endpoint baru
2. `app/Http/Requests` jika ada payload/validasi baru
3. `dokumentasi.md` bagian endpoint atau command terkait
4. `composer.json` atau `package.json` jika ada command baru


# ParkOps

Aplikasi HRIS untuk pengelolaan juru parkir harian: absensi berbasis
GPS dengan foto dan validasi geofence, penjadwalan shift, sampai payroll harian
dan laporan.

Dibangun dengan Laravel 12 + Blade, dengan interaksi halaman lewat AJAX (jQuery)
sehingga setiap modul punya satu URL yang melayani halaman Blade untuk request
biasa dan JSON untuk request AJAX.

---

## Fitur

| Modul | Isi |
|---|---|
| **Absensi** | Check-in/check-out dengan foto wajib, peta Leaflet, validasi radius & akurasi GPS di sisi server |
| **Monitoring & Riwayat** | Pantauan absensi harian untuk HR, riwayat pribadi untuk karyawan |
| **Master data** | Karyawan, Lokasi, Shift, Assignment, User, Role |
| **Roster** | Generate jadwal shift per periode, termasuk shift lintas hari |
| **Payroll** | Periode payroll, generate upah harian, potongan, tutup/buka kembali periode |
| **Laporan** | Laporan absensi dan payroll, lengkap dengan ekspor |

### Aturan absensi

Validasi absensi sepenuhnya dihitung ulang di server — nilai jarak yang dikirim
browser tidak pernah dipercaya:

- Jarak dihitung dengan formula Haversine terhadap titik lokasi.
- Absensi ditolak bila akurasi GPS perangkat melebihi batas lokasi.
- Absensi ditolak bila jarak melebihi radius lokasi.
- Waktu yang dicatat selalu jam server, bukan jam perangkat.
- Satu baris absensi per karyawan per hari, dijaga oleh unique index di database.

Foto absensi disimpan di disk privat `attendance`, **tidak** di-symlink ke
`public/`, dan hanya bisa diakses lewat route terproteksi
`/attendance/photo/{photo}`.

---

## Kebutuhan sistem

- PHP **8.2+**
- Composer
- MySQL / MariaDB
- Node.js *(opsional — lihat catatan aset di bawah)*

---

## Instalasi

```bash
git clone https://github.com/RifqiIrawan/HRIS_SEDERHANA.git
cd HRIS_SEDERHANA

composer install
cp .env.example .env
php artisan key:generate
```

Buat database, lalu sesuaikan `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hris_juru_parkir
DB_USERNAME=root
DB_PASSWORD=
```

> **Catatan port:** sesuaikan `DB_PORT` dengan MySQL yang benar-benar berjalan.
> XAMPP umumnya memakai `3306`, tetapi instalasi paralel sering dipindah
> (mis. `3310`). Kalau port salah, perintah artisan akan menggantung tanpa
> pesan error.

Jalankan migrasi beserta data awal:

```bash
php artisan migrate --seed
php artisan serve
```

Aplikasi berjalan di <http://127.0.0.1:8000>.

### Catatan aset

Tampilan aplikasi memakai Bootstrap 5.3, jQuery 3.7, DataTables 2.1 dan
Leaflet 1.9 **dari CDN**, sedangkan gaya dan skrip aplikasi berada di
`public/css` dan `public/js`. Artinya `npm install` dan build Vite **tidak
diperlukan** untuk menjalankan aplikasi — Vite hanya dipakai oleh halaman
`welcome` bawaan Laravel.

---

## Akun demo

Login memakai **email**, bukan username. Seluruh akun hasil seeder memakai
password `password`.

| Email | Role | Akses |
|---|---|---|
| `admin@parkops.test` | ADMIN | Seluruh modul |
| `hr@parkops.test` | HR | Modul operasional (tanpa User & Role) |
| `jp001@parkops.test` … `jp006@parkops.test` | EMPLOYEE | Absensi dan riwayat sendiri |

> Halaman absensi (`/attendance`) hanya bisa dibuka akun yang tertaut ke data
> karyawan. Akun `admin` dan `hr` bawaan seeder tidak tertaut, jadi gunakan
> `jp001@parkops.test` untuk mencoba layar check-in.

⚠️ Password di atas adalah default pengembangan. **Ganti sebelum dipakai di
lingkungan selain komputer lokal.**

---

## Data contoh

Seeder menyiapkan 3 shift (termasuk shift malam lintas hari), 3 lokasi parkir,
6 karyawan harian, beserta akun loginnya:

- **Shift** — S1 Pagi (06:00–14:00), S2 Siang (14:00–22:00), S3 Malam (22:00–06:00)
- **Lokasi** — Parkir Mall A, Parkir Stasiun B, Parkir Rumah Sakit C
- **Karyawan** — JP001 s.d. JP006

Roster dan absensi contoh ikut dibuat secara default. Untuk melewatinya:

```bash
PARKOPS_SEED_DEMO=false php artisan migrate:fresh --seed
```

---

## Konfigurasi

Ambang geofence dan absensi diatur lewat `.env`, dengan nilai per-lokasi di
tabel `locations` yang selalu menang bila diisi. Selengkapnya di
`config/parkops.php`.

| Variabel | Default | Keterangan |
|---|---|---|
| `PARKOPS_DEFAULT_RADIUS_METER` | `10` | Radius absensi (meter) |
| `PARKOPS_DEFAULT_GPS_ACCURACY_LIMIT` | `20` | Batas akurasi GPS yang diterima (meter) |
| `PARKOPS_DEFAULT_LATE_TOLERANCE_MINUTES` | `15` | Toleransi keterlambatan (menit) |
| `PARKOPS_CHECKIN_EARLY_WINDOW_MINUTES` | `120` | Seberapa awal check-in dibuka sebelum shift |
| `PARKOPS_CHECKOUT_GRACE_MINUTES` | `180` | Tenggang check-out setelah shift berakhir |
| `PARKOPS_ATTENDANCE_PHOTO_MAX_KB` | `5120` | Ukuran maksimal foto absensi |

---

## Hak akses

| Modul | ADMIN | HR | EMPLOYEE |
|---|:--:|:--:|:--:|
| Dashboard | ✅ | ✅ | ✅ |
| Riwayat absensi sendiri | ✅ | ✅ | ✅ |
| Check-in / check-out | ⚠️ | ⚠️ | ✅ |
| Monitoring absensi | ✅ | ✅ | — |
| Karyawan, Lokasi, Shift, Assignment | ✅ | ✅ | — |
| Roster, Payroll, Laporan | ✅ | ✅ | — |
| Buka kembali periode payroll | ✅ | — | — |
| User & Role | ✅ | — | — |

⚠️ Route check-in/check-out tidak dibatasi role, tetapi menuntut akun yang
tertaut ke data karyawan. Akun ADMIN/HR bawaan seeder belum tertaut sehingga
menerima 403 di halaman tersebut.

---

## Testing

Test dijalankan terhadap **MySQL sungguhan**, bukan SQLite — modul absensi
bergantung pada unique index dan kolom desimal yang perilakunya ingin diuji di
mesin database yang benar-benar dipakai.

Siapkan database test terlebih dahulu:

```sql
CREATE DATABASE hris_juru_parkir_test;
```

Lalu:

```bash
php artisan test
```

---

## Struktur singkat

```
app/
  Http/Controllers/   satu controller per modul, melayani Blade + JSON
  Services/           AttendanceService, GeofenceService, PayrollService, …
  Models/
resources/views/
  components/         data-table & modal-form yang dipakai ulang tiap modul
  layouts/            app shell, sidebar, navbar
public/
  css/app.css         gaya aplikasi
  js/                 satu berkas per halaman + app.js sebagai helper bersama
database/
  migrations/  seeders/  factories/
tests/
  Feature/  Unit/
```

Spesifikasi fungsional lengkap ada di
[`PARKOPS_Juru_Parkir_Laravel13_Blade_AJAX_MVP.md`](PARKOPS_Juru_Parkir_Laravel13_Blade_AJAX_MVP.md);
komentar di kode merujuk ke nomor pasal dokumen tersebut.

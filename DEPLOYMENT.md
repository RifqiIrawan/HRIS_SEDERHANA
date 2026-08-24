# Deployment ke Production

Panduan menaikkan HRIS Juru Parkir dari mesin lokal ke server. Template
lengkapnya ada di [`.env.production.example`](.env.production.example).

---

## Prasyarat server

| | |
|---|---|
| PHP | 8.2 atau lebih baru |
| MySQL / MariaDB | database kosong + user khusus aplikasi |
| Web server | Apache atau Nginx dengan **DocumentRoot ke `public/`** |
| Sertifikat | **HTTPS wajib** — lihat bagian di bawah |
| Composer | untuk `composer install` |

Node.js **tidak diperlukan**.

---

## 1. Yang berubah di `.env`

| Baris | Local | Production | Alasan |
|---|---|---|---|
| `APP_ENV` | `local` | `production` | Menyalakan penegakan geofence secara otomatis |
| `APP_DEBUG` | `true` | `false` | Kalau `true`, halaman error menampilkan stack trace berikut isi `.env` — termasuk password database |
| `APP_URL` | `http://localhost:8000` | `https://domain-anda` | Dipakai untuk membentuk URL absolut |
| `APP_KEY` | key lokal | **generate ulang di server** | Kunci enkripsi sesi tidak boleh dipakai bersama mesin dev |
| `LOG_LEVEL` | `debug` | `error` | `debug` mencatat setiap query; log membengkak cepat |
| `LOG_STACK` | `single` | `daily` | Log dirotasi harian, tidak menumpuk dalam satu berkas |
| `DB_PORT` | `3307` | `3306` (umumnya) | `3307` khusus XAMPP paralel di mesin dev |
| `DB_USERNAME` | `root` | user khusus aplikasi | `root` tidak boleh dipakai aplikasi |
| `DB_PASSWORD` | *(kosong)* | password kuat | |

Dua baris **belum ada di `.env` lokal** dan harus ditambahkan:

```env
SESSION_SECURE_COOKIE=true    # cookie sesi hanya lewat HTTPS
HRIS_SEED_DEMO=false          # cegah data contoh ikut ter-seed
```

`HRIS_ENFORCE_GEOFENCE` **tidak perlu diisi.** Nilainya mengikuti `APP_ENV`:
`local` → longgar, selain itu → ditegakkan. Isi baris ini hanya kalau suatu
environment butuh kebalikan dari default-nya (lihat `config/hris.php`).

---

## 2. Langkah deploy

```bash
git clone https://github.com/RifqiIrawan/HRIS_SEDERHANA.git
cd HRIS_SEDERHANA

composer install --no-dev --optimize-autoloader

cp .env.production.example .env
# isi APP_URL dan kredensial DB, lalu:
php artisan key:generate

php artisan migrate --force
```

### Seeder: jangan jalankan semuanya

```bash
php artisan db:seed --class="Database\Seeders\RoleSeeder" --force
php artisan db:seed --class="Database\Seeders\MenuSeeder" --force
php artisan db:seed --class="Database\Seeders\ReferenceSeeder" --force
```

Hanya tiga seeder ini yang boleh jalan di production. Ketiganya infrastruktur —
daftar role, registry menu yang dibaca middleware otorisasi, dan isi awal master
Status Kepegawaian / Tipe Kepegawaian / Status Karyawan — bukan data contoh.
Tanpa `MenuSeeder`, tabel `menus` kosong dan middleware (dengan benar) menolak
setiap halaman. Tanpa `ReferenceSeeder`, dropdown pada form Karyawan kosong dan
setiap simpan ditolak validasi.

Ketiganya aman dijalankan ulang: baris yang sudah ada tidak ditimpa, jadi
perubahan nama atau hak akses yang dibuat admin tetap bertahan.

> ⚠️ **Jangan** jalankan `php artisan migrate --seed` atau `db:seed` tanpa
> `--class`. Seeder penuh memasukkan karyawan contoh, lokasi contoh, dan akun
> login dengan password `password` (`admin@hris.test`, `hr@hris.test`, dan satu
> akun per karyawan contoh).

### Cache

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Ulangi `php artisan config:cache` setiap kali `.env` di server berubah — selama
config ter-cache, perubahan `.env` tidak terbaca.

---

## 3. Membuat akun administrator

Karena `UserSeeder` tidak dijalankan, akun pertama dibuat manual:

```bash
php artisan tinker
```

```php
$role = App\Models\Role::where('role_code', App\Models\Role::ADMIN)->firstOrFail();

App\Models\User::create([
    'name'     => 'Administrator',
    'email'    => 'admin@domain-anda.com',
    'password' => 'GANTI_DENGAN_PASSWORD_KUAT',   // otomatis di-hash
    'role_id'  => $role->id,
    'status'   => App\Models\User::ACTIVE,
]);
```

Karyawan, lokasi, dan shift selanjutnya diisi lewat menu Master Data di
aplikasi.

> Akun ADMIN yang belum tertaut ke baris karyawan tidak bisa memakai layar
> "Absensi Saya" (menu itu menuntut `employee_id`). Kalau administrator juga
> perlu absen, buat dulu data karyawannya lalu tautkan lewat menu User.

---

## 4. HTTPS itu wajib, bukan sekadar praktik baik

Layar check-in memakai `navigator.geolocation` (koordinat) dan
`getUserMedia` (kamera untuk foto absensi). Browser modern memblokir **kedua
API ini di halaman non-HTTPS**, dengan pengecualian `localhost` saja.

Artinya di domain ber-HTTP biasa, seluruh modul absensi mati: GPS tidak
terbaca dan kamera tidak bisa dibuka. Ini batasan browser, bukan bug aplikasi —
tidak ada pengaturan di sisi Laravel yang bisa mengakalinya.

Kalau aplikasi berada di belakang reverse proxy atau CDN yang mengakhiri TLS
(Nginx, Cloudflare), beri tahu Laravel supaya membaca header `X-Forwarded-*`
dengan menambahkan ke `bootstrap/app.php` di dalam `withMiddleware`:

```php
$middleware->trustProxies(at: '*');
```

Tanpa itu Laravel mengira koneksinya HTTP dan membentuk URL yang salah.

---

## 5. Web server & izin berkas

**DocumentRoot harus menunjuk ke `public/`**, jangan ke root proyek. Kalau
salah, `.env` bisa diunduh siapa saja lewat browser.

`public/.htaccess` bawaan sudah menangani rewrite untuk Apache. Untuk Nginx,
pakai konfigurasi Laravel standar (`try_files $uri $uri/ /index.php?$query_string`).

Dua direktori harus writable oleh user web server:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

`storage/app/attendance/` menyimpan foto absensi dan akan tumbuh terus —
masukkan ke rencana backup bersama database.

---

## 6. Yang tidak diperlukan

| | Alasan |
|---|---|
| `npm install` / build Vite | Bootstrap, jQuery, DataTables, Leaflet dari CDN; CSS & JS aplikasi sudah jadi di `public/` |
| Queue worker | Tidak ada job yang di-dispatch |
| Cron / scheduler | Tidak ada scheduled task |
| Konfigurasi SMTP | Aplikasi belum mengirim email |
| `php artisan storage:link` | Foto absensi disajikan lewat controller yang mem-stream berkas, bukan symlink publik — justru lebih aman |

---

## 7. Verifikasi setelah deploy

```bash
# Geofence harus ditegakkan
php artisan tinker --execute="echo config('hris.enforce_geofence') ? 'ENFORCED' : 'OFF';"

# Debug harus mati
php artisan tinker --execute="echo config('app.debug') ? 'DEBUG ON (BAHAYA)' : 'debug off';"
```

Health check tersedia di `GET /up` (bawaan Laravel) untuk uptime monitoring.

Lalu lewat browser, pastikan:

- [ ] Login berhasil dengan akun admin yang baru dibuat
- [ ] Sidebar menampilkan **17 menu** — 18 kalau akun admin sudah ditautkan ke
      baris karyawan, karena "Absensi Saya" baru muncul setelah tertaut.
      Sidebar kosong berarti `MenuSeeder` belum jalan
- [ ] Halaman check-in meminta izin lokasi dan kamera — kalau tidak, HTTPS bermasalah
- [ ] Check-in dari luar radius lokasi **ditolak** (bukti geofence aktif)
- [ ] Memicu error tidak menampilkan stack trace

---

## 8. Update berikutnya

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
# Rilis yang menambah menu atau master referensi: aman diulang tiap deploy.
php artisan db:seed --class="Database\Seeders\MenuSeeder" --force
php artisan db:seed --class="Database\Seeders\ReferenceSeeder" --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

Backup database sebelum `migrate` pada rilis yang mengubah skema.

# HRIS Juru Parkir — Technical Specification Final
## MVP Budget ±Rp5 Jutaan

**Version:** 1.0 Final  
**Target:** HRIS sederhana untuk karyawan/juru parkir harian  
**Backend + Frontend:** Laravel 13 + Blade  
**Communication:** jQuery AJAX  
**Database:** MySQL 8  
**Map:** Leaflet.js  
**GPS:** Browser Geolocation API  
**Camera:** Browser Camera API  
**Geofence:** Haversine di Laravel Backend  
**Radius:** Maksimal 10 meter  
**GPS Accuracy:** Default 20 meter  
**Mobile:** Responsive Web Browser  
**Native App:** Tidak ada Flutter

---

# 1. Keputusan Teknologi Final

```text
Backend + Frontend : Laravel 13
Template            : Blade
CSS                 : Bootstrap 5
JavaScript          : JavaScript + jQuery
Communication       : AJAX
Database            : MySQL 8
Authentication      : Laravel Session Authentication
Map                 : Leaflet.js
GPS                 : Browser Geolocation API
Camera              : Browser Camera API
Geofence            : Haversine di Laravel Backend
File Upload         : Laravel Storage
Mobile              : Responsive Web Browser
```

Tidak menggunakan:

```text
❌ React.js
❌ TypeScript
❌ Flutter
❌ CodeIgniter 3
❌ SPA
❌ OData
❌ PostGIS
```

---

# 2. Tujuan Sistem

Sistem digunakan untuk mengelola:

1. User dan role
2. Data karyawan/juru parkir
3. Lokasi parkir
4. Master shift
5. Assignment karyawan
6. Generate shift roster
7. Absensi check-in
8. Absensi check-out
9. GPS
10. GPS accuracy
11. Leaflet map
12. Camera browser
13. Foto absensi
14. Geofence 10 meter
15. Attendance history
16. Monitoring attendance
17. Payroll karyawan harian
18. Potongan sederhana
19. Laporan absensi
20. Laporan payroll

---

# 3. Scope MVP Rp5 Jutaan

## Included

```text
AUTH
├── Login
├── Logout
├── Session Authentication
└── Role Authorization

MASTER
├── User
├── Role
├── Karyawan
├── Lokasi
├── Shift
└── Assignment

JADWAL
├── Shift Roster
└── Generate Shift

ABSENSI
├── Check-In
├── Check-Out
├── GPS
├── GPS Accuracy
├── Leaflet
├── Camera
├── Photo
├── Haversine
├── Geofence 10 Meter
├── Attendance History
└── Monitoring

PAYROLL
├── Payroll Period
├── Generate Payroll
├── Daily Rate
├── Working Days
├── Gross Salary
├── Deduction
└── Net Salary

REPORT
├── Attendance Report
└── Payroll Report
```

## Excluded

```text
❌ React.js
❌ Flutter
❌ Native Android/iOS
❌ Face Recognition
❌ Face Anti-Spoofing
❌ Offline Attendance
❌ Push Notification
❌ WhatsApp Gateway
❌ BPJS Engine
❌ PPh21 Engine
❌ Multi Company
❌ Payroll Approval
❌ Payment Gateway
❌ ERP Integration
❌ Advanced BI
❌ Advanced GPS Anti-Spoofing
```

---

# 4. Arsitektur Aplikasi

```text
                     BROWSER
                        |
             +----------+----------+
             |                     |
          Desktop               Smartphone
             |                     |
             +----------+----------+
                        |
                        v
                 Laravel 13
                        |
          +-------------+-------------+
          |                           |
       Blade                     jQuery AJAX
          |                           |
          +-------------+-------------+
                        |
                        v
                  Laravel Routes
                        |
                        v
                   Controllers
                        |
                        v
                 Service / Logic
                        |
                        v
                     Models
                        |
                        v
                    MySQL 8
                        |
                        v
                 Photo Storage
```

---

# 5. Pola Frontend

Frontend tidak menggunakan React.

Pola:

```text
Blade
  +
Bootstrap
  +
jQuery
  +
AJAX
  +
Leaflet
```

Contoh:

```text
Blade Page
    |
    +-- HTML
    +-- Bootstrap UI
    +-- jQuery
    +-- AJAX
    +-- Leaflet
    +-- Browser GPS
    +-- Browser Camera
```

AJAX digunakan untuk:

```text
GET data
POST data
PUT/POST update
DELETE data
Check-In
Check-Out
Generate Roster
Generate Payroll
Filter Report
```

---

# 6. Struktur Menu Final

```text
DASHBOARD

MASTER DATA
├── User
├── Role
├── Karyawan
├── Lokasi
├── Shift
└── Assignment

JADWAL
└── Shift Roster

ABSENSI
├── Monitoring Absensi
└── Riwayat Absensi

PAYROLL
├── Periode Payroll
└── Proses & Daftar Payroll

LAPORAN
├── Laporan Absensi
└── Laporan Payroll
```

---

# 7. Role

MVP menggunakan:

```text
ADMIN
HR
EMPLOYEE
```

## ADMIN

```text
Full Access
```

## HR

```text
Dashboard
Employee
Location
Shift
Assignment
Roster
Attendance
Payroll
Report
```

## EMPLOYEE

```text
Dashboard
Check-In
Check-Out
Attendance History
Profile
```

Employee tidak boleh mengubah:

```text
Employee Master
Location
Shift
Roster
Payroll
```

---

# 8. Dashboard

Dashboard dibuat sederhana.

```text
+----------------+----------------+
| TOTAL EMPLOYEE | HADIR HARI INI |
|      120       |       105      |
+----------------+----------------+

+----------------+----------------+
| TERLAMBAT      | BELUM ABSEN    |
|       8        |        7       |
+----------------+----------------+
```

Payroll:

```text
Payroll Aktif
Total Employee : 120
Working Days   : 2.450
Total Payroll  : Rp xxx.xxx.xxx
```

---

# 9. Master User

Field:

```text
id
name
email
password
employee_id
role_id
status
last_login_at
created_at
updated_at
```

Password wajib menggunakan Laravel Hash.

Tidak boleh:

```text
password plain text
```

---

# 10. Master Role

Field:

```text
id
role_code
role_name
status
created_at
updated_at
```

Role:

```text
ADMIN
HR
EMPLOYEE
```

---

# 11. Master Karyawan

Field utama:

```text
employee_code
nik
full_name
photo
gender
birth_place
birth_date
phone
address
employment_status
employment_type
join_date
daily_rate
status
```

Contoh:

```text
Employee Code : JP001
Nama          : Budi
Tipe          : DAILY
Status        : ACTIVE
Join Date     : 01-08-2026
Daily Rate    : Rp150.000
```

Untuk MVP:

```text
employment_type = DAILY
```

---

# 12. Master Lokasi

Field:

```text
id
location_code
location_name
address
latitude
longitude
radius_meter
gps_accuracy_limit
status
created_at
updated_at
```

Default:

```text
radius_meter = 10
gps_accuracy_limit = 20
```

Contoh:

```text
Location : Parkir Mall A
Latitude : -6.1234567
Longitude: 106.1234567
Radius   : 10 meter
Accuracy : 20 meter
```

---

# 13. Leaflet

Leaflet digunakan pada Master Lokasi untuk:

```text
1. Menampilkan peta
2. Menentukan titik lokasi
3. Drag marker
4. Menampilkan koordinat
5. Menampilkan radius 10 meter
```

Visual:

```text
                 LEAFLET

                   🔴
              Location Point
                   |
             ⭕ 10 Meter
                   |
                   🔵
                User
```

Leaflet bukan sumber keputusan keamanan.

Validasi final tetap:

```text
Laravel Backend
```

---

# 14. Master Shift

Sistem menggunakan 3 shift.

| Shift | Mulai | Selesai | Cross Day |
|---|---:|---:|---|
| Shift 1 | 06:00 | 14:00 | Tidak |
| Shift 2 | 14:00 | 22:00 | Tidak |
| Shift 3 | 22:00 | 06:00 | Ya |

Field:

```text
id
shift_code
shift_name
start_time
end_time
cross_day
late_tolerance_minutes
status
created_at
updated_at
```

---

# 15. Shift Roster

Master shift hanya menyimpan pola jam.

Jadwal aktual disimpan pada:

```text
shift_rosters
```

Field:

```text
id
employee_id
location_id
shift_id
roster_date
start_datetime
end_datetime
status
created_at
updated_at
```

---

# 16. Generate Shift

HR memilih:

```text
Employee : Budi
Location : Mall A
Period   : 01-08-2026 s/d 31-08-2026
Pattern  : Shift 1, Shift 2, Shift 3, OFF
```

Sistem menghasilkan:

```text
01 Aug → Shift 1
02 Aug → Shift 2
03 Aug → Shift 3
04 Aug → OFF
05 Aug → Shift 1
06 Aug → Shift 2
07 Aug → Shift 3
08 Aug → OFF
...
```

Pattern dapat berupa:

```text
1,2,3,OFF
```

atau:

```text
1,1,2,2,3,3,OFF
```

---

# 17. Shift Malam

Shift 3:

```text
Start : 12 Aug 2026 22:00
End   : 13 Aug 2026 06:00
```

Backend wajib menggunakan:

```text
start_datetime
end_datetime
```

bukan hanya:

```text
roster_date
```

Ini mencegah kesalahan pada absensi shift malam.

---

# 18. Assignment

Assignment menghubungkan:

```text
Employee
   +
Location
   +
Shift
```

Field:

```text
id
employee_id
location_id
shift_id
start_date
end_date
status
```

Validasi:

```text
Employee aktif
Location aktif
Shift aktif
Tidak ada assignment bentrok
```

---

# 19. Attendance

Modul:

```text
ATTENDANCE
├── Check-In
├── Check-Out
├── GPS
├── GPS Accuracy
├── Leaflet
├── Camera
├── Photo
├── Haversine
├── Geofence 10m
└── History
```

---

# 20. Halaman Check-In

Mobile responsive:

```text
+-----------------------------+
| ABSENSI MASUK               |
+-----------------------------+
|                             |
|        LEAFLET MAP          |
|                             |
|        🔵 USER             |
|        🔴 LOCATION         |
|        ⭕ 10 METER          |
|                             |
+-----------------------------+
| GPS Accuracy : 7.2 m        |
| Jarak        : 4.8 m        |
| Status       : VALID        |
+-----------------------------+
|                             |
|     [ AMBIL FOTO ]          |
|                             |
|     [ CHECK IN ]            |
+-----------------------------+
```

---

# 21. Browser Geolocation API

Frontend menggunakan:

```javascript
navigator.geolocation.getCurrentPosition()
```

Data yang didapat:

```json
{
  "latitude": -6.1234567,
  "longitude": 106.1234567,
  "accuracy": 7.2
}
```

Frontend mengirim data tersebut melalui AJAX.

Frontend tidak menentukan:

```text
distance
validation_status
```

---

# 22. GPS Accuracy

Default:

```text
20 meter
```

Rule:

```text
accuracy <= 20m
```

Jika:

```text
accuracy > 20m
```

maka:

```text
GPS kurang akurat.
Silakan coba kembali di area terbuka.
```

---

# 23. Geofence

Radius maksimal:

```text
10 meter
```

Rule:

```text
distance <= 10m = VALID
distance > 10m  = REJECT
```

Contoh:

| Distance | Result |
|---:|---|
| 2 m | VALID |
| 5 m | VALID |
| 9.9 m | VALID |
| 10 m | VALID |
| 10.1 m | REJECT |
| 25 m | REJECT |

---

# 24. Haversine

Backend Laravel menghitung jarak.

```text
User Latitude
User Longitude
       +
Location Latitude
Location Longitude
       ↓
   Haversine
       ↓
 Distance Meter
```

Pseudo:

```php
$distance = $this->calculateDistance(
    $userLatitude,
    $userLongitude,
    $locationLatitude,
    $locationLongitude
);

if ($distance > 10) {
    return reject();
}
```

Nilai `distance` dari frontend tidak dipercaya.

---

# 25. Camera

Browser Camera API:

```javascript
navigator.mediaDevices.getUserMedia({
    video: true
});
```

Fallback:

```html
<input
    type="file"
    accept="image/*"
    capture="user"
>
```

Workflow:

```text
Camera
  ↓
Capture
  ↓
Preview
  ↓
Compress
  ↓
AJAX Upload
```

---

# 26. Foto Attendance

Check-In:

```text
Foto wajib
```

Check-Out:

```text
Foto wajib
```

Allowed:

```text
image/jpeg
image/png
image/webp
```

Maximum:

```text
5 MB
```

Foto disimpan sebagai file menggunakan Laravel Storage.

Database hanya menyimpan:

```text
file_path
file_name
mime_type
file_size
```

Jangan menyimpan foto Base64 di MySQL.

---

# 27. Check-In Workflow

```text
Login
  ↓
Employee dari Session
  ↓
Cari Roster Hari Ini
  ↓
Cari Location
  ↓
Request GPS
  ↓
Validasi Accuracy
  ↓
Leaflet tampil
  ↓
AJAX Check-In
  ↓
Laravel
  ↓
Haversine
  ↓
Distance <= 10m?
  │
  ├── NO → Reject
  │
  └── YES
        ↓
     Validasi Foto
        ↓
     Duplicate Check
        ↓
     Save Attendance
        ↓
     Save Photo
        ↓
      SUCCESS
```

---

# 28. Check-Out Workflow

```text
Employee
  ↓
Cari Attendance aktif
  ↓
Request GPS
  ↓
Accuracy <= 20m
  ↓
Haversine
  ↓
Distance <= 10m
  ↓
Camera
  ↓
Photo
  ↓
Save Check-Out
  ↓
SUCCESS
```

---

# 29. AJAX Check-In

Contoh:

```javascript
const formData = new FormData();

formData.append('latitude', latitude);
formData.append('longitude', longitude);
formData.append('accuracy', accuracy);
formData.append('photo', photoBlob);

$.ajax({
    url: '/attendance/check-in',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(response) {
        if (response.success) {
            alert(response.message);
        }
    },
    error: function(xhr) {
        alert(
            xhr.responseJSON?.message ||
            'Check-in gagal'
        );
    }
});
```

---

# 30. AJAX Pattern

Semua modul menggunakan pola:

```text
Blade
 ↓
jQuery
 ↓
AJAX
 ↓
Laravel Route
 ↓
Controller
 ↓
Service
 ↓
Model
 ↓
MySQL
```

Contoh GET:

```javascript
$.ajax({
    url: '/employees',
    type: 'GET',
    success: function(response) {
        // render table
    }
});
```

Contoh POST:

```javascript
$.ajax({
    url: '/employees',
    type: 'POST',
    data: formData,
    success: function(response) {
        // success
    }
});
```

---

# 31. Attendance Table

```text
id
employee_id
roster_id
location_id
attendance_date

check_in_at
check_in_latitude
check_in_longitude
check_in_accuracy
check_in_distance
check_in_photo

check_out_at
check_out_latitude
check_out_longitude
check_out_accuracy
check_out_distance
check_out_photo

status
created_at
updated_at
```

Status:

```text
PRESENT
LATE
ABSENT
INCOMPLETE
```

---

# 32. Attendance History

Employee melihat:

```text
Tanggal
Shift
Lokasi
Check-In
Check-Out
Distance
Accuracy
Status
Foto
```

Contoh:

```text
12 Aug 2026
Shift 1
Parkir Mall A
06:03
14:02
4.8m
7.2m
PRESENT
```

---

# 33. Attendance Monitoring

HR melihat:

```text
Tanggal
Employee
Location
Shift
Check-In
Check-Out
Distance
Accuracy
Status
```

Filter:

```text
Tanggal
Employee
Location
Shift
Status
```

AJAX digunakan untuk filter tanpa reload penuh halaman.

---

# 34. Terlambat

Contoh:

```text
Shift Start : 06:00
Tolerance   : 15 menit
```

Rule:

```text
06:00 - 06:15 = ON TIME
> 06:15        = LATE
```

Formula:

```text
late_threshold =
shift_start + late_tolerance_minutes
```

---

# 35. Payroll Harian

Model:

```text
Valid Working Days
       ×
Daily Rate
       =
Gross Salary
       -
Deduction
       =
Net Salary
```

Contoh:

```text
25 hari × Rp150.000
= Rp3.750.000

Potongan = Rp200.000

Net Salary
= Rp3.550.000
```

---

# 36. Payroll Menu

Hanya:

```text
PAYROLL
├── Periode Payroll
└── Proses & Daftar Payroll
```

Tidak ada:

```text
❌ Approval Payroll
❌ Payment Gateway
```

---

# 37. Payroll Period

Field:

```text
id
period_code
period_name
start_date
end_date
status
created_at
closed_at
```

Status:

```text
OPEN
PROCESSED
CLOSED
```

---

# 38. Generate Payroll

```text
Pilih Periode
      ↓
Klik Generate Payroll
      ↓
Employee ACTIVE
      ↓
Attendance VALID
      ↓
Hitung Working Days
      ↓
× Daily Rate
      ↓
Deduction
      ↓
Net Salary
      ↓
Simpan Payroll
```

---

# 39. Working Days

Yang dihitung:

```text
PRESENT
LATE
```

Tidak dihitung:

```text
ABSENT
OFF
Rejected
Duplicate
```

Contoh:

```text
PRESENT = 20
LATE    = 5
ABSENT  = 2
OFF     = 4

Working Days = 25
```

---

# 40. Payroll Table

```text
id
period_id
employee_id
working_days
daily_rate
gross_salary
total_deduction
net_salary
status
created_at
updated_at
```

---

# 41. Payroll Detail

```text
id
payroll_id
detail_type
description
amount
created_at
updated_at
```

Contoh:

```text
DEDUCTION | Kasbon | 200000
DEDUCTION | Denda  | 50000
```

---

# 42. Potongan

MVP hanya menggunakan potongan manual.

Contoh:

```text
Kasbon
Denda
Potongan Lain
```

Tidak ada engine payroll kompleks.

---

# 43. Payroll UI

```text
+------------------------------------------------+
| PAYROLL AGUSTUS 2026                          |
+------------------------------------------------+
| Periode : Agustus 2026                        |
|                                                |
| [ GENERATE PAYROLL ]                           |
+------------------------------------------------+
| Employee | Hari | Rate | Gross | Potongan     |
+----------+------+------+-------+--------------+
| Budi     | 25   | 150K | 3.75M | 200K         |
| Andi     | 24   | 175K | 4.20M | 100K         |
| Joko     | 26   | 150K | 3.90M | 0            |
+------------------------------------------------+
| Total Gross : Rp11.850.000                    |
| Total Deduct: Rp300.000                       |
| Total Net   : Rp11.550.000                    |
+------------------------------------------------+
```

---

# 44. Payroll Close

Workflow:

```text
OPEN
 ↓
GENERATE
 ↓
PROCESSED
 ↓
CLOSE
```

Setelah `CLOSED`:

```text
Payroll tidak boleh dihitung ulang
secara normal.
```

Admin dapat melakukan reopen jika diperlukan.

---

# 45. Database Minimal

13 tabel utama:

```text
users
roles
employees
locations
shifts
assignments
shift_rosters
attendances
attendance_photos
payroll_periods
payrolls
payroll_details
audit_logs
```

---

# 46. Relationship

```text
roles
  |
  +---- users
           |
           +---- employees
                    |
                    +---- assignments
                    |       |
                    |       +---- locations
                    |       +---- shifts
                    |
                    +---- shift_rosters
                    |       |
                    |       +---- locations
                    |       +---- shifts
                    |
                    +---- attendances
                            |
                            +---- attendance_photos

payroll_periods
      |
      +---- payrolls
                |
                +---- employees
                |
                +---- payroll_details
```

---

# 47. Laravel Routes

## Authentication

```php
POST /login
POST /logout
GET  /profile
```

## Employee

```php
GET    /employees
POST   /employees
GET    /employees/{id}
PUT    /employees/{id}
DELETE /employees/{id}
```

## Location

```php
GET    /locations
POST   /locations
GET    /locations/{id}
PUT    /locations/{id}
DELETE /locations/{id}
```

## Shift

```php
GET    /shifts
POST   /shifts
GET    /shifts/{id}
PUT    /shifts/{id}
DELETE /shifts/{id}
```

## Roster

```php
GET  /rosters
POST /rosters/generate
GET  /rosters/{id}
PUT  /rosters/{id}
```

## Attendance

```php
GET  /attendance
POST /attendance/check-in
POST /attendance/check-out
GET  /attendance/history
GET  /attendance/monitoring
```

## Payroll

```php
GET  /payroll/periods
POST /payroll/periods
POST /payroll/{period}/generate
GET  /payroll/{period}
POST /payroll/{payroll}/deduction
POST /payroll/{period}/close
```

---

# 48. Laravel Controller Structure

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── EmployeeController.php
│   │   ├── LocationController.php
│   │   ├── ShiftController.php
│   │   ├── RosterController.php
│   │   ├── AttendanceController.php
│   │   ├── PayrollController.php
│   │   └── ReportController.php
│   │
│   └── Requests/
│       ├── EmployeeRequest.php
│       ├── LocationRequest.php
│       ├── AttendanceRequest.php
│       └── PayrollRequest.php
│
├── Models/
│   ├── User.php
│   ├── Role.php
│   ├── Employee.php
│   ├── Location.php
│   ├── Shift.php
│   ├── Assignment.php
│   ├── ShiftRoster.php
│   ├── Attendance.php
│   ├── AttendancePhoto.php
│   ├── PayrollPeriod.php
│   ├── Payroll.php
│   └── PayrollDetail.php
│
└── Services/
    ├── GeofenceService.php
    ├── AttendanceService.php
    ├── RosterService.php
    └── PayrollService.php
```

---

# 49. Blade Structure

```text
resources/views/
├── layouts/
│   ├── app.blade.php
│   ├── navbar.blade.php
│   └── sidebar.blade.php
│
├── dashboard/
│   └── index.blade.php
│
├── employees/
│   └── index.blade.php
│
├── locations/
│   └── index.blade.php
│
├── shifts/
│   └── index.blade.php
│
├── rosters/
│   └── index.blade.php
│
├── attendance/
│   ├── check-in.blade.php
│   ├── check-out.blade.php
│   ├── history.blade.php
│   └── monitoring.blade.php
│
├── payroll/
│   ├── periods.blade.php
│   └── index.blade.php
│
└── reports/
    ├── attendance.blade.php
    └── payroll.blade.php
```

---

# 50. JavaScript Structure

```text
public/js/
├── app.js
├── auth.js
├── employees.js
├── locations.js
├── shifts.js
├── rosters.js
├── attendance.js
├── payroll.js
└── reports.js
```

---

# 51. Attendance JavaScript

```text
attendance.js
├── getCurrentLocation()
├── initializeMap()
├── updateUserMarker()
├── calculateDisplayDistance()
├── openCamera()
├── capturePhoto()
├── submitCheckIn()
├── submitCheckOut()
└── showAttendanceResult()
```

Catatan:

```text
calculateDisplayDistance()
```

hanya untuk tampilan.

Keputusan valid/tidak tetap dilakukan Laravel.

---

# 52. Laravel GeofenceService

Konsep:

```php
class GeofenceService
{
    public function calculateDistance(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        // Haversine
    }

    public function validate(
        float $userLat,
        float $userLng,
        float $accuracy,
        Location $location
    ): array {
        // Accuracy validation
        // Distance validation
    }
}
```

---

# 53. Check-In Security

Backend harus melakukan:

```text
1. Authenticate user
2. Get employee from session
3. Get active roster
4. Get location from database
5. Validate GPS accuracy
6. Calculate Haversine
7. Validate <= 10m
8. Validate photo
9. Check duplicate
10. Save server timestamp
11. Save attendance
12. Save photo
```

---

# 54. Server Time

Timestamp attendance wajib menggunakan server:

```php
now()
```

Bukan:

```text
waktu dari browser
```

Karena waktu browser dapat dimanipulasi.

---

# 55. CSRF

Karena menggunakan Laravel Blade + Session + AJAX:

```text
CSRF Protection
```

harus aktif.

Blade:

```html
<meta name="csrf-token"
      content="{{ csrf_token() }}">
```

AJAX:

```javascript
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN':
            $('meta[name="csrf-token"]').attr('content')
    }
});
```

---

# 56. Security Minimum

```text
HTTPS
Session Authentication
CSRF
Authorization
Password Hash
Input Validation
SQL Binding / Eloquent
Upload MIME Validation
Upload Size Validation
Server Time
Login Rate Limiting
```

---

# 57. Attendance Acceptance Criteria

```text
AC-001 Login wajib.
AC-002 Employee berasal dari authenticated session.
AC-003 Roster aktif wajib.
AC-004 GPS wajib.
AC-005 Accuracy <= 20m.
AC-006 Backend menghitung distance.
AC-007 Distance <= 10m = valid.
AC-008 Distance > 10m = reject.
AC-009 Foto wajib.
AC-010 MIME dan size foto divalidasi.
AC-011 Server time digunakan.
AC-012 Duplicate attendance ditolak.
AC-013 GPS disimpan.
AC-014 Distance disimpan.
AC-015 Photo disimpan.
```

---

# 58. Payroll Acceptance Criteria

```text
PAY-001 Periode payroll dapat dibuat.
PAY-002 Employee ACTIVE dapat dihitung.
PAY-003 Working days berasal dari attendance valid.
PAY-004 PRESENT dihitung.
PAY-005 LATE dihitung.
PAY-006 ABSENT tidak dihitung.
PAY-007 OFF tidak dihitung.
PAY-008 Daily rate digunakan.
PAY-009 Gross = working_days × daily_rate.
PAY-010 Deduction dapat ditambahkan.
PAY-011 Net = gross - deduction.
PAY-012 Payroll dapat ditutup.
PAY-013 Payroll closed tidak dihitung ulang secara normal.
```

---

# 59. Testing

## Authentication

```text
Login benar
Login salah
Session expired
Role access
Logout
```

## Location

```text
Koordinat valid
Koordinat kosong
Radius 10m
Radius >10m
```

## GPS

```text
Accuracy 5m  → PASS
Accuracy 20m → PASS
Accuracy 21m → REJECT
```

## Geofence

```text
Distance 5m     → PASS
Distance 9.99m  → PASS
Distance 10m    → PASS
Distance 10.01m → REJECT
```

## Camera

```text
Camera tersedia
Camera ditolak
Foto kosong
Foto terlalu besar
MIME tidak valid
```

## Attendance

```text
Check-In normal
Check-In duplicate
Check-Out normal
Check-Out tanpa Check-In
Roster tidak ada
GPS error
Location tidak aktif
```

## Payroll

```text
25 × 150.000
= 3.750.000

3.750.000 - 200.000
= 3.550.000
```

---

# 60. Development Priority

## Phase 1 — Foundation

```text
1. Laravel 13 setup
2. MySQL 8
3. Authentication
4. Role
5. Layout Blade
6. Bootstrap
7. jQuery AJAX
```

## Phase 2 — Master

```text
8. Employee
9. Location
10. Leaflet
11. Shift
12. Assignment
```

## Phase 3 — Roster

```text
13. Shift Roster
14. Generate Pattern
15. Shift 1
16. Shift 2
17. Shift 3
18. Cross Day
```

## Phase 4 — Attendance

```text
19. Mobile Responsive UI
20. Browser GPS
21. GPS Accuracy
22. Leaflet
23. Camera
24. Photo Upload
25. Haversine
26. Geofence 10m
27. Check-In
28. Check-Out
29. Attendance History
30. Monitoring
```

## Phase 5 — Payroll

```text
31. Payroll Period
32. Generate Payroll
33. Working Days
34. Daily Rate
35. Gross Salary
36. Deduction
37. Net Salary
38. Payroll Detail
39. Close Period
```

## Phase 6 — Report

```text
40. Attendance Report
41. Payroll Report
42. Export sederhana
43. Testing
44. Deployment
```

---

# 61. Estimasi Pembagian Budget

Target development sekitar Rp5.000.000:

| Modul | Target |
|---|---:|
| Laravel setup + Auth + Role | Rp500.000 |
| Employee | Rp500.000 |
| Location + Leaflet | Rp400.000 |
| Shift + Assignment + Roster | Rp600.000 |
| Attendance GPS + Camera + Geofence | Rp1.200.000 |
| Attendance History + Monitoring | Rp300.000 |
| Payroll Harian | Rp800.000 |
| Report sederhana | Rp300.000 |
| Testing + Deployment | Rp400.000 |
| **Total** | **Rp5.000.000** |

> Angka tersebut adalah target pembagian scope proyek, bukan tarif pasar baku. Harga Rp5 juta hanya realistis jika scope dikunci dan fitur tambahan diperlakukan sebagai change request.

---

# 62. Batas Revisi

```text
UI Revision:
maksimal 2 putaran

Bug:
termasuk selama masih sesuai scope

New Feature:
Change Request

New Integration:
Change Request

Perubahan Payroll Formula:
Change Request

Perubahan Geofence:
Change Request
```

---

# 63. Deliverables

```text
1. Source Code Laravel 13
2. Database SQL
3. Migration
4. Seeder
5. API/AJAX endpoint documentation
6. Installation Guide
7. User Guide sederhana
8. Deployment
9. Bug fixing sesuai masa garansi yang disepakati
```

---

# 64. Final Workflow

```text
LOGIN
  ↓
DASHBOARD
  ↓
MASTER DATA
  ↓
EMPLOYEE
  ↓
LOCATION
  ↓
SHIFT
  ↓
ASSIGNMENT
  ↓
GENERATE ROSTER
  ↓
EMPLOYEE ABSEN
  ↓
BROWSER GPS
  ↓
GPS ACCURACY <= 20m
  ↓
LEAFLET
  ↓
AJAX
  ↓
LARAVEL
  ↓
HAVERSINE
  ↓
DISTANCE <= 10m
  ↓
CAMERA
  ↓
PHOTO
  ↓
CHECK-IN / CHECK-OUT
  ↓
ATTENDANCE HISTORY
  ↓
PAYROLL PERIOD
  ↓
GENERATE PAYROLL
  ↓
VALID WORKING DAYS
  ↓
× DAILY RATE
  ↓
GROSS SALARY
  ↓
- DEDUCTION
  ↓
NET SALARY
  ↓
CLOSE PAYROLL
```

---

# 65. Final Architecture

```text
                 USER
                  |
         Desktop / Smartphone
                  |
                  v
             Laravel 13
                  |
        +---------+---------+
        |                   |
      Blade               AJAX
        |                   |
        +---------+---------+
                  |
          Laravel Controller
                  |
          Service / Business
                  |
        +---------+---------+
        |         |         |
      Auth    Attendance   Payroll
        |         |         |
        +---------+---------+
                  |
               Eloquent
                  |
               MySQL 8
                  |
             Photo Storage
```

---

# 66. Final Business Rules

## Attendance

```text
Authenticated user
AND
Employee ACTIVE
AND
Roster aktif
AND
GPS tersedia
AND
GPS Accuracy <= 20 meter
AND
Haversine Distance <= 10 meter
AND
Foto valid
AND
Tidak duplicate
=
Attendance VALID
```

## Payroll

```text
PRESENT + LATE
        ↓
Working Days
        ↓
Working Days × Daily Rate
        ↓
Gross Salary
        ↓
Gross Salary - Deduction
        ↓
Net Salary
```

---

# 67. Kesimpulan

Untuk budget sekitar Rp5 juta, teknologi dan scope final yang digunakan adalah:

```text
Laravel 13
+
Blade
+
Bootstrap 5
+
jQuery AJAX
+
Leaflet.js
+
Browser Geolocation API
+
Browser Camera API
+
MySQL 8
```

Sistem mencakup seluruh alur utama:

```text
Karyawan
   ↓
Lokasi
   ↓
Shift
   ↓
Assignment
   ↓
Roster
   ↓
Absensi GPS + Foto
   ↓
Geofence 10 Meter
   ↓
Attendance
   ↓
Payroll Harian
```

Tidak ada React, Flutter, CI3, OData, atau PostGIS.

Fokus MVP adalah menghasilkan sistem operasional yang sederhana, stabil, dan realistis untuk budget sekitar Rp5 juta.

# Host: localhost:3307  (Version 5.5.5-10.4.32-MariaDB)
# Date: 2026-08-26 00:02:34
# Generator: MySQL-Front 6.0  (Build 2.20)


#
# Structure for table "attendance_photos"
#

DROP TABLE IF EXISTS `attendance_photos`;
CREATE TABLE `attendance_photos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attendance_id` bigint(20) unsigned NOT NULL,
  `photo_type` varchar(20) NOT NULL COMMENT 'CHECK_IN / CHECK_OUT',
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` bigint(20) unsigned NOT NULL COMMENT 'bytes',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_photos_attendance_id_photo_type_unique` (`attendance_id`,`photo_type`),
  CONSTRAINT `attendance_photos_attendance_id_foreign` FOREIGN KEY (`attendance_id`) REFERENCES `attendances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "attendance_photos"
#

INSERT INTO `attendance_photos` VALUES (3,61,'CHECK_IN','photos/2026/08/JP001_20260825_check_in_sjWiq0Al.png','JP001_20260825_check_in_sjWiq0Al.png','image/png',248032,'2026-08-25 04:09:13','2026-08-25 04:09:13'),(4,61,'CHECK_OUT','photos/2026/08/JP001_20260825_check_out_EjaEtuQl.png','JP001_20260825_check_out_EjaEtuQl.png','image/png',248032,'2026-08-25 04:12:11','2026-08-25 04:12:11'),(7,63,'CHECK_IN','photos/2026/08/ADM001_20260827_check_in_0FyRzPAh.jpg','ADM001_20260827_check_in_0FyRzPAh.jpg','image/jpeg',41425,'2026-08-25 20:07:59','2026-08-25 20:07:59'),(8,64,'CHECK_IN','photos/2026/08/JP001_20260826_check_in_1i99C3OI.png','JP001_20260826_check_in_1i99C3OI.png','image/png',382825,'2026-08-25 20:33:09','2026-08-25 20:33:09'),(9,64,'CHECK_OUT','photos/2026/08/JP001_20260826_check_out_rDGUv4iV.png','JP001_20260826_check_out_rDGUv4iV.png','image/png',382825,'2026-08-25 20:33:32','2026-08-25 20:33:32'),(12,63,'CHECK_OUT','photos/2026/08/ADM001_20260827_check_out_3e09a98b.jpg','ADM001_20260827_check_out_3e09a98b.jpg','image/jpeg',946,'2026-08-25 22:15:59','2026-08-25 22:15:59'),(20,62,'CHECK_IN','photos/2026/08/ADM001_20260826_check_in_03ff8041.jpg','ADM001_20260826_check_in_03ff8041.jpg','image/jpeg',946,'2026-08-25 22:24:37','2026-08-25 22:24:37'),(22,62,'CHECK_OUT','photos/2026/08/ADM001_20260826_check_out_fb628987.jpg','ADM001_20260826_check_out_fb628987.jpg','image/jpeg',946,'2026-08-25 22:24:59','2026-08-25 22:24:59');

#
# Structure for table "cache"
#

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "cache"
#

INSERT INTO `cache` VALUES ('hris_juru_parkir_cache_23987a11add3daa789455b06faf2ec539d678627','i:1;',1787606564),('hris_juru_parkir_cache_23987a11add3daa789455b06faf2ec539d678627:timer','i:1787606564;',1787606564),('hris_juru_parkir_cache_5c785c036466adea360111aa28563bfd556b5fba','i:1;',1787606080),('hris_juru_parkir_cache_5c785c036466adea360111aa28563bfd556b5fba:timer','i:1787606080;',1787606080),('hris_juru_parkir_cache_77de68daecd823babbb58edb1c8e14d7106e83bb','i:1;',1787606571),('hris_juru_parkir_cache_77de68daecd823babbb58edb1c8e14d7106e83bb:timer','i:1787606571;',1787606571),('hris_juru_parkir_cache_geocode:-6.1551,106.5741','s:51:\"Kuta Baru, Pasar Kemis, Kabupaten Tangerang, Banten\";',1787688081),('parkops_cache_23987a11add3daa789455b06faf2ec539d678627','i:1;',1787669990),('parkops_cache_23987a11add3daa789455b06faf2ec539d678627:timer','i:1787669990;',1787669990),('parkops_cache_356a192b7913b04c54574d18c28d46e6395428ab','i:1;',1787663348),('parkops_cache_356a192b7913b04c54574d18c28d46e6395428ab:timer','i:1787663348;',1787663348),('parkops_cache_5c785c036466adea360111aa28563bfd556b5fba','i:1;',1787667598),('parkops_cache_5c785c036466adea360111aa28563bfd556b5fba:timer','i:1787667598;',1787667598),('parkops_cache_77de68daecd823babbb58edb1c8e14d7106e83bb','i:2;',1787664839),('parkops_cache_77de68daecd823babbb58edb1c8e14d7106e83bb:timer','i:1787664839;',1787664839),('parkops_cache_api|admin@hris.test|127.0.0.1','i:1;',1787608587),('parkops_cache_api|admin@hris.test|127.0.0.1:timer','i:1787608587;',1787608587),('parkops_cache_geocode:-6.1551,106.5741','s:51:\"Kuta Baru, Pasar Kemis, Kabupaten Tangerang, Banten\";',1787695266),('parkops_cache_geocode:-6.1551,106.5743','s:51:\"Kuta Baru, Pasar Kemis, Kabupaten Tangerang, Banten\";',1787747516),('parkops_cache_geocode:-6.1944,106.8229','s:55:\"Jalan Mohammad Husni Thamrin, Gondangdia, Jakarta Pusat\";',1787747184),('parkops_cache_geocode:-6.1945,106.8230','s:25:\"Gondangdia, Jakarta Pusat\";',1787747197);

#
# Structure for table "cache_locks"
#

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "cache_locks"
#


#
# Structure for table "employee_statuses"
#

DROP TABLE IF EXISTS `employee_statuses`;
CREATE TABLE `employee_statuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL COMMENT 'Disimpan apa adanya di employees.status',
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'ACTIVE' COMMENT 'ACTIVE / INACTIVE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_statuses_code_unique` (`code`),
  KEY `employee_statuses_status_sort_order_index` (`status`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "employee_statuses"
#

INSERT INTO `employee_statuses` VALUES (1,'ACTIVE','Aktif','Karyawan aktif dan dapat dijadwalkan',10,1,'ACTIVE','2026-08-25 01:35:02','2026-08-25 01:35:02'),(2,'INACTIVE','Nonaktif','Sementara tidak dijadwalkan',20,1,'ACTIVE','2026-08-25 01:35:02','2026-08-25 01:35:02'),(3,'RESIGNED','Resign','Sudah tidak bekerja',30,1,'ACTIVE','2026-08-25 01:35:02','2026-08-25 01:35:02');

#
# Structure for table "employees"
#

DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_code` varchar(30) NOT NULL,
  `nik` varchar(30) DEFAULT NULL,
  `full_name` varchar(150) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `gender` varchar(1) DEFAULT NULL COMMENT 'L / P',
  `birth_place` varchar(100) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `employment_status` varchar(20) NOT NULL DEFAULT 'PERCOBAAN' COMMENT 'PERCOBAAN / KONTRAK / TETAP',
  `employment_type` varchar(20) NOT NULL DEFAULT 'DAILY' COMMENT 'MVP: DAILY only',
  `join_date` date DEFAULT NULL,
  `daily_rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'ACTIVE' COMMENT 'ACTIVE / INACTIVE / RESIGNED',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employees_employee_code_unique` (`employee_code`),
  UNIQUE KEY `employees_nik_unique` (`nik`),
  KEY `employees_status_index` (`status`),
  KEY `employees_full_name_index` (`full_name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "employees"
#

INSERT INTO `employees` VALUES (1,'JP001','3171010101900001','Budi Santoso',NULL,'L','Jakarta','1990-01-10','081200000001','Jakarta','TETAP','DAILY','2026-08-01',150000.00,'ACTIVE','2026-08-15 15:21:36','2026-08-15 15:21:36'),(2,'JP002','3171010202920002','Andi Prasetyo',NULL,'L','Jakarta','1991-02-11','081200000002','Jakarta','TETAP','DAILY','2026-08-01',175000.00,'ACTIVE','2026-08-15 15:21:36','2026-08-15 15:21:36'),(3,'JP003','3171010303940003','Joko Widodo',NULL,'L','Jakarta','1992-03-12','081200000003','Jakarta','KONTRAK','DAILY','2026-08-01',150000.00,'ACTIVE','2026-08-15 15:21:36','2026-08-15 15:21:36'),(4,'JP004','3171010404960004','Siti Nurhaliza',NULL,'P','Jakarta','1993-04-13','081200000004','Jakarta','KONTRAK','DAILY','2026-08-01',150000.00,'ACTIVE','2026-08-15 15:21:36','2026-08-15 15:21:36'),(5,'JP005','3171010505980005','Rina Marlina',NULL,'P','Jakarta','1994-05-14','081200000005','Jakarta','PERCOBAAN','DAILY','2026-08-01',160000.00,'ACTIVE','2026-08-15 15:21:36','2026-08-15 15:21:36'),(6,'JP006','3171010606000006','Agus Setiawan',NULL,'L','Jakarta','1995-06-15','081200000006','Jakarta','KONTRAK','DAILY','2026-08-01',150000.00,'ACTIVE','2026-08-15 15:21:36','2026-08-15 15:21:36'),(7,'ADM001',NULL,'Administrator',NULL,NULL,NULL,NULL,NULL,NULL,'TETAP','DAILY','2026-08-01',0.00,'ACTIVE','2026-08-15 15:58:56','2026-08-15 15:58:56'),(8,'dasd','32432','asdasd',NULL,'L','asdsa','2026-08-05','344345','asasd','KONTRAK','DAILY','2026-08-24',100000.00,'ACTIVE','2026-08-25 01:08:41','2026-08-25 01:08:41');

#
# Structure for table "employment_statuses"
#

DROP TABLE IF EXISTS `employment_statuses`;
CREATE TABLE `employment_statuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL COMMENT 'Disimpan apa adanya di employees.employment_status',
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'ACTIVE' COMMENT 'ACTIVE / INACTIVE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employment_statuses_code_unique` (`code`),
  KEY `employment_statuses_status_sort_order_index` (`status`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "employment_statuses"
#

INSERT INTO `employment_statuses` VALUES (1,'PERCOBAAN','Percobaan','Masa percobaan sebelum diangkat',10,0,'ACTIVE','2026-08-25 01:35:02','2026-08-25 01:35:02'),(2,'KONTRAK','Kontrak','Perjanjian kerja waktu tertentu',20,0,'ACTIVE','2026-08-25 01:35:02','2026-08-25 01:35:02'),(3,'TETAP','Tetap','Karyawan tetap',30,0,'ACTIVE','2026-08-25 01:35:02','2026-08-25 01:35:02');

#
# Structure for table "employment_types"
#

DROP TABLE IF EXISTS `employment_types`;
CREATE TABLE `employment_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL COMMENT 'Disimpan apa adanya di employees.employment_type',
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'ACTIVE' COMMENT 'ACTIVE / INACTIVE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employment_types_code_unique` (`code`),
  KEY `employment_types_status_sort_order_index` (`status`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "employment_types"
#

INSERT INTO `employment_types` VALUES (1,'DAILY','Harian','Dibayar per hari kerja berdasarkan upah harian',10,0,'ACTIVE','2026-08-25 01:35:02','2026-08-25 01:35:02');

#
# Structure for table "failed_jobs"
#

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "failed_jobs"
#


#
# Structure for table "job_batches"
#

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "job_batches"
#


#
# Structure for table "jobs"
#

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "jobs"
#


#
# Structure for table "locations"
#

DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_code` varchar(30) NOT NULL,
  `location_name` varchar(150) NOT NULL,
  `address` text DEFAULT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `radius_meter` smallint(5) unsigned NOT NULL DEFAULT 10,
  `gps_accuracy_limit` smallint(5) unsigned NOT NULL DEFAULT 20,
  `status` varchar(20) NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locations_location_code_unique` (`location_code`),
  KEY `locations_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "locations"
#

INSERT INTO `locations` VALUES (1,'LOC001','Parkir Mall A','Jl. M.H. Thamrin No. 1, Jakarta Pusat',-6.1944000,106.8229000,10,20,'ACTIVE','2026-08-15 15:21:36','2026-08-15 15:21:36'),(2,'LOC002','Parkir Stasiun B','Jl. Stasiun Gambir, Jakarta Pusat',-6.1766000,106.8306000,10,20,'ACTIVE','2026-08-15 15:21:36','2026-08-15 15:21:36'),(3,'LOC003','Parkir Rumah Sakit C','Jl. Diponegoro No. 71, Jakarta Pusat',-6.1980000,106.8380000,10,20,'ACTIVE','2026-08-15 15:21:36','2026-08-15 15:21:36'),(4,'LOC999','Lokasi Uji (Testing)','Titik uji coba â€” jangan dipakai di produksi',-6.1944000,106.8229000,65535,65535,'ACTIVE','2026-08-15 16:12:49','2026-08-15 16:12:49');

#
# Structure for table "menus"
#

DROP TABLE IF EXISTS `menus`;
CREATE TABLE `menus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `menu_code` varchar(50) NOT NULL,
  `menu_name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `group_name` varchar(50) DEFAULT NULL COMMENT 'Sidebar heading; null = ungrouped top block',
  `route_name` varchar(100) NOT NULL,
  `route_patterns` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `requires_employee` tinyint(1) NOT NULL DEFAULT 0,
  `is_action` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Governs routes but renders no sidebar link',
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'ACTIVE' COMMENT 'ACTIVE / INACTIVE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menus_menu_code_unique` (`menu_code`),
  KEY `menus_status_sort_order_index` (`status`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "menus"
#

INSERT INTO `menus` VALUES (1,'dashboard','Dashboard','speedometer2',NULL,'dashboard','[\"dashboard\"]',0,0,0,10,'ACTIVE','2026-08-15 16:36:16','2026-08-15 16:36:16'),(2,'attendance','Absensi Saya','geo-alt',NULL,'attendance.index','[\"attendance.index\",\"attendance.check-in\",\"attendance.check-out\",\"attendance.geocode\",\"attendance.show\"]',1,0,0,20,'ACTIVE','2026-08-15 16:36:17','2026-08-15 16:46:51'),(3,'users','User','person-gear','Master Data','users.index','[\"users.*\"]',0,0,1,30,'ACTIVE','2026-08-15 16:36:17','2026-08-15 16:36:17'),(4,'roles','Role','shield-lock','Master Data','roles.index','[\"roles.*\"]',0,0,1,40,'ACTIVE','2026-08-15 16:36:17','2026-08-15 16:36:17'),(5,'employees','Karyawan','people','Master Data','employees.index','[\"employees.*\"]',0,0,0,50,'ACTIVE','2026-08-15 16:36:17','2026-08-15 16:36:17'),(6,'locations','Lokasi','pin-map','Master Data','locations.index','[\"locations.*\"]',0,0,0,90,'ACTIVE','2026-08-15 16:36:17','2026-08-25 01:35:03'),(7,'shifts','Shift','clock-history','Master Data','shifts.index','[\"shifts.*\"]',0,0,0,100,'ACTIVE','2026-08-15 16:36:17','2026-08-25 01:35:03'),(8,'assignments','Assignment','diagram-3','Master Data','assignments.index','[\"assignments.*\"]',0,0,0,110,'ACTIVE','2026-08-15 16:36:17','2026-08-25 01:35:03'),(9,'rosters','Shift Roster','calendar-week','Jadwal','rosters.index','[\"rosters.*\"]',0,0,0,120,'ACTIVE','2026-08-15 16:36:17','2026-08-25 01:35:03'),(10,'attendance_monitoring','Monitoring Absensi','activity','Absensi','attendance.monitoring','[\"attendance.monitoring\"]',0,0,0,130,'ACTIVE','2026-08-15 16:36:17','2026-08-25 01:35:03'),(11,'attendance_history','Riwayat Absensi','journal-text','Absensi','attendance.history','[\"attendance.history\"]',0,0,0,140,'ACTIVE','2026-08-15 16:36:17','2026-08-25 01:35:03'),(12,'payroll_periods','Periode Payroll','calendar2-range','Payroll','payroll.periods','[\"payroll.periods\",\"payroll.periods.*\"]',0,0,0,150,'ACTIVE','2026-08-15 16:36:17','2026-08-25 01:35:03'),(13,'payroll','Proses & Daftar Payroll','cash-stack','Payroll','payroll.index','[\"payroll.index\",\"payroll.show\",\"payroll.generate\",\"payroll.close\",\"payroll.reopen\",\"payroll.deduction\",\"payroll.detail.*\"]',0,0,0,160,'ACTIVE','2026-08-15 16:36:17','2026-08-25 01:35:03'),(14,'reports_attendance','Laporan Absensi','file-earmark-bar-graph','Laporan','reports.attendance','[\"reports.attendance\",\"reports.attendance.export\"]',0,0,0,180,'ACTIVE','2026-08-15 16:36:17','2026-08-25 02:43:57'),(15,'reports_payroll','Laporan Payroll','file-earmark-spreadsheet','Laporan','reports.payroll','[\"reports.payroll\",\"reports.payroll.export\"]',0,0,0,190,'ACTIVE','2026-08-15 16:36:17','2026-08-25 02:43:57'),(16,'employment_statuses','Status Kepegawaian','patch-check','Master Data','employment-statuses.index','[\"employment-statuses.*\"]',0,0,0,60,'ACTIVE','2026-08-25 01:35:03','2026-08-25 01:35:03'),(17,'employment_types','Tipe Kepegawaian','briefcase','Master Data','employment-types.index','[\"employment-types.*\"]',0,0,0,70,'ACTIVE','2026-08-25 01:35:03','2026-08-25 01:35:03'),(18,'employee_statuses','Status Karyawan','toggles','Master Data','employee-statuses.index','[\"employee-statuses.*\"]',0,0,0,80,'ACTIVE','2026-08-25 01:35:03','2026-08-25 01:35:03'),(19,'payroll_slip','Cetak Slip Gaji','printer','Payroll','payroll.slip','[\"payroll.slip\"]',0,1,0,170,'ACTIVE','2026-08-25 02:43:57','2026-08-25 02:43:57');

#
# Structure for table "migrations"
#

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "migrations"
#

INSERT INTO `migrations` VALUES (1,'0001_01_01_000001_create_cache_table',1),(2,'0001_01_01_000002_create_jobs_table',1),(3,'2026_01_01_000001_create_roles_table',1),(4,'2026_01_01_000002_create_employees_table',1),(5,'2026_01_01_000003_create_users_table',1),(6,'2026_01_01_000004_create_locations_table',1),(7,'2026_01_01_000005_create_shifts_table',1),(8,'2026_01_01_000006_create_assignments_table',1),(9,'2026_01_01_000007_create_shift_rosters_table',1),(10,'2026_01_01_000008_create_attendances_table',1),(11,'2026_01_01_000009_create_attendance_photos_table',1),(12,'2026_01_01_000010_create_payroll_periods_table',1),(13,'2026_01_01_000011_create_payrolls_table',1),(14,'2026_01_01_000012_create_payroll_details_table',1),(15,'2026_01_01_000013_create_audit_logs_table',1),(16,'2026_08_14_000001_add_geocoded_address_to_attendances_table',1),(17,'2026_08_16_000001_create_menus_table',2),(18,'2026_08_16_000002_create_menu_role_table',2),(19,'2026_08_25_000001_add_unique_employee_id_to_users_table',3),(20,'2026_08_25_000002_create_employment_statuses_table',4),(21,'2026_08_25_000003_create_employment_types_table',4),(22,'2026_08_25_000004_create_employee_statuses_table',4),(23,'2026_08_25_020614_create_personal_access_tokens_table',5),(24,'2026_08_25_000002_add_is_action_to_menus_table',6),(25,'2026_08_25_060001_add_actions_to_menu_role_table',7),(26,'2026_08_25_060000_drop_json_check_constraints_for_mariadb',8);

#
# Structure for table "password_reset_tokens"
#

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "password_reset_tokens"
#


#
# Structure for table "payroll_periods"
#

DROP TABLE IF EXISTS `payroll_periods`;
CREATE TABLE `payroll_periods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `period_code` varchar(30) NOT NULL,
  `period_name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'OPEN' COMMENT 'OPEN / PROCESSED / CLOSED',
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_periods_period_code_unique` (`period_code`),
  KEY `payroll_periods_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "payroll_periods"
#

INSERT INTO `payroll_periods` VALUES (1,'2026-08','Agustus 2026','2026-08-01','2026-08-31','CLOSED','2026-08-25 20:24:59','2026-08-15 15:21:41','2026-08-25 20:24:59'),(2,'2026-09','September 2026','2026-09-01','2026-09-30','PROCESSED',NULL,'2026-08-25 20:10:38','2026-08-25 20:10:48');

#
# Structure for table "payrolls"
#

DROP TABLE IF EXISTS `payrolls`;
CREATE TABLE `payrolls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `period_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `present_days` smallint(5) unsigned NOT NULL DEFAULT 0,
  `late_days` smallint(5) unsigned NOT NULL DEFAULT 0,
  `working_days` smallint(5) unsigned NOT NULL DEFAULT 0,
  `daily_rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `gross_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_deduction` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'DRAFT' COMMENT 'DRAFT / FINAL',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payrolls_period_id_employee_id_unique` (`period_id`,`employee_id`),
  KEY `payrolls_employee_id_foreign` (`employee_id`),
  CONSTRAINT `payrolls_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payrolls_period_id_foreign` FOREIGN KEY (`period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "payrolls"
#

INSERT INTO `payrolls` VALUES (1,1,7,1,0,1,0.00,0.00,0.00,0.00,'FINAL','2026-08-25 02:37:50','2026-08-25 20:24:59'),(2,1,8,0,0,0,100000.00,0.00,0.00,0.00,'FINAL','2026-08-25 02:37:50','2026-08-25 20:24:59'),(3,1,1,1,0,1,150000.00,150000.00,0.00,150000.00,'FINAL','2026-08-25 02:37:50','2026-08-25 20:24:59'),(4,1,2,0,0,0,175000.00,0.00,0.00,0.00,'FINAL','2026-08-25 02:37:50','2026-08-25 20:24:59'),(5,1,3,0,0,0,150000.00,0.00,0.00,0.00,'FINAL','2026-08-25 02:37:50','2026-08-25 20:24:59'),(6,1,4,0,0,0,150000.00,0.00,0.00,0.00,'FINAL','2026-08-25 02:37:50','2026-08-25 20:24:59'),(7,1,5,0,0,0,160000.00,0.00,0.00,0.00,'FINAL','2026-08-25 02:37:50','2026-08-25 20:24:59'),(8,1,6,0,0,0,150000.00,0.00,0.00,0.00,'FINAL','2026-08-25 02:37:50','2026-08-25 20:24:59'),(9,2,7,0,0,0,0.00,0.00,0.00,0.00,'DRAFT','2026-08-25 20:10:48','2026-08-25 20:10:48'),(10,2,8,0,0,0,100000.00,0.00,0.00,0.00,'DRAFT','2026-08-25 20:10:48','2026-08-25 20:10:48'),(11,2,1,0,0,0,150000.00,0.00,0.00,0.00,'DRAFT','2026-08-25 20:10:48','2026-08-25 20:10:48'),(12,2,2,0,0,0,175000.00,0.00,0.00,0.00,'DRAFT','2026-08-25 20:10:48','2026-08-25 20:10:48'),(13,2,3,0,0,0,150000.00,0.00,0.00,0.00,'DRAFT','2026-08-25 20:10:48','2026-08-25 20:10:48'),(14,2,4,0,0,0,150000.00,0.00,0.00,0.00,'DRAFT','2026-08-25 20:10:48','2026-08-25 20:10:48'),(15,2,5,0,0,0,160000.00,0.00,0.00,0.00,'DRAFT','2026-08-25 20:10:48','2026-08-25 20:10:48'),(16,2,6,0,0,0,150000.00,0.00,0.00,0.00,'DRAFT','2026-08-25 20:10:48','2026-08-25 20:10:48');

#
# Structure for table "payroll_details"
#

DROP TABLE IF EXISTS `payroll_details`;
CREATE TABLE `payroll_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payroll_id` bigint(20) unsigned NOT NULL,
  `detail_type` varchar(20) NOT NULL DEFAULT 'DEDUCTION',
  `description` varchar(150) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_details_payroll_id_detail_type_index` (`payroll_id`,`detail_type`),
  CONSTRAINT `payroll_details_payroll_id_foreign` FOREIGN KEY (`payroll_id`) REFERENCES `payrolls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "payroll_details"
#


#
# Structure for table "personal_access_tokens"
#

DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "personal_access_tokens"
#

INSERT INTO `personal_access_tokens` VALUES (11,'App\\Models\\User',3,'diag','1281e0cb5dc240975f938552bdcfa9bf590f90b5571b6191d776b889a3d56dd1','[\"*\"]','2026-08-25 04:13:42','2026-09-24 04:13:40','2026-08-25 04:13:40','2026-08-25 04:13:42'),(17,'App\\Models\\User',3,'Pixel 8 (probe)','946ad7943051cd2af0affb29185b00772f89e79400eb3f0418b568037ce08764','[\"*\"]','2026-08-25 05:58:58','2026-09-24 05:58:31','2026-08-25 05:58:31','2026-08-25 05:58:58'),(18,'App\\Models\\User',3,'chrome-web','c3dc97bdc8922ee009d73661b25bca2e8c09671b2a015fbab62596de7c63b2fc','[\"*\"]',NULL,'2026-09-24 06:14:04','2026-08-25 06:14:04','2026-08-25 06:14:04'),(19,'App\\Models\\User',3,'android','54bfe69be99d0d9666f7505ad2e3ec3198c0380fd996c5e5564f960ee1a58e76','[\"*\"]','2026-08-25 06:28:38','2026-09-24 06:21:29','2026-08-25 06:21:29','2026-08-25 06:28:38'),(21,'App\\Models\\User',3,'diag-mobile','843b0fa344066efd141ec54f53ebf65fdda8e55a67ea7473db51c7bae285c0f0','[\"*\"]',NULL,'2026-09-24 21:58:51','2026-08-25 21:58:51','2026-08-25 21:58:51');

#
# Structure for table "roles"
#

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_code` varchar(30) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_role_code_unique` (`role_code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "roles"
#

INSERT INTO `roles` VALUES (1,'ADMIN','Administrator','ACTIVE','2026-08-15 15:21:36','2026-08-15 15:21:36'),(2,'HR','Human Resource','ACTIVE','2026-08-15 15:21:36','2026-08-15 15:21:36'),(3,'EMPLOYEE','Karyawan','ACTIVE','2026-08-15 15:21:36','2026-08-15 15:21:36'),(4,'ADMIN2','hanya admin','ACTIVE','2026-08-25 02:41:53','2026-08-25 02:41:53');

#
# Structure for table "menu_role"
#

DROP TABLE IF EXISTS `menu_role`;
CREATE TABLE `menu_role` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menu_role_menu_id_role_id_unique` (`menu_id`,`role_id`),
  KEY `menu_role_role_id_foreign` (`role_id`),
  CONSTRAINT `menu_role_menu_id_foreign` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `menu_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "menu_role"
#

INSERT INTO `menu_role` VALUES (1,1,1,'[\"read\"]',NULL,'2026-08-25 19:38:40'),(2,1,2,NULL,NULL,NULL),(3,1,3,NULL,NULL,NULL),(4,2,1,'[\"read\",\"create\"]',NULL,'2026-08-25 19:38:40'),(5,2,2,NULL,NULL,NULL),(6,2,3,NULL,NULL,NULL),(7,3,1,'[\"read\",\"create\",\"update\",\"delete\"]',NULL,'2026-08-25 19:38:40'),(8,4,1,'[\"read\",\"create\",\"update\",\"delete\"]',NULL,'2026-08-25 19:38:40'),(9,5,1,'[\"read\",\"create\",\"update\",\"delete\"]',NULL,'2026-08-25 19:38:40'),(11,6,1,'[\"read\",\"create\",\"update\",\"delete\"]',NULL,'2026-08-25 19:38:40'),(12,6,2,NULL,NULL,NULL),(13,7,1,'[\"read\",\"create\",\"update\",\"delete\"]',NULL,'2026-08-25 19:38:40'),(14,7,2,NULL,NULL,NULL),(15,8,1,'[\"read\",\"create\",\"update\",\"delete\"]',NULL,'2026-08-25 19:38:40'),(16,8,2,NULL,NULL,NULL),(17,9,1,'[\"read\",\"create\",\"update\",\"delete\"]',NULL,'2026-08-25 19:38:40'),(18,9,2,NULL,NULL,NULL),(19,10,1,'[\"read\"]',NULL,'2026-08-25 19:38:40'),(20,10,2,NULL,NULL,NULL),(21,11,1,'[\"read\"]',NULL,'2026-08-25 19:38:40'),(22,11,2,NULL,NULL,NULL),(23,11,3,NULL,NULL,NULL),(24,12,1,'[\"read\",\"create\",\"update\",\"delete\"]',NULL,'2026-08-25 19:38:40'),(25,12,2,NULL,NULL,NULL),(26,13,1,'[\"read\",\"create\",\"delete\"]',NULL,'2026-08-25 19:38:40'),(27,13,2,NULL,NULL,NULL),(28,14,1,'[\"read\"]',NULL,'2026-08-25 19:38:40'),(29,14,2,NULL,NULL,NULL),(30,15,1,'[\"read\"]',NULL,'2026-08-25 19:38:40'),(31,15,2,NULL,NULL,NULL),(32,5,2,NULL,NULL,NULL),(33,16,1,'[\"read\",\"create\",\"update\",\"delete\"]',NULL,'2026-08-25 19:38:40'),(34,16,2,NULL,NULL,NULL),(35,17,1,'[\"read\",\"create\",\"update\",\"delete\"]',NULL,'2026-08-25 19:38:40'),(36,17,2,NULL,NULL,NULL),(37,18,1,'[\"read\",\"create\",\"update\",\"delete\"]',NULL,'2026-08-25 19:38:40'),(38,18,2,NULL,NULL,NULL),(39,19,1,'[\"read\"]',NULL,'2026-08-25 19:38:40'),(40,19,2,NULL,NULL,NULL);

#
# Structure for table "sessions"
#

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "sessions"
#

INSERT INTO `sessions` VALUES ('BSwP14UHrXHoNsQECd0XCKd6ixlIq1X6bL2H68K8',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQ2hKd0Z0T3dtZEgwSnF2ODVUcGxPZ3c5ejFCVnFGak1YcWZNYk5PUyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1787659955),('BWkNKOs5YOgmRcTvvS1fxDThA8kVzmNt4zGvG5tC',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUGNOVTAyTXNMZmdGb0FxWnN4SWRnM0ZmMm5mYjlUMFZPa0NuRlQ5aCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1787659955),('ezQ3Gqr9SuMB6i3CyOVhaObzDVsQZPY4nr9VYfBr',1,'192.168.1.5','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQkN2MDFDdzJNTjl1NEhJZk1STWY3b1R5Y0hrc0FLNURIb2U1Ynl3YiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly8xOTIuMTY4LjEuMTE6ODAwMC9hdHRlbmRhbmNlIjtzOjU6InJvdXRlIjtzOjE2OiJhdHRlbmRhbmNlLmluZGV4Ijt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9',1787664089),('iGhsebTkNI69Su9CxsFwEn5dTxnftYWTTy9R9q23',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRnA5aVZvOXdqR2hkcHk1djdWdE0wblZ5YURFOXhYenVXNThHMHRzVSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fX0=',1787676632),('MW4YrQtw0y69Ikq3ply2wnHrsqRC6zAoLe3vAa51',1,'127.0.0.1','curl/8.18.0','YTo0OntzOjY6Il90b2tlbiI7czo0MDoid3ZOUGI1TGhqQXl3RU9meWtaR2ZKVW9TQUFaRFIxOVhVYXVBaGU2bSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzI6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hdHRlbmRhbmNlIjtzOjU6InJvdXRlIjtzOjE2OiJhdHRlbmRhbmNlLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9',1787660797),('rzlucFfSpNzYr8lWOBvaIddP7RriHa9sp1RL94Ti',1,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiUkIyTzZzdkNWOVZtcVRrUWM5UFc3U09GOGUwQWFPVVQ1UnE4OU53NyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9lbXBsb3llZXMiO3M6NToicm91dGUiO3M6MTU6ImVtcGxveWVlcy5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==',1787660157),('sTf68iHGPjnWXCZMCucTEhmfR9l5ZLoNQd12BsFj',1,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSlFJdXhkRnJzb1pDakU1VHRIQWNVVzVUWEJHbUJ2SWplMmVCMEdLSCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==',1787660760),('uHTxPVI2y51kCh94kvQrT1SX5Gqr8Qgqq0PCQhuP',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.134.0 Chrome/148.0.7778.280 Electron/42.8.1 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSUhpSzZodlVTQUJEZlhRMDEzcHI1alppTUpYSm9qckNNTVZXWVFRdSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjE6e3M6ODoiaW50ZW5kZWQiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYXNoYm9hcmQiO319',1787659961),('yNzNRUrkIGf85mL1mhRbByPEVjxXYjd4Qp7pXE5P',1,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','YTo0OntzOjY6Il90b2tlbiI7czo0MDoidllNeGhndHFReFNySUl1dVM5bDdzbkFWQU9DSGViYk43eXhiVUNvUyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9yb2xlcyI7czo1OiJyb3V0ZSI7czoxMToicm9sZXMuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=',1787659963),('yX9l0TFHnjtkdcBgQGzyQnIoW8Pc8i7IiEVhfTDi',NULL,'192.168.1.5','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36','YToyOntzOjY6Il90b2tlbiI7czo0MDoiaUdSdUFKbHF1VkpXOTBCU0FhV1NkemtRVndGQTNLZWs5ajBVUGF4cCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1787664207),('yYp2Vs0mwtE2BUcxbDRf3Orkk8leEeCyRT5I06cO',1,'127.0.0.1','curl/8.18.0','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiWGRha0RqY2xJUFZraFEwVEp0ZkMyeE9qNHgxZVo4ZzFPbXVmN25zdCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9yb2xlcyI7czo1OiJyb3V0ZSI7czoxMToicm9sZXMuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=',1787661103);

#
# Structure for table "shifts"
#

DROP TABLE IF EXISTS `shifts`;
CREATE TABLE `shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shift_code` varchar(30) NOT NULL,
  `shift_name` varchar(100) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `cross_day` tinyint(1) NOT NULL DEFAULT 0,
  `late_tolerance_minutes` smallint(5) unsigned NOT NULL DEFAULT 15,
  `status` varchar(20) NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shifts_shift_code_unique` (`shift_code`),
  KEY `shifts_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "shifts"
#

INSERT INTO `shifts` VALUES (1,'S1','Shift 1 Pagi','06:00:00','14:00:00',0,15,'ACTIVE','2026-08-15 15:21:36','2026-08-15 15:21:36'),(2,'S2','Shift 2 Siang','14:00:00','22:00:00',0,15,'ACTIVE','2026-08-15 15:21:36','2026-08-15 15:21:36'),(3,'S3','Shift 3 Malam','22:00:00','06:00:00',1,15,'ACTIVE','2026-08-15 15:21:36','2026-08-15 15:21:36');

#
# Structure for table "shift_rosters"
#

DROP TABLE IF EXISTS `shift_rosters`;
CREATE TABLE `shift_rosters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `location_id` bigint(20) unsigned NOT NULL,
  `shift_id` bigint(20) unsigned DEFAULT NULL,
  `roster_date` date NOT NULL,
  `start_datetime` datetime DEFAULT NULL,
  `end_datetime` datetime DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'SCHEDULED' COMMENT 'SCHEDULED / OFF / CANCELLED',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shift_rosters_employee_id_roster_date_unique` (`employee_id`,`roster_date`),
  KEY `shift_rosters_location_id_foreign` (`location_id`),
  KEY `shift_rosters_shift_id_foreign` (`shift_id`),
  KEY `shift_rosters_roster_date_location_id_index` (`roster_date`,`location_id`),
  KEY `shift_rosters_window_index` (`employee_id`,`start_datetime`,`end_datetime`),
  CONSTRAINT `shift_rosters_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_rosters_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `shift_rosters_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=386 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "shift_rosters"
#

INSERT INTO `shift_rosters` VALUES (218,1,4,1,'2026-08-24','2026-08-24 06:00:00','2026-08-24 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:12:07'),(219,1,4,1,'2026-08-25','2026-08-25 06:00:00','2026-08-25 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(220,1,4,1,'2026-08-26','2026-08-26 06:00:00','2026-08-26 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(221,1,4,1,'2026-08-27','2026-08-27 06:00:00','2026-08-27 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(222,1,4,1,'2026-08-28','2026-08-28 06:00:00','2026-08-28 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(223,1,4,1,'2026-08-29','2026-08-29 06:00:00','2026-08-29 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(224,1,4,NULL,'2026-08-30',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(225,1,4,3,'2026-08-31','2026-08-31 22:00:00','2026-09-01 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(226,1,4,3,'2026-09-01','2026-09-01 22:00:00','2026-09-02 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(227,1,4,3,'2026-09-02','2026-09-02 22:00:00','2026-09-03 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(228,1,4,3,'2026-09-03','2026-09-03 22:00:00','2026-09-04 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(229,1,4,3,'2026-09-04','2026-09-04 22:00:00','2026-09-05 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(230,1,4,NULL,'2026-09-05',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(231,1,4,2,'2026-09-06','2026-09-06 14:00:00','2026-09-06 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(232,1,4,2,'2026-09-07','2026-09-07 14:00:00','2026-09-07 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(233,1,4,2,'2026-09-08','2026-09-08 14:00:00','2026-09-08 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(234,1,4,2,'2026-09-09','2026-09-09 14:00:00','2026-09-09 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(235,1,4,2,'2026-09-10','2026-09-10 14:00:00','2026-09-10 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(236,1,4,NULL,'2026-09-11',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(237,1,4,1,'2026-09-12','2026-09-12 06:00:00','2026-09-12 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(238,1,4,1,'2026-09-13','2026-09-13 06:00:00','2026-09-13 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(239,1,4,1,'2026-09-14','2026-09-14 06:00:00','2026-09-14 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(240,1,4,1,'2026-09-15','2026-09-15 06:00:00','2026-09-15 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(241,1,4,1,'2026-09-16','2026-09-16 06:00:00','2026-09-16 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(242,1,4,NULL,'2026-09-17',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(243,1,4,3,'2026-09-18','2026-09-18 22:00:00','2026-09-19 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(244,1,4,3,'2026-09-19','2026-09-19 22:00:00','2026-09-20 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(245,1,4,3,'2026-09-20','2026-09-20 22:00:00','2026-09-21 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(246,1,4,3,'2026-09-21','2026-09-21 22:00:00','2026-09-22 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(247,1,4,3,'2026-09-22','2026-09-22 22:00:00','2026-09-23 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(248,1,4,NULL,'2026-09-23',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(249,1,4,2,'2026-09-24','2026-09-24 14:00:00','2026-09-24 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(250,1,4,2,'2026-09-25','2026-09-25 14:00:00','2026-09-25 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(251,1,4,2,'2026-09-26','2026-09-26 14:00:00','2026-09-26 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(252,1,4,2,'2026-09-27','2026-09-27 14:00:00','2026-09-27 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(253,1,4,2,'2026-09-28','2026-09-28 14:00:00','2026-09-28 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(254,1,4,NULL,'2026-09-29',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(255,1,4,1,'2026-09-30','2026-09-30 06:00:00','2026-09-30 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(256,1,4,1,'2026-10-01','2026-10-01 06:00:00','2026-10-01 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(257,1,4,1,'2026-10-02','2026-10-02 06:00:00','2026-10-02 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(258,1,4,1,'2026-10-03','2026-10-03 06:00:00','2026-10-03 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(259,1,4,1,'2026-10-04','2026-10-04 06:00:00','2026-10-04 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(260,1,4,NULL,'2026-10-05',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(261,1,4,3,'2026-10-06','2026-10-06 22:00:00','2026-10-07 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(262,1,4,3,'2026-10-07','2026-10-07 22:00:00','2026-10-08 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(263,1,4,3,'2026-10-08','2026-10-08 22:00:00','2026-10-09 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(264,1,4,3,'2026-10-09','2026-10-09 22:00:00','2026-10-10 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(265,1,4,3,'2026-10-10','2026-10-10 22:00:00','2026-10-11 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(266,1,4,NULL,'2026-10-11',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(267,1,4,2,'2026-10-12','2026-10-12 14:00:00','2026-10-12 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(268,1,4,2,'2026-10-13','2026-10-13 14:00:00','2026-10-13 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(269,1,4,2,'2026-10-14','2026-10-14 14:00:00','2026-10-14 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(270,1,4,2,'2026-10-15','2026-10-15 14:00:00','2026-10-15 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(271,1,4,2,'2026-10-16','2026-10-16 14:00:00','2026-10-16 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(272,1,4,NULL,'2026-10-17',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(273,1,4,1,'2026-10-18','2026-10-18 06:00:00','2026-10-18 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(274,1,4,1,'2026-10-19','2026-10-19 06:00:00','2026-10-19 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(275,1,4,1,'2026-10-20','2026-10-20 06:00:00','2026-10-20 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(276,1,4,1,'2026-10-21','2026-10-21 06:00:00','2026-10-21 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(277,1,4,1,'2026-10-22','2026-10-22 06:00:00','2026-10-22 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(278,1,4,NULL,'2026-10-23',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(279,1,4,3,'2026-10-24','2026-10-24 22:00:00','2026-10-25 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(280,1,4,3,'2026-10-25','2026-10-25 22:00:00','2026-10-26 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(281,1,4,3,'2026-10-26','2026-10-26 22:00:00','2026-10-27 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(282,1,4,3,'2026-10-27','2026-10-27 22:00:00','2026-10-28 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(283,1,4,3,'2026-10-28','2026-10-28 22:00:00','2026-10-29 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(284,1,4,NULL,'2026-10-29',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(285,1,4,2,'2026-10-30','2026-10-30 14:00:00','2026-10-30 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(286,1,4,2,'2026-10-31','2026-10-31 14:00:00','2026-10-31 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(287,1,4,2,'2026-11-01','2026-11-01 14:00:00','2026-11-01 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(288,1,4,2,'2026-11-02','2026-11-02 14:00:00','2026-11-02 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(289,1,4,2,'2026-11-03','2026-11-03 14:00:00','2026-11-03 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(290,1,4,NULL,'2026-11-04',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(291,1,4,1,'2026-11-05','2026-11-05 06:00:00','2026-11-05 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(292,1,4,1,'2026-11-06','2026-11-06 06:00:00','2026-11-06 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(293,1,4,1,'2026-11-07','2026-11-07 06:00:00','2026-11-07 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(294,1,4,1,'2026-11-08','2026-11-08 06:00:00','2026-11-08 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(295,1,4,1,'2026-11-09','2026-11-09 06:00:00','2026-11-09 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(296,1,4,NULL,'2026-11-10',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(297,1,4,3,'2026-11-11','2026-11-11 22:00:00','2026-11-12 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(298,1,4,3,'2026-11-12','2026-11-12 22:00:00','2026-11-13 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(299,1,4,3,'2026-11-13','2026-11-13 22:00:00','2026-11-14 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(300,1,4,3,'2026-11-14','2026-11-14 22:00:00','2026-11-15 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(301,1,4,3,'2026-11-15','2026-11-15 22:00:00','2026-11-16 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(302,1,4,NULL,'2026-11-16',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(303,1,4,2,'2026-11-17','2026-11-17 14:00:00','2026-11-17 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(304,1,4,2,'2026-11-18','2026-11-18 14:00:00','2026-11-18 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(305,1,4,2,'2026-11-19','2026-11-19 14:00:00','2026-11-19 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(306,1,4,2,'2026-11-20','2026-11-20 14:00:00','2026-11-20 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(307,1,4,2,'2026-11-21','2026-11-21 14:00:00','2026-11-21 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(308,1,4,NULL,'2026-11-22',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(309,1,4,1,'2026-11-23','2026-11-23 06:00:00','2026-11-23 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(310,1,4,1,'2026-11-24','2026-11-24 06:00:00','2026-11-24 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(311,1,4,1,'2026-11-25','2026-11-25 06:00:00','2026-11-25 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(312,1,4,1,'2026-11-26','2026-11-26 06:00:00','2026-11-26 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(313,1,4,1,'2026-11-27','2026-11-27 06:00:00','2026-11-27 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(314,1,4,NULL,'2026-11-28',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(315,1,4,3,'2026-11-29','2026-11-29 22:00:00','2026-11-30 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(316,1,4,3,'2026-11-30','2026-11-30 22:00:00','2026-12-01 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(317,1,4,3,'2026-12-01','2026-12-01 22:00:00','2026-12-02 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(318,1,4,3,'2026-12-02','2026-12-02 22:00:00','2026-12-03 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(319,1,4,3,'2026-12-03','2026-12-03 22:00:00','2026-12-04 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(320,1,4,NULL,'2026-12-04',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(321,1,4,2,'2026-12-05','2026-12-05 14:00:00','2026-12-05 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(322,1,4,2,'2026-12-06','2026-12-06 14:00:00','2026-12-06 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(323,1,4,2,'2026-12-07','2026-12-07 14:00:00','2026-12-07 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(324,1,4,2,'2026-12-08','2026-12-08 14:00:00','2026-12-08 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(325,1,4,2,'2026-12-09','2026-12-09 14:00:00','2026-12-09 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(326,1,4,NULL,'2026-12-10',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(327,1,4,1,'2026-12-11','2026-12-11 06:00:00','2026-12-11 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(328,1,4,1,'2026-12-12','2026-12-12 06:00:00','2026-12-12 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(329,1,4,1,'2026-12-13','2026-12-13 06:00:00','2026-12-13 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(330,1,4,1,'2026-12-14','2026-12-14 06:00:00','2026-12-14 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(331,1,4,1,'2026-12-15','2026-12-15 06:00:00','2026-12-15 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(332,1,4,NULL,'2026-12-16',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(333,1,4,3,'2026-12-17','2026-12-17 22:00:00','2026-12-18 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(334,1,4,3,'2026-12-18','2026-12-18 22:00:00','2026-12-19 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(335,1,4,3,'2026-12-19','2026-12-19 22:00:00','2026-12-20 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(336,1,4,3,'2026-12-20','2026-12-20 22:00:00','2026-12-21 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(337,1,4,3,'2026-12-21','2026-12-21 22:00:00','2026-12-22 06:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(338,1,4,NULL,'2026-12-22',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(339,1,4,2,'2026-12-23','2026-12-23 14:00:00','2026-12-23 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(340,1,4,2,'2026-12-24','2026-12-24 14:00:00','2026-12-24 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(341,1,4,2,'2026-12-25','2026-12-25 14:00:00','2026-12-25 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(342,1,4,2,'2026-12-26','2026-12-26 14:00:00','2026-12-26 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(343,1,4,2,'2026-12-27','2026-12-27 14:00:00','2026-12-27 22:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(344,1,4,NULL,'2026-12-28',NULL,NULL,'OFF','2026-08-20 00:11:13','2026-08-20 00:11:13'),(345,1,4,1,'2026-12-29','2026-12-29 06:00:00','2026-12-29 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(346,1,4,1,'2026-12-30','2026-12-30 06:00:00','2026-12-30 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(347,1,4,1,'2026-12-31','2026-12-31 06:00:00','2026-12-31 14:00:00','SCHEDULED','2026-08-20 00:11:13','2026-08-20 00:11:13'),(348,1,4,1,'2026-08-01','2026-08-01 06:00:00','2026-08-01 14:00:00','SCHEDULED','2026-08-20 00:12:30','2026-08-20 00:12:30'),(349,1,4,2,'2026-08-02','2026-08-02 14:00:00','2026-08-02 22:00:00','SCHEDULED','2026-08-20 00:12:30','2026-08-20 00:12:30'),(350,1,4,3,'2026-08-03','2026-08-03 22:00:00','2026-08-04 06:00:00','SCHEDULED','2026-08-20 00:12:30','2026-08-20 00:12:30'),(351,1,4,NULL,'2026-08-04',NULL,NULL,'OFF','2026-08-20 00:12:30','2026-08-20 00:12:30'),(352,1,4,1,'2026-08-05','2026-08-05 06:00:00','2026-08-05 14:00:00','SCHEDULED','2026-08-20 00:12:30','2026-08-20 00:12:30'),(353,1,4,2,'2026-08-06','2026-08-06 14:00:00','2026-08-06 22:00:00','SCHEDULED','2026-08-20 00:12:30','2026-08-20 00:12:30'),(354,1,4,3,'2026-08-07','2026-08-07 22:00:00','2026-08-08 06:00:00','SCHEDULED','2026-08-20 00:12:30','2026-08-20 00:12:30'),(355,1,4,NULL,'2026-08-08',NULL,NULL,'OFF','2026-08-20 00:12:30','2026-08-20 00:12:30'),(356,1,4,1,'2026-08-09','2026-08-09 06:00:00','2026-08-09 14:00:00','SCHEDULED','2026-08-20 00:12:30','2026-08-20 00:12:30'),(357,1,4,2,'2026-08-10','2026-08-10 14:00:00','2026-08-10 22:00:00','SCHEDULED','2026-08-20 00:12:30','2026-08-20 00:12:30'),(358,1,4,3,'2026-08-11','2026-08-11 22:00:00','2026-08-12 06:00:00','SCHEDULED','2026-08-20 00:12:30','2026-08-20 00:12:30'),(359,1,4,NULL,'2026-08-12',NULL,NULL,'OFF','2026-08-20 00:12:30','2026-08-20 00:12:30'),(360,1,4,1,'2026-08-13','2026-08-13 06:00:00','2026-08-13 14:00:00','SCHEDULED','2026-08-20 00:12:30','2026-08-20 00:12:30'),(361,1,4,2,'2026-08-14','2026-08-14 14:00:00','2026-08-14 22:00:00','SCHEDULED','2026-08-20 00:12:30','2026-08-20 00:12:30'),(362,1,4,3,'2026-08-15','2026-08-15 22:00:00','2026-08-16 06:00:00','SCHEDULED','2026-08-20 00:12:30','2026-08-20 00:12:30'),(363,1,4,NULL,'2026-08-16',NULL,NULL,'OFF','2026-08-20 00:12:30','2026-08-20 00:12:30'),(364,1,4,1,'2026-08-17','2026-08-17 06:00:00','2026-08-17 14:00:00','SCHEDULED','2026-08-20 00:12:30','2026-08-20 00:12:30'),(365,1,4,2,'2026-08-18','2026-08-18 14:00:00','2026-08-18 22:00:00','SCHEDULED','2026-08-20 00:12:30','2026-08-20 00:12:30'),(366,1,4,3,'2026-08-19','2026-08-19 22:00:00','2026-08-20 06:00:00','SCHEDULED','2026-08-20 00:12:30','2026-08-20 00:12:30'),(367,1,4,NULL,'2026-08-20',NULL,NULL,'OFF','2026-08-20 00:12:30','2026-08-20 00:12:30'),(368,1,4,1,'2026-08-21','2026-08-21 06:00:00','2026-08-21 14:00:00','SCHEDULED','2026-08-20 00:12:30','2026-08-20 00:12:30'),(369,1,4,2,'2026-08-22','2026-08-22 14:00:00','2026-08-22 22:00:00','SCHEDULED','2026-08-20 00:12:30','2026-08-20 00:12:30'),(370,1,4,3,'2026-08-23','2026-08-23 22:00:00','2026-08-24 06:00:00','SCHEDULED','2026-08-20 00:12:30','2026-08-20 00:12:30'),(371,7,4,1,'2026-08-25','2026-08-25 06:00:00','2026-08-25 14:00:00','SCHEDULED','2026-08-25 19:25:22','2026-08-25 19:25:22'),(372,7,4,2,'2026-08-26','2026-08-26 14:00:00','2026-08-26 22:00:00','SCHEDULED','2026-08-25 19:25:22','2026-08-25 19:25:22'),(373,7,4,3,'2026-08-27','2026-08-27 22:00:00','2026-08-28 06:00:00','SCHEDULED','2026-08-25 19:25:22','2026-08-25 19:25:22'),(374,7,4,1,'2026-08-28','2026-08-28 06:00:00','2026-08-28 14:00:00','SCHEDULED','2026-08-25 19:25:22','2026-08-25 19:25:22'),(375,7,4,2,'2026-08-29','2026-08-29 14:00:00','2026-08-29 22:00:00','SCHEDULED','2026-08-25 19:25:22','2026-08-25 19:25:22'),(376,7,4,3,'2026-08-30','2026-08-30 22:00:00','2026-08-31 06:00:00','SCHEDULED','2026-08-25 19:25:22','2026-08-25 19:25:22'),(377,7,4,1,'2026-08-31','2026-08-31 06:00:00','2026-08-31 14:00:00','SCHEDULED','2026-08-25 19:25:22','2026-08-25 19:25:22'),(378,7,4,2,'2026-09-01','2026-09-01 14:00:00','2026-09-01 22:00:00','SCHEDULED','2026-08-25 19:25:22','2026-08-25 19:25:22'),(379,7,4,3,'2026-09-02','2026-09-02 22:00:00','2026-09-03 06:00:00','SCHEDULED','2026-08-25 19:25:22','2026-08-25 19:25:22'),(380,7,4,1,'2026-09-03','2026-09-03 06:00:00','2026-09-03 14:00:00','SCHEDULED','2026-08-25 19:25:22','2026-08-25 19:25:22'),(381,7,4,2,'2026-09-04','2026-09-04 14:00:00','2026-09-04 22:00:00','SCHEDULED','2026-08-25 19:25:22','2026-08-25 19:25:22'),(382,7,4,3,'2026-09-05','2026-09-05 22:00:00','2026-09-06 06:00:00','SCHEDULED','2026-08-25 19:25:22','2026-08-25 19:25:22'),(383,7,4,1,'2026-09-06','2026-09-06 06:00:00','2026-09-06 14:00:00','SCHEDULED','2026-08-25 19:25:22','2026-08-25 19:25:22'),(384,7,4,2,'2026-09-07','2026-09-07 14:00:00','2026-09-07 22:00:00','SCHEDULED','2026-08-25 19:25:22','2026-08-25 19:25:22');

#
# Structure for table "attendances"
#

DROP TABLE IF EXISTS `attendances`;
CREATE TABLE `attendances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `roster_id` bigint(20) unsigned DEFAULT NULL,
  `location_id` bigint(20) unsigned NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in_at` datetime DEFAULT NULL,
  `check_in_latitude` decimal(10,7) DEFAULT NULL,
  `check_in_longitude` decimal(10,7) DEFAULT NULL,
  `check_in_accuracy` decimal(8,2) DEFAULT NULL,
  `check_in_distance` decimal(10,2) DEFAULT NULL,
  `check_in_photo` varchar(255) DEFAULT NULL,
  `check_in_address` varchar(255) DEFAULT NULL,
  `check_out_at` datetime DEFAULT NULL,
  `check_out_latitude` decimal(10,7) DEFAULT NULL,
  `check_out_longitude` decimal(10,7) DEFAULT NULL,
  `check_out_accuracy` decimal(8,2) DEFAULT NULL,
  `check_out_distance` decimal(10,2) DEFAULT NULL,
  `check_out_photo` varchar(255) DEFAULT NULL,
  `check_out_address` varchar(255) DEFAULT NULL,
  `late_minutes` smallint(5) unsigned NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'INCOMPLETE' COMMENT 'PRESENT / LATE / ABSENT / INCOMPLETE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendances_employee_id_attendance_date_unique` (`employee_id`,`attendance_date`),
  KEY `attendances_roster_id_foreign` (`roster_id`),
  KEY `attendances_attendance_date_status_index` (`attendance_date`,`status`),
  KEY `attendances_location_id_attendance_date_index` (`location_id`,`attendance_date`),
  CONSTRAINT `attendances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendances_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `attendances_roster_id_foreign` FOREIGN KEY (`roster_id`) REFERENCES `shift_rosters` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "attendances"
#

INSERT INTO `attendances` VALUES (61,1,219,4,'2026-08-25','2026-08-25 04:09:13',-6.1550937,106.5741169,185.00,27848.04,'photos/2026/08/JP001_20260825_check_in_sjWiq0Al.png','Kuta Baru, Pasar Kemis, Kabupaten Tangerang, Banten','2026-08-25 04:12:11',-6.1550937,106.5741169,185.00,27848.04,'photos/2026/08/JP001_20260825_check_out_EjaEtuQl.png','Kuta Baru, Pasar Kemis, Kabupaten Tangerang, Banten',0,'PRESENT','2026-08-25 04:09:13','2026-08-25 04:12:11'),(62,7,372,4,'2026-08-26','2026-08-25 22:24:37',-6.1960000,106.8240000,33.00,215.50,'photos/2026/08/ADM001_20260826_check_in_03ff8041.jpg','Jalan Imam Bonjol, Menteng, Jakarta Pusat','2026-08-25 22:24:57',-6.1970000,106.8250000,44.00,370.78,'photos/2026/08/ADM001_20260826_check_out_fb628987.jpg','Jalan Imam Bonjol, Menteng, Jakarta Pusat',0,'PRESENT','2026-08-25 19:26:24','2026-08-25 22:24:59'),(63,7,373,4,'2026-08-27','2026-08-25 20:07:59',-6.1550983,106.5741310,148.00,27846.43,'photos/2026/08/ADM001_20260827_check_in_0FyRzPAh.jpg','Kuta Baru, Pasar Kemis, Kabupaten Tangerang, Banten','2026-08-25 22:15:59',-6.1944000,106.8229000,10.00,0.00,'photos/2026/08/ADM001_20260827_check_out_3e09a98b.jpg','Jalan Mohammad Husni Thamrin, Gondangdia, Jakarta Pusat',0,'PRESENT','2026-08-25 20:07:59','2026-08-25 22:15:59'),(64,1,220,4,'2026-08-26','2026-08-25 20:33:08',-6.1551002,106.5740981,144.00,27849.99,'photos/2026/08/JP001_20260826_check_in_1i99C3OI.png','Kuta Baru, Pasar Kemis, Kabupaten Tangerang, Banten','2026-08-25 20:33:32',-6.1550977,106.5740824,137.00,27851.75,'photos/2026/08/JP001_20260826_check_out_rDGUv4iV.png','Kuta Baru, Pasar Kemis, Kabupaten Tangerang, Banten',0,'PRESENT','2026-08-25 20:33:08','2026-08-25 20:33:32');

#
# Structure for table "assignments"
#

DROP TABLE IF EXISTS `assignments`;
CREATE TABLE `assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `location_id` bigint(20) unsigned NOT NULL,
  `shift_id` bigint(20) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assignments_location_id_foreign` (`location_id`),
  KEY `assignments_shift_id_foreign` (`shift_id`),
  KEY `assignments_employee_id_start_date_end_date_index` (`employee_id`,`start_date`,`end_date`),
  KEY `assignments_status_index` (`status`),
  CONSTRAINT `assignments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignments_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `assignments_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "assignments"
#

INSERT INTO `assignments` VALUES (8,1,4,1,'2026-08-24','2026-08-29','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(9,1,4,3,'2026-08-30','2026-09-04','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(10,1,4,2,'2026-09-05','2026-09-10','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(11,1,4,1,'2026-09-11','2026-09-16','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(12,1,4,3,'2026-09-17','2026-09-22','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(13,1,4,2,'2026-09-23','2026-09-28','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(14,1,4,1,'2026-09-29','2026-10-04','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(15,1,4,3,'2026-10-05','2026-10-10','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(16,1,4,2,'2026-10-11','2026-10-16','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(17,1,4,1,'2026-10-17','2026-10-22','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(18,1,4,3,'2026-10-23','2026-10-28','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(19,1,4,2,'2026-10-29','2026-11-03','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(20,1,4,1,'2026-11-04','2026-11-09','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(21,1,4,3,'2026-11-10','2026-11-15','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(22,1,4,2,'2026-11-16','2026-11-21','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(23,1,4,1,'2026-11-22','2026-11-27','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(24,1,4,3,'2026-11-28','2026-12-03','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(25,1,4,2,'2026-12-04','2026-12-09','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(26,1,4,1,'2026-12-10','2026-12-15','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(27,1,4,3,'2026-12-16','2026-12-21','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(28,1,4,2,'2026-12-22','2026-12-27','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13'),(29,1,4,1,'2026-12-28','2026-12-31','ACTIVE','2026-08-20 00:11:13','2026-08-20 00:11:13');

#
# Structure for table "users"
#

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `employee_id` bigint(20) unsigned DEFAULT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'ACTIVE',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_employee_id_unique` (`employee_id`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "users"
#

INSERT INTO `users` VALUES (1,'Administrator','admin@parkops.test',NULL,'$2y$12$UdQjRLFrI7Wm32UdEZcECu5Y16DEESMzNn5iY8e1GMZxRrXA/2Gjm',7,1,'ACTIVE','2026-08-25 22:15:46',NULL,'2026-08-15 15:21:36','2026-08-25 20:34:19'),(2,'Staff HR','hr@parkops.test',NULL,'$2y$12$Yxw0Z2pU5VgjNOMM0endxuShzXDLSq0HqA7kVHljoG9/6..c.CdQ2',NULL,2,'ACTIVE','2026-08-15 16:46:58',NULL,'2026-08-15 15:21:37','2026-08-25 04:55:16'),(3,'Budi Santoso','jp001@parkops.test',NULL,'$2y$12$y.PhADG.LQC1wOuK4w7sne7/BLW/Fzfqyqc1n0BQOAq58g6H8k9n.',1,3,'ACTIVE','2026-08-25 21:58:51',NULL,'2026-08-15 15:21:37','2026-08-25 21:58:51'),(4,'Andi Prasetyo','jp002@parkops.test',NULL,'$2y$12$x7ot2mZ1nRheZj6w1/rhuOkG6YJAZ21OIlYYZ4Pr2U.qW0GpsbC4u',2,3,'ACTIVE','2026-08-25 20:33:19',NULL,'2026-08-15 15:21:38','2026-08-25 04:55:16'),(5,'Joko Widodo','jp003@parkops.test',NULL,'$2y$12$6.iBDP7Nw4hCWXaEaRfb2edHcX3AHhOcR4jQI.u69sYnGLOQ42ZzC',3,3,'ACTIVE',NULL,NULL,'2026-08-15 15:21:38','2026-08-25 04:55:16'),(6,'Siti Nurhaliza','jp004@parkops.test',NULL,'$2y$12$tFZFoHBNi1SUUJ1Cr6YqqexwvUI.UrWdnny/vrFAQiYp6qAoC7U3C',4,3,'ACTIVE',NULL,NULL,'2026-08-15 15:21:39','2026-08-25 04:55:16'),(7,'Rina Marlina','jp005@parkops.test',NULL,'$2y$12$pXMrdwSE3m2hCv66T8KmIeKyU5988pm9g2DGYyJ5707PPvRFOi96u',5,3,'ACTIVE',NULL,NULL,'2026-08-15 15:21:39','2026-08-25 04:55:16'),(8,'Agus Setiawan','jp006@parkops.test',NULL,'$2y$12$s1Ot.TPDDyN2YdOkqrvzveHOZRz0aEpwsQowc9t2ig2pWJ1oXsiwq',6,3,'ACTIVE','2026-08-25 02:37:44',NULL,'2026-08-15 15:21:40','2026-08-25 04:55:16'),(10,'23423','rifqiirawan@gmail.com',NULL,'$2y$12$lkOsnCqHsw8B/L1lK3scFumWjb/NAlvO6yhSGXfGTVURg6tmn0PES',8,3,'ACTIVE',NULL,NULL,'2026-08-25 01:20:16','2026-08-25 01:20:16');

#
# Structure for table "audit_logs"
#

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `auditable_type` varchar(100) DEFAULT NULL,
  `auditable_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  KEY `audit_logs_action_created_at_index` (`action`,`created_at`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

#
# Data for table "audit_logs"
#

INSERT INTO `audit_logs` VALUES (1,NULL,'roster.generate','App\\Models\\Location',1,'Generate roster 01-08-2026 s/d 31-08-2026 untuk 2 karyawan (pola: S1,S2,S3,OFF)','{\"created\":62,\"updated\":0,\"skipped\":0,\"days\":31}','127.0.0.1','Symfony','2026-08-15 15:21:40'),(2,NULL,'roster.generate','App\\Models\\Location',2,'Generate roster 01-08-2026 s/d 31-08-2026 untuk 2 karyawan (pola: S2,S3,OFF,S1)','{\"created\":62,\"updated\":0,\"skipped\":0,\"days\":31}','127.0.0.1','Symfony','2026-08-15 15:21:40'),(3,NULL,'roster.generate','App\\Models\\Location',3,'Generate roster 01-08-2026 s/d 31-08-2026 untuk 2 karyawan (pola: S3,OFF,S1,S2)','{\"created\":62,\"updated\":0,\"skipped\":0,\"days\":31}','127.0.0.1','Symfony','2026-08-15 15:21:40'),(4,3,'auth.login','App\\Models\\User',3,'jp001@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 15:23:42'),(5,3,'auth.login','App\\Models\\User',3,'jp001@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 15:24:06'),(6,3,'auth.login','App\\Models\\User',3,'jp001@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 15:24:26'),(7,3,'auth.login','App\\Models\\User',3,'jp001@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 15:24:49'),(8,3,'auth.login','App\\Models\\User',3,'jp001@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 15:25:05'),(9,1,'auth.login','App\\Models\\User',1,'admin@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 15:27:24'),(10,1,'auth.login','App\\Models\\User',1,'admin@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 15:27:37'),(11,1,'auth.login','App\\Models\\User',1,'admin@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-15 15:27:58'),(12,1,'auth.login','App\\Models\\User',1,'admin@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 15:33:52'),(13,1,'auth.login','App\\Models\\User',1,'admin@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 15:34:10'),(14,2,'auth.login','App\\Models\\User',2,'hr@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 15:35:42'),(15,3,'auth.login','App\\Models\\User',3,'jp001@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 15:35:45'),(16,1,'auth.login','App\\Models\\User',1,'admin@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 15:38:14'),(17,1,'auth.login','App\\Models\\User',1,'admin@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 15:43:51'),(18,1,'auth.login','App\\Models\\User',1,'admin@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 15:51:41'),(19,3,'auth.login','App\\Models\\User',3,'jp001@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 15:51:43'),(20,3,'auth.login','App\\Models\\User',3,'jp001@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-15 15:52:48'),(21,NULL,'roster.generate','App\\Models\\Location',1,'Generate roster 01-08-2026 s/d 31-08-2026 untuk 1 karyawan (pola: S2)','{\"created\":30,\"updated\":0,\"skipped\":1,\"days\":31}','127.0.0.1','Symfony','2026-08-15 16:15:12'),(22,1,'auth.login','App\\Models\\User',1,'admin@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 16:16:47'),(23,2,'auth.login','App\\Models\\User',2,'hr@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 16:16:51'),(24,3,'attendance.check_in','App\\Models\\Attendance',60,'Check-in JP001 di Lokasi Uji (Testing) (jarak 27846.09 m, akurasi 156.00 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":27846.09,\"accuracy\":156,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','127.0.0.1','Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36','2026-08-15 16:26:36'),(25,1,'auth.login','App\\Models\\User',1,'admin@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 16:40:11'),(26,2,'auth.login','App\\Models\\User',2,'hr@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 16:40:18'),(27,3,'auth.login','App\\Models\\User',3,'jp001@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 16:40:22'),(28,1,'auth.login','App\\Models\\User',1,'admin@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 16:40:50'),(29,2,'auth.login','App\\Models\\User',2,'hr@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 16:40:53'),(30,1,'auth.login','App\\Models\\User',1,'admin@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 16:41:27'),(31,1,'menu_access.updated',NULL,NULL,'Pemetaan akses menu diperbarui',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 16:41:28'),(32,2,'auth.login','App\\Models\\User',2,'hr@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 16:41:30'),(33,1,'auth.login','App\\Models\\User',1,'admin@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 16:42:25'),(34,1,'menu_access.updated',NULL,NULL,'Pemetaan akses menu diperbarui',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 16:42:27'),(35,1,'auth.login','App\\Models\\User',1,'admin@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 16:46:53'),(36,2,'auth.login','App\\Models\\User',2,'hr@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 16:46:58'),(37,3,'auth.login','App\\Models\\User',3,'jp001@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-15 16:47:01'),(38,3,'attendance.check_out','App\\Models\\Attendance',60,'Check-out JP001 (jarak 27846.43 m, akurasi 148.00 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":27846.43,\"accuracy\":148,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-15 16:50:25'),(39,1,'auth.login','App\\Models\\User',1,'admin@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-19 23:15:24'),(40,1,'assignment.rotation','App\\Models\\Location',4,'Generate rotasi shift 2026-08-24 s/d 2026-12-31 untuk 1 karyawan (siklus 6 hari, arah DOWN, libur 1 hari/siklus)','{\"assignments_created\":22,\"assignments_replaced\":0,\"rosters_created\":130,\"rosters_updated\":0,\"off_days\":22}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-20 00:11:13'),(41,1,'roster.updated','App\\Models\\ShiftRoster',218,'Roster JP001 tanggal 24-08-2026 diubah',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-20 00:11:55'),(42,1,'roster.updated','App\\Models\\ShiftRoster',218,'Roster JP001 tanggal 24-08-2026 diubah',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-20 00:12:07'),(43,1,'roster.generate','App\\Models\\Location',4,'Generate roster 01-08-2026 s/d 31-08-2026 untuk 1 karyawan (pola: S1,S2,S3,OFF)','{\"created\":23,\"updated\":0,\"skipped\":8,\"days\":31}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-20 00:12:30'),(44,1,'roster.generate','App\\Models\\Location',4,'Generate roster 24-08-2026 s/d 30-12-2026 untuk 1 karyawan (pola: S1,S2,S3,OFF)','{\"created\":0,\"updated\":0,\"skipped\":129,\"days\":129}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-20 00:13:12'),(45,1,'auth.login','App\\Models\\User',1,'admin@hris.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 00:13:25'),(46,1,'user.created','App\\Models\\User',9,'User rifqiirawan@gmail.com dibuat',NULL,'127.0.0.1','Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36','2026-08-25 01:03:24'),(47,1,'user.deleted',NULL,NULL,'User rifqiirawan@gmail.com dihapus',NULL,'127.0.0.1','Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36','2026-08-25 01:07:14'),(48,1,'employee.created','App\\Models\\Employee',8,'Karyawan dasd dibuat',NULL,'127.0.0.1','Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36','2026-08-25 01:08:41'),(49,1,'user.created','App\\Models\\User',10,'User rifqiirawan@gmail.com dibuat',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 01:20:16'),(50,3,'auth.login_mobile','App\\Models\\User',3,'jp001@hris.test login via uji-otomatis',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 02:36:09'),(51,3,'auth.login_mobile','App\\Models\\User',3,'jp001@hris.test login via uji-otomatis',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 02:36:43'),(52,3,'auth.login_mobile','App\\Models\\User',3,'jp001@hris.test login via uji-2',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 02:36:57'),(53,8,'auth.login_mobile','App\\Models\\User',8,'jp006@hris.test login via uji-nonaktif',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 02:37:44'),(54,1,'payroll.generate','App\\Models\\PayrollPeriod',1,'Generate payroll periode Agustus 2026 (8 karyawan, net 0)','{\"employees\":8,\"working_days\":0,\"gross\":\"0.00\",\"deduction\":\"0.00\",\"net\":\"0.00\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 02:37:50'),(55,1,'payroll.close','App\\Models\\PayrollPeriod',1,'Periode Agustus 2026 ditutup',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 02:38:36'),(56,1,'role.created','App\\Models\\Role',4,'Role ADMIN2 dibuat',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 02:41:53'),(57,1,'role.updated','App\\Models\\Role',4,'Role ADMIN2 diperbarui',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 02:42:00'),(58,1,'menu_access.updated',NULL,NULL,'Pemetaan akses menu diperbarui',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 02:42:06'),(59,3,'auth.login_mobile','App\\Models\\User',3,'jp001@hris.test login via chrome-web',NULL,'192.168.1.11','curl/8.18.0','2026-08-25 03:00:13'),(60,3,'auth.login_mobile','App\\Models\\User',3,'jp001@hris.test login via android',NULL,'192.168.1.11','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 03:00:57'),(61,3,'auth.login_mobile','App\\Models\\User',3,'jp001@hris.test login via android',NULL,'192.168.1.11','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 03:28:29'),(62,3,'auth.login_mobile','App\\Models\\User',3,'jp001@hris.test login via android',NULL,'192.168.1.11','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 03:36:59'),(63,3,'auth.login_mobile','App\\Models\\User',3,'jp001@hris.test login via android',NULL,'192.168.1.11','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 04:08:57'),(64,3,'attendance.check_in','App\\Models\\Attendance',61,'Check-in JP001 di Lokasi Uji (Testing) (jarak 27848.04 m, akurasi 185.00 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":27848.04,\"accuracy\":185,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','192.168.1.11','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 04:09:13'),(65,3,'auth.login_mobile','App\\Models\\User',3,'jp001@hris.test login via cek-demo',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 04:10:57'),(66,3,'attendance.check_out','App\\Models\\Attendance',61,'Check-out JP001 (jarak 27848.04 m, akurasi 185.00 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":27848.04,\"accuracy\":185,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','192.168.1.11','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 04:12:11'),(67,3,'auth.login_mobile','App\\Models\\User',3,'jp001@hris.test login via diag',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 04:13:40'),(68,3,'auth.login_mobile','App\\Models\\User',3,'jp001@hris.test login via android',NULL,'192.168.1.11','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 04:21:45'),(69,1,'auth.logout','App\\Models\\User',1,'admin@hris.test logout',NULL,'127.0.0.1','Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36','2026-08-25 04:29:55'),(70,1,'auth.login_mobile','App\\Models\\User',1,'admin@hris.test login via cek-kredensial',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 04:51:55'),(71,3,'auth.login_mobile','App\\Models\\User',3,'jp001@hris.test login via cek-kredensial',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 04:51:56'),(72,1,'auth.login_mobile','App\\Models\\User',1,'admin@parkops.test login via cek-rename',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 04:55:26'),(73,3,'auth.login_mobile','App\\Models\\User',3,'jp001@parkops.test login via cek-rename',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 04:55:27'),(74,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'127.0.0.1','Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36','2026-08-25 05:05:46'),(75,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-25 05:46:39'),(76,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-25 05:47:01'),(77,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-25 05:47:26'),(78,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-25 05:57:10'),(79,3,'auth.login_mobile','App\\Models\\User',3,'jp001@parkops.test login via Pixel 8 (probe)',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 05:58:31'),(80,3,'auth.login_mobile','App\\Models\\User',3,'jp001@parkops.test login via chrome-web',NULL,'192.168.1.11','curl/8.18.0','2026-08-25 06:14:04'),(81,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'192.168.1.11','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 06:15:22'),(82,3,'auth.login_mobile','App\\Models\\User',3,'jp001@parkops.test login via android',NULL,'192.168.1.11','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 06:21:29'),(83,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-25 19:12:39'),(84,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 19:12:57'),(85,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-25 19:15:56'),(86,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 19:16:42'),(87,NULL,'roster.generate','App\\Models\\Location',4,'Generate roster 25-08-2026 s/d 07-09-2026 untuk 1 karyawan (pola: S1,S2,S3)','{\"created\":14,\"updated\":0,\"skipped\":0,\"days\":14}','127.0.0.1','Symfony','2026-08-25 19:25:22'),(88,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9168','2026-08-25 19:26:00'),(89,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 19:26:20'),(90,1,'attendance.check_in','App\\Models\\Attendance',62,'Check-in ADM001 di Lokasi Uji (Testing) (jarak 0.00 m, akurasi 18.50 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":0,\"accuracy\":18.5,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','127.0.0.1','curl/8.18.0','2026-08-25 19:26:25'),(91,1,'attendance.check_out','App\\Models\\Attendance',62,'Check-out ADM001 (jarak 7.84 m, akurasi 22.00 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":7.84,\"accuracy\":22,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','127.0.0.1','curl/8.18.0','2026-08-25 19:26:37'),(92,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'192.168.1.5','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36','2026-08-25 19:35:59'),(93,1,'menu_access.updated','App\\Models\\Role',1,'Akses menu role ADMIN diperbarui','{\"menus\":19,\"actions\":55}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 19:38:40'),(94,1,'auth.logout','App\\Models\\User',1,'admin@parkops.test logout',NULL,'192.168.1.5','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36','2026-08-25 19:44:52'),(95,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'192.168.1.5','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36','2026-08-25 19:45:13'),(96,1,'auth.logout','App\\Models\\User',1,'admin@parkops.test logout',NULL,'192.168.1.5','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36','2026-08-25 19:48:06'),(97,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 19:58:20'),(98,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'192.168.1.5','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36','2026-08-25 20:06:54'),(99,1,'attendance.check_in','App\\Models\\Attendance',63,'Check-in ADM001 di Lokasi Uji (Testing) (jarak 27846.43 m, akurasi 148.00 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":27846.43,\"accuracy\":148,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 20:07:59'),(100,1,'payroll.period_created','App\\Models\\PayrollPeriod',2,'Periode September 2026 dibuat',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 20:10:38'),(101,1,'payroll.generate','App\\Models\\PayrollPeriod',2,'Generate payroll periode September 2026 (8 karyawan, net 0)','{\"employees\":8,\"working_days\":0,\"gross\":\"0.00\",\"deduction\":\"0.00\",\"net\":\"0.00\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 20:10:48'),(102,1,'employee.created','App\\Models\\Employee',9,'Karyawan TST900 dibuat',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 20:21:08'),(103,1,'employee.updated','App\\Models\\Employee',9,'Karyawan TST900 diperbarui',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 20:21:20'),(104,1,'employee.deleted',NULL,NULL,'Karyawan TST900 dihapus',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 20:21:20'),(105,3,'auth.login','App\\Models\\User',3,'jp001@parkops.test login',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 20:21:49'),(106,1,'payroll.reopen','App\\Models\\PayrollPeriod',1,'Periode Agustus 2026 dibuka kembali',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 20:24:36'),(107,1,'payroll.generate','App\\Models\\PayrollPeriod',1,'Generate payroll periode Agustus 2026 (8 karyawan, net 150.000)','{\"employees\":8,\"working_days\":2,\"gross\":\"150000.00\",\"deduction\":\"0.00\",\"net\":\"150000.00\"}','127.0.0.1','curl/8.18.0','2026-08-25 20:24:36'),(108,1,'payroll.deduction_added','App\\Models\\Payroll',3,'Potongan \"Kasbon Uji\" sebesar 25.000',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 20:24:58'),(109,1,'payroll.deduction_removed','App\\Models\\Payroll',3,'Potongan \"Kasbon Uji\" dihapus',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 20:24:59'),(110,1,'payroll.close','App\\Models\\PayrollPeriod',1,'Periode Agustus 2026 ditutup',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 20:24:59'),(111,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 20:27:59'),(112,1,'auth.logout','App\\Models\\User',1,'admin@parkops.test logout',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 20:31:59'),(113,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 20:32:03'),(114,1,'auth.logout','App\\Models\\User',1,'admin@parkops.test logout',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 20:32:43'),(115,3,'auth.login','App\\Models\\User',3,'jp001@parkops.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 20:32:49'),(116,3,'attendance.check_in','App\\Models\\Attendance',64,'Check-in JP001 di Lokasi Uji (Testing) (jarak 27849.99 m, akurasi 144.00 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":27849.99,\"accuracy\":144,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 20:33:09'),(117,4,'auth.login','App\\Models\\User',4,'jp002@parkops.test login',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 20:33:19'),(118,3,'attendance.check_out','App\\Models\\Attendance',64,'Check-out JP001 (jarak 27851.75 m, akurasi 137.00 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":27851.75,\"accuracy\":137,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 20:33:32'),(120,3,'auth.logout','App\\Models\\User',3,'jp001@parkops.test logout',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 20:34:16'),(121,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 20:34:19'),(123,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 20:35:41'),(124,1,'menu_access.updated','App\\Models\\Role',4,'Akses menu role ADMIN2 diperbarui','{\"menus\":2,\"actions\":3}','127.0.0.1','curl/8.18.0','2026-08-25 20:37:07'),(125,1,'menu_access.updated','App\\Models\\Role',4,'Akses menu role ADMIN2 diperbarui','{\"menus\":1,\"actions\":1}','127.0.0.1','curl/8.18.0','2026-08-25 20:37:18'),(126,1,'menu_access.updated','App\\Models\\Role',4,'Akses menu role ADMIN2 diperbarui','{\"menus\":0,\"actions\":0}','127.0.0.1','curl/8.18.0','2026-08-25 20:37:18'),(127,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'::1','curl/8.18.0','2026-08-25 20:53:11'),(128,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 20:58:44'),(130,3,'auth.login_mobile','App\\Models\\User',3,'jp001@parkops.test login via cek-schema',NULL,'127.0.0.1','curl/8.18.0','2026-08-25 21:18:58'),(131,3,'auth.login_mobile','App\\Models\\User',3,'jp001@parkops.test login via diag-mobile',NULL,'192.168.1.11','curl/8.18.0','2026-08-25 21:58:51'),(132,1,'auth.login','App\\Models\\User',1,'admin@parkops.test login',NULL,'::1','curl/8.18.0','2026-08-25 22:15:46'),(133,1,'attendance.check_out','App\\Models\\Attendance',63,'Check-out ADM001 (jarak 0.00 m, akurasi 10.00 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":0,\"accuracy\":10,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','::1','curl/8.18.0','2026-08-25 22:15:59'),(134,1,'attendance.check_in','App\\Models\\Attendance',66,'Check-in ADM001 di Lokasi Uji (Testing) (jarak 0.00 m, akurasi 10.00 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":0,\"accuracy\":10,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','::1','curl/8.18.0','2026-08-25 22:22:54'),(135,1,'attendance.check_in','App\\Models\\Attendance',67,'Check-in ADM001 di Lokasi Uji (Testing) (jarak 94.08 m, akurasi 25.00 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":94.08,\"accuracy\":25,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','::1','curl/8.18.0','2026-08-25 22:22:59'),(136,1,'attendance.check_in','App\\Models\\Attendance',68,'Check-in ADM001 di Lokasi Uji (Testing) (jarak 215.50 m, akurasi 33.00 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":215.5,\"accuracy\":33,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','::1','curl/8.18.0','2026-08-25 22:23:03'),(137,1,'attendance.check_out','App\\Models\\Attendance',68,'Check-out ADM001 (jarak 27848.79 m, akurasi 135.00 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":27848.79,\"accuracy\":135,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 22:23:13'),(138,1,'attendance.check_out','App\\Models\\Attendance',67,'Check-out ADM001 (jarak 27850.49 m, akurasi 144.00 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":27850.49,\"accuracy\":144,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 22:23:41'),(139,1,'attendance.check_in','App\\Models\\Attendance',62,'Check-in ADM001 di Lokasi Uji (Testing) (jarak 0.00 m, akurasi 10.00 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":0,\"accuracy\":10,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','::1','curl/8.18.0','2026-08-25 22:24:33'),(140,1,'attendance.check_in','App\\Models\\Attendance',62,'Check-in ADM001 di Lokasi Uji (Testing) (jarak 94.08 m, akurasi 25.00 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":94.08,\"accuracy\":25,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','::1','curl/8.18.0','2026-08-25 22:24:35'),(141,1,'attendance.check_in','App\\Models\\Attendance',62,'Check-in ADM001 di Lokasi Uji (Testing) (jarak 215.50 m, akurasi 33.00 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":215.5,\"accuracy\":33,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','::1','curl/8.18.0','2026-08-25 22:24:38'),(142,1,'attendance.check_out','App\\Models\\Attendance',62,'Check-out ADM001 (jarak 0.00 m, akurasi 11.00 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":0,\"accuracy\":11,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','::1','curl/8.18.0','2026-08-25 22:24:55'),(143,1,'attendance.check_out','App\\Models\\Attendance',62,'Check-out ADM001 (jarak 370.78 m, akurasi 44.00 m)','{\"valid\":true,\"code\":\"OK\",\"message\":\"Lokasi diterima (penegakan geofence dinonaktifkan).\",\"distance\":370.78,\"accuracy\":44,\"radius_meter\":65535,\"gps_accuracy_limit\":65535}','::1','curl/8.18.0','2026-08-25 22:24:59'),(144,1,'auth.logout','App\\Models\\User',1,'admin@parkops.test logout',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-25 23:50:29');

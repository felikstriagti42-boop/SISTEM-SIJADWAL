-- =====================================================================
-- KASUS 3: KEAMANAN & KEPATUHAN UU PDP
-- File: 04_rbac_security.sql
--
-- Tujuan:
--   Menerapkan Role-Based Access Control (RBAC) di level MySQL agar
--   prinsip "least privilege" dari UU PDP (Pelindungan Data Pribadi)
--   terpenuhi: setiap peran hanya boleh mengakses/mengubah data
--   sesuai kebutuhannya.
--
-- Peran:
--   1. admin_prodi  -> Boleh INSERT/UPDATE/DELETE/SELECT pada tabel
--                      jadwal & ruangan (mengelola penjadwalan).
--   2. dosen        -> Hanya boleh SELECT (Read-Only) jadwal & ruangan.
--   3. mahasiswa    -> Hanya boleh SELECT (Read-Only) jadwal & ruangan.
--   4. app_backend  -> Akun teknis untuk aplikasi, dibatasi hanya bisa
--                      EXECUTE stored procedure (tidak akses tabel
--                      langsung) agar semua insert jadwal WAJIB lewat
--                      mekanisme concurrency control di prosedur.
-- =====================================================================
USE db_jadwal_kuliah;

-- ---------------------------------------------------------------------
-- 1. BUAT ROLE (MySQL 8+ mendukung CREATE ROLE)
-- ---------------------------------------------------------------------
DROP ROLE IF EXISTS 'role_admin_prodi', 'role_dosen', 'role_mahasiswa', 'role_app_backend';

CREATE ROLE 'role_admin_prodi';
CREATE ROLE 'role_dosen';
CREATE ROLE 'role_mahasiswa';
CREATE ROLE 'role_app_backend';

-- ---------------------------------------------------------------------
-- 2. GRANT HAK AKSES PER ROLE
-- ---------------------------------------------------------------------

-- Admin Prodi: kelola penuh data jadwal, ruangan, mk, dosen
GRANT SELECT, INSERT, UPDATE, DELETE
    ON db_jadwal_kuliah.jadwal_kuliah TO 'role_admin_prodi';
GRANT SELECT, INSERT, UPDATE, DELETE
    ON db_jadwal_kuliah.jadwal_ujian TO 'role_admin_prodi';
GRANT SELECT, INSERT, UPDATE, DELETE
    ON db_jadwal_kuliah.ruangan TO 'role_admin_prodi';
GRANT SELECT, INSERT, UPDATE, DELETE
    ON db_jadwal_kuliah.mata_kuliah TO 'role_admin_prodi';
GRANT SELECT, INSERT, UPDATE, DELETE
    ON db_jadwal_kuliah.dosen TO 'role_admin_prodi';
GRANT SELECT
    ON db_jadwal_kuliah.prodi TO 'role_admin_prodi';
GRANT EXECUTE
    ON PROCEDURE db_jadwal_kuliah.sp_tambah_jadwal_kuliah TO 'role_admin_prodi';

-- Dosen: HANYA Read-Only ke jadwal & ruangan
GRANT SELECT ON db_jadwal_kuliah.jadwal_kuliah TO 'role_dosen';
GRANT SELECT ON db_jadwal_kuliah.jadwal_ujian  TO 'role_dosen';
GRANT SELECT ON db_jadwal_kuliah.ruangan       TO 'role_dosen';
GRANT SELECT ON db_jadwal_kuliah.mata_kuliah   TO 'role_dosen';

-- Mahasiswa: HANYA Read-Only ke jadwal & ruangan (tanpa data dosen sensitif lain)
GRANT SELECT ON db_jadwal_kuliah.jadwal_kuliah TO 'role_mahasiswa';
GRANT SELECT ON db_jadwal_kuliah.jadwal_ujian  TO 'role_mahasiswa';
GRANT SELECT ON db_jadwal_kuliah.ruangan       TO 'role_mahasiswa';
GRANT SELECT ON db_jadwal_kuliah.mata_kuliah   TO 'role_mahasiswa';

-- App Backend: tidak boleh akses tabel jadwal langsung, hanya lewat
-- stored procedure (memaksa concurrency-safe insert)
GRANT EXECUTE
    ON PROCEDURE db_jadwal_kuliah.sp_tambah_jadwal_kuliah TO 'role_app_backend';
GRANT SELECT
    ON db_jadwal_kuliah.jadwal_kuliah TO 'role_app_backend';
GRANT SELECT
    ON db_jadwal_kuliah.ruangan TO 'role_app_backend';

-- ---------------------------------------------------------------------
-- 3. BUAT USER & BERIKAN ROLE (ganti password sebelum produksi!)
-- ---------------------------------------------------------------------
CREATE USER IF NOT EXISTS 'admin_prodi1'@'%' IDENTIFIED BY 'GantiPasswordKuat!1';
CREATE USER IF NOT EXISTS 'dosen_andreas'@'%' IDENTIFIED BY 'GantiPasswordKuat!2';
CREATE USER IF NOT EXISTS 'mhs_budi'@'%'      IDENTIFIED BY 'GantiPasswordKuat!3';
CREATE USER IF NOT EXISTS 'backend_app'@'%'   IDENTIFIED BY 'GantiPasswordKuat!4';

GRANT 'role_admin_prodi'  TO 'admin_prodi1'@'%';
GRANT 'role_dosen'        TO 'dosen_andreas'@'%';
GRANT 'role_mahasiswa'    TO 'mhs_budi'@'%';
GRANT 'role_app_backend'  TO 'backend_app'@'%';

-- Aktifkan role secara default saat user login (penting!)
-- CATATAN KOMPATIBILITAS:
--   - MySQL 8+   : SET DEFAULT ROLE 'role_x' TO 'user'@'host';
--   - MariaDB 10+: SET DEFAULT ROLE 'role_x' FOR 'user'@'host';
-- Skrip di bawah memakai sintaks MariaDB. Jika Anda memakai MySQL asli
-- (bukan MariaDB), ganti kata FOR menjadi TO pada 4 baris berikut.
SET DEFAULT ROLE 'role_admin_prodi'  FOR 'admin_prodi1'@'%';
SET DEFAULT ROLE 'role_dosen'        FOR 'dosen_andreas'@'%';
SET DEFAULT ROLE 'role_mahasiswa'    FOR 'mhs_budi'@'%';
SET DEFAULT ROLE 'role_app_backend'  FOR 'backend_app'@'%';

FLUSH PRIVILEGES;

-- ---------------------------------------------------------------------
-- 4. VERIFIKASI HAK AKSES
-- ---------------------------------------------------------------------
-- SHOW GRANTS FOR 'dosen_andreas'@'%';
-- SHOW GRANTS FOR 'admin_prodi1'@'%';

-- ---------------------------------------------------------------------
-- 5. CATATAN KEPATUHAN UU PDP
-- ---------------------------------------------------------------------
-- - Prinsip least privilege diterapkan: dosen & mahasiswa tidak bisa
--   mengubah data sama sekali (Read-Only), mencegah manipulasi/
--   perubahan data pribadi pihak lain tanpa otorisasi.
-- - Kolom data pribadi sensitif (NIDN dosen) tidak diekspos ke role
--   mahasiswa karena tabel dosen tidak di-GRANT ke role tersebut;
--   tampilan jadwal untuk mahasiswa sebaiknya menggunakan VIEW yang
--   hanya menampilkan nama dosen, bukan NIDN (lihat 05_views.sql).
-- - Semua akun memakai password terenkripsi bawaan MySQL (caching_sha2
--   / mysql_native_password tergantung versi) -- WAJIB diganti policy
--   password sebelum dipakai di production.
-- - Audit log dapat ditambahkan dengan trigger AFTER INSERT/UPDATE/DELETE
--   pada jadwal_kuliah ke tabel log_audit (lihat 06_audit_log.sql).

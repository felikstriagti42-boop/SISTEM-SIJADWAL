-- =====================================================================
-- KASUS 3: VIEW UNTUK READ-ONLY DOSEN/MAHASISWA
-- File: 05_views.sql
-- Menyembunyikan kolom sensitif (mis. NIDN) sesuai prinsip minimisasi
-- data UU PDP, hanya menampilkan kolom yang relevan untuk dilihat.
-- =====================================================================
USE db_jadwal_kuliah;

CREATE OR REPLACE VIEW v_jadwal_publik AS
SELECT
    jk.id_jadwal,
    mk.kode_mk,
    mk.nama_mk,
    d.nama_dosen,                 -- NIDN sengaja tidak ditampilkan
    r.kode_ruangan,
    r.nama_ruangan,
    jk.hari,
    jk.jam_mulai,
    jk.jam_selesai,
    jk.semester,
    jk.tahun_akademik
FROM jadwal_kuliah jk
JOIN mata_kuliah mk ON mk.id_mk = jk.id_mk
JOIN dosen d        ON d.id_dosen = jk.id_dosen
JOIN ruangan r      ON r.id_ruangan = jk.id_ruangan
WHERE jk.status = 'Aktif';

CREATE OR REPLACE VIEW v_jadwal_ujian_publik AS
SELECT
    ju.id_ujian,
    mk.kode_mk,
    mk.nama_mk,
    r.kode_ruangan,
    r.nama_ruangan,
    ju.jenis_ujian,
    ju.tanggal_ujian,
    ju.jam_mulai,
    ju.jam_selesai,
    ju.tahun_akademik
FROM jadwal_ujian ju
JOIN mata_kuliah mk ON mk.id_mk = ju.id_mk
JOIN ruangan r      ON r.id_ruangan = ju.id_ruangan;

-- Berikan akses view ini ke role dosen & mahasiswa (lebih aman
-- daripada GRANT langsung ke tabel dosen yang berisi NIDN)
GRANT SELECT ON db_jadwal_kuliah.v_jadwal_publik       TO 'role_dosen', 'role_mahasiswa';
GRANT SELECT ON db_jadwal_kuliah.v_jadwal_ujian_publik  TO 'role_dosen', 'role_mahasiswa';

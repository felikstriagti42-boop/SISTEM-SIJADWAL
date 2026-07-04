-- =====================================================================
-- KASUS 3: DATA CONTOH (SAMPLE DATA)
-- File: 02_sample_data.sql
-- =====================================================================
USE db_jadwal_kuliah;

INSERT INTO prodi (nama_prodi, jenjang) VALUES
('Teknik Informatika', 'S1'),
('Sistem Informasi', 'S1');

INSERT INTO dosen (nidn, nama_dosen) VALUES
('1101001', 'Dr. Andreas Wijaya'),
('1101002', 'Maria Lestari, M.Kom'),
('1101003', 'Yohanes Pratama, M.T');

INSERT INTO mata_kuliah (kode_mk, nama_mk, sks, id_prodi) VALUES
('TIF101', 'Basis Data', 3, 1),
('TIF102', 'Pemrograman Web', 3, 1),
('SIF201', 'Analisis Sistem Informasi', 3, 2);

-- Ruangan dengan spesifikasi JSON yang fleksibel (NoSQL style)
INSERT INTO ruangan (kode_ruangan, nama_ruangan, tipe, kapasitas, spesifikasi_fasilitas) VALUES
('LAB-01', 'Lab Komputer 1', 'Lab', 40,
    JSON_OBJECT('RAM', '16GB', 'Software', JSON_ARRAY('MySQL 9.0','Visual Studio Code'), 'AC', true)),
('LAB-02', 'Lab Komputer 2', 'Lab', 35,
    JSON_OBJECT('RAM', '8GB', 'Software', JSON_ARRAY('MySQL 8.0'), 'AC', true)),
('R-301',  'Ruang Kelas 301', 'Kelas', 50,
    JSON_OBJECT('Proyektor', true, 'AC', true)),
('AULA-01','Aula Utama', 'Kelas', 200,
    JSON_OBJECT('Proyektor', true, 'SoundSystem', true, 'AC', true));

-- Jadwal kuliah contoh
INSERT INTO jadwal_kuliah (id_mk, id_dosen, id_ruangan, hari, jam_mulai, jam_selesai, semester, tahun_akademik)
VALUES
(1, 1, 1, 'Senin', '08:00:00', '10:30:00', 'Ganjil', '2025/2026'),
(2, 2, 3, 'Senin', '10:30:00', '13:00:00', 'Ganjil', '2025/2026'),
(3, 3, 2, 'Selasa', '08:00:00', '10:30:00', 'Ganjil', '2025/2026');

-- Jadwal ujian massal contoh
INSERT INTO jadwal_ujian (id_mk, id_ruangan, jenis_ujian, tanggal_ujian, jam_mulai, jam_selesai, tahun_akademik)
VALUES
(1, 4, 'UTS', '2025-10-20', '08:00:00', '10:00:00', '2025/2026'),
(2, 4, 'UTS', '2025-10-20', '10:30:00', '12:30:00', '2025/2026');

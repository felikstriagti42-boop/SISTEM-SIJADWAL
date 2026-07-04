-- =====================================================================
-- KASUS 3: SISTEM PENGELOLA PENJADWALAN KULIAH & RUANGAN
-- Institut Shanti Bhuana
-- File: 01_schema.sql
-- Deskripsi: Struktur tabel utama (relasional) + kolom JSON (NoSQL-style)
-- =====================================================================

DROP DATABASE IF EXISTS db_jadwal_kuliah;
CREATE DATABASE db_jadwal_kuliah
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE db_jadwal_kuliah;

-- ---------------------------------------------------------------------
-- 1. TABEL PRODI
-- ---------------------------------------------------------------------
CREATE TABLE prodi (
    id_prodi        INT AUTO_INCREMENT PRIMARY KEY,
    nama_prodi      VARCHAR(100) NOT NULL,
    jenjang         VARCHAR(10)  NOT NULL DEFAULT 'S1'
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. TABEL DOSEN
-- ---------------------------------------------------------------------
CREATE TABLE dosen (
    id_dosen        INT AUTO_INCREMENT PRIMARY KEY,
    nidn            VARCHAR(20) UNIQUE,
    nama_dosen      VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. TABEL MATA KULIAH
-- ---------------------------------------------------------------------
CREATE TABLE mata_kuliah (
    id_mk           INT AUTO_INCREMENT PRIMARY KEY,
    kode_mk         VARCHAR(15) NOT NULL UNIQUE,
    nama_mk         VARCHAR(100) NOT NULL,
    sks             TINYINT NOT NULL,
    id_prodi        INT NOT NULL,
    CONSTRAINT fk_mk_prodi FOREIGN KEY (id_prodi) REFERENCES prodi(id_prodi)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. TABEL RUANGAN
-- NoSQL JSON: spesifikasi_fasilitas menyimpan kebutuhan/fasilitas
-- ruangan yang sifatnya dinamis/berubah-ubah (skema fleksibel),
-- contoh: {"RAM": "16GB", "Software": "MySQL 9.0", "Kapasitas_Kursi": 40}
-- ---------------------------------------------------------------------
CREATE TABLE ruangan (
    id_ruangan      INT AUTO_INCREMENT PRIMARY KEY,
    kode_ruangan    VARCHAR(20) NOT NULL UNIQUE,
    nama_ruangan    VARCHAR(50) NOT NULL,
    tipe            ENUM('Kelas','Lab') NOT NULL DEFAULT 'Kelas',
    kapasitas       INT NOT NULL,
    spesifikasi_fasilitas JSON NULL,
    CONSTRAINT chk_spesifikasi_json
        CHECK (spesifikasi_fasilitas IS NULL
               OR JSON_VALID(spesifikasi_fasilitas))
) ENGINE=InnoDB;

-- Contoh validasi tambahan: pastikan field json tertentu bertipe benar
-- (MySQL tidak mendukung skema JSON penuh, validasi detail dilakukan di
-- level aplikasi / trigger jika diperlukan)

-- ---------------------------------------------------------------------
-- 5. TABEL JADWAL KULIAH (harian)
-- ---------------------------------------------------------------------
CREATE TABLE jadwal_kuliah (
    id_jadwal       BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_mk           INT NOT NULL,
    id_dosen        INT NOT NULL,
    id_ruangan      INT NOT NULL,
    hari            ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
    jam_mulai       TIME NOT NULL,
    jam_selesai     TIME NOT NULL,
    semester        VARCHAR(10) NOT NULL,
    tahun_akademik  VARCHAR(9)  NOT NULL,  -- contoh: 2025/2026
    status          ENUM('Aktif','Batal') NOT NULL DEFAULT 'Aktif',
    dibuat_pada     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    diubah_pada     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_jadwal_mk      FOREIGN KEY (id_mk)      REFERENCES mata_kuliah(id_mk),
    CONSTRAINT fk_jadwal_dosen   FOREIGN KEY (id_dosen)   REFERENCES dosen(id_dosen),
    CONSTRAINT fk_jadwal_ruangan FOREIGN KEY (id_ruangan) REFERENCES ruangan(id_ruangan),

    CONSTRAINT chk_jam_valid CHECK (jam_selesai > jam_mulai)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. TABEL JADWAL UJIAN MASSAL (terpisah karena pola beda: banyak
--    mata kuliah pada satu sesi/ruangan besar secara serentak)
-- ---------------------------------------------------------------------
CREATE TABLE jadwal_ujian (
    id_ujian        BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_mk           INT NOT NULL,
    id_ruangan      INT NOT NULL,
    jenis_ujian     ENUM('UTS','UAS') NOT NULL,
    tanggal_ujian   DATE NOT NULL,
    jam_mulai       TIME NOT NULL,
    jam_selesai     TIME NOT NULL,
    tahun_akademik  VARCHAR(9) NOT NULL,

    CONSTRAINT fk_ujian_mk      FOREIGN KEY (id_mk)      REFERENCES mata_kuliah(id_mk),
    CONSTRAINT fk_ujian_ruangan FOREIGN KEY (id_ruangan) REFERENCES ruangan(id_ruangan),
    CONSTRAINT chk_jam_ujian_valid CHECK (jam_selesai > jam_mulai)
) ENGINE=InnoDB;

-- =====================================================================
-- ADVANCED INDEXING
-- Composite index pada (hari, ruangan, jam_mulai, jam_selesai)
-- Tujuan: mempercepat kueri pengecekan slot kosong / bentrok jadwal,
-- karena kueri pengecekan SELALU memfilter berdasarkan kombinasi
-- keempat kolom ini sekaligus.
-- =====================================================================
CREATE INDEX idx_composite_slot
    ON jadwal_kuliah (hari, id_ruangan, jam_mulai, jam_selesai);

-- Index pendukung untuk pencarian per dosen (cek bentrok jadwal dosen)
CREATE INDEX idx_dosen_hari_jam
    ON jadwal_kuliah (id_dosen, hari, jam_mulai, jam_selesai);

-- Index untuk JSON: generated column + index agar pencarian fasilitas
-- tertentu (mis. cari ruangan ber-RAM 16GB) tetap cepat walau di kolom JSON
ALTER TABLE ruangan
    ADD COLUMN gen_ram VARCHAR(20)
        GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(spesifikasi_fasilitas, '$.RAM'))) STORED;

CREATE INDEX idx_ruangan_ram ON ruangan (gen_ram);

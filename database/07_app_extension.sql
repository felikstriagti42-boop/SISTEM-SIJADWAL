-- =====================================================================
-- KASUS 3: EKSTENSI UNTUK KEBUTUHAN WEBSITE (APLIKASI)
-- File: 07_app_extension.sql
--
-- Berisi tambahan yang diperlukan agar website PHP dapat:
--   1. Login berbasis peran (admin_prodi, dosen, mahasiswa) -> tabel
--      akun_pengguna (terpisah dari user MySQL di 04_rbac_security.sql,
--      karena aplikasi web memakai SATU koneksi database/role
--      'role_app_backend', lalu otorisasi halaman diatur di level
--      aplikasi berdasarkan kolom `role` pada tabel ini).
--   2. Kapasitas ruang kelas ditegakkan saat jadwal ujian massal dibuat
--      (kolom jumlah_peserta + validasi pada stored procedure).
--   3. Jadwal ujian massal input serentak tetap aman dari bentrok
--      ruangan (mekanisme sama seperti sp_tambah_jadwal_kuliah).
-- =====================================================================
USE db_jadwal_kuliah;

-- ---------------------------------------------------------------------
-- 1. TABEL AKUN PENGGUNA WEBSITE
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS akun_pengguna (
    id_akun         INT AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(50) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('admin_prodi','dosen','mahasiswa') NOT NULL,
    nama_lengkap    VARCHAR(100) NOT NULL,
    id_dosen        INT NULL,
    aktif           TINYINT(1) NOT NULL DEFAULT 1,
    dibuat_pada     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_akun_dosen FOREIGN KEY (id_dosen) REFERENCES dosen(id_dosen)
) ENGINE=InnoDB;

-- Catatan: password_hash diisi lewat installer/seeder PHP
-- (website/install/seed.php) memakai fungsi password_hash() PHP,
-- BUKAN ditulis manual di SQL ini, supaya hash bcrypt selalu sesuai
-- dengan versi PHP di server Anda.

-- ---------------------------------------------------------------------
-- 2. KAPASITAS PESERTA UJIAN PADA JADWAL UJIAN MASSAL
-- ---------------------------------------------------------------------
ALTER TABLE jadwal_ujian
    ADD COLUMN jumlah_peserta INT NOT NULL DEFAULT 0 AFTER id_ruangan;

-- Index komposit untuk pengecekan bentrok slot ujian per ruangan/tanggal
CREATE INDEX idx_ujian_slot
    ON jadwal_ujian (tanggal_ujian, id_ruangan, jam_mulai, jam_selesai);

-- ---------------------------------------------------------------------
-- 3. STORED PROCEDURE: TAMBAH JADWAL UJIAN MASSAL (CONCURRENCY-SAFE)
--    Sama pola dengan sp_tambah_jadwal_kuliah:
--    - SERIALIZABLE + SELECT ... FOR UPDATE mencegah dua admin prodi
--      menjadwalkan ujian pada ruangan & jam yang sama secara serentak.
--    - Validasi kapasitas ruangan vs jumlah peserta ujian.
-- ---------------------------------------------------------------------
DELIMITER $$

DROP PROCEDURE IF EXISTS sp_tambah_jadwal_ujian $$

CREATE PROCEDURE sp_tambah_jadwal_ujian (
    IN p_id_mk          INT,
    IN p_id_ruangan     INT,
    IN p_jumlah_peserta INT,
    IN p_jenis_ujian    VARCHAR(5),
    IN p_tanggal_ujian  DATE,
    IN p_jam_mulai      TIME,
    IN p_jam_selesai    TIME,
    IN p_tahun_akademik VARCHAR(9),
    OUT p_status        VARCHAR(20),
    OUT p_pesan         VARCHAR(255)
)
proc_label: BEGIN
    DECLARE v_kapasitas INT DEFAULT 0;
    DECLARE v_bentrok   INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_status = 'ERROR';
        SET p_pesan  = 'Transaksi gagal (kemungkinan deadlock/lock timeout). Silakan coba lagi.';
    END;

    SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE;

    START TRANSACTION;

        -- Kunci baris ruangan sekaligus ambil kapasitasnya
        SELECT kapasitas INTO v_kapasitas
        FROM ruangan
        WHERE id_ruangan = p_id_ruangan
        FOR UPDATE;

        IF v_kapasitas IS NULL THEN
            ROLLBACK;
            SET p_status = 'GAGAL';
            SET p_pesan  = 'Ruangan tidak ditemukan.';
            LEAVE proc_label;
        ELSEIF p_jumlah_peserta > v_kapasitas THEN
            ROLLBACK;
            SET p_status = 'GAGAL';
            SET p_pesan  = CONCAT('Jumlah peserta (', p_jumlah_peserta,
                                   ') melebihi kapasitas ruangan (', v_kapasitas, ').');
            LEAVE proc_label;
        END IF;

        -- Kunci & cek bentrok jadwal ujian pada ruangan + tanggal yang sama
        SELECT COUNT(*) INTO v_bentrok
        FROM jadwal_ujian
        WHERE id_ruangan    = p_id_ruangan
          AND tanggal_ujian = p_tanggal_ujian
          AND (p_jam_mulai < jam_selesai AND p_jam_selesai > jam_mulai)
        FOR UPDATE;

        IF v_bentrok > 0 THEN
            ROLLBACK;
            SET p_status = 'GAGAL';
            SET p_pesan  = 'Ruangan sudah dipakai untuk ujian lain pada tanggal & jam tersebut.';
            LEAVE proc_label;
        ELSE
            INSERT INTO jadwal_ujian
                (id_mk, id_ruangan, jumlah_peserta, jenis_ujian, tanggal_ujian,
                 jam_mulai, jam_selesai, tahun_akademik)
            VALUES
                (p_id_mk, p_id_ruangan, p_jumlah_peserta, p_jenis_ujian, p_tanggal_ujian,
                 p_jam_mulai, p_jam_selesai, p_tahun_akademik);

            COMMIT;
            SET p_status = 'SUKSES';
            SET p_pesan  = 'Jadwal ujian massal berhasil ditambahkan.';
        END IF;

END $$

DELIMITER ;

-- ---------------------------------------------------------------------
-- 4. AUDIT LOG UNTUK JADWAL UJIAN (konsisten dengan 06_audit_log.sql)
-- ---------------------------------------------------------------------
DELIMITER $$

DROP TRIGGER IF EXISTS trg_ujian_after_insert $$
CREATE TRIGGER trg_ujian_after_insert
AFTER INSERT ON jadwal_ujian
FOR EACH ROW
BEGIN
    INSERT INTO log_audit (nama_tabel, aksi, id_record, user_db, detail_baru)
    VALUES ('jadwal_ujian', 'INSERT', NEW.id_ujian, CURRENT_USER(),
            JSON_OBJECT('id_ruangan', NEW.id_ruangan, 'tanggal_ujian', NEW.tanggal_ujian,
                        'jam_mulai', NEW.jam_mulai, 'jam_selesai', NEW.jam_selesai,
                        'jumlah_peserta', NEW.jumlah_peserta));
END $$

DROP TRIGGER IF EXISTS trg_ujian_after_update $$
CREATE TRIGGER trg_ujian_after_update
AFTER UPDATE ON jadwal_ujian
FOR EACH ROW
BEGIN
    INSERT INTO log_audit (nama_tabel, aksi, id_record, user_db, detail_lama, detail_baru)
    VALUES ('jadwal_ujian', 'UPDATE', NEW.id_ujian, CURRENT_USER(),
            JSON_OBJECT('id_ruangan', OLD.id_ruangan, 'tanggal_ujian', OLD.tanggal_ujian,
                        'jam_mulai', OLD.jam_mulai, 'jam_selesai', OLD.jam_selesai,
                        'jumlah_peserta', OLD.jumlah_peserta),
            JSON_OBJECT('id_ruangan', NEW.id_ruangan, 'tanggal_ujian', NEW.tanggal_ujian,
                        'jam_mulai', NEW.jam_mulai, 'jam_selesai', NEW.jam_selesai,
                        'jumlah_peserta', NEW.jumlah_peserta));
END $$

DROP TRIGGER IF EXISTS trg_ujian_after_delete $$
CREATE TRIGGER trg_ujian_after_delete
AFTER DELETE ON jadwal_ujian
FOR EACH ROW
BEGIN
    INSERT INTO log_audit (nama_tabel, aksi, id_record, user_db, detail_lama)
    VALUES ('jadwal_ujian', 'DELETE', OLD.id_ujian, CURRENT_USER(),
            JSON_OBJECT('id_ruangan', OLD.id_ruangan, 'tanggal_ujian', OLD.tanggal_ujian,
                        'jam_mulai', OLD.jam_mulai, 'jam_selesai', OLD.jam_selesai,
                        'jumlah_peserta', OLD.jumlah_peserta));
END $$

DELIMITER ;

-- ---------------------------------------------------------------------
-- 5. HAK AKSES TAMBAHAN (role yang sudah ada di 04_rbac_security.sql)
-- ---------------------------------------------------------------------
GRANT SELECT, INSERT, UPDATE ON db_jadwal_kuliah.akun_pengguna TO 'role_admin_prodi';
GRANT EXECUTE ON PROCEDURE db_jadwal_kuliah.sp_tambah_jadwal_ujian TO 'role_admin_prodi';
GRANT EXECUTE ON PROCEDURE db_jadwal_kuliah.sp_tambah_jadwal_ujian TO 'role_app_backend';
GRANT SELECT ON db_jadwal_kuliah.jadwal_ujian TO 'role_app_backend';
GRANT SELECT, INSERT ON db_jadwal_kuliah.akun_pengguna TO 'role_app_backend';

FLUSH PRIVILEGES;

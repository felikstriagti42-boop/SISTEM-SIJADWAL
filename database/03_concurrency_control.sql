-- =====================================================================
-- KASUS 3: PERFORMANCE ENGINEERING (FOKUS UTAMA)
-- File: 03_concurrency_control.sql
--
-- Tujuan:
--   Mencegah dua admin prodi menginput jadwal pada slot yang sama
--   (ruangan + hari + jam) secara bersamaan (race condition), yang
--   bisa terjadi saat ribuan baris data diinput serentak menjelang
--   awal semester.
--
-- Strategi:
--   1. Isolation level transaksi diset ke SERIALIZABLE -> transaksi
--      diperlakukan seolah berjalan satu per satu (paling ketat),
--      mencegah phantom read pada pengecekan slot kosong.
--   2. SELECT ... FOR UPDATE digunakan untuk mengunci baris jadwal
--      yang relevan (ruangan + hari yang sama) selama transaksi,
--      sehingga sesi lain yang ingin memasukkan jadwal pada
--      ruangan & hari yang sama harus menunggu (blocking) hingga
--      transaksi pertama selesai (COMMIT/ROLLBACK).
-- =====================================================================
USE db_jadwal_kuliah;

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_tambah_jadwal_kuliah $$

CREATE PROCEDURE sp_tambah_jadwal_kuliah (
    IN p_id_mk        INT,
    IN p_id_dosen     INT,
    IN p_id_ruangan   INT,
    IN p_hari         VARCHAR(10),
    IN p_jam_mulai    TIME,
    IN p_jam_selesai  TIME,
    IN p_semester     VARCHAR(10),
    IN p_tahun_akademik VARCHAR(9),
    OUT p_status      VARCHAR(20),
    OUT p_pesan       VARCHAR(255)
)
proc_label: BEGIN
    DECLARE v_bentrok INT DEFAULT 0;
    DECLARE v_bentrok_dosen INT DEFAULT 0;

    -- Handler jika terjadi deadlock / lock wait timeout dari MySQL
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_status = 'ERROR';
        SET p_pesan  = 'Transaksi gagal (kemungkinan deadlock/lock timeout). Silakan coba lagi.';
    END;

    -- 1) Set tingkat isolasi tertinggi khusus untuk transaksi ini
    SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE;

    START TRANSACTION;

        -- 2) Kunci baris-baris jadwal pada ruangan & hari yang sama.
        --    Memakai composite index idx_composite_slot agar lock
        --    dilakukan secara efisien (index range lock), bukan full
        --    table lock.
        SELECT COUNT(*) INTO v_bentrok
        FROM jadwal_kuliah
        WHERE id_ruangan = p_id_ruangan
          AND hari       = p_hari
          AND status     = 'Aktif'
          AND (p_jam_mulai < jam_selesai AND p_jam_selesai > jam_mulai)
        FOR UPDATE;

        -- 3) Cek juga bentrok jadwal dosen yang sama di hari & jam sama
        SELECT COUNT(*) INTO v_bentrok_dosen
        FROM jadwal_kuliah
        WHERE id_dosen = p_id_dosen
          AND hari     = p_hari
          AND status   = 'Aktif'
          AND (p_jam_mulai < jam_selesai AND p_jam_selesai > jam_mulai)
        FOR UPDATE;

        IF v_bentrok > 0 THEN
            ROLLBACK;
            SET p_status = 'GAGAL';
            SET p_pesan  = 'Ruangan sudah terpakai pada slot waktu tersebut.';
            LEAVE proc_label;
        ELSEIF v_bentrok_dosen > 0 THEN
            ROLLBACK;
            SET p_status = 'GAGAL';
            SET p_pesan  = 'Dosen sudah memiliki jadwal lain pada slot waktu tersebut.';
            LEAVE proc_label;
        ELSE
            INSERT INTO jadwal_kuliah
                (id_mk, id_dosen, id_ruangan, hari, jam_mulai, jam_selesai, semester, tahun_akademik)
            VALUES
                (p_id_mk, p_id_dosen, p_id_ruangan, p_hari, p_jam_mulai, p_jam_selesai, p_semester, p_tahun_akademik);

            COMMIT;
            SET p_status = 'SUKSES';
            SET p_pesan  = 'Jadwal berhasil ditambahkan.';
        END IF;

END $$

DELIMITER ;

-- =====================================================================
-- CONTOH PEMANGGILAN
-- =====================================================================
-- CALL sp_tambah_jadwal_kuliah(1, 1, 1, 'Rabu', '08:00:00', '10:30:00',
--                               'Ganjil', '2025/2026', @status, @pesan);
-- SELECT @status, @pesan;

-- =====================================================================
-- SIMULASI KONKURENSI TINGGI TINGGI (UNTUK TESTING MANUAL)
-- =====================================================================
-- Sesi A (Admin Prodi 1):
--   SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE;
--   START TRANSACTION;
--   SELECT * FROM jadwal_kuliah
--     WHERE id_ruangan = 1 AND hari = 'Rabu' AND status='Aktif'
--     FOR UPDATE;
--   -- (belum COMMIT, simulasikan delay)
--
-- Sesi B (Admin Prodi 2) -- dijalankan SAAT sesi A belum commit:
--   SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE;
--   START TRANSACTION;
--   SELECT * FROM jadwal_kuliah
--     WHERE id_ruangan = 1 AND hari = 'Rabu' AND status='Aktif'
--     FOR UPDATE;
--   -- Sesi B akan BLOCKING (menunggu) sampai Sesi A COMMIT/ROLLBACK,
--   -- sehingga tidak mungkin dua jadwal bentrok ter-insert bersamaan.

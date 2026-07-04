-- =====================================================================
-- KASUS 3: AUDIT LOG (AKUNTABILITAS PERUBAHAN DATA - UU PDP)
-- File: 06_audit_log.sql
-- =====================================================================
USE db_jadwal_kuliah;

CREATE TABLE IF NOT EXISTS log_audit (
    id_log      BIGINT AUTO_INCREMENT PRIMARY KEY,
    nama_tabel  VARCHAR(50) NOT NULL,
    aksi        ENUM('INSERT','UPDATE','DELETE') NOT NULL,
    id_record   BIGINT NOT NULL,
    user_db     VARCHAR(100) NOT NULL,
    waktu       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    detail_lama JSON NULL,
    detail_baru JSON NULL
) ENGINE=InnoDB;

DELIMITER $$

DROP TRIGGER IF EXISTS trg_jadwal_after_insert $$
CREATE TRIGGER trg_jadwal_after_insert
AFTER INSERT ON jadwal_kuliah
FOR EACH ROW
BEGIN
    INSERT INTO log_audit (nama_tabel, aksi, id_record, user_db, detail_baru)
    VALUES ('jadwal_kuliah', 'INSERT', NEW.id_jadwal, CURRENT_USER(),
            JSON_OBJECT('id_ruangan', NEW.id_ruangan, 'hari', NEW.hari,
                        'jam_mulai', NEW.jam_mulai, 'jam_selesai', NEW.jam_selesai));
END $$

DROP TRIGGER IF EXISTS trg_jadwal_after_update $$
CREATE TRIGGER trg_jadwal_after_update
AFTER UPDATE ON jadwal_kuliah
FOR EACH ROW
BEGIN
    INSERT INTO log_audit (nama_tabel, aksi, id_record, user_db, detail_lama, detail_baru)
    VALUES ('jadwal_kuliah', 'UPDATE', NEW.id_jadwal, CURRENT_USER(),
            JSON_OBJECT('id_ruangan', OLD.id_ruangan, 'hari', OLD.hari,
                        'jam_mulai', OLD.jam_mulai, 'jam_selesai', OLD.jam_selesai),
            JSON_OBJECT('id_ruangan', NEW.id_ruangan, 'hari', NEW.hari,
                        'jam_mulai', NEW.jam_mulai, 'jam_selesai', NEW.jam_selesai));
END $$

DROP TRIGGER IF EXISTS trg_jadwal_after_delete $$
CREATE TRIGGER trg_jadwal_after_delete
AFTER DELETE ON jadwal_kuliah
FOR EACH ROW
BEGIN
    INSERT INTO log_audit (nama_tabel, aksi, id_record, user_db, detail_lama)
    VALUES ('jadwal_kuliah', 'DELETE', OLD.id_jadwal, CURRENT_USER(),
            JSON_OBJECT('id_ruangan', OLD.id_ruangan, 'hari', OLD.hari,
                        'jam_mulai', OLD.jam_mulai, 'jam_selesai', OLD.jam_selesai));
END $$

DELIMITER ;

<?php
require_once __DIR__ . '/config.php';

const DAFTAR_HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

function jamTampil(?string $jam): string
{
    if (!$jam) return '-';
    return substr($jam, 0, 5);
}

function tanggalTampil(?string $tanggal): string
{
    if (!$tanggal) return '-';
    $bulan = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun',
              '07'=>'Jul','08'=>'Agu','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'];
    [$y, $m, $d] = explode('-', $tanggal);
    return $d . ' ' . ($bulan[$m] ?? $m) . ' ' . $y;
}

/**
 * Cek bentrok jadwal kuliah (ruangan & dosen) di dalam transaksi dengan
 * SELECT ... FOR UPDATE, dipakai untuk EDIT jadwal (INSERT baru memakai
 * stored procedure sp_tambah_jadwal_kuliah agar konsisten).
 * Mengembalikan: ['ok' => bool, 'pesan' => string]
 */
function editJadwalKuliahAman(PDO $pdo, array $data, int $idJadwal): array
{
    $pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE');
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM jadwal_kuliah
             WHERE id_ruangan = ? AND hari = ? AND status = "Aktif"
               AND id_jadwal <> ?
               AND (? < jam_selesai AND ? > jam_mulai)
             FOR UPDATE'
        );
        $stmt->execute([$data['id_ruangan'], $data['hari'], $idJadwal, $data['jam_mulai'], $data['jam_selesai']]);
        if ((int)$stmt->fetchColumn() > 0) {
            $pdo->rollBack();
            return ['ok' => false, 'pesan' => 'Ruangan sudah terpakai pada slot waktu tersebut.'];
        }

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM jadwal_kuliah
             WHERE id_dosen = ? AND hari = ? AND status = "Aktif"
               AND id_jadwal <> ?
               AND (? < jam_selesai AND ? > jam_mulai)
             FOR UPDATE'
        );
        $stmt->execute([$data['id_dosen'], $data['hari'], $idJadwal, $data['jam_mulai'], $data['jam_selesai']]);
        if ((int)$stmt->fetchColumn() > 0) {
            $pdo->rollBack();
            return ['ok' => false, 'pesan' => 'Dosen sudah memiliki jadwal lain pada slot waktu tersebut.'];
        }

        $upd = $pdo->prepare(
            'UPDATE jadwal_kuliah
             SET id_mk = ?, id_dosen = ?, id_ruangan = ?, hari = ?, jam_mulai = ?, jam_selesai = ?,
                 semester = ?, tahun_akademik = ?
             WHERE id_jadwal = ?'
        );
        $upd->execute([
            $data['id_mk'], $data['id_dosen'], $data['id_ruangan'], $data['hari'],
            $data['jam_mulai'], $data['jam_selesai'], $data['semester'], $data['tahun_akademik'],
            $idJadwal,
        ]);

        $pdo->commit();
        return ['ok' => true, 'pesan' => 'Jadwal berhasil diperbarui.'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['ok' => false, 'pesan' => 'Gagal memperbarui jadwal: ' . $e->getMessage()];
    }
}

/** Sama seperti di atas tapi untuk jadwal ujian massal (+ cek kapasitas). */
function editJadwalUjianAman(PDO $pdo, array $data, int $idUjian): array
{
    $pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE');
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT kapasitas FROM ruangan WHERE id_ruangan = ? FOR UPDATE');
        $stmt->execute([$data['id_ruangan']]);
        $kapasitas = $stmt->fetchColumn();

        if ($kapasitas === false) {
            $pdo->rollBack();
            return ['ok' => false, 'pesan' => 'Ruangan tidak ditemukan.'];
        }
        if ((int)$data['jumlah_peserta'] > (int)$kapasitas) {
            $pdo->rollBack();
            return ['ok' => false, 'pesan' => "Jumlah peserta ({$data['jumlah_peserta']}) melebihi kapasitas ruangan ({$kapasitas})."];
        }

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM jadwal_ujian
             WHERE id_ruangan = ? AND tanggal_ujian = ? AND id_ujian <> ?
               AND (? < jam_selesai AND ? > jam_mulai)
             FOR UPDATE'
        );
        $stmt->execute([$data['id_ruangan'], $data['tanggal_ujian'], $idUjian, $data['jam_mulai'], $data['jam_selesai']]);
        if ((int)$stmt->fetchColumn() > 0) {
            $pdo->rollBack();
            return ['ok' => false, 'pesan' => 'Ruangan sudah dipakai untuk ujian lain pada tanggal & jam tersebut.'];
        }

        $upd = $pdo->prepare(
            'UPDATE jadwal_ujian
             SET id_mk = ?, id_ruangan = ?, jumlah_peserta = ?, jenis_ujian = ?, tanggal_ujian = ?,
                 jam_mulai = ?, jam_selesai = ?, tahun_akademik = ?
             WHERE id_ujian = ?'
        );
        $upd->execute([
            $data['id_mk'], $data['id_ruangan'], $data['jumlah_peserta'], $data['jenis_ujian'],
            $data['tanggal_ujian'], $data['jam_mulai'], $data['jam_selesai'], $data['tahun_akademik'],
            $idUjian,
        ]);

        $pdo->commit();
        return ['ok' => true, 'pesan' => 'Jadwal ujian berhasil diperbarui.'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['ok' => false, 'pesan' => 'Gagal memperbarui jadwal ujian: ' . $e->getMessage()];
    }
}

/** Palet warna konsisten per mata kuliah untuk plot jadwal mingguan. */
function warnaUntukMk(int $idMk): string
{
    $palet = ['#3F5B98', '#C96A3C', '#2F9E68', '#8A5FBF', '#B84A63', '#3C8FA3', '#A67C3D', '#5C6B9E'];
    return $palet[$idMk % count($palet)];
}

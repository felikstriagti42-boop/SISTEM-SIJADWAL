<?php
$halamanAktif = 'jadwal_kuliah';
$judulHalaman = 'Tambah Jadwal Kuliah';
$subJudulHalaman = 'Disimpan melalui stored procedure sp_tambah_jadwal_kuliah (aman dari bentrok input serentak)';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
wajibPeran(['admin_prodi']);
require_once __DIR__ . '/includes/header.php';

$pdo = getKoneksi();
$errorPesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        $errorPesan = 'Sesi form tidak valid, silakan coba lagi.';
    } else {
        $idMk       = (int)$_POST['id_mk'];
        $idDosen    = (int)$_POST['id_dosen'];
        $idRuangan  = (int)$_POST['id_ruangan'];
        $hari       = $_POST['hari'];
        $jamMulai   = $_POST['jam_mulai'];
        $jamSelesai = $_POST['jam_selesai'];
        $semester   = trim($_POST['semester']);
        $tahunAkademik = trim($_POST['tahun_akademik']);

        try {
            $stmt = $pdo->prepare('CALL sp_tambah_jadwal_kuliah(?,?,?,?,?,?,?,?,@status,@pesan)');
            $stmt->execute([$idMk, $idDosen, $idRuangan, $hari, $jamMulai, $jamSelesai, $semester, $tahunAkademik]);
            $stmt->closeCursor();
            $hasil = $pdo->query('SELECT @status AS status, @pesan AS pesan')->fetch();

            if ($hasil['status'] === 'SUKSES') {
                setFlash('sukses', $hasil['pesan']);
                header('Location: jadwal_kuliah.php');
                exit;
            }
            $errorPesan = $hasil['pesan'];
        } catch (Throwable $e) {
            $errorPesan = 'Gagal menyimpan jadwal: ' . $e->getMessage();
        }
    }
}

$mkList = $pdo->query('SELECT mk.*, p.nama_prodi FROM mata_kuliah mk JOIN prodi p ON p.id_prodi = mk.id_prodi ORDER BY mk.kode_mk')->fetchAll();
$dosenList = $pdo->query('SELECT * FROM dosen ORDER BY nama_dosen')->fetchAll();
$ruanganList = $pdo->query('SELECT * FROM ruangan ORDER BY nama_ruangan')->fetchAll();
$old = $_POST ?? [];
?>
<div class="card" style="max-width:760px;">
  <?php if ($errorPesan): ?><div class="alert alert-error"><?= htmlspecialchars($errorPesan) ?></div><?php endif; ?>
  <form method="post">
    <?= csrfField() ?>
    <div class="form-grid">
      <div class="field">
        <label>Mata Kuliah</label>
        <select name="id_mk" required>
          <option value="">— Pilih Mata Kuliah —</option>
          <?php foreach ($mkList as $mk): ?>
            <option value="<?= $mk['id_mk'] ?>" <?= (($old['id_mk'] ?? '') == $mk['id_mk']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($mk['kode_mk'] . ' — ' . $mk['nama_mk'] . ' (' . $mk['nama_prodi'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Dosen Pengampu</label>
        <select name="id_dosen" required>
          <option value="">— Pilih Dosen —</option>
          <?php foreach ($dosenList as $d): ?>
            <option value="<?= $d['id_dosen'] ?>" <?= (($old['id_dosen'] ?? '') == $d['id_dosen']) ? 'selected' : '' ?>><?= htmlspecialchars($d['nama_dosen']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Ruangan</label>
        <select name="id_ruangan" required>
          <option value="">— Pilih Ruangan —</option>
          <?php foreach ($ruanganList as $r): ?>
            <option value="<?= $r['id_ruangan'] ?>" <?= (($old['id_ruangan'] ?? '') == $r['id_ruangan']) ? 'selected' : '' ?>><?= htmlspecialchars($r['nama_ruangan'] . ' · kapasitas ' . $r['kapasitas']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Hari</label>
        <select name="hari" required>
          <?php foreach (DAFTAR_HARI as $h): ?>
            <option value="<?= $h ?>" <?= (($old['hari'] ?? '') === $h) ? 'selected' : '' ?>><?= $h ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Jam Mulai</label>
        <input type="time" name="jam_mulai" value="<?= htmlspecialchars($old['jam_mulai'] ?? '08:30') ?>" required>
      </div>
      <div class="field">
        <label>Jam Selesai</label>
        <input type="time" name="jam_selesai" value="<?= htmlspecialchars($old['jam_selesai'] ?? '16:00') ?>" required>
      </div>
      <div class="field">
        <label>Semester</label>
        <select name="semester" required>
          <option value="Ganjil" <?= (($old['semester'] ?? '') === 'Ganjil') ? 'selected' : '' ?>>Ganjil</option>
          <option value="Genap" <?= (($old['semester'] ?? '') === 'Genap') ? 'selected' : '' ?>>Genap</option>
        </select>
      </div>
      <div class="field">
        <label>Tahun Akademik</label>
        <input type="text" name="tahun_akademik" placeholder="2026/2027" value="<?= htmlspecialchars($old['tahun_akademik'] ?? '2026/2027') ?>" required>
      </div>
    </div>
    <p class="help-text">Sistem otomatis menolak jadwal jika ruangan atau dosen sudah terpakai pada slot waktu yang sama (dicegah di level database dengan transaksi terkunci).</p>
    <div style="display:flex;gap:10px;margin-top:14px;">
      <button type="submit" class="btn btn-primary" style="width:auto;">Simpan Jadwal</button>
      <a href="jadwal_kuliah.php" class="btn btn-ghost">Batal</a>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

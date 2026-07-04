<?php
$halamanAktif = 'jadwal_ujian';
$judulHalaman = 'Ubah Jadwal Ujian';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
wajibPeran(['admin_prodi']);
require_once __DIR__ . '/includes/header.php';

$pdo = getKoneksi();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM jadwal_ujian WHERE id_ujian = ?');
$stmt->execute([$id]);
$ujian = $stmt->fetch();

if (!$ujian) {
    setFlash('error', 'Jadwal ujian tidak ditemukan.');
    header('Location: jadwal_ujian.php');
    exit;
}

$errorPesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        $errorPesan = 'Sesi form tidak valid, silakan coba lagi.';
    } else {
        $data = [
            'id_mk'          => (int)$_POST['id_mk'],
            'id_ruangan'     => (int)$_POST['id_ruangan'],
            'jumlah_peserta' => (int)$_POST['jumlah_peserta'],
            'jenis_ujian'    => $_POST['jenis_ujian'],
            'tanggal_ujian'  => $_POST['tanggal_ujian'],
            'jam_mulai'      => $_POST['jam_mulai'],
            'jam_selesai'    => $_POST['jam_selesai'],
            'tahun_akademik' => trim($_POST['tahun_akademik']),
        ];
        $hasil = editJadwalUjianAman($pdo, $data, $id);
        if ($hasil['ok']) {
            setFlash('sukses', $hasil['pesan']);
            header('Location: jadwal_ujian.php');
            exit;
        }
        $errorPesan = $hasil['pesan'];
        $ujian = array_merge($ujian, $data);
    }
}

$mkList = $pdo->query('SELECT mk.*, p.nama_prodi FROM mata_kuliah mk JOIN prodi p ON p.id_prodi = mk.id_prodi ORDER BY mk.kode_mk')->fetchAll();
$ruanganList = $pdo->query('SELECT * FROM ruangan ORDER BY kapasitas DESC')->fetchAll();
?>
<div class="card" style="max-width:760px;">
  <?php if ($errorPesan): ?><div class="alert alert-error"><?= htmlspecialchars($errorPesan) ?></div><?php endif; ?>
  <form method="post">
    <?= csrfField() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="form-grid">
      <div class="field">
        <label>Mata Kuliah</label>
        <select name="id_mk" required>
          <?php foreach ($mkList as $mk): ?>
            <option value="<?= $mk['id_mk'] ?>" <?= $ujian['id_mk'] == $mk['id_mk'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($mk['kode_mk'] . ' — ' . $mk['nama_mk'] . ' (' . $mk['nama_prodi'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Jenis Ujian</label>
        <select name="jenis_ujian" required>
          <option value="UTS" <?= $ujian['jenis_ujian'] === 'UTS' ? 'selected' : '' ?>>UTS</option>
          <option value="UAS" <?= $ujian['jenis_ujian'] === 'UAS' ? 'selected' : '' ?>>UAS</option>
        </select>
      </div>
      <div class="field">
        <label>Ruangan</label>
        <select name="id_ruangan" required>
          <?php foreach ($ruanganList as $r): ?>
            <option value="<?= $r['id_ruangan'] ?>" <?= $ujian['id_ruangan'] == $r['id_ruangan'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nama_ruangan'] . ' · kapasitas ' . $r['kapasitas']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Jumlah Peserta</label>
        <input type="number" name="jumlah_peserta" min="1" value="<?= $ujian['jumlah_peserta'] ?>" required>
      </div>
      <div class="field">
        <label>Tanggal Ujian</label>
        <input type="date" name="tanggal_ujian" value="<?= $ujian['tanggal_ujian'] ?>" required>
      </div>
      <div class="field">
        <label>Tahun Akademik</label>
        <input type="text" name="tahun_akademik" value="<?= htmlspecialchars($ujian['tahun_akademik']) ?>" required>
      </div>
      <div class="field">
        <label>Jam Mulai</label>
        <input type="time" name="jam_mulai" value="<?= jamTampil($ujian['jam_mulai']) ?>" required>
      </div>
      <div class="field">
        <label>Jam Selesai</label>
        <input type="time" name="jam_selesai" value="<?= jamTampil($ujian['jam_selesai']) ?>" required>
      </div>
    </div>
    <div style="display:flex;gap:10px;margin-top:14px;">
      <button type="submit" class="btn btn-primary" style="width:auto;">Simpan Perubahan</button>
      <a href="jadwal_ujian.php" class="btn btn-ghost">Batal</a>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

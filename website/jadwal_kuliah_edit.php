<?php
$halamanAktif = 'jadwal_kuliah';
$judulHalaman = 'Ubah Jadwal Kuliah';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
wajibPeran(['admin_prodi']);
require_once __DIR__ . '/includes/header.php';

$pdo = getKoneksi();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM jadwal_kuliah WHERE id_jadwal = ?');
$stmt->execute([$id]);
$jadwal = $stmt->fetch();

if (!$jadwal) {
    setFlash('error', 'Jadwal tidak ditemukan.');
    header('Location: jadwal_kuliah.php');
    exit;
}

$errorPesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        $errorPesan = 'Sesi form tidak valid, silakan coba lagi.';
    } else {
        $data = [
            'id_mk'          => (int)$_POST['id_mk'],
            'id_dosen'       => (int)$_POST['id_dosen'],
            'id_ruangan'     => (int)$_POST['id_ruangan'],
            'hari'           => $_POST['hari'],
            'jam_mulai'      => $_POST['jam_mulai'],
            'jam_selesai'    => $_POST['jam_selesai'],
            'semester'       => trim($_POST['semester']),
            'tahun_akademik' => trim($_POST['tahun_akademik']),
        ];
        $hasil = editJadwalKuliahAman($pdo, $data, $id);
        if ($hasil['ok']) {
            setFlash('sukses', $hasil['pesan']);
            header('Location: jadwal_kuliah.php');
            exit;
        }
        $errorPesan = $hasil['pesan'];
        $jadwal = array_merge($jadwal, $data);
    }
}

$mkList = $pdo->query('SELECT mk.*, p.nama_prodi FROM mata_kuliah mk JOIN prodi p ON p.id_prodi = mk.id_prodi ORDER BY mk.kode_mk')->fetchAll();
$dosenList = $pdo->query('SELECT * FROM dosen ORDER BY nama_dosen')->fetchAll();
$ruanganList = $pdo->query('SELECT * FROM ruangan ORDER BY nama_ruangan')->fetchAll();
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
            <option value="<?= $mk['id_mk'] ?>" <?= $jadwal['id_mk'] == $mk['id_mk'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($mk['kode_mk'] . ' — ' . $mk['nama_mk'] . ' (' . $mk['nama_prodi'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Dosen Pengampu</label>
        <select name="id_dosen" required>
          <?php foreach ($dosenList as $d): ?>
            <option value="<?= $d['id_dosen'] ?>" <?= $jadwal['id_dosen'] == $d['id_dosen'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nama_dosen']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Ruangan</label>
        <select name="id_ruangan" required>
          <?php foreach ($ruanganList as $r): ?>
            <option value="<?= $r['id_ruangan'] ?>" <?= $jadwal['id_ruangan'] == $r['id_ruangan'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nama_ruangan'] . ' · kapasitas ' . $r['kapasitas']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Hari</label>
        <select name="hari" required>
          <?php foreach (DAFTAR_HARI as $h): ?>
            <option value="<?= $h ?>" <?= $jadwal['hari'] === $h ? 'selected' : '' ?>><?= $h ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Jam Mulai</label>
        <input type="time" name="jam_mulai" value="<?= jamTampil($jadwal['jam_mulai']) ?>" required>
      </div>
      <div class="field">
        <label>Jam Selesai</label>
        <input type="time" name="jam_selesai" value="<?= jamTampil($jadwal['jam_selesai']) ?>" required>
      </div>
      <div class="field">
        <label>Semester</label>
        <select name="semester" required>
          <option value="Ganjil" <?= $jadwal['semester'] === 'Ganjil' ? 'selected' : '' ?>>Ganjil</option>
          <option value="Genap" <?= $jadwal['semester'] === 'Genap' ? 'selected' : '' ?>>Genap</option>
        </select>
      </div>
      <div class="field">
        <label>Tahun Akademik</label>
        <input type="text" name="tahun_akademik" value="<?= htmlspecialchars($jadwal['tahun_akademik']) ?>" required>
      </div>
    </div>
    <div style="display:flex;gap:10px;margin-top:14px;">
      <button type="submit" class="btn btn-primary" style="width:auto;">Simpan Perubahan</button>
      <a href="jadwal_kuliah.php" class="btn btn-ghost">Batal</a>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
$halamanAktif = 'ruangan';
$judulHalaman = 'Ubah Ruangan';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
wajibPeran(['admin_prodi']);
require_once __DIR__ . '/includes/header.php';

$pdo = getKoneksi();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM ruangan WHERE id_ruangan = ?');
$stmt->execute([$id]);
$ruangan = $stmt->fetch();

if (!$ruangan) {
    setFlash('error', 'Ruangan tidak ditemukan.');
    header('Location: ruangan.php');
    exit;
}

$errorPesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        $errorPesan = 'Sesi form tidak valid, silakan coba lagi.';
    } else {
        $kode = trim($_POST['kode_ruangan']);
        $nama = trim($_POST['nama_ruangan']);
        $tipe = $_POST['tipe'];
        $kapasitas = (int)$_POST['kapasitas'];
        $fasilitasJson = $_POST['fasilitas_json'] ?: '{}';

        if (json_decode($fasilitasJson) === null && $fasilitasJson !== 'null') {
            $errorPesan = 'Data fasilitas tidak valid.';
        } else {
            try {
                $upd = $pdo->prepare(
                    'UPDATE ruangan SET kode_ruangan=?, nama_ruangan=?, tipe=?, kapasitas=?, spesifikasi_fasilitas=?
                     WHERE id_ruangan=?'
                );
                $upd->execute([$kode, $nama, $tipe, $kapasitas, $fasilitasJson, $id]);
                setFlash('sukses', 'Ruangan berhasil diperbarui.');
                header('Location: ruangan.php');
                exit;
            } catch (Throwable $e) {
                $errorPesan = 'Gagal memperbarui ruangan: ' . $e->getMessage();
            }
            $ruangan = array_merge($ruangan, compact('kode', 'nama', 'tipe', 'kapasitas'));
        }
    }
}

$fasilitasLama = $ruangan['spesifikasi_fasilitas'] ? json_decode($ruangan['spesifikasi_fasilitas'], true) : [];
?>
<div class="card" style="max-width:700px;">
  <?php if ($errorPesan): ?><div class="alert alert-error"><?= htmlspecialchars($errorPesan) ?></div><?php endif; ?>
  <form method="post" onsubmit="return siapkanFasilitasJSON(this)">
    <?= csrfField() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="form-grid">
      <div class="field"><label>Kode Ruangan</label><input type="text" name="kode_ruangan" value="<?= htmlspecialchars($ruangan['kode_ruangan']) ?>" required></div>
      <div class="field"><label>Nama Ruangan</label><input type="text" name="nama_ruangan" value="<?= htmlspecialchars($ruangan['nama_ruangan']) ?>" required></div>
      <div class="field">
        <label>Tipe</label>
        <select name="tipe">
          <option value="Kelas" <?= $ruangan['tipe'] === 'Kelas' ? 'selected' : '' ?>>Kelas</option>
          <option value="Lab" <?= $ruangan['tipe'] === 'Lab' ? 'selected' : '' ?>>Lab</option>
        </select>
      </div>
      <div class="field"><label>Kapasitas (orang)</label><input type="number" name="kapasitas" min="1" value="<?= $ruangan['kapasitas'] ?>" required></div>
    </div>

    <div class="field full" style="margin-top:6px;">
      <label>Spesifikasi Fasilitas (fleksibel — disimpan sebagai JSON)</label>
      <div id="fasilitas-rows"></div>
      <button type="button" class="btn btn-ghost btn-sm" onclick="tambahBarisFasilitas()">+ Tambah Baris Fasilitas</button>
      <div class="help-text">Contoh: RAM = 16GB, Proyektor = true, Software = MySQL 9.0, VS Code (pisahkan dengan koma untuk daftar).</div>
      <input type="hidden" name="fasilitas_json" id="fasilitas_json">
    </div>

    <div style="display:flex;gap:10px;margin-top:14px;">
      <button type="submit" class="btn btn-primary" style="width:auto;">Simpan Perubahan</button>
      <a href="ruangan.php" class="btn btn-ghost">Batal</a>
    </div>
  </form>
</div>
<script src="assets/js/app.js"></script>
<script>
  const dataFasilitasLama = <?= json_encode($fasilitasLama, JSON_UNESCAPED_UNICODE) ?>;
  const kunci = Object.keys(dataFasilitasLama);
  if (kunci.length) {
    kunci.forEach(k => {
      const v = dataFasilitasLama[k];
      const tampil = Array.isArray(v) ? v.join(', ') : (typeof v === 'boolean' ? (v ? 'true' : 'false') : v);
      tambahBarisFasilitas(k, String(tampil));
    });
  } else {
    tambahBarisFasilitas();
  }
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

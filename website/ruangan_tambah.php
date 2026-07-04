<?php
$halamanAktif = 'ruangan';
$judulHalaman = 'Tambah Ruangan';
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
        $kode = trim($_POST['kode_ruangan']);
        $nama = trim($_POST['nama_ruangan']);
        $tipe = $_POST['tipe'];
        $kapasitas = (int)$_POST['kapasitas'];
        $fasilitasJson = $_POST['fasilitas_json'] ?: '{}';

        if (json_decode($fasilitasJson) === null && $fasilitasJson !== 'null') {
            $errorPesan = 'Data fasilitas tidak valid.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO ruangan (kode_ruangan, nama_ruangan, tipe, kapasitas, spesifikasi_fasilitas)
                     VALUES (?,?,?,?,?)'
                );
                $stmt->execute([$kode, $nama, $tipe, $kapasitas, $fasilitasJson]);
                setFlash('sukses', 'Ruangan berhasil ditambahkan.');
                header('Location: ruangan.php');
                exit;
            } catch (Throwable $e) {
                $errorPesan = 'Gagal menyimpan ruangan (kode mungkin sudah dipakai): ' . $e->getMessage();
            }
        }
    }
}
$old = $_POST ?? [];
?>
<div class="card" style="max-width:700px;">
  <?php if ($errorPesan): ?><div class="alert alert-error"><?= htmlspecialchars($errorPesan) ?></div><?php endif; ?>
  <form method="post" onsubmit="return siapkanFasilitasJSON(this)">
    <?= csrfField() ?>
    <div class="form-grid">
      <div class="field"><label>Kode Ruangan</label><input type="text" name="kode_ruangan" placeholder="LAB-03" value="<?= htmlspecialchars($old['kode_ruangan'] ?? '') ?>" required></div>
      <div class="field"><label>Nama Ruangan</label><input type="text" name="nama_ruangan" placeholder="Lab Komputer 3" value="<?= htmlspecialchars($old['nama_ruangan'] ?? '') ?>" required></div>
      <div class="field">
        <label>Tipe</label>
        <select name="tipe">
          <option value="Kelas" <?= (($old['tipe'] ?? '') === 'Kelas') ? 'selected' : '' ?>>Kelas</option>
          <option value="Lab" <?= (($old['tipe'] ?? '') === 'Lab') ? 'selected' : '' ?>>Lab</option>
        </select>
      </div>
      <div class="field"><label>Kapasitas (orang)</label><input type="number" name="kapasitas" min="1" value="<?= htmlspecialchars($old['kapasitas'] ?? '40') ?>" required></div>
    </div>

    <div class="field full" style="margin-top:6px;">
      <label>Spesifikasi Fasilitas (fleksibel — disimpan sebagai JSON)</label>
      <div id="fasilitas-rows"></div>
      <button type="button" class="btn btn-ghost btn-sm" onclick="tambahBarisFasilitas()">+ Tambah Baris Fasilitas</button>
      <div class="help-text">Contoh: RAM = 16GB, Proyektor = true, Software = MySQL 9.0, VS Code (pisahkan dengan koma untuk daftar).</div>
      <input type="hidden" name="fasilitas_json" id="fasilitas_json">
    </div>

    <div style="display:flex;gap:10px;margin-top:14px;">
      <button type="submit" class="btn btn-primary" style="width:auto;">Simpan Ruangan</button>
      <a href="ruangan.php" class="btn btn-ghost">Batal</a>
    </div>
  </form>
</div>
<script src="assets/js/app.js"></script>
<script>tambahBarisFasilitas('AC', 'true'); tambahBarisFasilitas('Proyektor', 'true');</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

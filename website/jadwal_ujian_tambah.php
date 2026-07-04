<?php
$halamanAktif = 'jadwal_ujian';
$judulHalaman = 'Tambah Jadwal Ujian Massal';
$subJudulHalaman = 'Disimpan melalui stored procedure sp_tambah_jadwal_ujian (validasi kapasitas + anti-bentrok)';
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
        $idMk = (int)$_POST['id_mk'];
        $idRuangan = (int)$_POST['id_ruangan'];
        $jumlahPeserta = (int)$_POST['jumlah_peserta'];
        $jenisUjian = $_POST['jenis_ujian'];
        $tanggal = $_POST['tanggal_ujian'];
        $jamMulai = $_POST['jam_mulai'];
        $jamSelesai = $_POST['jam_selesai'];
        $tahunAkademik = trim($_POST['tahun_akademik']);

        try {
            $stmt = $pdo->prepare('CALL sp_tambah_jadwal_ujian(?,?,?,?,?,?,?,?,@status,@pesan)');
            $stmt->execute([$idMk, $idRuangan, $jumlahPeserta, $jenisUjian, $tanggal, $jamMulai, $jamSelesai, $tahunAkademik]);
            $stmt->closeCursor();
            $hasil = $pdo->query('SELECT @status AS status, @pesan AS pesan')->fetch();

            if ($hasil['status'] === 'SUKSES') {
                setFlash('sukses', $hasil['pesan']);
                header('Location: jadwal_ujian.php');
                exit;
            }
            $errorPesan = $hasil['pesan'];
        } catch (Throwable $e) {
            $errorPesan = 'Gagal menyimpan jadwal ujian: ' . $e->getMessage();
        }
    }
}

$mkList = $pdo->query('SELECT mk.*, p.nama_prodi FROM mata_kuliah mk JOIN prodi p ON p.id_prodi = mk.id_prodi ORDER BY mk.kode_mk')->fetchAll();
$ruanganList = $pdo->query('SELECT * FROM ruangan ORDER BY kapasitas DESC')->fetchAll();
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
        <label>Jenis Ujian</label>
        <select name="jenis_ujian" required>
          <option value="UTS" <?= (($old['jenis_ujian'] ?? '') === 'UTS') ? 'selected' : '' ?>>UTS</option>
          <option value="UAS" <?= (($old['jenis_ujian'] ?? '') === 'UAS') ? 'selected' : '' ?>>UAS</option>
        </select>
      </div>
      <div class="field">
        <label>Ruangan (diurutkan dari kapasitas terbesar)</label>
        <select name="id_ruangan" id="selRuangan" required onchange="tampilkanKapasitas()">
          <option value="">— Pilih Ruangan —</option>
          <?php foreach ($ruanganList as $r): ?>
            <option value="<?= $r['id_ruangan'] ?>" data-kapasitas="<?= $r['kapasitas'] ?>" <?= (($old['id_ruangan'] ?? '') == $r['id_ruangan']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($r['nama_ruangan'] . ' · kapasitas ' . $r['kapasitas']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="help-text" id="hintKapasitas">Pilih ruangan untuk melihat kapasitas maksimal.</div>
      </div>
      <div class="field">
        <label>Jumlah Peserta</label>
        <input type="number" name="jumlah_peserta" id="inputPeserta" min="1" value="<?= htmlspecialchars($old['jumlah_peserta'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label>Tanggal Ujian</label>
        <input type="date" name="tanggal_ujian" value="<?= htmlspecialchars($old['tanggal_ujian'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label>Tahun Akademik</label>
        <input type="text" name="tahun_akademik" placeholder="2026/2027" value="<?= htmlspecialchars($old['tahun_akademik'] ?? '2026/2027') ?>" required>
      </div>
      <div class="field">
        <label>Jam Mulai</label>
        <input type="time" name="jam_mulai" value="<?= htmlspecialchars($old['jam_mulai'] ?? '08:00') ?>" required>
      </div>
      <div class="field">
        <label>Jam Selesai</label>
        <input type="time" name="jam_selesai" value="<?= htmlspecialchars($old['jam_selesai'] ?? '10:00') ?>" required>
      </div>
    </div>
    <p class="help-text">Sistem menolak penjadwalan jika jumlah peserta melebihi kapasitas ruangan, atau jika ruangan sudah dipakai ujian lain pada tanggal &amp; jam yang sama.</p>
    <div style="display:flex;gap:10px;margin-top:14px;">
      <button type="submit" class="btn btn-primary" style="width:auto;">Simpan Jadwal Ujian</button>
      <a href="jadwal_ujian.php" class="btn btn-ghost">Batal</a>
    </div>
  </form>
</div>
<script>
function tampilkanKapasitas() {
  const sel = document.getElementById('selRuangan');
  const opt = sel.options[sel.selectedIndex];
  const kap = opt ? opt.getAttribute('data-kapasitas') : null;
  const hint = document.getElementById('hintKapasitas');
  const input = document.getElementById('inputPeserta');
  if (kap) {
    hint.textContent = 'Kapasitas maksimal ruangan ini: ' + kap + ' orang.';
    input.setAttribute('max', kap);
  } else {
    hint.textContent = 'Pilih ruangan untuk melihat kapasitas maksimal.';
    input.removeAttribute('max');
  }
}
tampilkanKapasitas();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

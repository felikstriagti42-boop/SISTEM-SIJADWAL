<?php
$halamanAktif = 'jadwal_kuliah';
$judulHalaman = 'Jadwal Kuliah';
$subJudulHalaman = 'Plot jadwal perkuliahan harian per ruangan dan dosen';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$pdo = getKoneksi();

$filterHari  = $_GET['hari'] ?? '';
$filterProdi = $_GET['prodi'] ?? '';

$sql = "SELECT jk.*, mk.nama_mk, mk.kode_mk, mk.id_prodi, d.nama_dosen, r.nama_ruangan, r.kode_ruangan
        FROM jadwal_kuliah jk
        JOIN mata_kuliah mk ON mk.id_mk = jk.id_mk
        JOIN dosen d ON d.id_dosen = jk.id_dosen
        JOIN ruangan r ON r.id_ruangan = jk.id_ruangan
        WHERE jk.status = 'Aktif'";
$params = [];
if ($filterHari !== '') { $sql .= ' AND jk.hari = ?'; $params[] = $filterHari; }
if ($filterProdi !== '') { $sql .= ' AND mk.id_prodi = ?'; $params[] = $filterProdi; }
$sql .= " ORDER BY FIELD(jk.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), jk.jam_mulai";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftar = $stmt->fetchAll();

$prodiList = $pdo->query('SELECT * FROM prodi ORDER BY nama_prodi')->fetchAll();
?>
<div class="card">
  <div class="card-head">
    <div>
      <h3>Filter</h3>
      <div class="desc"><?= count($daftar) ?> jadwal ditemukan</div>
    </div>
    <?php if (isAdmin()): ?>
      <a href="jadwal_kuliah_tambah.php" class="btn btn-amber btn-sm">+ Tambah Jadwal</a>
    <?php endif; ?>
  </div>
  <form method="get" class="form-grid">
    <div class="field">
      <label>Hari</label>
      <select name="hari" onchange="this.form.submit()">
        <option value="">Semua Hari</option>
        <?php foreach (DAFTAR_HARI as $h): ?>
          <option value="<?= $h ?>" <?= $filterHari === $h ? 'selected' : '' ?>><?= $h ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Program Studi</label>
      <select name="prodi" onchange="this.form.submit()">
        <option value="">Semua Prodi</option>
        <?php foreach ($prodiList as $p): ?>
          <option value="<?= $p['id_prodi'] ?>" <?= (string)$filterProdi === (string)$p['id_prodi'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nama_prodi']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Hari</th><th>Jam</th><th>Mata Kuliah</th><th>Dosen</th><th>Ruangan</th><th>Semester / T.A.</th>
          <?php if (isAdmin()): ?><th>Aksi</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
      <?php if (!$daftar): ?>
        <tr><td colspan="7" class="empty">Belum ada jadwal yang sesuai filter.</td></tr>
      <?php else: foreach ($daftar as $j): ?>
        <tr>
          <td><b><?= $j['hari'] ?></b></td>
          <td class="mono"><?= jamTampil($j['jam_mulai']) ?>–<?= jamTampil($j['jam_selesai']) ?></td>
          <td><b><?= htmlspecialchars($j['kode_mk']) ?></b> · <?= htmlspecialchars($j['nama_mk']) ?></td>
          <td><?= htmlspecialchars($j['nama_dosen']) ?></td>
          <td><?= htmlspecialchars($j['nama_ruangan']) ?> <span class="mono" style="color:var(--ink-soft);">(<?= htmlspecialchars($j['kode_ruangan']) ?>)</span></td>
          <td><?= htmlspecialchars($j['semester']) ?> · <?= htmlspecialchars($j['tahun_akademik']) ?></td>
          <?php if (isAdmin()): ?>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="jadwal_kuliah_edit.php?id=<?= $j['id_jadwal'] ?>">Ubah</a>
            <form method="post" action="jadwal_kuliah_hapus.php" onsubmit="return confirm('Batalkan jadwal ini?');" style="display:inline;">
              <?= csrfField() ?>
              <input type="hidden" name="id" value="<?= $j['id_jadwal'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">Batalkan</button>
            </form>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
$halamanAktif = 'jadwal_ujian';
$judulHalaman = 'Jadwal Ujian Massal';
$subJudulHalaman = 'Penjadwalan ujian UTS/UAS prodi dengan validasi kapasitas ruangan';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$pdo = getKoneksi();

$filterJenis = $_GET['jenis'] ?? '';
$sql = "SELECT ju.*, mk.nama_mk, mk.kode_mk, r.nama_ruangan, r.kode_ruangan, r.kapasitas
        FROM jadwal_ujian ju
        JOIN mata_kuliah mk ON mk.id_mk = ju.id_mk
        JOIN ruangan r ON r.id_ruangan = ju.id_ruangan
        WHERE 1=1";
$params = [];
if ($filterJenis !== '') { $sql .= ' AND ju.jenis_ujian = ?'; $params[] = $filterJenis; }
$sql .= ' ORDER BY ju.tanggal_ujian, ju.jam_mulai';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftar = $stmt->fetchAll();
?>
<div class="card">
  <div class="card-head">
    <div><h3>Filter</h3><div class="desc"><?= count($daftar) ?> jadwal ujian</div></div>
    <?php if (isAdmin()): ?><a href="jadwal_ujian_tambah.php" class="btn btn-amber btn-sm">+ Tambah Jadwal Ujian</a><?php endif; ?>
  </div>
  <form method="get">
    <div class="field" style="max-width:220px;">
      <label>Jenis Ujian</label>
      <select name="jenis" onchange="this.form.submit()">
        <option value="">Semua Jenis</option>
        <option value="UTS" <?= $filterJenis === 'UTS' ? 'selected' : '' ?>>UTS</option>
        <option value="UAS" <?= $filterJenis === 'UAS' ? 'selected' : '' ?>>UAS</option>
      </select>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Tanggal</th><th>Jam</th><th>Jenis</th><th>Mata Kuliah</th><th>Ruangan</th><th>Peserta / Kapasitas</th><?php if (isAdmin()): ?><th>Aksi</th><?php endif; ?></tr>
      </thead>
      <tbody>
      <?php if (!$daftar): ?>
        <tr><td colspan="7" class="empty">Belum ada jadwal ujian.</td></tr>
      <?php else: foreach ($daftar as $u):
        $persen = $u['kapasitas'] > 0 ? min(100, round($u['jumlah_peserta'] / $u['kapasitas'] * 100)) : 0;
        $warna = $persen >= 100 ? 'var(--red)' : ($persen >= 80 ? 'var(--amber)' : 'var(--green)');
      ?>
        <tr>
          <td><?= tanggalTampil($u['tanggal_ujian']) ?></td>
          <td class="mono"><?= jamTampil($u['jam_mulai']) ?>–<?= jamTampil($u['jam_selesai']) ?></td>
          <td><span class="badge badge-amber"><?= $u['jenis_ujian'] ?></span></td>
          <td><b><?= htmlspecialchars($u['kode_mk']) ?></b> · <?= htmlspecialchars($u['nama_mk']) ?></td>
          <td><?= htmlspecialchars($u['nama_ruangan']) ?></td>
          <td style="min-width:140px;">
            <?= $u['jumlah_peserta'] ?> / <?= $u['kapasitas'] ?>
            <?php if ($persen >= 100): ?><span class="badge badge-red">Penuh</span><?php endif; ?>
            <div class="capacity-bar"><div class="capacity-fill" style="width:<?= $persen ?>%;background:<?= $warna ?>;"></div></div>
          </td>
          <?php if (isAdmin()): ?>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="jadwal_ujian_edit.php?id=<?= $u['id_ujian'] ?>">Ubah</a>
            <form method="post" action="jadwal_ujian_hapus.php" onsubmit="return confirm('Hapus jadwal ujian ini?');" style="display:inline;">
              <?= csrfField() ?>
              <input type="hidden" name="id" value="<?= $u['id_ujian'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
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

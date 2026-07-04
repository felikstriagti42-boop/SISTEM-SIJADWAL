<?php
$halamanAktif = 'ruangan';
$judulHalaman = 'Ruangan & Kapasitas';
$subJudulHalaman = 'Kelola ruang kelas/lab beserta kapasitas dan spesifikasi fasilitas (kolom JSON fleksibel)';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$pdo = getKoneksi();
$daftar = $pdo->query('SELECT * FROM ruangan ORDER BY nama_ruangan')->fetchAll();

// Hitung pemakaian jadwal kuliah aktif per ruangan (jumlah slot/minggu)
$pakai = $pdo->query(
    "SELECT id_ruangan, COUNT(*) AS jumlah_slot
     FROM jadwal_kuliah WHERE status='Aktif' GROUP BY id_ruangan"
)->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<div class="card">
  <div class="card-head">
    <div><h3>Daftar Ruangan</h3><div class="desc"><?= count($daftar) ?> ruangan terdaftar</div></div>
    <?php if (isAdmin()): ?><a href="ruangan_tambah.php" class="btn btn-amber btn-sm">+ Tambah Ruangan</a><?php endif; ?>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Kode</th><th>Nama Ruangan</th><th>Tipe</th><th>Kapasitas</th><th>Fasilitas</th><th>Pemakaian/Minggu</th><?php if (isAdmin()): ?><th>Aksi</th><?php endif; ?></tr>
      </thead>
      <tbody>
      <?php if (!$daftar): ?>
        <tr><td colspan="7" class="empty">Belum ada ruangan.</td></tr>
      <?php else: foreach ($daftar as $r):
        $fasilitas = $r['spesifikasi_fasilitas'] ? json_decode($r['spesifikasi_fasilitas'], true) : [];
      ?>
        <tr>
          <td class="mono"><?= htmlspecialchars($r['kode_ruangan']) ?></td>
          <td><?= htmlspecialchars($r['nama_ruangan']) ?></td>
          <td><span class="badge <?= $r['tipe'] === 'Lab' ? 'badge-amber' : 'badge-green' ?>"><?= $r['tipe'] ?></span></td>
          <td><b><?= $r['kapasitas'] ?></b> orang</td>
          <td>
            <?php if ($fasilitas): foreach ($fasilitas as $k => $v):
              $tampil = is_array($v) ? implode(', ', $v) : (is_bool($v) ? ($v ? 'Ya' : 'Tidak') : $v);
            ?>
              <span class="chip"><?= htmlspecialchars($k) ?>: <?= htmlspecialchars($tampil) ?></span>
            <?php endforeach; else: ?><span style="color:var(--ink-soft);">—</span><?php endif; ?>
          </td>
          <td><?= (int)($pakai[$r['id_ruangan']] ?? 0) ?> slot/minggu</td>
          <?php if (isAdmin()): ?>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="ruangan_edit.php?id=<?= $r['id_ruangan'] ?>">Ubah</a>
            <form method="post" action="ruangan_hapus.php" onsubmit="return confirm('Hapus ruangan ini? Jadwal yang memakai ruangan ini harus dipindahkan terlebih dahulu.');" style="display:inline;">
              <?= csrfField() ?>
              <input type="hidden" name="id" value="<?= $r['id_ruangan'] ?>">
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

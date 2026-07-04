<?php
$halamanAktif = 'dashboard';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$pdo = getKoneksi();

$totalMk       = $pdo->query('SELECT COUNT(*) FROM mata_kuliah')->fetchColumn();
$totalRuangan  = $pdo->query('SELECT COUNT(*) FROM ruangan')->fetchColumn();
$totalJadwal   = $pdo->query("SELECT COUNT(*) FROM jadwal_kuliah WHERE status='Aktif'")->fetchColumn();
$totalUjian    = $pdo->query('SELECT COUNT(*) FROM jadwal_ujian')->fetchColumn();

$hariIni = DAFTAR_HARI[(int)date('N') - 1] ?? 'Senin';

$stmt = $pdo->prepare(
    "SELECT jk.*, mk.nama_mk, mk.kode_mk, d.nama_dosen, r.nama_ruangan, r.kode_ruangan
     FROM jadwal_kuliah jk
     JOIN mata_kuliah mk ON mk.id_mk = jk.id_mk
     JOIN dosen d ON d.id_dosen = jk.id_dosen
     JOIN ruangan r ON r.id_ruangan = jk.id_ruangan
     WHERE jk.hari = ? AND jk.status = 'Aktif'
     ORDER BY jk.jam_mulai"
);
$stmt->execute([$hariIni]);
$jadwalHariIni = $stmt->fetchAll();

$ujianMendatang = $pdo->query(
    "SELECT ju.*, mk.nama_mk, mk.kode_mk, r.nama_ruangan, r.kapasitas
     FROM jadwal_ujian ju
     JOIN mata_kuliah mk ON mk.id_mk = ju.id_mk
     JOIN ruangan r ON r.id_ruangan = ju.id_ruangan
     WHERE ju.tanggal_ujian >= CURDATE()
     ORDER BY ju.tanggal_ujian, ju.jam_mulai
     LIMIT 6"
)->fetchAll();
?>
<div class="stat-grid">
  <div class="stat-card"><div class="accent" style="background:var(--navy);"></div>
    <div class="num"><?= $totalJadwal ?></div><div class="lbl">Jadwal Kuliah Aktif</div></div>
  <div class="stat-card"><div class="accent" style="background:var(--amber);"></div>
    <div class="num"><?= $totalUjian ?></div><div class="lbl">Jadwal Ujian Massal</div></div>
  <div class="stat-card"><div class="accent" style="background:var(--green);"></div>
    <div class="num"><?= $totalRuangan ?></div><div class="lbl">Ruang Kelas &amp; Lab</div></div>
  <div class="stat-card"><div class="accent" style="background:#8A5FBF;"></div>
    <div class="num"><?= $totalMk ?></div><div class="lbl">Mata Kuliah Terdaftar</div></div>
</div>

<div class="card">
  <div class="card-head">
    <div>
      <h3>Jadwal Kuliah Hari Ini — <?= $hariIni ?></h3>
      <div class="desc">Diambil langsung dari tabel jadwal_kuliah, difilter berdasarkan hari &amp; status aktif.</div>
    </div>
    <a href="plot_mingguan.php" class="btn btn-ghost btn-sm">Lihat Plot Mingguan &rarr;</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Jam</th><th>Mata Kuliah</th><th>Dosen</th><th>Ruangan</th></tr></thead>
      <tbody>
      <?php if (!$jadwalHariIni): ?>
        <tr><td colspan="4" class="empty">Tidak ada jadwal kuliah pada hari <?= $hariIni ?>.</td></tr>
      <?php else: foreach ($jadwalHariIni as $j): ?>
        <tr>
          <td class="mono"><?= jamTampil($j['jam_mulai']) ?>–<?= jamTampil($j['jam_selesai']) ?></td>
          <td><b><?= htmlspecialchars($j['kode_mk']) ?></b> · <?= htmlspecialchars($j['nama_mk']) ?></td>
          <td><?= htmlspecialchars($j['nama_dosen']) ?></td>
          <td><?= htmlspecialchars($j['nama_ruangan']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-head">
    <div>
      <h3>Ujian Massal Mendatang</h3>
      <div class="desc">Kapasitas ruangan dibandingkan jumlah peserta terdaftar.</div>
    </div>
    <a href="jadwal_ujian.php" class="btn btn-ghost btn-sm">Kelola Ujian &rarr;</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Tanggal</th><th>Jam</th><th>Mata Kuliah</th><th>Ruangan</th><th>Peserta / Kapasitas</th></tr></thead>
      <tbody>
      <?php if (!$ujianMendatang): ?>
        <tr><td colspan="5" class="empty">Belum ada jadwal ujian mendatang.</td></tr>
      <?php else: foreach ($ujianMendatang as $u):
          $persen = $u['kapasitas'] > 0 ? min(100, round($u['jumlah_peserta'] / $u['kapasitas'] * 100)) : 0;
          $warna = $persen >= 100 ? 'var(--red)' : ($persen >= 80 ? 'var(--amber)' : 'var(--green)');
      ?>
        <tr>
          <td><?= tanggalTampil($u['tanggal_ujian']) ?> <span class="badge badge-amber"><?= $u['jenis_ujian'] ?></span></td>
          <td class="mono"><?= jamTampil($u['jam_mulai']) ?>–<?= jamTampil($u['jam_selesai']) ?></td>
          <td><b><?= htmlspecialchars($u['kode_mk']) ?></b> · <?= htmlspecialchars($u['nama_mk']) ?></td>
          <td><?= htmlspecialchars($u['nama_ruangan']) ?></td>
          <td style="min-width:130px;">
            <?= $u['jumlah_peserta'] ?> / <?= $u['kapasitas'] ?>
            <div class="capacity-bar"><div class="capacity-fill" style="width:<?= $persen ?>%;background:<?= $warna ?>;"></div></div>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

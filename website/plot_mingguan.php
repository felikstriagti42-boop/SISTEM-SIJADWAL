<?php
$halamanAktif = 'plot';
$judulHalaman = 'Plot Jadwal Mingguan';
$subJudulHalaman = 'Visualisasi jadwal kuliah harian dalam satu grid Senin–Sabtu';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$pdo = getKoneksi();
$ruanganList = $pdo->query('SELECT * FROM ruangan ORDER BY nama_ruangan')->fetchAll();
$idRuanganFilter = $_GET['ruangan'] ?? '';

$sql = "SELECT jk.*, mk.nama_mk, mk.kode_mk, d.nama_dosen, r.nama_ruangan
        FROM jadwal_kuliah jk
        JOIN mata_kuliah mk ON mk.id_mk = jk.id_mk
        JOIN dosen d ON d.id_dosen = jk.id_dosen
        JOIN ruangan r ON r.id_ruangan = jk.id_ruangan
        WHERE jk.status = 'Aktif'";
$params = [];
if ($idRuanganFilter !== '') { $sql .= ' AND jk.id_ruangan = ?'; $params[] = $idRuanganFilter; }
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$semuaJadwal = $stmt->fetchAll();

// Grid jam 07:00 - 19:00 per 30 menit
$mulaiMenit = 7 * 60;
$akhirMenit = 19 * 60;
$slotMenit  = 30;
$totalSlot  = ($akhirMenit - $mulaiMenit) / $slotMenit;

function menitDariJam(string $jam): int
{
    [$h, $m] = explode(':', $jam);
    return ((int)$h) * 60 + (int)$m;
}

// Susun jadwal per hari
$perHari = array_fill_keys(DAFTAR_HARI, []);
foreach ($semuaJadwal as $j) {
    $perHari[$j['hari']][] = $j;
}
?>
<div class="card">
  <div class="card-head">
    <div><h3>Filter Ruangan</h3><div class="desc">Kosongkan untuk melihat seluruh ruangan sekaligus (kolom hari akan menumpuk jika ada lebih dari satu jadwal pada slot yang sama).</div></div>
  </div>
  <form method="get" style="max-width:340px;">
    <div class="field">
      <select name="ruangan" onchange="this.form.submit()">
        <option value="">Semua Ruangan</option>
        <?php foreach ($ruanganList as $r): ?>
          <option value="<?= $r['id_ruangan'] ?>" <?= (string)$idRuanganFilter === (string)$r['id_ruangan'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nama_ruangan']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<div class="plot-scroll">
  <div class="plot-grid" style="grid-template-rows:38px repeat(<?= $totalSlot ?>, 30px);">
    <div class="corner">Jam</div>
    <?php foreach (DAFTAR_HARI as $h): ?><div class="day-head"><?= $h ?></div><?php endforeach; ?>

    <?php for ($s = 0; $s < $totalSlot; $s++):
        $menitAwal = $mulaiMenit + $s * $slotMenit;
        $label = sprintf('%02d:%02d', intdiv($menitAwal, 60), $menitAwal % 60);
    ?>
      <div class="time-label" style="grid-row:<?= $s + 2 ?>; grid-column:1;"><?= $label ?></div>
      <?php foreach (DAFTAR_HARI as $ci => $h): ?>
        <div class="plot-cell" style="grid-row:<?= $s + 2 ?>; grid-column:<?= $ci + 2 ?>;"></div>
      <?php endforeach; ?>
    <?php endfor; ?>

    <?php foreach (DAFTAR_HARI as $ci => $h):
        foreach ($perHari[$h] as $j):
            $mulai = menitDariJam($j['jam_mulai']);
            $selesai = menitDariJam($j['jam_selesai']);
            if ($selesai <= $mulaiMenit || $mulai >= $akhirMenit) continue;
            $mulaiClamp = max($mulai, $mulaiMenit);
            $selesaiClamp = min($selesai, $akhirMenit);
            $baris = intdiv($mulaiClamp - $mulaiMenit, $slotMenit) + 2;
            $span = max(1, intdiv($selesaiClamp - $mulaiClamp, $slotMenit));
    ?>
      <div class="plot-block" style="grid-row:<?= $baris ?> / span <?= $span ?>; grid-column:<?= $ci + 2 ?>; background:<?= warnaUntukMk($j['id_mk']) ?>;">
        <b><?= htmlspecialchars($j['kode_mk']) ?></b>
        <div class="meta"><?= jamTampil($j['jam_mulai']) ?>–<?= jamTampil($j['jam_selesai']) ?></div>
        <div class="meta"><?= htmlspecialchars($j['nama_ruangan']) ?> · <?= htmlspecialchars($j['nama_dosen']) ?></div>
      </div>
    <?php endforeach; endforeach; ?>
  </div>
</div>

<?php if (!$semuaJadwal): ?>
  <div class="card" style="margin-top:18px;"><div class="empty">Tidak ada jadwal untuk ditampilkan pada filter ini.</div></div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

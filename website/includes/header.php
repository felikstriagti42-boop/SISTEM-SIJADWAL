<?php
require_once __DIR__ . '/auth.php';
wajibLogin();
$user = penggunaSaatIni();
$halamanAktif = $halamanAktif ?? '';

function navLink(string $key, string $href, string $ikon, string $label, string $aktif): string
{
    $cls = $key === $aktif ? 'nav-link active' : 'nav-link';
    return '<a class="' . $cls . '" href="' . $href . '"><span class="nav-icon">' . $ikon . '</span>' . $label . '</a>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($judulHalaman) ? htmlspecialchars($judulHalaman) . ' — ' : '' ?>SiJadwal · Institut Shanti Bhuana</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="brand">
      <div class="mark">SB</div>
      <span>SiJadwal<br><small style="font-weight:400;color:#9aa2ba;">Institut Shanti Bhuana</small></span>
    </div>

    <div class="nav-group">
      <div class="nav-label">Ringkasan</div>
      <?= navLink('dashboard', 'index.php', '&#9673;', 'Dashboard', $halamanAktif) ?>
      <?= navLink('plot', 'plot_mingguan.php', '&#9638;', 'Plot Jadwal Mingguan', $halamanAktif) ?>
    </div>

    <div class="nav-group">
      <div class="nav-label">Penjadwalan</div>
      <?= navLink('jadwal_kuliah', 'jadwal_kuliah.php', '&#128197;', 'Jadwal Kuliah', $halamanAktif) ?>
      <?= navLink('jadwal_ujian', 'jadwal_ujian.php', '&#128221;', 'Ujian Massal', $halamanAktif) ?>
      <?= navLink('ruangan', 'ruangan.php', '&#127979;', 'Ruangan & Kapasitas', $halamanAktif) ?>
    </div>

    <?php if (isAdmin()): ?>
    <div class="nav-group">
      <div class="nav-label">Administrasi</div>
      <?= navLink('audit', 'audit_log.php', '&#128272;', 'Log Audit', $halamanAktif) ?>
    </div>
    <?php endif; ?>

    <div class="sidebar-foot">
      <span class="role-badge"><?= htmlspecialchars(labelPeran($user['role'])) ?></span>
      <div style="font-size:13px;font-weight:600;color:#fff;"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
      <a href="logout.php" class="btn btn-ghost btn-sm" style="margin-top:10px;width:100%;color:#dbe0ee;border-color:rgba(255,255,255,.18);">Keluar</a>
    </div>
  </aside>

  <div class="main">
    <div class="topbar">
      <div>
        <h1><?= htmlspecialchars($judulHalaman ?? 'Dashboard') ?></h1>
        <?php if (!empty($subJudulHalaman)): ?><div class="sub"><?= htmlspecialchars($subJudulHalaman) ?></div><?php endif; ?>
      </div>
    </div>
    <div class="content">
      <?php tampilkanFlash(); ?>

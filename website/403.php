<?php $judulHalaman = 'Akses Ditolak'; require_once __DIR__ . '/includes/header.php'; ?>
<div class="card" style="text-align:center;padding:50px 20px;">
  <h2 style="font-size:22px;">Akses ditolak</h2>
  <p style="color:var(--ink-soft);margin-top:8px;">Peran Anda (<?= htmlspecialchars(labelPeran(penggunaSaatIni()['role'])) ?>) tidak memiliki izin untuk mengubah data ini. Hubungi admin prodi jika Anda memerlukan akses.</p>
  <a href="index.php" class="btn btn-primary" style="width:auto;margin-top:16px;display:inline-flex;">Kembali ke Dashboard</a>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

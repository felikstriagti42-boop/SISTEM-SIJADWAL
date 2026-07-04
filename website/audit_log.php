<?php
$halamanAktif = 'audit';
$judulHalaman = 'Log Audit';
$subJudulHalaman = 'Riwayat perubahan data jadwal untuk akuntabilitas (sesuai prinsip UU PDP)';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
wajibPeran(['admin_prodi']);
require_once __DIR__ . '/includes/header.php';

$pdo = getKoneksi();
$daftar = $pdo->query('SELECT * FROM log_audit ORDER BY waktu DESC LIMIT 200')->fetchAll();
?>
<div class="card">
  <div class="card-head">
    <div><h3>200 Aktivitas Terbaru</h3><div class="desc">Dicatat otomatis oleh trigger database pada setiap INSERT/UPDATE/DELETE.</div></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Waktu</th><th>Tabel</th><th>Aksi</th><th>ID Record</th><th>User DB</th><th>Detail</th></tr></thead>
      <tbody>
      <?php if (!$daftar): ?>
        <tr><td colspan="6" class="empty">Belum ada aktivitas tercatat.</td></tr>
      <?php else: foreach ($daftar as $log):
        $badge = ['INSERT' => 'badge-green', 'UPDATE' => 'badge-amber', 'DELETE' => 'badge-red'][$log['aksi']] ?? 'badge-green';
      ?>
        <tr>
          <td class="mono"><?= htmlspecialchars($log['waktu']) ?></td>
          <td><?= htmlspecialchars($log['nama_tabel']) ?></td>
          <td><span class="badge <?= $badge ?>"><?= $log['aksi'] ?></span></td>
          <td class="mono">#<?= $log['id_record'] ?></td>
          <td class="mono" style="font-size:12px;"><?= htmlspecialchars($log['user_db']) ?></td>
          <td style="font-size:12px;max-width:340px;">
            <?php if ($log['detail_lama']): ?><div><b>Lama:</b> <?= htmlspecialchars($log['detail_lama']) ?></div><?php endif; ?>
            <?php if ($log['detail_baru']): ?><div><b>Baru:</b> <?= htmlspecialchars($log['detail_baru']) ?></div><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

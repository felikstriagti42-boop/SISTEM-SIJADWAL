<?php
require_once __DIR__ . '/includes/auth.php';
wajibPeran(['admin_prodi']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfValid()) {
    $id = (int)($_POST['id'] ?? 0);
    $pdo = getKoneksi();
    $stmt = $pdo->prepare('DELETE FROM jadwal_ujian WHERE id_ujian = ?');
    $stmt->execute([$id]);
    setFlash('sukses', 'Jadwal ujian berhasil dihapus.');
} else {
    setFlash('error', 'Permintaan tidak valid.');
}

header('Location: jadwal_ujian.php');
exit;

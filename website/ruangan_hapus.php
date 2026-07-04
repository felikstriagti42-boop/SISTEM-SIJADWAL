<?php
require_once __DIR__ . '/includes/auth.php';
wajibPeran(['admin_prodi']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfValid()) {
    $id = (int)($_POST['id'] ?? 0);
    $pdo = getKoneksi();
    try {
        $stmt = $pdo->prepare('DELETE FROM ruangan WHERE id_ruangan = ?');
        $stmt->execute([$id]);
        setFlash('sukses', 'Ruangan berhasil dihapus.');
    } catch (Throwable $e) {
        setFlash('error', 'Tidak dapat menghapus ruangan karena masih dipakai pada jadwal kuliah/ujian.');
    }
} else {
    setFlash('error', 'Permintaan tidak valid.');
}

header('Location: ruangan.php');
exit;

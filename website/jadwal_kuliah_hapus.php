<?php
require_once __DIR__ . '/includes/auth.php';
wajibPeran(['admin_prodi']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfValid()) {
    $id = (int)($_POST['id'] ?? 0);
    $pdo = getKoneksi();
    $stmt = $pdo->prepare("UPDATE jadwal_kuliah SET status = 'Batal' WHERE id_jadwal = ?");
    $stmt->execute([$id]);
    setFlash('sukses', 'Jadwal berhasil dibatalkan.');
} else {
    setFlash('error', 'Permintaan tidak valid.');
}

header('Location: jadwal_kuliah.php');
exit;

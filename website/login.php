<?php
require_once __DIR__ . '/includes/auth.php';

if (sedangLogin()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        $error = 'Sesi form tidak valid, silakan coba lagi.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Username dan password wajib diisi.';
        } else {
            try {
                $pdo = getKoneksi();
                $stmt = $pdo->prepare('SELECT * FROM akun_pengguna WHERE username = ? AND aktif = 1 LIMIT 1');
                $stmt->execute([$username]);
                $akun = $stmt->fetch();

                if ($akun && password_verify($password, $akun['password_hash'])) {
                    $_SESSION['user'] = [
                        'id_akun'      => $akun['id_akun'],
                        'username'     => $akun['username'],
                        'role'         => $akun['role'],
                        'nama_lengkap' => $akun['nama_lengkap'],
                        'id_dosen'     => $akun['id_dosen'],
                    ];
                    header('Location: index.php');
                    exit;
                }
                $error = 'Username atau password salah.';
            } catch (Throwable $e) {
                $error = 'Tidak dapat terhubung ke database. Periksa includes/config.php.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk — SiJadwal</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-brand">
      <div class="mark">SB</div>
      <div>
        <div style="font-family:'Poppins';font-weight:700;font-size:15px;">SiJadwal</div>
        <small>Institut Shanti Bhuana</small>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <?= csrfField() ?>
      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" autocomplete="username" required autofocus>
      </div>
      <div class="field">
        <label for="password">Kata Sandi</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn btn-primary">Masuk</button>
    </form>

    <div class="login-hint">
      Belum punya akun? Jalankan <span class="mono">install/seed.php</span> sekali untuk membuat
      akun contoh (admin prodi, dosen, mahasiswa) — lihat README.md.
    </div>
  </div>
</div>
</body>
</html>

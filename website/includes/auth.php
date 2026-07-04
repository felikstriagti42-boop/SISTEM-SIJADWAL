<?php
/**
 * Modul autentikasi & otorisasi (RBAC di level aplikasi).
 * Peran mengikuti peran yang sama dengan database/04_rbac_security.sql:
 *   - admin_prodi : kelola penuh (CRUD) jadwal, ruangan, ujian
 *   - dosen       : hanya lihat (read-only)
 *   - mahasiswa   : hanya lihat (read-only)
 */

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function sedangLogin(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function penggunaSaatIni(): ?array
{
    return $_SESSION['user'] ?? null;
}

function wajibLogin(): void
{
    if (!sedangLogin()) {
        header('Location: login.php');
        exit;
    }
}

/** Hanya izinkan peran tertentu; selain itu tolak akses. */
function wajibPeran(array $peranDiizinkan): void
{
    wajibLogin();
    $user = penggunaSaatIni();
    if (!in_array($user['role'], $peranDiizinkan, true)) {
        http_response_code(403);
        require __DIR__ . '/../403.php';
        exit;
    }
}

function isAdmin(): bool
{
    $u = penggunaSaatIni();
    return $u && $u['role'] === 'admin_prodi';
}

function labelPeran(string $role): string
{
    return match ($role) {
        'admin_prodi' => 'Admin Prodi',
        'dosen'       => 'Dosen',
        'mahasiswa'   => 'Mahasiswa',
        default       => ucfirst($role),
    };
}

function setFlash(string $tipe, string $pesan): void
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function tampilkanFlash(): void
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        $kelas = $f['tipe'] === 'sukses' ? 'alert-sukses' : ($f['tipe'] === 'error' ? 'alert-error' : 'alert-info');
        echo '<div class="alert ' . $kelas . '">' . htmlspecialchars($f['pesan']) . '</div>';
        unset($_SESSION['flash']);
    }
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function csrfValid(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

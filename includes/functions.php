<?php
/**
 * QRMed Card - Fungsi-fungsi bantu global
 */

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Membersihkan input teks sederhana */
function clean(string $data): string
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/** Redirect helper */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/** Cek apakah user sedang login */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/** Cek role user yang sedang login */
function currentRole(): ?string
{
    return $_SESSION['role'] ?? null;
}

/** Wajibkan login dengan role tertentu, kalau tidak redirect ke halaman login */
function requireRole(string $role): void
{
    if (!isLoggedIn() || currentRole() !== $role) {
        redirect(BASE_URL . '/' . ($role === 'admin' ? 'admin/login.php' : 'login.php'));
    }
}

/** Generate Patient ID unik, format: QRM-000001 */
function generatePatientCode(PDO $pdo): string
{
    $stmt = $pdo->query('SELECT COUNT(*) AS total FROM patients');
    $total = (int) $stmt->fetch()['total'];
    do {
        $total++;
        $code = 'QRM-' . str_pad((string) $total, 6, '0', STR_PAD_LEFT);
        $check = $pdo->prepare('SELECT COUNT(*) AS c FROM patients WHERE patient_code = ?');
        $check->execute([$code]);
    } while ((int) $check->fetch()['c'] > 0);

    return $code;
}

/** Generate token unik untuk QR Code (acak, tidak bisa ditebak) */
function generateUniqueToken(): string
{
    return bin2hex(random_bytes(32)); // 64 karakter hex
}

/** Hitung umur dari tanggal lahir */
function calculateAge(string $tanggalLahir): int
{
    $dob = new DateTime($tanggalLahir);
    $now = new DateTime('today');
    return $dob->diff($now)->y;
}

/** Format tanggal ke format Indonesia */
function formatTanggalIndo(?string $date): string
{
    if (empty($date)) {
        return '-';
    }
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];
    $ts = strtotime($date);
    return date('d', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

/** Ambil data lengkap pasien berdasarkan patient_id */
function getPatientById(PDO $pdo, int $patientId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM patients WHERE patient_id = ?');
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();
    return $patient ?: null;
}

/** Ambil patient_id dari user_id yang sedang login */
function getPatientIdByUser(PDO $pdo, int $userId): ?int
{
    $stmt = $pdo->prepare('SELECT patient_id FROM patients WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ? (int) $row['patient_id'] : null;
}

/** Flash message sederhana via session */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/** Upload foto pasien, mengembalikan nama file atau null jika tidak ada upload */
function uploadPhoto(array $file, ?string $oldFile = null): ?string
{
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $oldFile;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Gagal mengupload foto (kode error: ' . $file['error'] . ')');
    }

    $allowed = ['jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Format foto harus JPG atau PNG.');
    }
    if ($file['size'] > 3 * 1024 * 1024) {
        throw new RuntimeException('Ukuran foto maksimal 3MB.');
    }

    $targetDir = __DIR__ . '/../assets/img/uploads/photos/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $filename = 'photo_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $targetDir . $filename)) {
        throw new RuntimeException('Gagal menyimpan file foto ke server.');
    }

    // Hapus foto lama jika ada
    if ($oldFile && file_exists($targetDir . $oldFile)) {
        @unlink($targetDir . $oldFile);
    }

    return $filename;
}

/** URL emergency profile publik berdasarkan token */
function emergencyUrl(string $token): string
{
    return BASE_URL . '/emergency.php?token=' . $token;
}

/**
 * Kumpulan ikon garis (line-icon) minimal, dipakai di sidebar, kartu statistik,
 * dan tombol agar tampilan lebih elegan dibanding emoji.
 */
function icon(string $name, string $class = ''): string
{
    $icons = [
        'dashboard'  => '<path d="M4 13h6V4H4v9Zm0 7h6v-5H4v5Zm10 0h6V11h-6v9Zm0-16v5h6V4h-6Z"/>',
        'user'       => '<path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Z"/><path d="M4 20.5c1.4-3.6 4.4-5.5 8-5.5s6.6 1.9 8 5.5"/>',
        'pulse'      => '<path d="M3 12h4l2-6 4 12 2-6h6"/>',
        'shield'     => '<path d="M12 3l7 3v6c0 4.6-3 7.8-7 9-4-1.2-7-4.4-7-9V6l7-3Z"/>',
        'pill'       => '<path d="M6.5 17.5 17.5 6.5a4 4 0 1 1 5.7 5.7L12.2 23.2a4 4 0 1 1-5.7-5.7Z"/><path d="M9 15 15 9"/>',
        'contact'    => '<path d="M22 16.9v2a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 1h2a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.6a2 2 0 0 1-.5 2.1L7 8.6a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.8.3 1.7.5 2.6.6a2 2 0 0 1 1.7 2.1Z"/>',
        'card'       => '<rect x="2.5" y="5.5" width="19" height="13" rx="2.5"/><path d="M2.5 9.5h19"/><path d="M6 14h4"/>',
        'logout'     => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
        'add'        => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'patients'   => '<path d="M17 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-3A3.5 3.5 0 0 0 7 18.5V20"/><circle cx="12" cy="8" r="3.5"/><path d="M21 20v-1.2a3 3 0 0 0-2.2-2.9"/><path d="M15.5 4.5a3 3 0 0 1 0 6"/>',
        'check'      => '<path d="M20 6 9 17l-5-5"/>',
        'alert'      => '<path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 2.3 18a2 2 0 0 0 1.7 3h16a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>',
        'phone'      => '<path d="M22 16.9v2a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 1h2a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.6a2 2 0 0 1-.5 2.1L7 8.6a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.8.3 1.7.5 2.6.6a2 2 0 0 1 1.7 2.1Z"/>',
        'qr'         => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3z"/><path d="M19 14h2v2"/><path d="M14 19h2v2"/><path d="M19 19h2v2"/>',
        'search'     => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
    ];
    $path = $icons[$name] ?? '';
    return '<svg class="icon ' . clean($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

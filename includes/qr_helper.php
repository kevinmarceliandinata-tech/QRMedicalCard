<?php
/**
 * QRMed Card - Helper untuk membuat & mengelola QR Code dinamis
 *
 * Prinsip: QR Code TIDAK berisi data pasien, hanya berisi URL yang memuat
 * token unik. Token menunjuk ke baris di tabel qr_codes -> patient_id.
 * Jika data pasien berubah, token & QR Code TETAP SAMA.
 */

require_once __DIR__ . '/../libs/phpqrcode/qrlib.php';
require_once __DIR__ . '/functions.php';

/**
 * Membuat QR Code baru untuk pasien (dipanggil sekali saat registrasi,
 * atau saat admin menerbitkan ulang kartu karena hilang).
 */
function createQrForPatient(PDO $pdo, int $patientId): array
{
    $token = generateUniqueToken();
    $filename = renderQrImage($token);

    $stmt = $pdo->prepare(
        'INSERT INTO qr_codes (patient_id, unique_token, status, qr_image) VALUES (?, ?, "active", ?)'
    );
    $stmt->execute([$patientId, $token, $filename]);

    return ['token' => $token, 'image' => $filename];
}

/** Menggambar file PNG QR Code dari sebuah token, mengembalikan nama file */
function renderQrImage(string $token): string
{
    $dir = __DIR__ . '/../assets/img/uploads/qr/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = 'qr_' . $token . '.png';
    $url = emergencyUrl($token);

    // QR_ECLEVEL_M = toleransi kesalahan menengah, ukuran modul 8px, margin 2
    QRcode::png($url, $dir . $filename, QR_ECLEVEL_M, 8, 2);

    return $filename;
}

/** Ambil data QR aktif milik seorang pasien */
function getQrByPatient(PDO $pdo, int $patientId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM qr_codes WHERE patient_id = ? LIMIT 1');
    $stmt->execute([$patientId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Nonaktifkan QR (misal karena kartu fisik hilang) */
function deactivateQr(PDO $pdo, int $patientId): void
{
    $stmt = $pdo->prepare('UPDATE qr_codes SET status = "inactive" WHERE patient_id = ?');
    $stmt->execute([$patientId]);
}

/** Aktifkan kembali QR */
function activateQr(PDO $pdo, int $patientId): void
{
    $stmt = $pdo->prepare('UPDATE qr_codes SET status = "active" WHERE patient_id = ?');
    $stmt->execute([$patientId]);
}

/**
 * Terbitkan ulang kartu dengan token BARU (dipakai saat kartu hilang lalu
 * ingin diterbitkan kartu pengganti; token lama otomatis tidak berlaku lagi
 * karena diganti, bukan sekadar dinonaktifkan).
 */
function reissueQr(PDO $pdo, int $patientId): array
{
    $token = generateUniqueToken();
    $filename = renderQrImage($token);

    $stmt = $pdo->prepare(
        'UPDATE qr_codes SET unique_token = ?, qr_image = ?, status = "active" WHERE patient_id = ?'
    );
    $stmt->execute([$token, $filename, $patientId]);

    return ['token' => $token, 'image' => $filename];
}

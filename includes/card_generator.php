<?php
/**
 * QRMed Card - Generator Kartu Kesehatan Digital (PNG)
 * Menggabungkan foto pasien + data ringkas + QR Code menjadi satu kartu
 * menyerupai kartu identitas (ukuran rasio kartu ID standar).
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/qr_helper.php';

/**
 * Membuat file PNG kartu digital untuk seorang pasien.
 * Return: path file relatif (nama file) di assets/img/uploads/cards/
 */
function generateDigitalCard(array $patient, array $qr): string
{
    $width = 1013;   // ~ rasio kartu ID (85.6mm x 54mm) pada 300dpi/skala
    $height = 638;

    $im = imagecreatetruecolor($width, $height);
    imageantialias($im, true);

    // Palet warna
    $bgTop      = imagecolorallocate($im, 15, 98, 108);   // teal gelap
    $bgBottom   = imagecolorallocate($im, 20, 130, 140);  // teal
    $white      = imagecolorallocate($im, 255, 255, 255);
    $lightGray  = imagecolorallocate($im, 230, 240, 240);
    $darkText   = imagecolorallocate($im, 20, 40, 45);
    $accentRed  = imagecolorallocate($im, 220, 70, 70);

    // Background gradient sederhana (atas ke bawah)
    for ($y = 0; $y < $height; $y++) {
        $ratio = $y / $height;
        $r = (int) (15 + (20 - 15) * $ratio);
        $g = (int) (98 + (130 - 98) * $ratio);
        $b = (int) (108 + (140 - 108) * $ratio);
        $color = imagecolorallocate($im, $r, $g, $b);
        imageline($im, 0, $y, $width, $y, $color);
    }

    // Panel putih di bawah untuk area data
    imagefilledrectangle($im, 0, 130, $width, $height, $white);

    // Header strip
    $fontDir = __DIR__ . '/../assets/fonts/';
    $fontBold = $fontDir . 'DejaVuSans-Bold.ttf';
    $fontReg  = $fontDir . 'DejaVuSans.ttf';
    $hasTTF = is_file($fontBold) && is_file($fontReg);

    if ($hasTTF) {
        imagettftext($im, 26, 0, 40, 55, $white, $fontBold, 'QRMED CARD');
        imagettftext($im, 15, 0, 40, 85, $white, $fontReg, 'Emergency Health Card');
    } else {
        imagestring($im, 5, 40, 30, 'QRMED CARD', $white);
        imagestring($im, 3, 40, 55, 'Emergency Health Card', $white);
    }

    // Cross / plus medis kecil sebagai ikon
    $cx = $width - 90;
    $cy = 65;
    imagefilledrectangle($im, $cx - 8, $cy - 24, $cx + 8, $cy + 24, $white);
    imagefilledrectangle($im, $cx - 24, $cy - 8, $cx + 24, $cy + 8, $white);

    // --- Foto pasien ---
    $photoBoxX = 45;
    $photoBoxY = 165;
    $photoW = 220;
    $photoH = 260;

    $photoPath = null;
    if (!empty($patient['foto'])) {
        $candidate = __DIR__ . '/../assets/img/uploads/photos/' . $patient['foto'];
        if (is_file($candidate)) {
            $photoPath = $candidate;
        }
    }

    imagefilledrectangle($im, $photoBoxX, $photoBoxY, $photoBoxX + $photoW, $photoBoxY + $photoH, $lightGray);

    if ($photoPath) {
        $info = @getimagesize($photoPath);
        $src = null;
        if ($info) {
            $mime = $info['mime'];
            if ($mime === 'image/jpeg') {
                $src = @imagecreatefromjpeg($photoPath);
            } elseif ($mime === 'image/png') {
                $src = @imagecreatefrompng($photoPath);
            }
        }
        if ($src) {
            $srcW = imagesx($src);
            $srcH = imagesy($src);
            // Crop tengah agar pas rasio kotak foto
            $srcRatio = $srcW / $srcH;
            $boxRatio = $photoW / $photoH;
            if ($srcRatio > $boxRatio) {
                $newSrcW = (int) ($srcH * $boxRatio);
                $srcX = (int) (($srcW - $newSrcW) / 2);
                imagecopyresampled($im, $src, $photoBoxX, $photoBoxY, $srcX, 0, $photoW, $photoH, $newSrcW, $srcH);
            } else {
                $newSrcH = (int) ($srcW / $boxRatio);
                $srcY = (int) (($srcH - $newSrcH) / 2);
                imagecopyresampled($im, $src, $photoBoxX, $photoBoxY, 0, $srcY, $photoW, $photoH, $srcW, $newSrcH);
            }
            imagedestroy($src);
        }
    } else if ($hasTTF) {
        imagettftext($im, 12, 0, $photoBoxX + 55, $photoBoxY + $photoH / 2, $darkText, $fontReg, 'FOTO');
    }

    // --- QR Code (posisi dihitung lebih dulu agar lebar nama bisa menyesuaikan) ---
    $qrPath = __DIR__ . '/../assets/img/uploads/qr/' . $qr['qr_image'];
    $qrSize = 260;
    $qrX = $width - $qrSize - 60;
    $qrY = 190;

    // --- Data teks pasien (sejajar di sebelah kanan foto) ---
    $textX = $photoBoxX + $photoW + 45;
    $textY = $photoBoxY + 40;
    $lineGap = 42;
    $maxNameWidth = $qrX - $textX - 30;

    $nama = mb_strtoupper($patient['nama_lengkap']);
    $tglLahir = formatTanggalIndo($patient['tanggal_lahir']);
    $gender = $patient['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan';
    $golDarah = (!empty($patient['golongan_darah']) && $patient['gol_darah_verified'])
        ? $patient['golongan_darah']
        : '-';
    $usia = calculateAge($patient['tanggal_lahir']);

    if ($hasTTF) {
        // Cari ukuran font terbesar (22 turun ke 15) yang muat dalam satu baris;
        // jika nama tetap terlalu panjang, pecah jadi dua baris agar tidak terpotong.
        $nameLines = [$nama];
        $nameFontSize = 22;
        $fitsOneLine = false;

        foreach ([22, 20, 18, 16, 15] as $trySize) {
            $bbox = imagettfbbox($trySize, 0, $fontBold, $nama);
            $w = abs($bbox[4] - $bbox[0]);
            if ($w <= $maxNameWidth) {
                $nameFontSize = $trySize;
                $fitsOneLine = true;
                break;
            }
        }

        if (!$fitsOneLine) {
            $fitsTwoLines = false;
            foreach ([16, 15, 14, 13, 12] as $trySize) {
                $words = explode(' ', $nama);
                $line1 = ''; $line2 = '';
                foreach ($words as $w) {
                    $candidate = trim($line1 . ' ' . $w);
                    $bbox = imagettfbbox($trySize, 0, $fontBold, $candidate);
                    if (abs($bbox[4] - $bbox[0]) <= $maxNameWidth || $line1 === '') {
                        $line1 = $candidate;
                    } else {
                        $line2 = trim($line2 . ' ' . $w);
                    }
                }
                $bbox2 = imagettfbbox($trySize, 0, $fontBold, $line2);
                if ($line2 === '' || abs($bbox2[4] - $bbox2[0]) <= $maxNameWidth) {
                    $nameFontSize = $trySize;
                    $fitsTwoLines = true;
                    break;
                }
            }
            if (!$fitsTwoLines) {
                // Fallback ekstrem: potong baris kedua dengan elipsis pada ukuran terkecil
                $nameFontSize = 12;
                while ($line2 !== '' && abs((imagettfbbox($nameFontSize, 0, $fontBold, $line2 . '…')[4] ?? 0) - (imagettfbbox($nameFontSize, 0, $fontBold, $line2 . '…')[0] ?? 0)) > $maxNameWidth) {
                    $line2 = mb_substr($line2, 0, -1);
                }
                if ($line2 !== '') {
                    $line2 .= '…';
                }
            }
            $nameLines = $line2 !== '' ? [$line1, $line2] : [$line1];
        }

        $nameLineHeight = (int) round($nameFontSize * 1.25);
        foreach ($nameLines as $i => $lineText) {
            imagettftext($im, $nameFontSize, 0, $textX, $textY + ($i * $nameLineHeight), $darkText, $fontBold, $lineText);
        }
        $blockOffset = (count($nameLines) - 1) * $nameLineHeight;

        $labelColor = imagecolorallocate($im, 120, 140, 140);
        imagettftext($im, 11, 0, $textX, $textY + $lineGap + $blockOffset, $labelColor, $fontReg, strtoupper('Tanggal Lahir'));
        imagettftext($im, 14, 0, $textX, $textY + $lineGap + 22 + $blockOffset, $darkText, $fontReg, $tglLahir . '  (' . $usia . ' tahun)');

        imagettftext($im, 11, 0, $textX, $textY + $lineGap * 2 + 14 + $blockOffset, $labelColor, $fontReg, strtoupper('Jenis Kelamin'));
        imagettftext($im, 14, 0, $textX, $textY + $lineGap * 2 + 36 + $blockOffset, $darkText, $fontReg, $gender);

        imagettftext($im, 11, 0, $textX, $textY + $lineGap * 3 + 28 + $blockOffset, $labelColor, $fontReg, strtoupper('Golongan Darah'));
        imagettftext($im, 18, 0, $textX, $textY + $lineGap * 3 + 55 + $blockOffset, $accentRed, $fontBold, $golDarah);

        imagettftext($im, 12, 0, $textX, $photoBoxY + $photoH - 8, $labelColor, $fontReg, 'ID Pasien: ' . $patient['patient_code']);
    } else {
        imagestring($im, 5, $textX, (int) $textY, $nama, $darkText);
        imagestring($im, 3, $textX, (int) $textY + 40, 'Tgl Lahir: ' . $tglLahir, $darkText);
        imagestring($im, 3, $textX, (int) $textY + 60, 'Gender: ' . $gender, $darkText);
        imagestring($im, 3, $textX, (int) $textY + 80, 'Gol. Darah: ' . $golDarah, $accentRed);
        imagestring($im, 3, $textX, (int) $textY + 100, 'ID: ' . $patient['patient_code'], $darkText);
    }

    // Self-healing: jika file QR tidak ditemukan atau gagal dibaca (mis. rusak,
    // terhapus, atau masalah izin tulis di server), buat ulang gambar QR-nya
    // langsung dari token sebelum digambar ke kartu — kartu tidak akan pernah kosong.
    $qrImg = false;
    if (is_file($qrPath) && filesize($qrPath) > 0) {
        $qrImg = @imagecreatefrompng($qrPath);
    }
    if (!$qrImg && !empty($qr['unique_token'])) {
        try {
            renderQrImage($qr['unique_token']);
            if (is_file($qrPath) && filesize($qrPath) > 0) {
                $qrImg = @imagecreatefrompng($qrPath);
            }
        } catch (Throwable $e) {
            $qrImg = false;
        }
    }
    if ($qrImg) {
        imagefilledrectangle($im, $qrX - 10, $qrY - 10, $qrX + $qrSize + 10, $qrY + $qrSize + 10, $white);
        imagecopyresampled($im, $qrImg, $qrX, $qrY, 0, 0, $qrSize, $qrSize, imagesx($qrImg), imagesy($qrImg));
        imagedestroy($qrImg);
    } else {
        // Jaring pengaman terakhir: tampilkan pesan alih-alih kotak kosong,
        // supaya masalah langsung terlihat jelas alih-alih diam-diam kosong.
        imagefilledrectangle($im, $qrX - 10, $qrY - 10, $qrX + $qrSize + 10, $qrY + $qrSize + 10, $lightGray);
        if ($hasTTF) {
            $msg = 'QR gagal dimuat';
            $bbox = imagettfbbox(11, 0, $fontReg, $msg);
            $msgW = abs($bbox[4] - $bbox[0]);
            imagettftext($im, 11, 0, (int) ($qrX + $qrSize / 2 - $msgW / 2), (int) ($qrY + $qrSize / 2), $accentRed, $fontReg, $msg);
        }
    }

    $captionY = $qrY + $qrSize + 30;
    $caption = 'Scan untuk info kesehatan darurat';
    if ($hasTTF) {
        $bbox = imagettfbbox(10, 0, $fontReg, $caption);
        $textW = abs($bbox[4] - $bbox[0]);
        imagettftext($im, 10, 0, (int) ($qrX + $qrSize / 2 - $textW / 2), $captionY, $darkText, $fontReg, $caption);
    }

    // Border tipis pemisah panel
    $borderColor = imagecolorallocate($im, 200, 210, 210);
    imageline($im, 0, 130, $width, 130, $borderColor);

    // Simpan file
    $dir = __DIR__ . '/../assets/img/uploads/cards/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = 'card_' . $patient['patient_code'] . '.png';
    imagepng($im, $dir . $filename, 6);
    imagedestroy($im);

    return $filename;
}

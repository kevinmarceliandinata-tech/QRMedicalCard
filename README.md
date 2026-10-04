# QRMed Card — Kartu Informasi Kesehatan Darurat Berbasis QR Code

Website ini dibangun dengan **PHP native (tanpa framework)** + **MySQL**, dirancang khusus
agar langsung bisa dijalankan di **Laragon** dengan **PHP 8.4**. Sudah diuji penuh
(registrasi, login, CRUD data medis, generate QR & kartu digital, panel admin) di
lingkungan PHP 8.3/8.4 sebelum diserahkan.

---

## 1. Cara Instalasi di Laragon

1. **Salin folder project**
   Salin seluruh folder `qrmed-card` ke dalam folder `www` Laragon Anda, misalnya:
   ```
   C:\laragon\www\qrmed-card
   ```

2. **Buat database**
   - Buka Laragon → klik **Database** (phpMyAdmin/HeidiSQL akan terbuka), atau buka terminal Laragon.
   - Import file `database/qrmed_card.sql` yang ada di dalam folder project ini.
     Cara termudah lewat phpMyAdmin:
     1. Buka `http://localhost/phpmyadmin`
     2. Klik tab **Import**
     3. Pilih file `database/qrmed_card.sql`
     4. Klik **Go / Kirim**
   - File ini akan otomatis membuat database `qrmed_card` beserta seluruh tabel dan satu akun admin default.

3. **Sesuaikan koneksi database (jika perlu)**
   Buka `config/database.php`. Secara default sudah diatur untuk Laragon standar:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'qrmed_card');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('APP_SUBPATH', '/qrmed-card');
   ```
   - Alamat website (`BASE_URL`) **terdeteksi otomatis** dari alamat yang dipakai mengakses situs — tidak perlu diisi manual, dan otomatis menyesuaikan saat Anda pindah jaringan (lihat bagian 4).
   - Jika Anda memberi nama folder project **berbeda** dari `qrmed-card`, ubah `APP_SUBPATH` sesuai nama foldernya (contoh: `/qrmed-card-saya`).
   - Jika MySQL Laragon Anda punya password root, isi `DB_PASS`.

4. **Pastikan Apache & MySQL Laragon menyala** (klik tombol **Start All** di Laragon).

5. **Akses website**
   Buka browser ke:
   ```
   http://localhost/qrmed-card/
   ```

6. **Login**
   - **Pasien**: `http://localhost/qrmed-card/login.php`
   - **Admin**: `http://localhost/qrmed-card/admin/login.php` (tautan "Masuk sebagai Admin" juga tersedia di footer landing page)

   Login admin default:
   ```
   Username: admin
   Password: admin123
   ```
   ⚠️ **Segera ganti password admin** setelah login pertama (lewat fitur ubah password di database/phpMyAdmin, karena saat ini belum ada halaman ubah password khusus admin — bisa ditambahkan jika diperlukan).

   Catatan: form login pasien dan admin **terpisah total** — akun admin tidak bisa login lewat form pasien, dan sebaliknya.

---

## 2. Struktur Folder

```
qrmed-card/
├── admin/                  Panel admin (dashboard, kelola pasien, kelola QR)
├── patient/                Area pasien (dashboard, profil, riwayat medis, kartu digital)
├── assets/
│   ├── css/style.css       Styling utama
│   ├── fonts/               Font DejaVu Sans untuk kartu digital (PNG)
│   └── img/uploads/        Foto pasien, QR Code, dan kartu digital hasil generate
├── config/database.php     Konfigurasi koneksi database & BASE_URL
├── includes/                Fungsi bantu, helper QR, generator kartu digital, header/footer
├── libs/phpqrcode/          Library pihak ketiga untuk membuat QR Code (murni PHP, tanpa Composer)
├── database/qrmed_card.sql  Skema database + data awal (akun admin)
├── index.php                Landing page
├── register.php              Registrasi pasien
├── login.php / logout.php    Autentikasi
└── emergency.php              Halaman publik yang tampil saat QR Code discan
```

---

## 3. Alur Penggunaan Sistem

1. **Pasien mendaftar** di `register.php` → sistem otomatis membuat:
   - Patient ID unik (format `QRM-000001`)
   - QR Code unik yang **tidak berubah selamanya**, kecuali diterbitkan ulang oleh admin
2. **Pasien melengkapi data** di dashboard: foto, golongan darah, riwayat penyakit, alergi, obat, kontak darurat.
3. **Golongan darah** yang diisi pasien akan berstatus "menunggu verifikasi" sampai **admin memverifikasinya** — ini untuk mencegah info golongan darah yang salah dipakai saat darurat.
4. **Kartu digital** (PNG) dan **QR Code** bisa diunduh pasien dari menu *Kartu Digital*, untuk disimpan di HP atau dicetak.
5. **Saat QR Code discan** siapa pun, akan terbuka halaman publik `emergency.php` yang menampilkan **hanya** info yang relevan untuk keadaan darurat (nama, usia, gender, golongan darah terverifikasi, kondisi khusus, riwayat penyakit, alergi, obat aktif, dan kontak darurat dengan tombol *tap-to-call*). **NIK dan alamat lengkap tidak pernah ditampilkan** di halaman ini.
6. **Jika kartu fisik hilang**, admin bisa:
   - **Nonaktifkan QR** → siapa pun yang scan kartu lama akan melihat pesan "tidak aktif", data tidak bisa diakses.
   - **Terbitkan Kartu Baru** → membuat token & QR Code baru, kartu lama otomatis tidak berlaku lagi.
7. **Update data kapan saja**: karena QR Code hanya berisi *link + token*, bukan data itu sendiri, perubahan data pasien akan langsung terlihat di halaman darurat tanpa perlu cetak ulang kartu.

---

## 4. Agar QR Code Bisa Discan dari HP (di WiFi manapun, termasuk Hotspot)

Alamat website (`BASE_URL`) sekarang **terdeteksi otomatis** dari alamat yang sedang Anda pakai untuk membuka situs ini — tidak perlu edit `config/database.php` lagi setiap kali pindah jaringan (rumah, kampus, atau hotspot HP). QR Code akan otomatis mengikuti alamat yang aktif saat halaman *Kartu Digital* dibuka.

Yang perlu Anda lakukan hanyalah **mengakses situs lewat alamat yang bisa dijangkau perangkat lain** (bukan `localhost`), lalu buka halaman *Kartu Digital* sekali supaya QR ikut ter-update. Langkahnya:

1. **Pastikan HP (yang akan scan) dan komputer terhubung ke jaringan yang sama** — baik itu WiFi rumah, WiFi kampus, atau **hotspot dari HP Anda sendiri**.

2. **Cari alamat IP lokal komputer Anda** sesuai jaringan yang sedang aktif saat itu:
   - Buka Command Prompt, ketik: `ipconfig`
   - Cari bagian adapter yang sedang **terhubung** (biasanya "Wireless LAN adapter Wi-Fi"), lihat **IPv4 Address**, contoh: `192.168.1.13`
   - Penting: alamat IP ini **berubah** setiap kali Anda pindah jaringan (WiFi rumah != WiFi kampus != hotspot HP). Selalu cek ulang `ipconfig` di lokasi/jaringan yang akan dipakai saat presentasi.

3. **Izinkan Apache lewat Windows Firewall** (cukup sekali saja, tidak perlu diulang tiap ganti jaringan):
   - Buka **Windows Defender Firewall -> Allow an app through firewall**
   - Pastikan **Apache HTTP Server** dicentang untuk jaringan **Private**
   - Jika belum ada di daftar, klik **Allow another app** dan arahkan ke `C:\laragon\bin\apache\...\bin\httpd.exe`

4. **Buka situs dari browser komputer memakai alamat IP tadi** (bukan `localhost`), contoh:
   ```
   http://192.168.1.13/qrmed-card/
   ```
   Login dan buka halaman **Kartu Digital** — QR akan otomatis mengarah ke alamat IP ini.

5. **Scan dari HP** yang terhubung ke jaringan yang sama. Selesai.

### Khusus presentasi pakai Hotspot HP sendiri

Kalau lokasi presentasi tidak ada WiFi dan Anda pakai **hotspot dari HP sendiri**:

1. Nyalakan hotspot di HP Anda, sambungkan **laptop** ke hotspot tersebut.
2. Cek `ipconfig` di laptop -> catat IPv4 Address yang baru (biasanya `192.168.43.x` untuk Android atau `172.20.10.x` untuk iPhone).
3. Buka situs dari laptop pakai IP baru tersebut (langkah 4 di atas), login, buka halaman Kartu Digital.
4. **Dosen bisa scan pakai HP-nya sendiri** asalkan HP dosen juga disambungkan ke hotspot yang sama — atau gunakan HP Anda sendiri (yang menjadi sumber hotspot) untuk scan, karena HP sumber hotspot otomatis bisa mengakses semua perangkat yang tersambung.

Penting: **login & buka Kartu Digital langsung dari alamat yang SAMA PERSIS dengan yang akan dipakai saat presentasi** (jangan login lewat `localhost` lalu berharap pindah ke IP hotspot — sesi login browser tidak akan terbawa karena dianggap "situs" berbeda). Lakukan uji coba scan **sebelum** hari-H untuk memastikan semua lancar.

## 5. Troubleshooting

**QR Code tidak muncul di kartu digital (area kosong):**
Sistem punya mekanisme *self-healing* — jika file QR hilang, rusak, atau gagal dibaca (misalnya karena masalah izin folder di server tertentu), sistem otomatis membuat ulang QR Code tersebut saat halaman *Kartu Digital* dimuat ulang. Jika masalah tetap terjadi setelah refresh, periksa:
- Ekstensi **GD** aktif di `php.ini` (`extension=gd`)
- Folder `assets/img/uploads/qr/` dan `libs/phpqrcode/cache/` bisa ditulis oleh Apache/Laragon

## 6. Catatan Keamanan

- Password disimpan dengan `password_hash()` (bcrypt), tidak pernah plain text.
- Token QR Code adalah string acak sepanjang 64 karakter (`random_bytes(32)` di-hex-kan) — praktis mustahil ditebak.
- Folder `config/`, `includes/`, `libs/`, dan `database/` diblokir aksesnya langsung lewat browser (`.htaccess`).
- Folder upload foto/QR/kartu diblokir dari eksekusi PHP untuk mencegah upload file berbahaya.
- Setiap akses ke halaman darurat via QR dicatat di tabel `access_logs` (opsional, untuk audit).

---

## 7. Yang Bisa Dikembangkan Lebih Lanjut

- Halaman ubah password untuk pasien & admin.
- Export data pasien ke PDF.
- Statistik & laporan untuk admin.
- Notifikasi email saat QR dinonaktifkan/diterbitkan ulang.

Jika ada bagian rancangan yang belum tercakup atau ingin ditambahkan, beri tahu saya bagian mana yang perlu disesuaikan.

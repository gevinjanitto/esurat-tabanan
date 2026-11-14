# E-Surat Tabanan (Laravel 12 + MySQL)

Port dari `gevinjanitto/esurat-kkgusnam` (CodeIgniter 3) ke Laravel 12, MySQL/MariaDB, Tailwind 4 + GSAP + PDF.js.

## Fitur
- Login dengan ilustrasi 3D yang mengikuti gerakan mouse (dan kemiringan HP).
- Dashboard per peran (admin, operator, pimpinan, sekpri, pegawai).
- Surat masuk: pencatatan, unggah PDF, disposisi bertingkat, tindak lanjut, tandai selesai.
- Surat keluar: konsep → verifikasi (bersamaan/berjenjang) → tanda tangan berurutan → selesai. Revisi mengembalikan surat ke pembuat dan nomor revisi bertambah.
- Studio verifikasi: coretan pena/stabilo, penghapus, catatan tempel, paraf yang bisa digeser dan diubah ukurannya. Bisa dipakai dengan mouse, layar sentuh, maupun stylus.
- TTE: QR verifikasi, blok penandatangan, footer, dan paraf verifikator dibubuhkan langsung ke PDF (FPDI).
- Dokumen Saya: semua dokumen yang terkait dengan Anda, dengan tautan berbagi (hanya lihat / lihat & unduh, masa aktif, bisa dicabut, jumlah dibuka).
- Halaman verifikasi publik `/verifikasi/{uuid}` (tujuan QR).
- Data master, pengguna, log aktivitas, profil + paraf (digambar atau diunggah).

### Revisi Tahap 1 (menu, tema merah marun, alur surat inti)
- Sidebar & hak akses per role disamakan dengan kkgusnam (Surat, Surat Masuk, Surat Keluar, Profil, Administrasi, Surat/setting, Catatan Aktivitas); lonceng notifikasi disembunyikan untuk admin/operator, menu akun di topbar.
- Tema merah marun (`maroon-*`, `ruby-*`), aksen emas tetap; ilustrasi `public/images/*.webp` diwarnai ulang (logo Kabupaten Tabanan).
- Surat masuk: teruskan oleh operator ke Direksi / Divisi / Jabatan (+ batal), teruskan ke Sekpri, draft disposisi Sekpri untuk atasan (setujui/ubah/hapus), kotak Disposisi & Disposisi Keluar, Riwayat Disposisi berbentuk pohon (filter per akar & per satker), ubah/hapus disposisi selama belum ditindaklanjuti, master Subjek Disposisi.
- Surat keluar: draft form tersimpan otomatis, banyak lampiran, unggah ulang file, batal kirim, info revisi & info verifikasi per versi, verifikator "Setujui & Teruskan" ke pejabat lain, gerbang Sekpri untuk pimpinan, antrean Verifikasi & Tanda Tangan, pengiriman otomatis ke surat masuk satker tujuan internal setelah TTD selesai.
- Pengguna: kolom `disposisi_awal` dan `atasan_id` (Sekpri → pimpinan); satker punya `kategori` (direksi/divisi/cabang).
- Uji: `php artisan test` (`tests/Feature/Tahap1Test.php`).

### Revisi Tahap 2 (template, penomoran, master, agenda, pelengkap surat)
- Template Surat (admin): kop + isi dari editor teks, isian dinamis (teks/paragraf/tanggal/angka) dengan kode `${KODE}` + kode sistem `${SYS_NO_SURAT}`, `${SYS_TGL_SURAT}`, `${SYS_PERIHAL}`, `${SYS_SIFAT}`, `${SYS_KLASIFIKASI}`, `${SYS_LAMPIRAN}`, `${SYS_TUJUAN}`. Di form surat keluar pilih template → isi → pratinjau → PDF dibuat otomatis (dompdf).
- Penomoran: Template Nomor Surat (operator; susunan klasifikasi keamanan/nomor urut/klasifikasi/unit kerja/unit pemrakarsa/bulan romawi/tahun/kustom), Nomor Inisiasi per satker per tahun (admin & operator), nomor otomatis saat surat diajukan. Daftar Nomor + filter tanggal + ekspor Excel.
- Master: Unit Pemrakarsa, Jabatan per Satker, Alur Surat (template verifikator & penandatangan), Template Tujuan Surat, grup penerima disposisi (tanpa menu), cek username/NRK unik langsung di form pengguna.
- Agenda (operator, sekpri, pegawai): daftar/tambah/ubah/hapus/detail, tautan ke surat asal, widget agenda akan datang di dashboard.
- Pelengkap: cetak Lembar Disposisi (PDF), tanggapan/komentar per disposisi, tag pribadi + filter tag, notifikasi email (SMTP di `.env`, `MAIL_NOTIFIKASI=true`), status tindak lanjut surat keluar, input arsip surat keluar lama, Catatan Aktivitas Esign (admin).
- Uji: `tests/Feature/Tahap2Test.php`.

### Revisi Tahap 3 (laporan, administrasi, integrasi, API mobile)
- Penomoran lanjutan: **Nomor Cadangan** (operator) per satker/jenis surat/bulan (default 5) + perintah `php artisan catat_nomor_cadangan [Y-m-d]` yang mencatat rentang nomor hari sebelumnya (Sabtu, Minggu, hari libur dilewati). Surat bertanggal mundur otomatis memakai nomor cadangan tanggal itu. Satker penomoran terpusat **Sekper / Sisdur** diatur di Setting Sistem (untuk template ber-counter Sekper/Sisdur).
- Laporan (admin): **Rekap Jumlah Surat** per satker (surat masuk, surat keluar, disposisi) per periode + ekspor `.xlsx`.
- **Setting Sistem** (admin): batas gagal login, lama kunci (menit), lama sesi (menit), jam sinkron SDM, satker penomoran Sekper/Sisdur, rate limiting per IP / per login / per pengguna (on/off), status integrasi, statistik reCAPTCHA.
- Kunci login setelah gagal lebih dari N kali selama X menit (per sesi & per username), terbuka otomatis.
- **SDM** (admin): data pegawai dari layanan **HAIRISMA** (`HAIRISMA_URL`), tombol Sinkron Sekarang, `php artisan sync_sdm` (jalan otomatis pada jam di Setting Sistem; `--force` untuk langsung). Aturan sama dengan kkgusnam: satker & jabatan baru dibuat otomatis, user baru username = NIP, Sopir/Satpam dilewati, penempatan jabatan hanya saat sinkron pertama.
- **Jadwal → Hari Libur** (admin), **Transfer Surat** (admin: Disposisi Surat & Surat Keluar, hitung jumlah dulu lalu pindahkan).
- Login dua jenis: **Pegawai (NIP)** via LDAP (`LDAP_HOSTS`; tanpa LDAP memakai password lokal; awalan `o`/`k` dan akun `plt` seperti kkgusnam) dan **Admin/Operator (username)**. Captcha memakai **Google reCAPTCHA** yang sudah ada (isi `RECAPTCHA_SITE_KEY`/`RECAPTCHA_SECRET_KEY`), hasil berhasil/gagal dicatat harian di Setting Sistem.
- **SSO OAuth2 + PKCE**: `/oauth/login`, `/oauth/callback`, `/oauth/verify`, `/oauth/logout`, `/oauth/backchannel-logout` (isi `SSO_*`; tombol tersembunyi bila kosong).
- **Notifikasi push** Firebase Cloud Messaging (isi `FIREBASE_CREDENTIALS` = path file service account JSON) saat ada notifikasi surat/disposisi.
- **API mobile** `/api/m/...` (token Bearer + refresh): `auth`, `refresh-access`, `fcm-token`, `logout`, `me`, `satker`, `surat/masuk`, `surat/masuk/{id}`, `surat/masuk/tujuan-teruskan`, `surat/masuk/teruskan`, `agenda`, `agenda/{id}`, `agenda-akan-datang`, `surat/keluar`, `surat/keluar/{id}`, `surat/keluar/verifikasi`, `surat/keluar/tandatangan`, `surat/keluar/setuju|revisi|simpan-tandatangan`, `disposisi`, `disposisi/{id}`, `disposisi/tujuan`, `disposisi/add`, `disposisi/edit`, dan pratinjau PDF `/loadpdf?type=masuk|keluar&id=..&token=..`.
- Halaman `/privacy` (`/privacy.html`), halaman 404 khusus, dan halaman 503 "sistem sedang disiapkan" bila migrasi belum dijalankan / database belum bisa dihubungi.
- Penjadwal: `php artisan schedule:work` (sudah dijalankan otomatis oleh `start-local.sh` dan `docker/start.sh`). Di server biasa cukup cron: `* * * * * cd /path && php artisan schedule:run`.
- Uji: `tests/Feature/Tahap3Test.php`.

### Revisi Tahap 4 (gambar, agenda dari disposisi, metode tanda tangan, data dummy)
- Ilustrasi amplop: tulisan nama dihapus, segel lilin berisi logo Kabupaten Tabanan.
- Disposisi: opsi **Masukkan ke Agenda** (otomatis tercentang untuk surat undangan/rapat) dengan judul, waktu, metode, tempat & tautan. Agenda terlihat oleh semua pengguna (termasuk pimpinan).
- Tambah agenda manual: pilih **dari surat masuk** (pilih suratnya) atau **agenda mandiri**.
- Surat keluar: tujuan & tembusan internal memakai dropdown (satu satker), pilihan **Metode Tandatangan** (Elektronik / Basah). Tanda tangan basah: penandatangan mengunduh, menandatangani, lalu mengunggah scan PDF.
- Popup konfirmasi (ringkasan isian) sebelum menyimpan surat keluar maupun surat masuk.
- `DemoSeeder`: satker, pegawai, 15+ surat masuk, disposisi bertingkat, 14 surat keluar berbagai status, agenda, notifikasi, log, SDM, hari libur.

## Jalankan lokal
```
bash setup.sh            # installer lokal: ekstrak project, .env, database, composer, yarn, migrate --seed
./start-local.sh         # menjalankan aplikasi + penjadwal (Windows: start-local.bat)
# atau manual:
composer install && yarn install && yarn build
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve
```
Akun awal (password `SEED_PASSWORD`, default `Esurat123!`): admin, operator (tab Admin/Operator), dan pegawai pimpinan, sekpri, kadiv, staf, staf2 (tab Pegawai dengan NRK 10001-10005 atau username).

## Deploy Railway
Kalau Anda push seluruh folder `/app` dari Emergent ke GitHub, tidak perlu setting apa pun: `Dockerfile` dan `railway.json` di folder paling atas sudah otomatis membangun `esurat-laravel/`. Kalau yang Anda push hanya folder `esurat-laravel`, Railway akan memakai `Dockerfile` di folder ini.
1. Push folder ini ke GitHub, lalu di Railway: New Project → Deploy from GitHub (Dockerfile & railway.json otomatis terbaca).
2. Tambah service **MySQL** pada project yang sama.
3. Variables pada service app:
   - `APP_KEY` = hasil `php artisan key:generate --show`
   - `APP_URL` = https://domain-anda.up.railway.app
   - `APP_ENV=production`, `APP_DEBUG=false`, `FORCE_HTTPS=true`, `SESSION_SECURE_COOKIE=true`
   - `DB_CONNECTION=mysql`, `DB_URL=${{MySQL.MYSQL_URL}}`
   - `SEED_PASSWORD` = password awal semua akun (ganti setelah login)
   - `RECAPTCHA_SITE_KEY` dan `RECAPTCHA_SECRET_KEY` = kunci Google reCAPTCHA v2 "Saya bukan robot" (buat di https://www.google.com/recaptcha/admin/create, tambahkan domain Railway Anda). Jika dikosongkan, captcha tidak ditampilkan.
4. Tambah **Volume** ke service app dengan mount path `/app/storage/app` agar PDF & paraf tidak hilang saat redeploy.
5. Deploy. Migrasi dan seeder awal (hanya jika belum ada data) berjalan otomatis lewat `docker/start.sh`.

## Catatan
- Aplikasi berdiri sendiri: tidak memakai plugin, library, maupun layanan Emergent. Font sudah di-bundle (tidak memanggil Google Fonts). Layanan luar yang dipakai hanya Google reCAPTCHA, dan itu pun hanya bila kuncinya diisi.
- TTE memakai passphrase = password akun dan segel lokal (QR + blok tanda tangan). Belum tersambung ke penyedia TTE tersertifikasi (BSrE). Integrasi itu bisa ditambahkan di `app/Services/TteService.php`.
- Semua modul kkgusnam sudah diporting. SSO, LDAP, HAIRISMA, dan Firebase aktif setelah datanya diisi di `.env` (lihat `.env.example`); tanpa itu aplikasi tetap berjalan penuh dengan login lokal.
- Layanan luar yang dipakai hanya bila diisi: Google reCAPTCHA, SSO bank, LDAP bank, HAIRISMA, Firebase Cloud Messaging.

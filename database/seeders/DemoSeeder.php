<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Agenda;
use App\Models\AppNotification;
use App\Models\DisposisiComment;
use App\Models\EsignLog;
use App\Models\HariLibur;
use App\Models\Jabatan;
use App\Models\Klasifikasi;
use App\Models\KlasifikasiKeamanan;
use App\Models\NomorInisiasi;
use App\Models\Satker;
use App\Models\Sdm;
use App\Models\Sifat;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\TemplateTujuanDisposisi;
use App\Models\TemplateTujuanSurat;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DemoSeeder extends Seeder
{
    private array $u = [];

    public function run(): void
    {
        if (app()->environment('testing') || SuratMasuk::where('nomor_agenda', 'like', 'DM-%')->exists() || !User::where('username', 'operator')->exists()) {
            return;
        }
        try {
            DB::transaction(function () {
                $this->satkerDanPengguna();
                $masuk = $this->suratMasuk();
                $this->agenda($masuk);
                $this->suratKeluar();
                $this->pelengkap();
            });
            $this->command?->info('Data dummy tambahan berhasil dibuat.');
        } catch (\Throwable $e) {
            report($e);
            $this->command?->warn('Data dummy dilewati: ' . $e->getMessage());
        }
    }

    private function satkerDanPengguna(): void
    {
        $password = config('app.seed_password');
        $dir = Satker::firstOrCreate(['kode' => 'DIR'], ['nama' => 'Direksi', 'kategori' => 'direksi']);
        foreach ([['SKP', 'Divisi Sekretariat Perusahaan'], ['UMM', 'Divisi Umum'], ['TI', 'Divisi Teknologi Informasi']] as [$k, $n]) {
            $s = Satker::firstOrCreate(['kode' => $k], ['nama' => $n, 'kategori' => 'divisi', 'parent_id' => $dir->id]);
            NomorInisiasi::firstOrCreate(['satker_id' => $s->id, 'tahun' => now()->year], ['nomor_awal' => 1]);
        }
        $dasar = [
            ['admin', 'Administrator Sistem', 'admin', 'TI', null, false],
            ['operator', 'Ni Made Ayu Lestari', 'operator', 'SKP', 'Operator Persuratan', false],
            ['pimpinan', 'I Gusti Ngurah Agung', 'pimpinan', 'DIR', 'Direktur Utama', true],
            ['sekpri', 'Kadek Dwi Pratiwi', 'sekpri', 'SKP', 'Sekretaris Direksi', true],
            ['kadiv', 'I Wayan Sudarma', 'pimpinan', 'UMM', 'Kepala Divisi Umum', true],
            ['staf', 'Putu Gede Arimbawa', 'user', 'UMM', 'Staf Divisi Umum', false],
            ['staf2', 'Komang Indah Sari', 'user', 'TI', 'Staf Teknologi Informasi', false],
        ];
        foreach ($dasar as [$un, $nama, $role, $kode, $jab, $pimpinan]) {
            $s = Satker::where('kode', $kode)->first();
            $j = $jab ? Jabatan::firstOrCreate(['nama' => $jab, 'satker_id' => $s->id], ['is_pimpinan' => $pimpinan]) : null;
            $user = User::firstOrCreate(['username' => $un], ['name' => $nama, 'role' => $role, 'password' => $password, 'satker_id' => $s->id, 'jabatan_id' => $j?->id, 'is_active' => true, 'disposisi_awal' => $pimpinan]);
            if (!$user->satker_id) {
                $user->forceFill(['satker_id' => $s->id])->save();
            }
        }
        $baru = [
            ['KPT', 'Kantor Pusat', 'divisi'], ['KRD', 'Divisi Kredit', 'divisi'], ['KPN', 'Divisi Kepatuhan', 'divisi'], ['TRS', 'Divisi Treasury', 'divisi'],
            ['SDM', 'Divisi Sumber Daya Manusia', 'divisi'], ['CDP', 'Kantor Cabang Denpasar', 'cabang'], ['CGN', 'Kantor Cabang Gianyar', 'cabang'], ['CSR', 'Kantor Cabang Singaraja', 'cabang'],
        ];
        foreach ($baru as [$k, $n, $kat]) {
            $s = Satker::firstOrCreate(['kode' => $k], ['nama' => $n, 'kategori' => $kat, 'parent_id' => $dir?->id]);
            NomorInisiasi::firstOrCreate(['satker_id' => $s->id, 'tahun' => now()->year], ['nomor_awal' => 1]);
        }
        $orang = [
            ['dirkredit', 'Ida Bagus Made Wiryawan', 'pimpinan', 'DIR', 'Direktur Kredit', true, '10006'],
            ['kadivkredit', 'Ni Luh Putu Sri Wahyuni', 'pimpinan', 'KRD', 'Kepala Divisi Kredit', true, '10007'],
            ['stafkredit', 'Gede Yoga Pramana', 'user', 'KRD', 'Analis Kredit', false, '10008'],
            ['kadivkpn', 'Made Suastika', 'pimpinan', 'KPN', 'Kepala Divisi Kepatuhan', true, '10009'],
            ['stafkpn', 'Luh Gede Ratna Dewi', 'user', 'KPN', 'Staf Kepatuhan', false, '10010'],
            ['kadivsdm', 'Nyoman Adi Putra', 'pimpinan', 'SDM', 'Kepala Divisi SDM', true, '10011'],
            ['stafsdm', 'Kadek Ayu Wulandari', 'user', 'SDM', 'Staf Rekrutmen', false, '10012'],
            ['pincadps', 'Wayan Eka Saputra', 'pimpinan', 'CDP', 'Pemimpin Cabang Denpasar', true, '10013'],
            ['stafdps', 'Komang Tri Handayani', 'user', 'CDP', 'Staf Operasional Cabang', false, '10014'],
            ['kadivtrs', 'Putu Agus Suryawan', 'pimpinan', 'TRS', 'Kepala Divisi Treasury', true, '10015'],
        ];
        foreach ($orang as [$un, $nama, $role, $kode, $jab, $pimpinan, $nrk]) {
            $s = Satker::where('kode', $kode)->first();
            $j = Jabatan::firstOrCreate(['nama' => $jab, 'satker_id' => $s->id], ['is_pimpinan' => $pimpinan]);
            User::firstOrCreate(['username' => $un], ['name' => $nama, 'role' => $role, 'nrk' => $nrk, 'nip' => '19' . rand(70, 99) . str_pad((string) rand(1, 9999999999), 14, '0', STR_PAD_LEFT), 'password' => $password,
                'satker_id' => $s->id, 'jabatan_id' => $j->id, 'is_active' => true, 'disposisi_awal' => $pimpinan, 'email' => $un . '@tabanankab.example']);
        }
        foreach (User::all() as $user) {
            $this->u[$user->username] = $user;
        }
    }

    private function suratMasuk(): array
    {
        $sf = Sifat::pluck('id', 'nama');
        $kl = Klasifikasi::pluck('id', 'kode');
        $km = KlasifikasiKeamanan::pluck('id', 'kode');
        $sk = Satker::pluck('id', 'kode');
        $op = $this->u['operator'];
        $rows = [
            ['Bank Indonesia Provinsi Bali', 'Undangan Sosialisasi Kebijakan Makroprudensial', 'Undangan sosialisasi kebijakan makroprudensial terbaru kepada perbankan daerah.', 'Segera', 'UM', 'disposisi', 1],
            ['Otoritas Jasa Keuangan Regional 8', 'Permintaan Laporan Profil Risiko Semester I', 'OJK meminta laporan profil risiko semester I paling lambat akhir bulan.', 'Sangat Segera', 'HK', 'disposisi', 4],
            ['Pemerintah Provinsi Bali', 'Undangan Rapat Penyaluran KUR Daerah', 'Rapat koordinasi target penyaluran Kredit Usaha Rakyat daerah.', 'Segera', 'KU', 'disposisi', 6],
            ['PT Telkom Indonesia', 'Penawaran Upgrade Jaringan Kantor Cabang', 'Penawaran peningkatan kapasitas jaringan untuk 12 kantor cabang.', 'Biasa', 'PR', 'baru', 2],
            ['Lembaga Penjamin Simpanan', 'Pemberitahuan Tingkat Bunga Penjaminan', 'Pemberitahuan perubahan tingkat bunga penjaminan simpanan.', 'Biasa', 'KU', 'selesai', 15],
            ['Universitas Udayana', 'Permohonan Tempat Magang Mahasiswa', 'Permohonan penempatan 6 mahasiswa magang pada divisi TI dan Kredit.', 'Biasa', 'KP', 'disposisi', 8],
            ['Kantor Akuntan Publik Sudana & Rekan', 'Jadwal Audit Laporan Keuangan Tahunan', 'Penyampaian jadwal audit lapangan laporan keuangan tahunan.', 'Segera', 'KU', 'disposisi', 3],
            ['Asosiasi Bank Pembangunan Daerah', 'Undangan Rapat Kerja Nasional ASBANDA', 'Undangan rapat kerja nasional seluruh pimpinan pemerintah daerah se-Indonesia.', 'Segera', 'UM', 'disposisi', 5],
            ['Kepolisian Daerah Bali', 'Permintaan Data Rekening Nasabah', 'Permintaan data dalam rangka penyidikan (sesuai ketentuan rahasia bank).', 'Sangat Segera', 'HK', 'baru', 1],
            ['PT PLN (Persero) UID Bali', 'Kerja Sama Pembayaran Tagihan Listrik', 'Perpanjangan kerja sama payment point online bank.', 'Biasa', 'UM', 'selesai', 20],
            ['Dinas Pendidikan Provinsi Bali', 'Undangan Peluncuran Tabungan Pelajar', 'Undangan acara peluncuran program tabungan pelajar SimPel.', 'Biasa', 'UM', 'disposisi', 2],
            ['Badan Pemeriksa Keuangan RI', 'Permintaan Dokumen Pemeriksaan Kinerja', 'Permintaan dokumen pendukung pemeriksaan kinerja BUMD.', 'Segera', 'HK', 'disposisi', 7],
            ['Bank Mandiri Kanwil XI', 'Penawaran Kerja Sama Sindikasi Kredit', 'Penawaran partisipasi sindikasi kredit proyek infrastruktur.', 'Biasa', 'KU', 'baru', 0],
            ['Divisi Sumber Daya Manusia', 'Undangan Pelatihan Manajemen Risiko', 'Pelatihan sertifikasi manajemen risiko level 1 untuk pegawai.', 'Biasa', 'KP', 'disposisi', 9],
            ['Kementerian Dalam Negeri', 'Surat Edaran Pengelolaan Kas Daerah', 'Surat edaran tentang pengelolaan kas daerah pada bank daerah.', 'Biasa', 'KU', 'selesai', 25],
        ];
        $tambahan = [
            ['Bank Indonesia Provinsi Bali', 'Permintaan Data Penyaluran Kredit UMKM', 'BI meminta data penyaluran kredit UMKM per sektor ekonomi.'],
            ['Otoritas Jasa Keuangan Regional 8', 'Undangan Sosialisasi POJK Tata Kelola', 'Sosialisasi peraturan OJK terbaru mengenai tata kelola bank umum.'],
            ['Pemerintah Kabupaten Badung', 'Permohonan Kerja Sama Pembayaran Pajak Daerah', 'Kerja sama kanal pembayaran pajak hotel dan restoran.'],
            ['Pemerintah Kota Denpasar', 'Undangan Festival UMKM Denpasar', 'Undangan partisipasi stan perbankan pada festival UMKM.'],
            ['PT Jasa Raharja Cabang Bali', 'Penawaran Kerja Sama Penempatan Dana', 'Penawaran penempatan dana deposito korporasi.'],
            ['BPJS Ketenagakerjaan Bali', 'Pemberitahuan Penyesuaian Iuran Pegawai', 'Pemberitahuan penyesuaian iuran jaminan pensiun pegawai.'],
            ['BPJS Kesehatan Cabang Denpasar', 'Rekonsiliasi Data Kepesertaan Pegawai', 'Permintaan rekonsiliasi data kepesertaan pegawai dan keluarga.'],
            ['Kejaksaan Tinggi Bali', 'Undangan Penandatanganan MoU Bantuan Hukum', 'Penandatanganan nota kesepahaman bantuan hukum perdata dan TUN.'],
            ['Pengadilan Negeri Denpasar', 'Pemberitahuan Sidang Perkara Perdata', 'Pemberitahuan jadwal sidang perkara wanprestasi debitur.'],
            ['Badan Pertanahan Nasional Kanwil Bali', 'Konfirmasi Status Sertifikat Agunan', 'Konfirmasi status sertifikat hak milik atas agunan kredit.'],
            ['Kantor Pelayanan Pajak Pratama Denpasar Timur', 'Himbauan Pelaporan SPT Masa PPh', 'Himbauan pelaporan SPT masa PPh pasal 21 dan 23 tepat waktu.'],
            ['Dinas Koperasi dan UKM Provinsi Bali', 'Usulan Program Kredit Mikro Koperasi', 'Usulan kolaborasi program kredit mikro untuk koperasi desa.'],
            ['Dinas Pariwisata Provinsi Bali', 'Undangan Rapat Pemulihan Sektor Pariwisata', 'Rapat koordinasi dukungan pembiayaan sektor pariwisata.'],
            ['Universitas Pendidikan Ganesha', 'Permohonan Narasumber Seminar Literasi Keuangan', 'Permohonan narasumber seminar literasi keuangan mahasiswa.'],
            ['Politeknik Negeri Bali', 'Permohonan Kunjungan Industri Mahasiswa', 'Kunjungan industri 40 mahasiswa jurusan akuntansi.'],
            ['PT Visionet Data Internasional', 'Penawaran Layanan QRIS Merchant', 'Penawaran integrasi layanan QRIS untuk merchant nasabah.'],
            ['PT Artajasa Pembayaran Elektronis', 'Pemberitahuan Pemeliharaan Jaringan ATM Bersama', 'Jadwal pemeliharaan jaringan ATM Bersama akhir pekan.'],
            ['PT Jalin Pembayaran Nusantara', 'Perpanjangan Kerja Sama Switching', 'Perpanjangan perjanjian layanan switching transaksi.'],
            ['Asosiasi Bank Pembangunan Daerah', 'Survei Kesiapan Transformasi Digital Pemda', 'Pengisian survei kesiapan transformasi digital pemda.'],
            ['Lembaga Pengembangan Perbankan Indonesia', 'Undangan Pelatihan Sertifikasi Treasury', 'Pelatihan sertifikasi dealer treasury tingkat dasar.'],
            ['Kantor Akuntan Publik Sudana & Rekan', 'Permintaan Konfirmasi Saldo Antarbank', 'Permintaan konfirmasi saldo penempatan antarbank.'],
            ['PT Pegadaian Kanwil VII Denpasar', 'Penawaran Kerja Sama Tabungan Emas', 'Penawaran kerja sama produk tabungan emas bagi nasabah.'],
            ['Pemerintah Kabupaten Gianyar', 'Permohonan Penyaluran Gaji ASN', 'Permohonan penyaluran gaji ASN melalui rekening bank daerah.'],
            ['Pemerintah Kabupaten Buleleng', 'Undangan Musrenbang Kabupaten', 'Undangan musyawarah perencanaan pembangunan kabupaten.'],
            ['Pemerintah Kabupaten Tabanan', 'Permintaan Laporan Dana CSR', 'Permintaan laporan realisasi dana CSR di wilayah Tabanan.'],
            ['Komisi Pemberantasan Korupsi', 'Undangan Sosialisasi Pengendalian Gratifikasi', 'Sosialisasi pengendalian gratifikasi bagi BUMD.'],
            ['Pusat Pelaporan dan Analisis Transaksi Keuangan', 'Permintaan Informasi Transaksi Mencurigakan', 'Permintaan informasi transaksi keuangan mencurigakan (rahasia).'],
            ['Kementerian Keuangan RI', 'Pemberitahuan Penyaluran Dana Transfer Daerah', 'Pemberitahuan jadwal penyaluran dana transfer ke daerah.'],
            ['PT Asuransi Kredit Indonesia', 'Penawaran Perpanjangan Penjaminan Kredit', 'Penawaran perpanjangan polis penjaminan kredit produktif.'],
            ['PT Bank Rakyat Indonesia Kanwil Denpasar', 'Undangan Forum Komunikasi Perbankan Bali', 'Forum komunikasi pimpinan perbankan se-Bali.'],
            ['Dinas Kependudukan dan Catatan Sipil Bali', 'Perpanjangan Akses Verifikasi Data Kependudukan', 'Perpanjangan perjanjian akses verifikasi NIK nasabah.'],
            ['Badan Siber dan Sandi Negara', 'Undangan Asesmen Keamanan Siber', 'Asesmen tingkat kematangan keamanan siber perbankan.'],
            ['Ombudsman RI Perwakilan Bali', 'Permintaan Klarifikasi Pengaduan Nasabah', 'Permintaan klarifikasi atas pengaduan layanan nasabah.'],
            ['PT Telkom Indonesia', 'Tagihan Layanan Jaringan Bulan Berjalan', 'Penyampaian tagihan layanan jaringan dan internet kantor.'],
            ['Divisi Sumber Daya Manusia', 'Undangan Pemeriksaan Kesehatan Berkala Pegawai', 'Jadwal medical check-up berkala seluruh pegawai.'],
        ];
        $sifatList = ['Biasa', 'Segera', 'Sangat Segera', 'Biasa'];
        $klasList = ['UM', 'KU', 'HK', 'KP', 'PR'];
        $statusList = ['disposisi', 'baru', 'disposisi', 'selesai', 'disposisi'];
        foreach ($tambahan as $k => [$pengirim, $perihal, $ringkas]) {
            $rows[] = [$pengirim, $perihal, $ringkas, $sifatList[$k % 4], $klasList[$k % 5], $statusList[$k % 5], 1 + ($k * 3) % 58];
        }
        $out = [];
        foreach ($rows as $i => [$pengirim, $perihal, $ringkas, $sifat, $klas, $status, $hari]) {
            $no = $i + 1;
            $path = $this->samplePdf("surat-masuk/demo-$no.pdf", $perihal, $pengirim);
            $m = SuratMasuk::create([
                'nomor_agenda' => sprintf('DM-%s-%04d', now()->year, $no), 'nomor_surat' => sprintf('%03d/%s/%s/%s', 100 + $no * 7, strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $pengirim), 0, 3)), $this->romawi((int) now()->subDays($hari + 2)->month), now()->year),
                'tanggal_surat' => now()->subDays($hari + 2), 'tanggal_terima' => now()->subDays($hari), 'pengirim' => $pengirim, 'perihal' => $perihal, 'ringkasan' => $ringkas,
                'sifat_id' => $sf[$sifat] ?? null, 'klasifikasi_id' => $kl[$klas] ?? null, 'klasifikasi_keamanan_id' => $klas === 'HK' ? ($km['R'] ?? null) : ($km['B'] ?? null),
                'file_path' => $path, 'file_name' => str($perihal)->slug() . '.pdf', 'status' => $status, 'jenis' => 'eksternal', 'created_by' => $op->id, 'satker_id' => $sk['SKP'] ?? null,
            ]);
            $m->forceFill(['created_at' => now()->subDays($hari), 'updated_at' => now()->subDays(max(0, $hari - 1))])->save();
            $m->teruskans()->create(['tipe' => 'direksi', 'satker_id' => $sk['DIR'], 'user_id' => $this->u['pimpinan']->id, 'dari_user_id' => $op->id, 'status' => 'aktif']);
            if ($i % 3 === 1) {
                $m->teruskans()->create(['tipe' => 'divisi', 'satker_id' => $sk['KRD'], 'user_id' => null, 'dari_user_id' => $op->id, 'status' => 'aktif']);
            }
            $kadivs = ['kadiv', 'kadivkredit', 'kadivkpn', 'kadivsdm', 'kadivtrs', 'pincadps'];
            $kd = $this->u[$kadivs[$i % count($kadivs)]];
            if ($kd->satker_id !== ($sk['KRD'] ?? null) || $i % 3 !== 1) {
                $m->teruskans()->create(['tipe' => 'divisi', 'satker_id' => $kd->satker_id, 'user_id' => null, 'dari_user_id' => $op->id, 'status' => 'aktif']);
            }
            $stafs = ['staf', 'staf2', 'stafkredit', 'stafkpn', 'stafsdm', 'stafdps'];
            $st = $this->u[$stafs[$i % count($stafs)]];
            $m->teruskans()->create(['tipe' => 'jabatan', 'satker_id' => $st->satker_id, 'user_id' => $st->id, 'dari_user_id' => $op->id, 'status' => 'aktif']);
            if ($i % 2 === 0) {
                $m->teruskans()->create(['tipe' => 'sekpri', 'satker_id' => $this->u['sekpri']->satker_id, 'user_id' => $this->u['sekpri']->id, 'dari_user_id' => $this->u['pimpinan']->id, 'status' => 'aktif']);
            }
            if ($status !== 'baru') {
                $this->disposisiChain($m, $i, $status === 'selesai');
            }
            $out[] = $m;
        }
        return $out;
    }

    private function disposisiChain(SuratMasuk $m, int $i, bool $selesai): void
    {
        $u = $this->u;
        $instruksi = ['Mohon arahan', 'Untuk ditindaklanjuti', 'Hadiri / wakili', 'Siapkan jawaban', 'Untuk dipelajari', 'Koordinasikan'];
        $kadivs = ['kadiv', 'kadivkredit', 'kadivkpn', 'kadivsdm', 'kadivtrs'];
        $kadiv = $u[$kadivs[$i % count($kadivs)]];
        $stafMap = ['kadiv' => 'staf', 'kadivkredit' => 'stafkredit', 'kadivkpn' => 'stafkpn', 'kadivsdm' => 'stafsdm', 'kadivtrs' => 'staf2'];
        $staf = $u[$stafMap[$kadivs[$i % count($kadivs)]]];
        $d1 = $m->disposisis()->create(['dari_user_id' => $u['pimpinan']->id, 'ke_user_id' => $kadiv->id, 'instruksi' => $instruksi[$i % 6], 'catatan' => 'Mohon segera ditindaklanjuti dan laporkan hasilnya.', 'batas_waktu' => now()->addDays(3 + $i % 5), 'status' => 'ditindaklanjuti', 'dibaca_at' => now()->subDays(1), 'tindak_lanjut' => 'Diteruskan ke staf terkait.']);
        $d2 = $m->disposisis()->create(['parent_id' => $d1->id, 'dari_user_id' => $kadiv->id, 'ke_user_id' => $staf->id, 'instruksi' => $instruksi[($i + 1) % 6], 'catatan' => 'Siapkan bahan dan koordinasikan dengan unit terkait.', 'batas_waktu' => now()->addDays(2 + $i % 4),
            'status' => $selesai ? 'ditindaklanjuti' : ['terkirim', 'dibaca', 'ditindaklanjuti'][$i % 3], 'dibaca_at' => $selesai || $i % 3 ? now()->subHours(5) : null, 'tindak_lanjut' => $selesai || $i % 3 === 2 ? 'Sudah dikerjakan, laporan terlampir di sistem.' : null]);
        if ($i % 2 === 0) {
            $m->disposisis()->create(['dari_user_id' => $u['pimpinan']->id, 'ke_user_id' => $u['sekpri']->id, 'instruksi' => 'Untuk diketahui', 'catatan' => 'Catat di agenda pimpinan.', 'status' => 'dibaca', 'dibaca_at' => now()->subHours(3)]);
        }
        DisposisiComment::create(['disposisi_id' => $d2->id, 'user_id' => $staf->id, 'komentar' => 'Baik, akan segera saya kerjakan.']);
        DisposisiComment::create(['disposisi_id' => $d2->id, 'user_id' => $kadiv->id, 'komentar' => 'Terima kasih, mohon kabari bila ada kendala.']);
    }

    private function agenda(array $masuk): void
    {
        $u = $this->u;
        $items = [
            [0, 'Sosialisasi Kebijakan Makroprudensial BI', 'hybrid', 'Kantor Perwakilan BI Bali, Renon', 'https://meet.example.com/bi-makro', 2, 9, 'pimpinan', true],
            [2, 'Rapat Penyaluran KUR Daerah', 'offline', 'Ruang Rapat Gubernur, Kantor Gubernur Bali', null, 4, 10, 'kadivkredit', true],
            [7, 'Rakernas ASBANDA', 'offline', 'Hotel Grand Hyatt, Nusa Dua', null, 9, 8, 'pimpinan', true],
            [10, 'Peluncuran Tabungan Pelajar SimPel', 'offline', 'Gedung Ksirarnawa, Art Center Denpasar', null, 6, 9, 'operator', false],
            [13, 'Pelatihan Manajemen Risiko Level 1', 'online', null, 'https://meet.example.com/pelatihan-mr', 12, 13, 'kadivsdm', true],
            [6, 'Entry Meeting Audit Tahunan KAP', 'offline', 'Ruang Rapat Direksi Lt. 5', null, 1, 14, 'staf', false],
            [null, 'Rapat Evaluasi Kinerja Bulanan Direksi', 'offline', 'Ruang Rapat Direksi Lt. 5', null, 3, 15, 'sekpri', false],
            [null, 'Town Hall Meeting Seluruh Pegawai', 'hybrid', 'Auditorium Kantor Pusat', 'https://meet.example.com/townhall', 14, 16, 'operator', false],
            [null, 'Uji Coba Disaster Recovery Center', 'online', null, 'https://meet.example.com/drc-test', 5, 20, 'staf2', false],
            [null, 'Kunjungan Kerja ke Cabang Singaraja', 'offline', 'Kantor Cabang Singaraja', null, -4, 9, 'kadiv', false],
            [4, 'Briefing Perubahan Bunga Penjaminan LPS', 'online', null, 'https://meet.example.com/lps', -8, 10, 'kadivtrs', false],
        ];
        foreach ($items as [$idx, $judul, $metode, $tempat, $link, $hari, $jam, $pemilik, $dariDisposisi]) {
            $surat = $idx !== null ? $masuk[$idx] : null;
            $disp = $dariDisposisi && $surat ? $surat->disposisis()->oldest('id')->first() : null;
            Agenda::create(['surat_masuk_id' => $surat?->id, 'disposisi_id' => $disp?->id, 'user_id' => $u[$pemilik]->id, 'judul' => $judul, 'metode' => $metode, 'tempat' => $tempat, 'link_meeting' => $link,
                'mulai' => now()->addDays($hari)->setTime($jam, 0), 'selesai' => now()->addDays($hari)->setTime($jam + 2, 0), 'deskripsi' => $disp ? 'Dari disposisi: ' . $disp->instruksi : 'Mohon hadir tepat waktu.']);
        }
    }

    private function suratKeluar(): void
    {
        $u = $this->u;
        $sf = Sifat::pluck('id', 'nama');
        $kl = Klasifikasi::pluck('id', 'kode');
        $km = KlasifikasiKeamanan::pluck('id', 'kode');
        $sk = Satker::pluck('id', 'kode');
        $rows = [
            ['staf', 'Laporan Realisasi Anggaran Triwulan II', 'Direksi', 'UMM', 'KU', 'DIR', 'SKP', 0, 'elektronik'],
            ['staf2', 'Permohonan Penambahan Lisensi Perangkat Lunak', 'Divisi Umum', 'TI', 'PR', 'UMM', 'KPT', 1, 'elektronik'],
            ['stafkredit', 'Usulan Restrukturisasi Kredit Debitur UMKM', 'Direktur Kredit', 'KRD', 'KU', 'DIR', 'KPN', 10, 'basah'],
            ['stafkpn', 'Laporan Kepatuhan APU-PPT Semester I', 'Otoritas Jasa Keuangan Regional 8', 'KPN', 'HK', 'DIR', 'SKP', 2, 'elektronik'],
            ['stafsdm', 'Pengumuman Rekrutmen Pegawai Tahun Ini', 'Seluruh Kantor Cabang', 'SDM', 'KP', 'CDP', 'CGN', 2, 'elektronik'],
            ['stafdps', 'Permohonan Perbaikan Gedung Kantor Cabang', 'Divisi Umum', 'CDP', 'PR', 'UMM', 'KPT', 11, 'basah'],
            ['staf', 'Nota Dinas Penghematan Energi Kantor', 'Seluruh Divisi', 'UMM', 'UM', 'TI', 'KRD', 12, 'elektronik'],
            ['staf2', 'Jadwal Pemeliharaan Sistem Core Banking', 'Seluruh Kantor Cabang', 'TI', 'UM', 'CDP', 'CSR', 2, 'basah'],
            ['stafkredit', 'Rekap Penyaluran KUR Bulanan', 'Direksi', 'KRD', 'KU', 'DIR', 'TRS', 1, 'basah'],
            ['stafsdm', 'Undangan Pelatihan Pelayanan Prima', 'Kantor Cabang Gianyar', 'SDM', 'KP', 'CGN', 'CSR', 10, 'elektronik'],
            ['staf', 'Permohonan Pengadaan Kendaraan Operasional', 'Direktur Utama', 'UMM', 'PR', 'DIR', 'SKP', 0, 'basah'],
            ['stafkpn', 'Tanggapan atas Temuan Audit Internal', 'Satuan Kerja Audit Internal', 'KPN', 'HK', 'KPT', 'DIR', 2, 'elektronik'],
        ];
        $pembuatSatker = ['staf' => 'UMM', 'staf2' => 'TI', 'stafkredit' => 'KRD', 'stafkpn' => 'KPN', 'stafsdm' => 'SDM', 'stafdps' => 'CDP'];
        $tambahanKeluar = [
            ['staf', 'Permohonan Pengadaan Alat Tulis Kantor', 'Divisi Umum'], ['staf2', 'Laporan Insiden Gangguan Jaringan', 'Direksi'],
            ['stafkredit', 'Usulan Plafon Kredit Modal Kerja Koperasi', 'Direktur Kredit'], ['stafkpn', 'Laporan Penerapan Manajemen Risiko', 'Otoritas Jasa Keuangan Regional 8'],
            ['stafsdm', 'Pengumuman Hasil Seleksi Promosi Jabatan', 'Seluruh Divisi'], ['stafdps', 'Laporan Kinerja Cabang Denpasar Bulanan', 'Direksi'],
            ['staf', 'Undangan Rapat Koordinasi Divisi Umum', 'Seluruh Divisi'], ['staf2', 'Permohonan Akses VPN Pegawai Baru', 'Divisi Teknologi Informasi'],
            ['stafkredit', 'Laporan Kolektibilitas Kredit Triwulan', 'Direksi'], ['stafkpn', 'Jawaban Klarifikasi Pengaduan Nasabah', 'Ombudsman RI Perwakilan Bali'],
            ['stafsdm', 'Surat Tugas Pelatihan Sertifikasi Treasury', 'Divisi Treasury'], ['stafdps', 'Permohonan Penambahan Mesin ATM', 'Divisi Umum'],
            ['staf', 'Pemberitahuan Jadwal Fumigasi Gedung', 'Seluruh Pegawai'], ['staf2', 'Rencana Migrasi Server Data Center', 'Direksi'],
            ['stafkredit', 'Permohonan Penilaian Ulang Agunan', 'Divisi Kepatuhan'], ['stafkpn', 'Laporan Pelaksanaan Program APU-PPT', 'Pusat Pelaporan dan Analisis Transaksi Keuangan'],
            ['stafsdm', 'Edaran Penyesuaian Jam Kerja Ramadan', 'Seluruh Kantor Cabang'], ['stafdps', 'Laporan Stock Opname Kas Cabang', 'Divisi Treasury'],
            ['staf', 'Permohonan Sewa Gedung Kantor Kas', 'Direktur Utama'], ['staf2', 'Laporan Uji Penetrasi Aplikasi Mobile', 'Direksi'],
            ['stafkredit', 'Rekomendasi Hapus Buku Kredit Macet', 'Direktur Kredit'], ['stafkpn', 'Tanggapan Hasil Pemeriksaan BPK', 'Badan Pemeriksa Keuangan RI'],
            ['stafsdm', 'Permohonan Pengangkatan Pegawai Tetap', 'Direktur Utama'], ['stafdps', 'Undangan Customer Gathering Cabang Denpasar', 'Nasabah Prioritas'],
            ['staf', 'Laporan Pemeliharaan Kendaraan Dinas', 'Divisi Umum'], ['staf2', 'Permohonan Perpanjangan Lisensi Antivirus', 'Divisi Umum'],
            ['stafkredit', 'Laporan Penyaluran Kredit Pariwisata', 'Dinas Pariwisata Provinsi Bali'], ['stafkpn', 'Nota Dinas Pemutakhiran Kebijakan Kepatuhan', 'Seluruh Divisi'],
            ['stafsdm', 'Jadwal Pemeriksaan Kesehatan Berkala', 'Seluruh Pegawai'], ['stafdps', 'Permohonan Renovasi Ruang Layanan Nasabah', 'Divisi Umum'],
            ['staf', 'Laporan Penggunaan Anggaran Operasional', 'Direksi'], ['staf2', 'Pemberitahuan Pembaruan Aplikasi Internet Banking', 'Seluruh Kantor Cabang'],
            ['stafkredit', 'Usulan Program Kredit Pelajar dan Mahasiswa', 'Direktur Kredit'], ['stafkpn', 'Laporan Tindak Lanjut Temuan Audit Eksternal', 'Kantor Akuntan Publik Sudana & Rekan'],
            ['stafsdm', 'Surat Keputusan Mutasi Pegawai', 'Kantor Cabang Singaraja'], ['stafdps', 'Laporan Penanganan Komplain Nasabah', 'Divisi Kepatuhan'],
            ['staf', 'Permohonan Pengadaan Seragam Pegawai', 'Direktur Utama'], ['staf2', 'Laporan Ketersediaan Layanan Core Banking', 'Direksi'],
        ];
        $klasK = ['UM', 'KU', 'PR', 'KP', 'HK'];
        $tujK = ['DIR', 'UMM', 'KRD', 'KPN', 'SDM', 'CDP', 'TI', 'TRS'];
        $tembK = ['SKP', 'KPT', 'CGN', 'CSR'];
        $statusK = [0, 1, 10, 2, 11, 2, 12, 2];
        foreach ($tambahanKeluar as $k => [$pembuat, $perihal, $tujuan]) {
            $satker = $pembuatSatker[$pembuat];
            $tuj = $tujK[$k % 8] === $satker ? 'DIR' : $tujK[$k % 8];
            $rows[] = [$pembuat, $perihal, $tujuan, $satker, $klasK[$k % 5], $tuj, $tembK[$k % 4], $statusK[$k % 8], $k % 2 ? 'basah' : 'elektronik'];
        }
        $kadivOf = ['UMM' => 'kadiv', 'TI' => 'kadiv', 'KRD' => 'kadivkredit', 'KPN' => 'kadivkpn', 'SDM' => 'kadivsdm', 'CDP' => 'pincadps'];
        $seq = 1;
        foreach ($rows as $i => [$pembuat, $perihal, $tujuan, $satker, $klas, $tuj, $temb, $status, $ttd]) {
            $creator = $u[$pembuat];
            $verif = $u[$kadivOf[$satker]];
            $signer = $i % 3 === 2 ? $u['dirkredit'] : $u['pimpinan'];
            $path = $this->samplePdf("surat-keluar/source/demo-$i.pdf", $perihal, $creator->satker?->nama ?? 'Kabupaten Tabanan');
            $selesai = $status === SuratKeluar::SELESAI;
            $s = SuratKeluar::create([
                'perihal' => $perihal, 'tujuan' => $tujuan, 'ringkasan' => 'Surat contoh: ' . strtolower($perihal) . '.', 'klasifikasi_id' => $kl[$klas] ?? null, 'sifat_id' => $sf[['Biasa', 'Segera', 'Sangat Segera'][$i % 3]] ?? null,
                'klasifikasi_keamanan_id' => $km[$klas === 'HK' ? 'T' : 'B'] ?? null, 'satker_id' => $sk[$satker], 'metode_verifikasi' => $i % 2 ? 'bersamaan' : 'berjenjang', 'metode_ttd' => $ttd,
                'status' => $status, 'urut_alur' => in_array($status, [1, 10]) ? 1 : null, 'revisi' => in_array($status, [11, 12]) ? 1 : 0, 'created_by' => $creator->id,
                'file_path' => $path, 'file_name' => str($perihal)->slug() . '.pdf', 'tanggal_surat' => $selesai ? now()->subDays(10 - $i % 7) : null,
                'nomor_surat' => $selesai ? sprintf('%s/%04d/%s/TBN/%s/%s', $klas === 'HK' ? 'T' : 'B', $seq++, $klas, $this->romawi((int) now()->month), now()->year) : null,
                'signed_path' => $selesai ? $path : null, 'signed_at' => $selesai ? now()->subDays(9 - $i % 7) : null, 'terkirim_at' => $selesai ? now()->subDays(9 - $i % 7) : null,
            ]);
            $s->tujuans()->create(['satker_id' => $sk[$tuj], 'jenis' => 'tujuan']);
            $s->tujuans()->create(['satker_id' => $sk[$temb], 'jenis' => 'tembusan']);
            $verifStatus = match (true) { $status === 1 => 0, $status === 11 => 2, default => $status === 0 ? 0 : 1 };
            $s->alurs()->create(['user_id' => $verif->id, 'peran' => 'verifikator', 'urut' => 1, 'status' => $verifStatus, 'acted_at' => $verifStatus ? now()->subDays(2) : null, 'catatan' => $status === 11 ? 'Mohon perbaiki lampiran dan dasar hukum.' : null]);
            $signStatus = $selesai ? 1 : ($status === 12 ? 2 : 0);
            $s->alurs()->create(['user_id' => $signer->id, 'peran' => 'penandatangan', 'urut' => 1, 'status' => $signStatus, 'status_sekpri' => $status === 10 && $signer->id === $u['pimpinan']->id && $i % 2 ? 1 : 2, 'acted_at' => $signStatus ? now()->subDay() : null, 'catatan' => $status === 12 ? 'Redaksi paragraf kedua perlu diperjelas.' : null]);
            $s->riwayats()->create(['user_id' => $creator->id, 'aksi' => 'dibuat', 'revisi' => 0]);
            if ($status !== 0) {
                $s->riwayats()->create(['user_id' => $creator->id, 'aksi' => 'diajukan', 'revisi' => 0]);
            }
            if (in_array($status, [10, 12, 2])) {
                $s->riwayats()->create(['user_id' => $verif->id, 'aksi' => 'disetujui', 'revisi' => 0]);
            }
            if (in_array($status, [11, 12])) {
                $s->riwayats()->create(['user_id' => $status === 11 ? $verif->id : $signer->id, 'aksi' => 'revisi', 'catatan' => $status === 11 ? 'Mohon perbaiki lampiran dan dasar hukum.' : 'Redaksi paragraf kedua perlu diperjelas.', 'revisi' => 0]);
            }
            if ($selesai) {
                $s->riwayats()->create(['user_id' => $signer->id, 'aksi' => 'ditandatangani', 'revisi' => 0]);
                $s->riwayats()->create(['user_id' => null, 'aksi' => 'terkirim', 'revisi' => 0]);
                EsignLog::create(['surat_keluar_id' => $s->id, 'user_id' => $signer->id, 'status' => 'berhasil', 'dokumen' => $s->nomor_surat, 'keterangan' => $ttd === 'basah' ? 'Scan tanda tangan basah diunggah (penandatangan terakhir)' : 'TTE dibubuhkan (penandatangan terakhir)', 'ip' => '127.0.0.1']);
                SuratMasuk::create(['nomor_agenda' => sprintf('DM-%s-I%03d', now()->year, $i), 'nomor_surat' => $s->nomor_surat, 'tanggal_surat' => $s->tanggal_surat, 'tanggal_terima' => $s->signed_at, 'pengirim' => $creator->satker?->nama,
                    'perihal' => $perihal, 'ringkasan' => $s->ringkasan, 'sifat_id' => $s->sifat_id, 'klasifikasi_id' => $s->klasifikasi_id, 'file_path' => $path, 'file_name' => $s->file_name, 'status' => 'baru', 'jenis' => 'internal',
                    'satker_id' => $sk[$tuj], 'surat_keluar_id' => $s->id, 'created_by' => $creator->id]);
            }
        }
        foreach ([['Surat Pemberitahuan Libur Bersama (Arsip)', 'Seluruh Pegawai', 'SDM'], ['Kerja Sama Payment Point PLN (Arsip)', 'PT PLN (Persero) UID Bali', 'UMM']] as $j => [$perihal, $tujuan, $satker]) {
            $path = $this->samplePdf("surat-keluar/source/arsip-$j.pdf", $perihal, 'Kabupaten Tabanan');
            SuratKeluar::create(['perihal' => $perihal, 'tujuan' => $tujuan, 'satker_id' => $sk[$satker], 'metode_verifikasi' => 'bersamaan', 'metode_ttd' => 'basah', 'status' => SuratKeluar::SELESAI, 'is_arsip' => true, 'revisi' => 0,
                'created_by' => $u['operator']->id, 'file_path' => $path, 'file_name' => str($perihal)->slug() . '.pdf', 'signed_path' => $path, 'nomor_surat' => sprintf('B/%04d/UM/TBN/%s/%s', 900 + $j, $this->romawi(1), now()->year - 1), 'tanggal_surat' => now()->subYear()->subDays($j * 30)]);
        }
    }

    private function pelengkap(): void
    {
        $u = $this->u;
        $notif = [
            ['pimpinan', 'Surat perlu ditandatangani', 'Usulan Restrukturisasi Kredit Debitur UMKM', '/surat-keluar/tandatangan'],
            ['pimpinan', 'Disposisi ditindaklanjuti', 'Permintaan Laporan Profil Risiko Semester I', '/surat-masuk/disposisi'],
            ['kadiv', 'Disposisi baru', 'Undangan Sosialisasi Kebijakan Makroprudensial', '/surat-masuk/disposisi'],
            ['kadiv', 'Surat perlu diverifikasi', 'Permohonan Penambahan Lisensi Perangkat Lunak', '/surat-keluar/verifikasi'],
            ['staf', 'Disposisi baru', 'Jadwal Audit Laporan Keuangan Tahunan', '/surat-masuk/disposisi'],
            ['staf', 'Surat dikembalikan penandatangan', 'Nota Dinas Penghematan Energi Kantor', '/surat-keluar?tab=konsep'],
            ['staf2', 'Surat telah ditandatangani', 'Jadwal Pemeliharaan Sistem Core Banking', '/surat-keluar?tab=selesai'],
            ['sekpri', 'Surat untuk pimpinan menunggu diteruskan', 'Undangan Pelatihan Pelayanan Prima', '/surat-keluar'],
            ['operator', 'Agenda baru', 'Town Hall Meeting Seluruh Pegawai', '/agenda'],
            ['kadivkredit', 'Surat perlu diverifikasi', 'Rekap Penyaluran KUR Bulanan', '/surat-keluar/verifikasi'],
            ['stafkredit', 'Disposisi baru', 'Undangan Rapat Penyaluran KUR Daerah', '/surat-masuk/disposisi'],
            ['dirkredit', 'Surat perlu ditandatangani', 'Usulan Restrukturisasi Kredit Debitur UMKM', '/surat-keluar/tandatangan'],
        ];
        foreach ($notif as $i => [$un, $judul, $pesan, $url]) {
            AppNotification::create(['user_id' => $u[$un]->id, 'judul' => $judul, 'pesan' => $pesan, 'url' => $url, 'read_at' => $i % 3 === 0 ? now()->subHours(2) : null]);
        }
        $logs = [
            ['operator', 'surat_masuk.tambah', 'Mencatat surat masuk: Undangan Sosialisasi Kebijakan Makroprudensial'],
            ['operator', 'surat_masuk.teruskan', 'Meneruskan surat ke Direksi: Permintaan Laporan Profil Risiko Semester I'],
            ['pimpinan', 'disposisi.kirim', 'Disposisi surat: Undangan Rapat Penyaluran KUR Daerah'],
            ['kadiv', 'surat_keluar.verifikasi', 'Laporan Kepatuhan APU-PPT Semester I'],
            ['pimpinan', 'tte.tandatangan', 'Menandatangani surat: Pengumuman Rekrutmen Pegawai Tahun Ini'],
            ['staf', 'surat_keluar.tambah', 'Membuat konsep surat: Laporan Realisasi Anggaran Triwulan II'],
            ['sekpri', 'agenda.tambah', 'Menambah agenda: Rapat Evaluasi Kinerja Bulanan Direksi'],
            ['admin', 'pengguna.tambah', 'Menambah pengguna: Gede Yoga Pramana'],
            ['staf2', 'auth.login', 'Login berhasil'],
            ['kadivkredit', 'disposisi.kirim', 'Disposisi surat: Permohonan Tempat Magang Mahasiswa'],
        ];
        foreach ($logs as $i => [$un, $aksi, $desk]) {
            $log = ActivityLog::create(['user_id' => $u[$un]->id, 'aksi' => $aksi, 'deskripsi' => $desk, 'ip' => '10.10.1.' . (20 + $i), 'user_agent' => 'Mozilla/5.0 (Demo)']);
            $log->forceFill(['created_at' => now()->subHours($i * 5)])->save();
        }
        TemplateTujuanDisposisi::firstOrCreate(['user_id' => $u['pimpinan']->id, 'nama' => 'Para Kepala Divisi'], ['penerima' => collect(['kadiv', 'kadivkredit', 'kadivkpn', 'kadivsdm', 'kadivtrs'])->map(fn ($x) => $u[$x]->id)->all()]);
        TemplateTujuanDisposisi::firstOrCreate(['user_id' => $u['pimpinan']->id, 'nama' => 'Direksi & Sekpri'], ['penerima' => [$u['dirkredit']->id, $u['sekpri']->id]]);
        TemplateTujuanSurat::firstOrCreate(['user_id' => $u['staf']->id, 'nama' => 'Ke Direksi'], ['tujuan' => [Satker::where('kode', 'DIR')->value('id')], 'tembusan' => [Satker::where('kode', 'SKP')->value('id')]]);
        foreach ([['10001', 'I Gusti Ngurah Agung', 'Direktur Utama'], ['10003', 'I Wayan Sudarma', 'Kepala Divisi Umum'], ['10004', 'Putu Gede Arimbawa', 'Staf Divisi Umum'], ['10008', 'Gede Yoga Pramana', 'Analis Kredit'], ['10013', 'Wayan Eka Saputra', 'Pemimpin Cabang Denpasar'], ['10014', 'Komang Tri Handayani', 'Staf Operasional Cabang']] as [$nrk, $nama, $jab]) {
            Sdm::firstOrCreate(['nrk' => $nrk], ['nama' => $nama, 'unit' => 'Kantor Pusat', 'satuan_kerja' => $jab, 'jabatan' => $jab, 'email' => strtolower(str_replace(' ', '.', $nama)) . '@tabanankab.example', 'status_cuti' => false]);
        }
        $year = now()->year;
        foreach (["$year-03-29" => 'Hari Raya Nyepi', "$year-04-18" => 'Wafat Isa Almasih', "$year-06-01" => 'Hari Lahir Pancasila', "$year-09-17" => 'Hari Raya Galungan', "$year-09-27" => 'Hari Raya Kuningan', "$year-12-26" => 'Cuti Bersama Natal'] as $tgl => $ket) {
            HariLibur::firstOrCreate(['tanggal' => $tgl], ['keterangan' => $ket]);
        }
    }

    private function romawi(int $n): string
    {
        return ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$n] ?? 'I';
    }

    private function samplePdf(string $path, string $title, string $from): string
    {
        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $logo = public_path('images/logo.png');
        if (is_file($logo)) {
            $pdf->Image($logo, 15, 10, 18);
        }
        $pdf->SetFont('Helvetica', 'B', 15);
        $pdf->Cell(0, 8, 'PT BANK PEMBANGUNAN DAERAH BALI', 0, 1, 'C');
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, 'Jl. Raya Puputan No. 1, Renon, Denpasar - Telp. (0361) 223301', 0, 1, 'C');
        $pdf->Line(15, 32, 195, 32);
        $pdf->Ln(14);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->MultiCell(0, 7, 'Perihal: ' . $title);
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->Cell(0, 7, 'Dari: ' . $from, 0, 1);
        $pdf->Ln(6);
        $pdf->MultiCell(0, 7, 'Dengan hormat, bersama surat ini kami sampaikan perihal ' . $title . ' untuk dapat ditindaklanjuti sesuai ketentuan yang berlaku. Mohon kiranya Bapak/Ibu dapat memberikan arahan dan tanggapan dalam waktu yang telah ditentukan.');
        $pdf->Ln(4);
        $pdf->MultiCell(0, 7, 'Demikian kami sampaikan, atas perhatian dan kerja samanya kami ucapkan terima kasih. (Dokumen contoh - data dummy.)');
        Storage::disk('local')->put($path, $pdf->Output('S'));
        return $path;
    }
}

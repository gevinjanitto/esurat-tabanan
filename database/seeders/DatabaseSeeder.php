<?php

namespace Database\Seeders;

use App\Models\Disposisi;
use App\Models\Jabatan;
use App\Models\Klasifikasi;
use App\Models\KlasifikasiKeamanan;
use App\Models\Satker;
use App\Models\Sifat;
use App\Models\SubjekDisposisi;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('username', 'admin')->exists()) {
            $this->restoreSampleFiles();
            $this->call([Tahap2Seeder::class, Tahap3Seeder::class, DemoSeeder::class]);
            $this->command?->info('Data awal sudah ada, seeder dilewati.');
            return;
        }
        $password = config('app.seed_password');

        $pusat = Satker::create(['kode' => 'DIR', 'nama' => 'Direksi', 'kategori' => 'direksi']);
        $sekper = Satker::create(['kode' => 'SKP', 'nama' => 'Divisi Sekretariat Perusahaan', 'parent_id' => $pusat->id]);
        $umum = Satker::create(['kode' => 'UMM', 'nama' => 'Divisi Umum', 'parent_id' => $pusat->id]);
        $ti = Satker::create(['kode' => 'TI', 'nama' => 'Divisi Teknologi Informasi', 'parent_id' => $pusat->id]);

        $jab = fn ($nama, $satker, $p = false) => Jabatan::create(['nama' => $nama, 'satker_id' => $satker->id, 'is_pimpinan' => $p]);
        $jDirut = $jab('Direktur Utama', $pusat, true);
        $jSekpri = $jab('Sekretaris Direksi', $sekper);
        $jKadiv = $jab('Kepala Divisi Umum', $umum, true);
        $jStaf = $jab('Staf Divisi Umum', $umum);
        $jStafTi = $jab('Staf Teknologi Informasi', $ti);
        $jOp = $jab('Operator Persuratan', $sekper);

        $mk = fn ($name, $username, $role, $satker, $jabatan, $nip, $da = false) => User::create(['name' => $name, 'username' => $username, 'role' => $role, 'nip' => $nip, 'password' => $password, 'satker_id' => $satker?->id, 'jabatan_id' => $jabatan?->id, 'is_active' => true, 'disposisi_awal' => $da]);
        $mk('Administrator Sistem', 'admin', 'admin', $ti, null, null)->update(['email' => 'ajikganteng758@gmail.com']);
        $op = $mk('Ni Made Ayu Lestari', 'operator', 'operator', $sekper, $jOp, '198901012015032001');
        $dirut = $mk('I Gusti Ngurah Agung', 'pimpinan', 'pimpinan', $pusat, $jDirut, '197003112000121001', true);
        $sekpri = $mk('Kadek Dwi Pratiwi', 'sekpri', 'sekpri', $sekper, $jSekpri, '199204072018012002', true);
        $sekpri->update(['atasan_id' => $dirut->id]);
        $kadiv = $mk('I Wayan Sudarma', 'kadiv', 'pimpinan', $umum, $jKadiv, '197805222005011003', true);
        $staf = $mk('Putu Gede Arimbawa', 'staf', 'user', $umum, $jStaf, '199506152020121004');
        $mk('Komang Indah Sari', 'staf2', 'user', $ti, $jStafTi, '199703302021012005');

        foreach ([['UM', 'Umum'], ['KP', 'Kepegawaian'], ['KU', 'Keuangan'], ['PR', 'Perlengkapan'], ['HK', 'Hukum']] as [$k, $n]) {
            Klasifikasi::create(['kode' => $k, 'nama' => $n]);
        }
        foreach ([['B', 'Biasa'], ['T', 'Terbatas'], ['R', 'Rahasia']] as [$k, $n]) {
            KlasifikasiKeamanan::create(['kode' => $k, 'nama' => $n]);
        }
        foreach (['Mohon arahan', 'Untuk diketahui', 'Untuk ditindaklanjuti', 'Untuk dipelajari', 'Siapkan jawaban', 'Hadiri / wakili', 'Koordinasikan', 'Arsipkan'] as $n) {
            SubjekDisposisi::create(['nama' => $n]);
        }
        $biasa = Sifat::create(['nama' => 'Biasa', 'warna' => 'ruby']);
        $segera = Sifat::create(['nama' => 'Segera', 'warna' => 'amber']);
        Sifat::create(['nama' => 'Sangat Segera', 'warna' => 'rose']);

        $masuk = SuratMasuk::create([
            'nomor_agenda' => 'AG-' . now()->year . '-0001', 'nomor_surat' => '045/OJK/' . now()->year, 'tanggal_surat' => now()->subDays(3),
            'tanggal_terima' => now()->subDays(2), 'pengirim' => 'Otoritas Jasa Keuangan Regional 8', 'perihal' => 'Undangan Rapat Koordinasi Triwulan',
            'ringkasan' => 'Undangan rapat koordinasi evaluasi kinerja triwulan.', 'sifat_id' => $segera->id, 'klasifikasi_id' => Klasifikasi::where('kode', 'UM')->value('id'),
            'file_path' => $this->samplePdf('surat-masuk/contoh-undangan.pdf', 'Undangan Rapat Koordinasi Triwulan', 'Otoritas Jasa Keuangan Regional 8'),
            'file_name' => 'undangan-rapat.pdf', 'status' => 'baru', 'created_by' => $op->id, 'satker_id' => $sekper->id,
        ]);
        $masuk->teruskans()->create(['tipe' => 'direksi', 'satker_id' => $pusat->id, 'user_id' => $dirut->id, 'dari_user_id' => $op->id]);

        $keluar = SuratKeluar::create([
            'perihal' => 'Permohonan Pengadaan Perangkat Kerja', 'tujuan' => 'Kepala Divisi Teknologi Informasi', 'ringkasan' => 'Pengajuan pengadaan laptop untuk staf baru.',
            'klasifikasi_id' => Klasifikasi::where('kode', 'PR')->value('id'), 'sifat_id' => $biasa->id, 'klasifikasi_keamanan_id' => KlasifikasiKeamanan::where('kode', 'B')->value('id'), 'satker_id' => $umum->id, 'metode_verifikasi' => 'berjenjang',
            'status' => 1, 'urut_alur' => 1, 'created_by' => $staf->id,
            'file_path' => $this->samplePdf('surat-keluar/source/contoh-pengadaan.pdf', 'Permohonan Pengadaan Perangkat Kerja', 'Divisi Umum'),
            'file_name' => 'permohonan-pengadaan.pdf',
        ]);
        $keluar->tujuans()->create(['satker_id' => $ti->id, 'jenis' => 'tujuan']);
        $keluar->alurs()->create(['user_id' => $kadiv->id, 'peran' => 'verifikator', 'urut' => 1]);
        $keluar->alurs()->create(['user_id' => $dirut->id, 'peran' => 'penandatangan', 'urut' => 1, 'status_sekpri' => 1]);
        $keluar->riwayats()->create(['user_id' => $staf->id, 'aksi' => 'diajukan']);
        $this->call([Tahap2Seeder::class, Tahap3Seeder::class, DemoSeeder::class]);
    }

    private function restoreSampleFiles(): void
    {
        $samples = [
            ['surat-masuk/contoh-undangan.pdf', 'Undangan Rapat Koordinasi Triwulan', 'Otoritas Jasa Keuangan Regional 8'],
            ['surat-keluar/source/contoh-pengadaan.pdf', 'Permohonan Pengadaan Perangkat Kerja', 'Divisi Umum'],
        ];
        foreach ($samples as [$path, $title, $from]) {
            if (!Storage::disk('local')->exists($path)) {
                $this->samplePdf($path, $title, $from);
            }
        }
    }

    private function samplePdf(string $path, string $title, string $from): string
    {
        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'B', 15);
        $pdf->Cell(0, 10, 'KABUPATEN TABANAN', 0, 1, 'C');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 6, '[Alamat kantor - ganti dengan alamat asli]', 0, 1, 'C');
        $pdf->Line(15, 32, 195, 32);
        $pdf->Ln(12);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Perihal: ' . $title, 0, 1);
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->Cell(0, 7, 'Dari: ' . $from, 0, 1);
        $pdf->Ln(6);
        $pdf->MultiCell(0, 7, str_repeat('Dengan hormat, bersama surat ini kami sampaikan hal sebagaimana perihal di atas untuk dapat ditindaklanjuti sesuai ketentuan yang berlaku. ', 4));
        $pdf->Ln(4);
        $pdf->MultiCell(0, 7, 'Demikian kami sampaikan, atas perhatian dan kerja samanya kami ucapkan terima kasih. (Dokumen contoh - silakan ganti dengan surat asli.)');
        Storage::disk('local')->put($path, $pdf->Output('S'));
        return $path;
    }
}

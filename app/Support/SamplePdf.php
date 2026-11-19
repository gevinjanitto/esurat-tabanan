<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

// Membuat dokumen surat contoh yang lengkap (kop, nomor, tujuan, isi, penutup, tanda tangan).
class SamplePdf
{
    public static function make(string $path, array $d): string
    {
        $t = fn ($s) => iconv('UTF-8', 'windows-1252//TRANSLIT', (string) $s);
        $perihal = $d['perihal'] ?? 'Pemberitahuan';
        $dari = $d['dari'] ?? 'Pemerintah Kabupaten Tabanan';
        $kepada = $d['kepada'] ?? 'Bapak/Ibu Pimpinan Perangkat Daerah';
        $nomor = $d['nomor'] ?? ('000.1/' . random_int(100, 999) . '/SETDA/' . now()->year);
        $tanggal = $d['tanggal'] ?? now();
        $sifat = $d['sifat'] ?? 'Biasa';
        $ringkasan = trim((string) ($d['ringkasan'] ?? ''));
        $penandatangan = $d['penandatangan'] ?? 'Sekretaris Daerah Kabupaten Tabanan';
        $nama = $d['nama'] ?? 'I Gede Susila, S.H., M.Si.';
        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $tgl = $tanggal->format('j') . ' ' . $bulan[(int) $tanggal->format('n')] . ' ' . $tanggal->format('Y');

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(22, 15, 20);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();
        $logo = public_path('images/logo.png');
        if (is_file($logo)) {
            $pdf->Image($logo, 22, 12, 22);
        }
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->Cell(0, 7, 'PEMERINTAH KABUPATEN TABANAN', 0, 1, 'C');
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 6, $t(strtoupper($d['instansi'] ?? 'SEKRETARIAT DAERAH')), 0, 1, 'C');
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, 'Jalan Pahlawan No. 19 Tabanan, Bali 82113 - Telepon (0361) 811604', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Laman: tabanankab.go.id - Pos-el: setda@tabanankab.go.id', 0, 1, 'C');
        $pdf->SetLineWidth(0.8);
        $pdf->Line(22, 40, 190, 40);
        $pdf->SetLineWidth(0.2);
        $pdf->Line(22, 41.2, 190, 41.2);
        $pdf->SetY(47);

        $pdf->SetFont('Helvetica', '', 11);
        $pdf->Cell(0, 6, $t('Tabanan, ' . $tgl), 0, 1, 'R');
        $pdf->Ln(2);
        foreach ([['Nomor', $nomor], ['Sifat', $sifat], ['Lampiran', $d['lampiran'] ?? '1 (satu) berkas'], ['Hal', $perihal]] as [$k, $v]) {
            $pdf->Cell(26, 6, $k, 0, 0);
            $pdf->Cell(4, 6, ':', 0, 0);
            $pdf->SetFont('Helvetica', $k === 'Hal' ? 'B' : '', 11);
            $pdf->MultiCell(0, 6, $t($v));
            $pdf->SetFont('Helvetica', '', 11);
        }
        $pdf->Ln(5);
        $pdf->Cell(0, 6, 'Yth.', 0, 1);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->MultiCell(90, 6, $t($kepada));
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->Cell(0, 6, 'di -', 0, 1);
        $pdf->Cell(10);
        $pdf->Cell(0, 6, 'Tempat', 0, 1);
        $pdf->Ln(5);

        $paras = [
            'Dengan hormat, menindaklanjuti program kerja Pemerintah Kabupaten Tabanan Tahun Anggaran ' . $tanggal->format('Y') . ' serta dalam rangka meningkatkan kualitas pelayanan publik dan tertib administrasi pemerintahan, bersama ini kami sampaikan hal-hal terkait ' . $perihal . '.',
            $ringkasan !== '' ? $ringkasan : 'Sehubungan dengan hal tersebut, kami mohon Bapak/Ibu berkenan menindaklanjuti surat ini sesuai dengan tugas pokok dan fungsi masing-masing, serta melakukan koordinasi dengan unit kerja terkait agar pelaksanaannya dapat berjalan efektif, efisien, dan tepat waktu.',
            'Adapun hal-hal yang perlu mendapat perhatian adalah sebagai berikut:',
        ];
        foreach ($paras as $p) {
            $pdf->MultiCell(0, 6.2, $t($p), 0, 'J');
            $pdf->Ln(2.5);
        }
        $poin = $d['poin'] ?? [
            'Menyiapkan data dan dokumen pendukung yang diperlukan paling lambat 7 (tujuh) hari kerja sejak surat ini diterima;',
            'Menunjuk 1 (satu) orang pejabat penghubung (contact person) beserta nomor telepon yang dapat dihubungi;',
            'Menyampaikan laporan pelaksanaan kegiatan secara tertulis kepada Bupati Tabanan melalui Sekretaris Daerah;',
            'Memastikan seluruh kegiatan dilaksanakan sesuai ketentuan peraturan perundang-undangan yang berlaku.',
        ];
        foreach ($poin as $i => $p) {
            $pdf->Cell(8, 6.2, ($i + 1) . '.', 0, 0);
            $pdf->MultiCell(0, 6.2, $t($p), 0, 'J');
        }
        $pdf->Ln(3);
        $pdf->MultiCell(0, 6.2, $t('Demikian disampaikan, atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.'), 0, 'J');
        $pdf->Ln(8);

        $x = 112;
        if ($pdf->GetY() > 225) {
            $pdf->AddPage();
        }
        $pdf->SetX($x);
        $pdf->MultiCell(78, 6, $t('a.n. Bupati Tabanan' . "\n" . $penandatangan . ','), 0, 'C');
        $pdf->Ln(22);
        $pdf->SetX($x);
        $pdf->SetFont('Helvetica', 'BU', 11);
        $pdf->MultiCell(78, 6, $t($nama), 0, 'C');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetX($x);
        $pdf->Cell(78, 5, $t($d['nip'] ?? 'NIP. 19720315 199803 1 007'), 0, 1, 'C');

        $pdf->Ln(8);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, 'Tembusan disampaikan kepada Yth.:', 0, 1);
        foreach (['Bupati Tabanan (sebagai laporan);', 'Inspektur Kabupaten Tabanan;', $t('Arsip.')] as $i => $tb) {
            $pdf->Cell(0, 5, ($i + 1) . '. ' . $tb, 0, 1);
        }
        $pdf->SetY(-14);
        $pdf->SetFont('Helvetica', 'I', 7.5);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(0, 5, $t('Dokumen ini dikelola melalui E-Surat Kabupaten Tabanan. Pengirim: ' . $dari), 0, 0, 'C');

        Storage::disk('local')->put($path, $pdf->Output('S'));
        return $path;
    }
}

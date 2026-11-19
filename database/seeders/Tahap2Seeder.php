<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\AlurSurat;
use App\Models\NomorInisiasi;
use App\Models\Satker;
use App\Models\SuratMasuk;
use App\Models\TemplateNomor;
use App\Models\TemplateSurat;
use App\Models\TemplateTujuanSurat;
use App\Models\UnitPemrakarsa;
use App\Models\User;
use Illuminate\Database\Seeder;

class Tahap2Seeder extends Seeder
{
    public function run(): void
    {
        if (TemplateSurat::exists()) {
            return;
        }
        $kop = '<h2 class="text-center"><b>PT BANK PEMBANGUNAN DAERAH BALI</b></h2><p class="text-center">Jl. Raya Puputan No. 1, Renon, Denpasar · Telp. (0361) 223301</p>';
        $nota = TemplateSurat::create([
            'nama' => 'Nota Dinas', 'deskripsi' => 'Nota dinas internal antar divisi.', 'counter_penomoran' => 'satker_uker', 'status' => true, 'kop' => $kop,
            'isi' => '<p style="text-align:center"><b><u>NOTA DINAS</u></b><br>Nomor: ${SYS_NO_SURAT}</p><p>&nbsp;</p>'
                . '<table><tr><td>Kepada</td><td>: ${SYS_TUJUAN}</td></tr><tr><td>Dari</td><td>: ${NAMA_PENGIRIM}</td></tr><tr><td>Sifat</td><td>: ${SYS_SIFAT}</td></tr><tr><td>Lampiran</td><td>: ${SYS_LAMPIRAN}</td></tr><tr><td>Perihal</td><td>: <b>${SYS_PERIHAL}</b></td></tr></table>'
                . '<p>&nbsp;</p><p style="text-align:justify">${ISI_NOTA}</p><p>&nbsp;</p><p>Demikian disampaikan, atas perhatiannya diucapkan terima kasih.</p>'
                . '<p>&nbsp;</p><p style="text-align:right">Denpasar, ${SYS_TGL_SURAT}<br>${JABATAN_PENANDATANGAN}</p>',
        ]);
        foreach ([['Nama pengirim (unit)', 'NAMA_PENGIRIM', 'teks'], ['Isi nota dinas', 'ISI_NOTA', 'textarea'], ['Jabatan penandatangan', 'JABATAN_PENANDATANGAN', 'teks']] as $i => [$n, $k, $j]) {
            $nota->isians()->create(['nama' => $n, 'kode' => $k, 'jenis' => $j, 'urut' => $i + 1]);
        }
        $undangan = TemplateSurat::create([
            'nama' => 'Surat Undangan', 'deskripsi' => 'Undangan rapat/kegiatan.', 'counter_penomoran' => 'satker_uker', 'status' => true, 'kop' => $kop,
            'isi' => '<p>Nomor: ${SYS_NO_SURAT}<br>Perihal: <b>${SYS_PERIHAL}</b></p><p>&nbsp;</p><p>Kepada Yth.<br>${SYS_TUJUAN}</p><p>&nbsp;</p>'
                . '<p style="text-align:justify">Dengan hormat, kami mengundang Bapak/Ibu untuk hadir pada:</p>'
                . '<table><tr><td>Hari/Tanggal</td><td>: ${TANGGAL_ACARA}</td></tr><tr><td>Waktu</td><td>: ${WAKTU_ACARA}</td></tr><tr><td>Tempat</td><td>: ${TEMPAT_ACARA}</td></tr></table>'
                . '<p>&nbsp;</p><p>Atas kehadirannya kami ucapkan terima kasih.</p><p style="text-align:right">Denpasar, ${SYS_TGL_SURAT}</p>',
        ]);
        foreach ([['Tanggal acara', 'TANGGAL_ACARA', 'tanggal'], ['Waktu acara', 'WAKTU_ACARA', 'teks'], ['Tempat acara', 'TEMPAT_ACARA', 'teks']] as $i => [$n, $k, $j]) {
            $undangan->isians()->create(['nama' => $n, 'kode' => $k, 'jenis' => $j, 'urut' => $i + 1]);
        }

        $umum = Satker::where('kode', 'UMM')->first();
        $op = User::where('username', 'operator')->first();
        UnitPemrakarsa::create(['kode' => 'HUMAS', 'nama' => 'Hubungan Masyarakat', 'satker_id' => Satker::where('kode', 'SKP')->value('id'), 'status' => true]);
        UnitPemrakarsa::create(['kode' => 'LOG', 'nama' => 'Logistik', 'satker_id' => $umum?->id, 'status' => true]);
        TemplateNomor::create(['nama' => 'Format umum', 'segments' => [['type' => 'klasifikasi_keamanan'], ['type' => 'nomor'], ['type' => 'klasifikasi'], ['type' => 'unit_kerja', 'value' => 'TBN'], ['type' => 'bulan'], ['type' => 'tahun']], 'padding' => 4, 'nomor_tambahan' => 'angka', 'created_by' => $op?->id]);
        foreach (Satker::all() as $s) {
            NomorInisiasi::create(['satker_id' => $s->id, 'tahun' => now()->year, 'nomor_awal' => 1]);
        }

        $staf = User::where('username', 'staf')->first();
        $kadiv = User::where('username', 'kadiv')->first();
        $dirut = User::where('username', 'pimpinan')->first();
        if ($staf && $kadiv && $dirut) {
            AlurSurat::create(['user_id' => $staf->id, 'judul' => 'Ke Direksi via Kadiv', 'metode_verifikasi' => 'berjenjang', 'verifikator' => [$kadiv->id], 'penandatangan' => [$dirut->id]]);
            TemplateTujuanSurat::create(['user_id' => $staf->id, 'nama' => 'Divisi pendukung', 'tujuan' => Satker::whereIn('kode', ['TI'])->pluck('id')->all(), 'tembusan' => Satker::whereIn('kode', ['SKP'])->pluck('id')->all()]);
        }
        $masuk = SuratMasuk::first();
        if ($masuk && $op) {
            Agenda::create(['surat_masuk_id' => $masuk->id, 'user_id' => $op->id, 'judul' => 'Rapat Koordinasi Triwulan OJK', 'metode' => 'hybrid', 'tempat' => 'Kantor OJK Regional 8, Denpasar', 'link_meeting' => 'https://meet.example.com/rakor-triwulan', 'mulai' => now()->addDays(3)->setTime(9, 0), 'selesai' => now()->addDays(3)->setTime(12, 0), 'deskripsi' => 'Membawa laporan kinerja triwulan.']);
        }
    }
}

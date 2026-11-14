<?php

namespace Tests\Feature;

use App\Mail\NotifikasiSurat;
use App\Models\Agenda;
use App\Models\Disposisi;
use App\Models\EsignLog;
use App\Models\NomorInisiasi;
use App\Models\Satker;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\TemplateNomor;
use App\Models\TemplateSurat;
use App\Models\User;
use App\Models\UserTag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Tahap2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed();
    }

    private function u(string $username): User
    {
        return User::where('username', $username)->firstOrFail();
    }

    private function pdf(): UploadedFile
    {
        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, 'Arsip');
        return UploadedFile::fake()->createWithContent('arsip.pdf', $pdf->Output('S'));
    }

    public function test_menu_dan_halaman_tahap2_per_role(): void
    {
        $pages = [
            'admin' => ['/master/surat/template', '/master/surat/template/create', '/master/unit-pemrakarsa', '/master/nomor-inisiasi', '/master/surat/template-tujuan-surat', '/log-esign', '/master/satker/' . Satker::value('id') . '/jabatan'],
            'operator' => ['/surat-keluar/daftar-nomor', '/agenda', '/agenda/create', '/master/surat/template-nomor', '/master/surat/template-nomor/create', '/master/nomor-inisiasi', '/master/surat/template-tujuan-surat'],
            'staf' => ['/surat-keluar/daftar-nomor', '/agenda', '/master/surat/alur', '/master/surat/template-tujuan-surat', '/surat-keluar/arsip/create', '/surat-keluar/create'],
            'sekpri' => ['/agenda', '/surat-keluar/daftar-nomor'],
            'pimpinan' => ['/surat-keluar/daftar-nomor'],
        ];
        foreach ($pages as $username => $urls) {
            foreach ($urls as $url) {
                $this->actingAs($this->u($username))->get($url)->assertOk();
            }
        }
        $this->actingAs($this->u('admin'))->get('/')->assertSee('Template Surat')->assertSee('Unit Pemrakarsa')->assertSee('Catatan Aktivitas Esign')->assertDontSee('Template Nomor Surat');
        $this->actingAs($this->u('operator'))->get('/')->assertSee('Template Nomor Surat')->assertSee('Nomor Inisiasi')->assertSee('Daftar Nomor')->assertSee('Agenda');
        $this->actingAs($this->u('staf'))->get('/')->assertSee('Alur Surat')->assertSee('Template Tujuan Surat')->assertDontSee('Nomor Inisiasi');
        $this->actingAs($this->u('pimpinan'))->get('/')->assertSee('href="' . route('agenda.index') . '"', false);
        $this->actingAs($this->u('operator'))->get('/master/surat/template')->assertForbidden();
        $this->actingAs($this->u('operator'))->get('/master/unit-pemrakarsa')->assertForbidden();
        $this->actingAs($this->u('admin'))->get('/master/surat/template-nomor')->assertForbidden();
        $this->actingAs($this->u('pimpinan'))->get('/agenda')->assertOk();
        $this->actingAs($this->u('staf'))->get('/log-esign')->assertForbidden();
    }

    public function test_template_surat_crud_dan_validasi_kode(): void
    {
        $admin = $this->u('admin');
        $this->actingAs($admin)->post('/master/surat/template', ['nama' => 'X', 'counter_penomoran' => 'satker_uker', 'isi' => '<p>a</p>', 'isian' => [['nama' => 'A', 'kode' => 'SYS_A', 'jenis' => 'teks']]])->assertSessionHasErrors('isian.0.kode');
        $this->actingAs($admin)->post('/master/surat/template', ['nama' => 'Memo', 'counter_penomoran' => 'tanpa_nomor', 'status' => 1, 'kop' => '<b>KOP</b>', 'isi' => '<p>Halo ${NAMA}</p>', 'isian' => [['nama' => 'Nama', 'kode' => 'nama', 'jenis' => 'teks']]])->assertSessionHasNoErrors();
        $t = TemplateSurat::where('nama', 'Memo')->firstOrFail();
        $this->assertSame('NAMA', $t->isians()->first()->kode);
        $this->actingAs($admin)->put("/master/surat/template/{$t->id}", ['nama' => 'Memo Internal', 'counter_penomoran' => 'tanpa_nomor', 'isi' => '<p>x</p>', 'isian' => []])->assertSessionHasNoErrors();
        $this->assertSame(0, $t->isians()->count());
        $this->assertFalse($t->fresh()->status);
        $this->actingAs($admin)->delete("/master/surat/template/{$t->id}")->assertSessionHas('success');
    }

    public function test_surat_dari_template_penomoran_otomatis_dan_log_esign(): void
    {
        $staf = $this->u('staf'); $kadiv = $this->u('kadiv'); $dirut = $this->u('pimpinan'); $sekpri = $this->u('sekpri');
        $umum = Satker::where('kode', 'UMM')->first();
        NomorInisiasi::where('satker_id', $umum->id)->update(['nomor_awal' => 25]);
        $nota = TemplateSurat::where('nama', 'Nota Dinas')->firstOrFail();

        $this->actingAs($staf)->post('/surat-keluar/template/preview', ['template_surat_id' => $nota->id, 'perihal' => 'Uji', 'isian' => ['ISI_NOTA' => 'Isi uji']])->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->actingAs($staf)->post('/surat-keluar', [
            'perihal' => 'Nota Pengadaan ATK', 'tujuan' => 'Kepala Divisi TI', 'template_surat_id' => $nota->id, 'klasifikasi_id' => \App\Models\Klasifikasi::where('kode', 'UM')->value('id'), 'klasifikasi_keamanan_id' => \App\Models\KlasifikasiKeamanan::where('kode', 'B')->value('id'), 'satker_id' => $umum->id,
            'isian' => ['NAMA_PENGIRIM' => 'Divisi Umum', 'ISI_NOTA' => 'Mohon pengadaan ATK.', 'JABATAN_PENANDATANGAN' => 'Direktur Utama'],
            'metode_verifikasi' => 'bersamaan', 'verifikator' => [$kadiv->id], 'penandatangan' => [$dirut->id], 'action' => 'ajukan',
        ])->assertSessionHasNoErrors();
        $surat = SuratKeluar::where('perihal', 'Nota Pengadaan ATK')->firstOrFail();
        $this->assertNotNull($surat->file_path);
        Storage::disk('local')->assertExists($surat->file_path);
        $this->assertSame('Mohon pengadaan ATK.', $surat->isian['ISI_NOTA']);
        $this->assertSame(25, $surat->no_urut);
        $this->assertSame('B/0025/UM/TBN/' . \App\Services\NomorSuratService::roman(now()->month) . '/' . now()->year, $surat->nomor_surat);

        $this->actingAs($kadiv)->post("/surat-keluar/{$surat->id}/keputusan", ['action' => 'setuju'])->assertRedirect();
        $this->actingAs($sekpri)->post("/surat-keluar/{$surat->id}/teruskan-sekpri")->assertSessionHas('success');
        $this->actingAs($dirut)->post("/surat-keluar/{$surat->id}/keputusan", ['action' => 'tandatangan', 'passphrase' => 'salah'])->assertSessionHasErrors('passphrase');
        $this->actingAs($dirut)->post("/surat-keluar/{$surat->id}/keputusan", ['action' => 'tandatangan', 'passphrase' => config('app.seed_password')])->assertSessionHasNoErrors();
        $this->assertSame(SuratKeluar::SELESAI, $surat->fresh()->status);
        $this->assertSame(['gagal', 'berhasil'], EsignLog::orderBy('id')->pluck('status')->all());
        $this->actingAs($this->u('admin'))->get('/log-esign')->assertSee($surat->nomor_surat)->assertSee('Passphrase TTE tidak valid');

        $this->actingAs($staf)->get('/surat-keluar/daftar-nomor')->assertSee($surat->nomor_surat);
        $this->actingAs($staf)->get('/surat-keluar/daftar-nomor?dari=2000-01-01&sampai=2000-12-31')->assertDontSee($surat->nomor_surat);
        $res = $this->actingAs($this->u('operator'))->get('/surat-keluar/daftar-nomor/export');
        $res->assertOk();
        $this->assertStringContainsString('spreadsheetml', $res->headers->get('content-type'));

        $this->actingAs($staf)->post('/surat-keluar', [
            'perihal' => 'Nota Kedua', 'tujuan' => 'Divisi TI', 'template_surat_id' => $nota->id, 'satker_id' => $umum->id, 'isian' => ['ISI_NOTA' => 'x'],
            'metode_verifikasi' => 'bersamaan', 'penandatangan' => [$kadiv->id], 'action' => 'ajukan',
        ])->assertSessionHasNoErrors();
        $this->assertSame(26, SuratKeluar::where('perihal', 'Nota Kedua')->value('no_urut'));
    }

    public function test_template_nomor_nomor_inisiasi_dan_unit_pemrakarsa(): void
    {
        $op = $this->u('operator');
        $this->actingAs($op)->post('/master/surat/template-nomor', ['nama' => 'Tanpa urut', 'padding' => 3, 'nomor_tambahan' => 'angka', 'segments' => [['type' => 'tahun']]])->assertStatus(422);
        $this->actingAs($op)->post('/master/surat/template-nomor', ['nama' => 'Umum TI', 'padding' => 3, 'nomor_tambahan' => 'romawi', 'satker_id' => Satker::where('kode', 'TI')->value('id'),
            'segments' => [['type' => 'nomor'], ['type' => 'custom_i', 'value' => 'TI'], ['type' => 'tahun']]])->assertSessionHasNoErrors();
        $this->assertSame('001/TI/2026', preg_replace('/\d{4}$/', '2026', TemplateNomor::where('nama', 'Umum TI')->first()->sample));
        $this->actingAs($op)->get('/master/surat/template-nomor')->assertSee('Umum TI');

        $ti = Satker::where('kode', 'TI')->first();
        $this->actingAs($op)->post('/master/nomor-inisiasi', ['satker_id' => $ti->id, 'tahun' => now()->year, 'nomor_awal' => 5])->assertSessionHasErrors('satker_id');
        $this->actingAs($op)->post('/master/nomor-inisiasi', ['satker_id' => $ti->id, 'tahun' => now()->year + 1, 'nomor_awal' => 5])->assertSessionHas('success');
        $this->actingAs($this->u('admin'))->post('/master/unit-pemrakarsa', ['kode' => 'SDM', 'nama' => 'Sumber Daya Manusia', 'status' => 1])->assertSessionHas('success');
        $this->actingAs($this->u('admin'))->get('/master/unit-pemrakarsa')->assertSee('Sumber Daya Manusia');
        $this->actingAs($this->u('admin'))->post('/master/satker/' . $ti->id . '/jabatan', ['nama' => 'Kepala Bagian Infrastruktur', 'is_pimpinan' => 1])->assertSessionHas('success');
        $this->actingAs($this->u('admin'))->get('/master/satker/' . $ti->id . '/jabatan')->assertSee('Kepala Bagian Infrastruktur');
    }

    public function test_alur_tujuan_template_dan_validasi_user_unik(): void
    {
        $staf = $this->u('staf'); $kadiv = $this->u('kadiv'); $dirut = $this->u('pimpinan');
        $this->actingAs($staf)->post('/master/surat/alur', ['judul' => 'Ke Direksi via Kadiv', 'metode_verifikasi' => 'berjenjang', 'penandatangan' => [$dirut->id]])->assertSessionHasErrors('judul');
        $this->actingAs($staf)->post('/master/surat/alur', ['judul' => 'Langsung Dirut', 'metode_verifikasi' => 'bersamaan', 'penandatangan' => [$dirut->id]])->assertSessionHas('success');
        $this->actingAs($staf)->get('/surat-keluar/create')->assertSee('Gunakan alur tersimpan')->assertSee('Langsung Dirut')->assertSee('Gunakan template tujuan surat');
        $this->actingAs($staf)->post('/master/surat/template-tujuan-surat', ['nama' => 'Semua divisi', 'tujuan' => Satker::pluck('id')->all()])->assertSessionHas('success');
        $this->actingAs($kadiv)->get('/master/surat/template-tujuan-surat')->assertForbidden();
        $this->actingAs($this->u('operator'))->get('/master/surat/template-tujuan-surat')->assertDontSee('Semua divisi');

        $this->actingAs($kadiv)->postJson('/template-tujuan-disposisi', ['nama' => 'Tim Umum', 'penerima' => [$staf->id]])->assertOk()->assertJson(['nama' => 'Tim Umum']);

        $op = $this->u('operator');
        $this->actingAs($op)->getJson('/users/unique?field=username&value=staf')->assertJson(['available' => false]);
        $this->actingAs($op)->getJson('/users/unique?field=username&value=staf&ignore=' . $staf->id)->assertJson(['available' => true]);
        $this->actingAs($op)->getJson('/users/unique?field=nip&value=' . $staf->nip)->assertJson(['available' => false]);
        $this->actingAs($op)->post('/users', ['name' => 'Baru', 'username' => 'baru', 'nip' => $staf->nip, 'role' => 'user', 'password' => 'Rahasia123', 'is_active' => 1])->assertSessionHasErrors('nip');
    }

    public function test_agenda_crud_dan_widget_dashboard(): void
    {
        $op = $this->u('operator'); $staf = $this->u('staf');
        $surat = SuratMasuk::first();
        $this->actingAs($op)->get("/agenda/create?surat={$surat->id}")->assertOk()->assertSee($surat->perihal);
        $this->actingAs($op)->post('/agenda', ['surat_masuk_id' => $surat->id, 'judul' => 'Rapat Internal', 'metode' => 'online', 'mulai' => now()->addDay()->format('Y-m-d H:i')])->assertSessionHasErrors('link_meeting');
        $this->actingAs($op)->post('/agenda', ['surat_masuk_id' => $surat->id, 'judul' => 'Rapat Internal', 'metode' => 'offline', 'tempat' => 'Ruang Rapat 2', 'mulai' => now()->addDay()->format('Y-m-d H:i')])->assertRedirect();
        $agenda = Agenda::where('judul', 'Rapat Internal')->firstOrFail();
        $this->actingAs($op)->get('/')->assertSee('Rapat Internal')->assertSee('dashboard-agenda', false);
        $this->actingAs($op)->get("/surat-masuk/{$surat->id}")->assertSee('Rapat Internal');
        $this->actingAs($staf)->get("/agenda/{$agenda->id}")->assertOk();
        $this->actingAs($op)->put("/agenda/{$agenda->id}", ['judul' => 'Rapat Internal Diubah', 'metode' => 'offline', 'tempat' => 'R3', 'mulai' => now()->addDays(2)->format('Y-m-d H:i')])->assertRedirect();
        $this->assertSame('Rapat Internal Diubah', $agenda->fresh()->judul);
        $this->actingAs($op)->get('/agenda?waktu=lalu')->assertDontSee('Rapat Internal Diubah');
        $this->actingAs($op)->delete("/agenda/{$agenda->id}")->assertRedirect('/agenda');
    }

    public function test_pelengkap_cetak_komentar_tag_email_arsip_tindak_lanjut(): void
    {
        Mail::fake();
        $op = $this->u('operator'); $dirut = $this->u('pimpinan'); $kadiv = $this->u('kadiv'); $staf = $this->u('staf');
        $kadiv->update(['email' => 'kadiv@example.com']);
        $surat = SuratMasuk::first();

        $this->actingAs($op)->post("/surat-masuk/{$surat->id}/teruskan", ['tipe' => 'jabatan', 'target_id' => $kadiv->id])->assertSessionHas('success');
        Mail::assertSent(NotifikasiSurat::class, fn ($m) => $m->hasTo('kadiv@example.com') && str_contains($m->subjek, 'diteruskan'));
        $this->actingAs($dirut)->post("/surat-masuk/{$surat->id}/disposisi", ['ke_user_id' => [$kadiv->id], 'subjek' => ['Untuk ditindaklanjuti']])->assertSessionHasNoErrors();
        Mail::assertSent(NotifikasiSurat::class, fn ($m) => str_contains($m->subjek, 'Disposisi baru'));
        $d = Disposisi::where('ke_user_id', $kadiv->id)->firstOrFail();

        $this->actingAs($kadiv)->post("/disposisi/{$d->id}/komentar", ['komentar' => 'Siap, segera diproses'])->assertSessionHas('success');
        $this->actingAs($staf)->post("/disposisi/{$d->id}/komentar", ['komentar' => 'ikut'])->assertForbidden();
        $this->actingAs($dirut)->get("/surat-masuk/{$surat->id}")->assertSee('Siap, segera diproses');

        $print = $this->actingAs($dirut)->get("/surat-masuk/{$surat->id}/lembar-disposisi");
        $print->assertOk();
        $this->assertSame('application/pdf', $print->headers->get('content-type'));

        $this->actingAs($kadiv)->postJson('/tags', ['type' => 'disposisi', 'id' => $d->id, 'tags' => 'rapat, triwulan, Rapat'])->assertOk()->assertJson(['tags' => ['rapat', 'triwulan']]);
        $this->actingAs($kadiv)->get('/surat-masuk/disposisi?tag=triw')->assertSee('disposisi-row-' . $d->id, false);
        $this->actingAs($kadiv)->get('/surat-masuk/disposisi?tag=lain')->assertDontSee('disposisi-row-' . $d->id, false);
        $this->actingAs($op)->postJson('/tags', ['type' => 'surat_masuk', 'id' => $surat->id, 'tags' => 'ojk'])->assertOk();
        $this->assertSame([], UserTag::for($kadiv, 'surat_masuk', $surat->id));
        $this->actingAs($staf)->postJson('/tags', ['type' => 'surat_masuk', 'id' => $surat->id, 'tags' => 'x'])->assertForbidden();

        $this->actingAs($staf)->post('/surat-keluar/arsip', ['nomor_surat' => '010/UM/TBN/2025', 'tanggal_surat' => '2025-03-01', 'perihal' => 'Arsip Lama', 'tujuan' => 'Divisi TI', 'file' => $this->pdf()])->assertSessionHasNoErrors();
        $arsip = SuratKeluar::where('perihal', 'Arsip Lama')->firstOrFail();
        $this->assertTrue($arsip->is_arsip);
        $this->assertSame(SuratKeluar::SELESAI, $arsip->status);
        $this->actingAs($staf)->get('/surat-keluar/daftar-nomor')->assertSee('010/UM/TBN/2025');
        $this->actingAs($staf)->postJson('/tags', ['type' => 'surat_keluar', 'id' => $arsip->id, 'tags' => 'arsip2025'])->assertOk();
        $this->actingAs($staf)->get('/surat-keluar?tab=semua&tag=arsip2025')->assertSee('Arsip Lama');
        $this->actingAs($staf)->get("/surat-keluar/{$arsip->id}/tindak-lanjut")->assertOk()->assertSee('Belum ada penerima internal');
    }
}

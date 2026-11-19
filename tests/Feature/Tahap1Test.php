<?php

namespace Tests\Feature;

use App\Models\Disposisi;
use App\Models\FormDraft;
use App\Models\Satker;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Tahap1Test extends TestCase
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

    private function pdf(string $name = 'surat.pdf'): UploadedFile
    {
        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, 'Contoh surat uji');
        return UploadedFile::fake()->createWithContent($name, $pdf->Output('S'));
    }

    public function test_menu_per_role_dan_halaman_utama_bisa_dibuka(): void
    {
        $pages = [
            'admin' => ['/', '/users', '/master/satker', '/master/subjek-disposisi', '/log-aktivitas'],
            'operator' => ['/', '/surat-masuk', '/surat-keluar', '/users', '/log-aktivitas'],
            'pimpinan' => ['/', '/surat-masuk', '/surat-masuk/disposisi', '/surat-masuk/riwayat-disposisi', '/surat-keluar', '/surat-keluar/verifikasi', '/surat-keluar/tandatangan', '/log-aktivitas'],
            'sekpri' => ['/', '/surat-masuk/disposisi', '/surat-masuk/disposisi-keluar', '/surat-keluar/tandatangan'],
            'staf' => ['/', '/surat-masuk/disposisi', '/surat-keluar/verifikasi', '/log-aktivitas'],
        ];
        foreach ($pages as $username => $urls) {
            foreach ($urls as $url) {
                $this->actingAs($this->u($username))->get($url)->assertOk();
            }
        }
        $this->actingAs($this->u('admin'))->get('/')->assertSee('Subjek Disposisi')->assertDontSee('Riwayat Disposisi');
        $this->actingAs($this->u('admin'))->get('/surat-masuk')->assertForbidden();
        $this->actingAs($this->u('operator'))->get('/master/satker')->assertForbidden();
        $this->actingAs($this->u('sekpri'))->get('/')->assertSee('Disposisi Keluar');
        $this->actingAs($this->u('staf'))->get('/')->assertDontSee('Disposisi Keluar');
    }

    public function test_catatan_aktivitas_role_lain_hanya_milik_sendiri(): void
    {
        \App\Models\ActivityLog::create(['user_id' => $this->u('operator')->id, 'aksi' => 'rahasia.operator', 'deskripsi' => 'aktivitas operator']);
        $this->actingAs($this->u('staf'))->get('/log-aktivitas')->assertDontSee('aktivitas operator');
        $this->actingAs($this->u('admin'))->get('/log-aktivitas')->assertSee('aktivitas operator');
    }

    public function test_teruskan_surat_masuk_sekpri_draft_dan_disposisi(): void
    {
        $op = $this->u('operator'); $dirut = $this->u('pimpinan'); $sekpri = $this->u('sekpri'); $kadiv = $this->u('kadiv'); $staf = $this->u('staf');
        $surat = SuratMasuk::first();
        $umum = Satker::where('kode', 'UMM')->first();

        $this->actingAs($op)->get("/surat-masuk/{$surat->id}/teruskan?tab=divisi")->assertOk()->assertSee('Divisi Umum');
        $this->actingAs($op)->post("/surat-masuk/{$surat->id}/teruskan", ['tipe' => 'divisi', 'target_id' => $umum->id])->assertSessionHasNoErrors();
        $this->actingAs($kadiv)->get('/surat-masuk')->assertSee('surat-masuk-card-' . $surat->id);
        $t = $surat->teruskans()->where('satker_id', $umum->id)->whereNull('user_id')->first();
        $this->actingAs($op)->delete("/surat-masuk/teruskan/{$t->id}")->assertSessionHas('success');
        $this->actingAs($kadiv)->get('/surat-masuk')->assertDontSee('surat-masuk-card-' . $surat->id);

        $this->actingAs($dirut)->get("/surat-masuk/{$surat->id}")->assertOk()->assertSee('Teruskan ke Sekpri');
        $this->actingAs($dirut)->post("/surat-masuk/{$surat->id}/teruskan-sekpri", ['sekpri_id' => $sekpri->id])->assertSessionHas('success');
        $this->actingAs($sekpri)->get("/surat-masuk/{$surat->id}")->assertOk()->assertSee('Siapkan disposisi pimpinan');

        $this->actingAs($sekpri)->post("/surat-masuk/{$surat->id}/disposisi", ['as_draft' => 1, 'ke_user_id' => [$kadiv->id], 'subjek' => ['Untuk ditindaklanjuti'], 'catatan' => 'segera'])->assertSessionHasNoErrors();
        $draft = Disposisi::where('is_draft', true)->firstOrFail();
        $this->assertSame($dirut->id, $draft->dari_user_id);
        $this->actingAs($kadiv)->get('/surat-masuk/disposisi')->assertDontSee('disposisi-row-' . $draft->id);
        $this->actingAs($sekpri)->get('/surat-masuk/disposisi-keluar')->assertSee($surat->perihal);

        $this->actingAs($dirut)->post("/disposisi/{$draft->id}/kirim-draft")->assertSessionHas('success');
        $this->assertFalse($draft->fresh()->is_draft);
        $this->actingAs($kadiv)->get('/surat-masuk/disposisi')->assertSee('disposisi-row-' . $draft->id);

        $this->actingAs($kadiv)->post("/surat-masuk/{$surat->id}/disposisi", ['ke_user_id' => [$staf->id], 'subjek' => ['Siapkan jawaban']])->assertSessionHasNoErrors();
        $child = Disposisi::where('ke_user_id', $staf->id)->firstOrFail();
        $this->assertSame($draft->id, $child->parent_id);
        $this->actingAs($dirut)->put("/disposisi/{$draft->id}", ['instruksi' => 'ubah'])->assertForbidden();
        $this->actingAs($kadiv)->put("/disposisi/{$child->id}", ['subjek' => ['Arsipkan'], 'catatan' => 'revisi'])->assertSessionHas('success');
        $this->assertSame('Arsipkan', $child->fresh()->instruksi);

        $this->actingAs($dirut)->get("/surat-masuk/{$surat->id}/riwayat-disposisi")->assertOk()->assertSee($staf->name);
        $this->actingAs($dirut)->get("/surat-masuk/{$surat->id}/riwayat-disposisi?satker={$umum->id}")->assertOk()->assertSee($kadiv->name);

        $this->actingAs($staf)->post("/disposisi/{$child->id}/tindak-lanjut", ['tindak_lanjut' => 'Sudah'])->assertSessionHas('success');
        $this->actingAs($kadiv)->delete("/disposisi/{$child->id}")->assertForbidden();
    }

    public function test_alur_surat_keluar_lengkap_sampai_terkirim(): void
    {
        $staf = $this->u('staf'); $kadiv = $this->u('kadiv'); $dirut = $this->u('pimpinan'); $sekpri = $this->u('sekpri'); $staf2 = $this->u('staf2');
        $ti = Satker::where('kode', 'TI')->first();
        $staf2->update(['disposisi_awal' => true]);

        $this->actingAs($staf)->postJson('/surat-keluar/autosave', ['key' => 'baru', 'data' => ['perihal' => 'Draf otomatis']])->assertOk();
        $this->actingAs($staf)->get('/surat-keluar/create')->assertSee('Draf otomatis')->assertSee('Draft otomatis dipulihkan');

        $this->actingAs($staf)->post('/surat-keluar', [
            'perihal' => 'Undangan Internal TI', 'tujuan_satker' => [$ti->id], 'metode_verifikasi' => 'berjenjang',
            'verifikator' => [$kadiv->id], 'penandatangan' => [$dirut->id], 'file' => $this->pdf(),
            'lampiran' => [UploadedFile::fake()->create('daftar.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')], 'action' => 'ajukan',
        ])->assertSessionHasNoErrors();
        $this->assertSame(0, FormDraft::count());
        $surat = SuratKeluar::where('perihal', 'Undangan Internal TI')->firstOrFail();
        $this->assertSame('Divisi Teknologi Informasi', $surat->tujuan);
        $this->assertSame(1, $surat->lampirans()->count());
        $this->assertSame(SuratKeluar::VERIFIKASI, $surat->status);

        $this->actingAs($staf)->post("/surat-keluar/{$surat->id}/batal-kirim")->assertSessionHas('success');
        $this->assertSame(SuratKeluar::DRAFT, $surat->fresh()->status);
        $this->actingAs($staf)->post("/surat-keluar/{$surat->id}/upload", ['file' => $this->pdf('baru.pdf')])->assertSessionHas('success');
        $this->assertSame('baru.pdf', $surat->fresh()->file_name);
        $this->actingAs($staf)->post("/surat-keluar/{$surat->id}/ajukan")->assertSessionHas('success');

        $this->actingAs($kadiv)->get('/surat-keluar/verifikasi')->assertSee('Undangan Internal TI');
        $this->actingAs($kadiv)->post("/surat-keluar/{$surat->id}/teruskan", ['user_id' => $staf2->id, 'catatan' => 'mohon dicek'])->assertSessionHas('success');
        $this->actingAs($staf)->post("/surat-keluar/{$surat->id}/batal-kirim")->assertSessionHas('error');
        $this->actingAs($staf2)->get('/surat-keluar/verifikasi')->assertSee('Undangan Internal TI');
        $this->actingAs($staf2)->post("/surat-keluar/{$surat->id}/keputusan", ['action' => 'setuju'])->assertRedirect();
        $surat->refresh();
        $this->assertSame(SuratKeluar::TANDATANGAN, $surat->status);

        $this->actingAs($dirut)->get('/surat-keluar/tandatangan')->assertDontSee('Undangan Internal TI');
        $this->actingAs($dirut)->post("/surat-keluar/{$surat->id}/keputusan", ['action' => 'tandatangan', 'passphrase' => config('app.seed_password')])->assertForbidden();
        $this->actingAs($sekpri)->get('/surat-keluar/tandatangan')->assertSee('Undangan Internal TI');
        $this->actingAs($sekpri)->post("/surat-keluar/{$surat->id}/teruskan-sekpri")->assertSessionHas('success');
        $this->actingAs($dirut)->get('/surat-keluar/tandatangan')->assertSee('Undangan Internal TI');
        $this->actingAs($dirut)->post("/surat-keluar/{$surat->id}/keputusan", ['action' => 'tandatangan', 'passphrase' => config('app.seed_password')])->assertSessionHasNoErrors();

        $surat->refresh();
        $this->assertSame(SuratKeluar::SELESAI, $surat->status);
        $this->assertNotNull($surat->terkirim_at);
        $masuk = SuratMasuk::where('surat_keluar_id', $surat->id)->firstOrFail();
        $this->assertSame($ti->id, $masuk->satker_id);
        $this->actingAs($staf2)->get('/surat-masuk')->assertSee('Undangan Internal TI');
        $this->actingAs($staf)->get("/surat-keluar/{$surat->id}")->assertOk()->assertSee('Info Verifikasi')->assertSee('Selesai &amp; Terkirim', false);
    }
}

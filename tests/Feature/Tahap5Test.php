<?php

namespace Tests\Feature;

use App\Models\GrupOpd;
use App\Models\Satker;
use App\Models\SuratMasuk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Tahap5Test extends TestCase
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
        return UploadedFile::fake()->createWithContent('surat.pdf', "%PDF-1.4\n%%EOF");
    }

    public function test_pengirim_luar_opd_dan_opd_tabanan(): void
    {
        $op = $this->u('operator');
        $opd = Satker::where('kode', 'UMM')->first();
        $base = ['nomor_surat' => '1/X', 'tanggal_surat' => now()->toDateString(), 'tanggal_terima' => now()->toDateString(), 'perihal' => 'Uji pengirim', 'file' => $this->pdf()];
        $this->actingAs($op)->get('/surat-masuk/create')->assertSee('Luar OPD Tabanan')->assertSee('OPD Tabanan');
        $this->actingAs($op)->post('/surat-masuk', $base + ['pengirim_jenis' => 'opd'])->assertSessionHasErrors('pengirim_satker_id');
        $this->actingAs($op)->post('/surat-masuk', $base + ['pengirim_jenis' => 'opd', 'pengirim_satker_id' => $opd->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('surat_masuks', ['perihal' => 'Uji pengirim', 'pengirim' => $opd->nama, 'pengirim_jenis' => 'opd', 'pengirim_satker_id' => $opd->id]);
        $this->actingAs($op)->post('/surat-masuk', array_merge($base, ['perihal' => 'Uji luar', 'file' => $this->pdf()]) + ['pengirim_jenis' => 'luar', 'pengirim' => 'Pemprov Bali'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('surat_masuks', ['perihal' => 'Uji luar', 'pengirim' => 'Pemprov Bali', 'pengirim_satker_id' => null]);
    }

    public function test_operator_hanya_teruskan_jabatan_opd_sendiri(): void
    {
        $op = $this->u('operator');
        $surat = SuratMasuk::first();
        $this->actingAs($op)->get("/surat-masuk/{$surat->id}")->assertSee('Riwayat Disposisi')->assertDontSee('Kirim disposisi');
        $this->actingAs($op)->post("/surat-masuk/{$surat->id}/disposisi", ['ke_user_id' => [$this->u('staf')->id], 'subjek' => ['Arsipkan']])->assertForbidden();
        $this->actingAs($op)->get("/surat-masuk/{$surat->id}/teruskan")->assertSee('OPD')->assertDontSee('Divisi / Satker')->assertSee($this->u('sekpri')->name)->assertDontSee($this->u('staf')->name);
        $this->actingAs($op)->post("/surat-masuk/{$surat->id}/teruskan", ['tipe' => 'jabatan', 'target_id' => $this->u('staf')->id])->assertNotFound();
        $this->actingAs($op)->post("/surat-masuk/{$surat->id}/teruskan", ['tipe' => 'direksi', 'target_id' => $this->u('pimpinan')->id])->assertSessionHasErrors('tipe');
    }

    public function test_dokumen_saya_operator_berisi_dokumen_opd(): void
    {
        $op = $this->u('operator');
        $count = SuratMasuk::where('satker_id', $op->satker_id)->count();
        $this->actingAs($op)->get('/dokumen-saya?relasi=opd')->assertOk()->assertSee('Dokumen OPD');
        $this->assertGreaterThanOrEqual($count, collect(app(\App\Services\DocumentHub::class)->forUser($op))->filter(fn ($i) => in_array('opd', $i['relasi'], true))->count());
        $this->actingAs($this->u('staf'))->get('/dokumen-saya')->assertDontSee('Dokumen OPD');
    }

    public function test_grup_opd_master_dan_form_staf(): void
    {
        $this->actingAs($this->u('admin'))->post('/master/grup-opd', ['nama' => 'Grup Uji'])->assertSessionHas('success');
        $g = GrupOpd::where('nama', 'Grup Uji')->first();
        $s = Satker::first();
        $this->actingAs($this->u('admin'))->put("/master/satker/{$s->id}", ['kode' => $s->kode, 'nama' => $s->nama, 'kategori' => $s->kategori, 'grup_opd_id' => $g->id])->assertSessionHas('success');
        $this->assertSame($g->id, $s->fresh()->grup_opd_id);
        $this->actingAs($this->u('staf'))->get('/surat-keluar/create')->assertSee('Tujuan OPD')->assertSee('Tembusan OPD')->assertSee('Grup Uji')
            ->assertDontSee('Satker pembuat')->assertDontSee('Unit Pemrakarsa');
    }
}

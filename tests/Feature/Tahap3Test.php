<?php

namespace Tests\Feature;

use App\Http\Middleware\SchemaReady;
use App\Models\CaptchaStat;
use App\Models\Disposisi;
use App\Models\HariLibur;
use App\Models\Jabatan;
use App\Models\MobileToken;
use App\Models\NomorCadangan;
use App\Models\RiwayatNomor;
use App\Models\Satker;
use App\Models\Sdm;
use App\Models\Setting;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\TemplateSurat;
use App\Models\User;
use App\Services\NomorCadanganService;
use App\Services\NomorSuratService;
use App\Services\PegawaiAuth;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Tahap3Test extends TestCase
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

    private function login(array $data)
    {
        return $this->post('/login', $data);
    }

    public function test_menu_dan_halaman_tahap3_per_role(): void
    {
        $admin = $this->u('admin');
        foreach (['/laporan/rekap-surat-satker', '/master/administrasi/sdm', '/master/config', '/master/hari-libur', '/transfer-surat/disposisi', '/transfer-surat/keluar'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
        $this->actingAs($admin)->get('/')->assertSee('Setting Sistem')->assertSee('Rekap Jumlah Surat')->assertSee('Hari Libur')->assertSee('SDM')->assertSee('Transfer Surat');
        $this->actingAs($admin)->get('/master/nomor-cadangan')->assertForbidden();

        $op = $this->u('operator');
        $this->actingAs($op)->get('/master/nomor-cadangan')->assertOk()->assertSee('Nomor Cadangan');
        $this->actingAs($op)->get('/')->assertSee('Nomor Cadangan')->assertDontSee('Setting Sistem');
        foreach (['/master/config', '/laporan/rekap-surat-satker', '/master/administrasi/sdm', '/transfer-surat/keluar', '/master/hari-libur'] as $url) {
            $this->actingAs($op)->get($url)->assertForbidden();
        }
        $this->actingAs($this->u('staf'))->get('/laporan/rekap-surat-satker')->assertForbidden();
    }

    public function test_login_dua_jenis_recaptcha_dan_aturan_nrk(): void
    {
        $this->get('/login')->assertOk()->assertSee('Pegawai (NRK)')->assertSee('Admin / Operator')->assertDontSee('login-captcha-image', false)->assertDontSee('Masuk dengan SSO');
        $this->get('/captcha')->assertNotFound();

        config(['recaptcha.site_key' => 'site', 'recaptcha.secret_key' => 'secret']);
        Http::fake(['www.google.com/recaptcha/*' => Http::sequence()->push(['success' => false])->push(['success' => true])]);
        $this->get('/login')->assertSee('g-recaptcha', false);
        $this->login(['tipe' => 'admin', 'username' => 'admin', 'password' => 'Esurat123!'])->assertSessionHasErrors('g-recaptcha-response');
        $this->login(['tipe' => 'admin', 'username' => 'admin', 'password' => 'Esurat123!', 'g-recaptcha-response' => 'bad'])->assertSessionHasErrors('g-recaptcha-response');
        $this->assertGuest();
        $this->login(['tipe' => 'admin', 'username' => 'admin', 'password' => 'Esurat123!', 'g-recaptcha-response' => 'ok'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->u('admin'));
        $this->assertSame([1, 1], [CaptchaStat::value('success'), CaptchaStat::value('failed')]);
        $this->post('/logout');
        config(['recaptcha.site_key' => null, 'recaptcha.secret_key' => null]);

        $this->login(['tipe' => 'pegawai', 'username' => 'admin', 'password' => 'Esurat123!'])->assertSessionHasErrors(['username' => 'User tidak terdaftar.']);
        foreach (['10004', 'o10004', 'k10004', 'staf'] as $nrk) {
            $this->login(['tipe' => 'pegawai', 'username' => $nrk, 'password' => 'Esurat123!'])->assertRedirect(route('dashboard'));
            $this->assertAuthenticatedAs($this->u('staf'));
            $this->post('/logout');
        }
        $this->login(['tipe' => 'pegawai', 'username' => '10004', 'password' => 'salah'])->assertSessionHasErrors(['username' => 'NRK atau password salah.']);
    }

    public function test_login_ldap_dan_plt(): void
    {
        config(['services.ldap.hosts' => 'ldap://127.0.0.1:389']);
        $this->partialMock(PegawaiAuth::class, fn ($m) => $m->shouldReceive('ldapBind')->with('10004', 'PasswordDomain!')->andReturn(true)->shouldReceive('ldapBind')->andReturn(false));
        $this->login(['tipe' => 'pegawai', 'username' => '10004', 'password' => 'PasswordDomain!'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->u('staf'));
        $this->post('/logout');
        $this->login(['tipe' => 'pegawai', 'username' => '10004', 'password' => 'Esurat123!'])->assertSessionHasErrors(['username' => 'NRK atau password salah.']);

        $this->u('staf2')->update(['username' => 'plt10005']);
        $this->login(['tipe' => 'pegawai', 'username' => 'plt10005', 'password' => 'Esurat123!'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->u('plt10005'));
    }

    public function test_kunci_akun_setelah_gagal_dan_lama_sesi(): void
    {
        Setting::put(['login_fail_count' => 2, 'login_fail_lock_minutes' => 10, 'session_minutes' => 30]);
        for ($i = 0; $i < 3; $i++) {
            $this->login(['tipe' => 'admin', 'username' => 'operator', 'password' => 'salah']);
        }
        $this->login(['tipe' => 'admin', 'username' => 'operator', 'password' => 'Esurat123!'])->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->assertStringContainsString('dikunci', session('errors')->first('username'));
        $this->get('/login')->assertSee('login-locked-alert', false);

        $this->travel(11)->minutes();
        $this->login(['tipe' => 'admin', 'username' => 'operator', 'password' => 'Esurat123!'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->get('/')->assertOk();
        $this->travel(31)->minutes();
        $this->get('/')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_setting_sistem_dan_rate_limit(): void
    {
        $admin = $this->u('admin');
        $skp = Satker::where('kode', 'SKP')->first();
        $this->actingAs($admin)->put('/master/config', [
            'login_fail_count' => 3, 'login_fail_lock_minutes' => 20, 'session_minutes' => 60, 'sdm_sync_time' => '02:30',
            'satker_penomoran_sekper' => $skp->id, 'rate_limit_login' => '1', 'rate_limit_user' => '1',
        ])->assertSessionHas('success');
        $this->assertSame('3', Setting::get('login_fail_count'));
        $this->assertSame('02:30', Setting::get('sdm_sync_time'));
        $this->assertFalse(Setting::flag('rate_limit_ip'));
        $this->assertTrue(Setting::flag('rate_limit_user'));

        $tpl = TemplateSurat::first();
        $tpl->update(['counter_penomoran' => 'sekper']);
        $surat = new SuratKeluar(['template_surat_id' => $tpl->id, 'satker_id' => Satker::where('kode', 'UMM')->value('id')]);
        $this->assertSame($skp->id, app(NomorSuratService::class)->satkerPenomoran($surat));

        config(['rate_limit.user.requests' => 3, 'rate_limit.login.requests' => 2]);
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($admin)->get('/master/config')->assertOk();
        }
        $this->actingAs($admin)->get('/master/config')->assertStatus(429);
        $this->post('/logout');
        auth()->logout();
        $this->login(['tipe' => 'admin', 'username' => 'x', 'password' => 'y']);
        $this->login(['tipe' => 'admin', 'username' => 'x', 'password' => 'y']);
        $this->login(['tipe' => 'admin', 'username' => 'admin', 'password' => 'Esurat123!'])->assertSessionHasErrors('username');
        $this->assertStringContainsString('Terlalu banyak permintaan', session('errors')->first('username'));
        $this->assertSame(0, Artisan::call('cleanup_rate_limits'));
    }

    public function test_hari_libur_nomor_cadangan_dan_job_harian(): void
    {
        $admin = $this->u('admin');
        $this->actingAs($admin)->post('/master/hari-libur', ['tanggal' => '2026-03-19', 'keterangan' => 'Hari Suci Nyepi'])->assertSessionHas('success');
        $this->actingAs($admin)->get('/master/hari-libur')->assertSee('Hari Suci Nyepi');
        $this->actingAs($admin)->post('/master/hari-libur', ['tanggal' => '2026-03-19', 'keterangan' => 'Duplikat'])->assertSessionHasErrors('tanggal');

        $skp = Satker::where('kode', 'SKP')->first();
        $op = $this->u('operator');
        NomorCadangan::query()->delete();
        $this->actingAs($op)->post('/master/nomor-cadangan', ['satker_id' => $skp->id, 'bulan' => 3, 'jumlah' => 3])->assertSessionHas('success');
        $this->actingAs($op)->get('/master/nomor-cadangan')->assertSee('Maret')->assertSee('Semua jenis surat');

        \App\Models\NomorInisiasi::updateOrCreate(['satker_id' => $skp->id, 'tahun' => 2026], ['nomor_awal' => 10]);
        Artisan::call('catat_nomor_cadangan', ['tanggal' => '2026-03-14']);
        Artisan::call('catat_nomor_cadangan', ['tanggal' => '2026-03-19']);
        $this->assertSame(0, RiwayatNomor::count(), 'Sabtu dan hari libur dilewati');

        Artisan::call('catat_nomor_cadangan', ['tanggal' => '2026-03-16']);
        $r = RiwayatNomor::where('satker_id', $skp->id)->first();
        $this->assertSame([9, 12], [$r->no_akhir, $r->no_max]);
        $this->assertSame(1, RiwayatNomor::where('satker_id', $skp->id)->count());

        $svc = app(NomorCadanganService::class);
        $this->assertSame(10, $svc->reserved($skp->id, Carbon::parse('2026-03-16')));
        SuratKeluar::create(['perihal' => 'Mundur', 'tujuan' => 'X', 'created_by' => $op->id, 'satker_nomor_id' => $skp->id, 'tanggal_surat' => '2026-03-16', 'no_urut' => 10]);
        $this->assertSame(11, $svc->reserved($skp->id, Carbon::parse('2026-03-17')));
        $this->assertSame(12, $svc->maxReserved($skp->id, 2026));

        Artisan::call('catat_nomor_cadangan', ['tanggal' => '2026-03-17']);
        $this->assertSame(12, RiwayatNomor::where('satker_id', $skp->id)->whereDate('tanggal', '2026-03-17')->value('no_akhir'));
        $this->assertTrue(HariLibur::isLibur(Carbon::parse('2026-03-19')));
    }

    public function test_sinkron_sdm_dari_hairisma(): void
    {
        $admin = $this->u('admin');
        $this->actingAs($admin)->post('/master/administrasi/sdm/sync')->assertSessionHas('error');

        config(['services.hairisma.url' => 'http://hairisma.test/service?TYPE=0']);
        Http::fake(['hairisma.test/*' => Http::sequence()
            ->push([
                ['UNIT' => 'Kantor Cabang Gianyar', 'SATUAN_KERJA' => 'Kantor Cabang Gianyar', 'SUB_UNIT' => '-', 'JABATAN' => 'Pemimpin Cabang', 'NAMA' => 'I Made Baru', 'NRK' => '20001', 'EMAIL' => 'made@bpd.test', 'STATUS_CUTI' => false],
                ['UNIT' => 'Kantor Cabang Gianyar', 'SATUAN_KERJA' => 'Kantor Cabang Gianyar', 'SUB_UNIT' => '-', 'JABATAN' => 'Sopir', 'NAMA' => 'Ketut Sopir', 'NRK' => '20002', 'EMAIL' => '', 'STATUS_CUTI' => false],
                ['UNIT' => 'Kantor Cabang Gianyar', 'SATUAN_KERJA' => 'Kantor Cabang Gianyar', 'SUB_UNIT' => 'Capem Ubud', 'JABATAN' => 'Teller', 'NAMA' => 'Luh Capem', 'NRK' => '20003', 'EMAIL' => '', 'STATUS_CUTI' => true],
            ])
            ->push([
                ['UNIT' => 'Kantor Cabang Gianyar', 'SATUAN_KERJA' => 'Kantor Cabang Gianyar', 'SUB_UNIT' => '-', 'JABATAN' => 'Pemimpin Cabang', 'NAMA' => 'I Made Baru Sekali', 'NRK' => '20001', 'EMAIL' => 'made@bpd.test', 'STATUS_CUTI' => false],
                ['UNIT' => 'Kantor Cabang Gianyar', 'SATUAN_KERJA' => 'Kantor Cabang Gianyar', 'SUB_UNIT' => '-', 'JABATAN' => 'Analis Kredit', 'NAMA' => 'Wayan Susul', 'NRK' => '20004', 'EMAIL' => '', 'STATUS_CUTI' => false],
            ]),
        ]);
        $this->actingAs($admin)->post('/master/administrasi/sdm/sync')->assertSessionHas('success');
        $this->assertSame(3, Sdm::count());
        $this->assertTrue(Sdm::where('nrk', '20003')->value('status_cuti'));
        $cabang = Satker::where('nama', 'Kantor Cabang Gianyar')->firstOrFail();
        $this->assertSame('cabang', $cabang->kategori);
        $made = User::where('nrk', '20001')->firstOrFail();
        $this->assertSame(['20001', 'user', $cabang->id], [$made->username, $made->role, $made->satker_id]);
        $this->assertSame('Pemimpin Cabang', $made->jabatan?->nama);
        $this->assertTrue(Jabatan::where('nama', 'Sopir')->exists());
        $this->assertFalse(User::where('nrk', '20002')->exists(), 'Sopir dilewati');
        $this->assertFalse(User::where('nrk', '20003')->exists(), 'Sub unit hanya disimpan di SDM');
        $this->actingAs($admin)->get('/master/administrasi/sdm?q=Capem')->assertSee('Luh Capem')->assertSee('Cuti');

        $this->assertSame(0, Artisan::call('sync_sdm', ['--force' => true]));
        $this->assertSame('I Made Baru Sekali', $made->fresh()->name);
        $wayan = User::where('nrk', '20004')->firstOrFail();
        $this->assertNull($wayan->jabatan_id, 'Penempatan jabatan hanya saat sinkron pertama');
        $this->assertSame(0, Artisan::call('sync_sdm'));
    }

    public function test_transfer_surat_dan_laporan_rekap(): void
    {
        $admin = $this->u('admin');
        $staf = $this->u('staf');
        $staf2 = $this->u('staf2');
        $masuk = SuratMasuk::first();
        Disposisi::create(['surat_masuk_id' => $masuk->id, 'dari_user_id' => $this->u('pimpinan')->id, 'ke_user_id' => $staf->id, 'instruksi' => 'Tindak lanjuti']);
        $range = ['tgl_awal' => now()->subMonth()->toDateString(), 'tgl_akhir' => now()->toDateString()];

        $this->actingAs($admin)->getJson('/transfer-surat/hitung?' . http_build_query(['jenis' => 'disposisi', 'asal_id' => $staf->id] + $range))->assertOk()->assertJsonPath('jumlah.disposisi', 1);
        $this->actingAs($admin)->getJson('/transfer-surat/hitung?' . http_build_query(['jenis' => 'keluar', 'asal_id' => $staf->id] + $range))->assertJsonPath('jumlah.surat_keluar', 1);
        $this->actingAs($admin)->post('/transfer-surat', ['jenis' => 'disposisi', 'asal_id' => $staf->id, 'tujuan_id' => $staf->id] + $range)->assertSessionHasErrors('tujuan_id');
        $this->actingAs($admin)->post('/transfer-surat', ['jenis' => 'disposisi', 'asal_id' => $staf->id, 'tujuan_id' => $staf2->id] + $range)->assertRedirect(route('transfer.index', 'disposisi'))->assertSessionHas('success');
        $this->assertSame(0, Disposisi::where('ke_user_id', $staf->id)->count());
        $this->assertSame(1, Disposisi::where('ke_user_id', $staf2->id)->count());
        $this->actingAs($admin)->post('/transfer-surat', ['jenis' => 'keluar', 'asal_id' => $staf->id, 'tujuan_id' => $staf2->id] + $range)->assertSessionHas('success');
        $this->assertSame(0, SuratKeluar::where('created_by', $staf->id)->count());

        $page = $this->actingAs($admin)->get('/laporan/rekap-surat-satker?' . http_build_query($range));
        $page->assertOk()->assertSee('Direksi');
        $rows = $page->viewData('rows')->keyBy('kode');
        $this->assertSame(1, $rows['DIR']->masuk);
        $this->assertSame(1, $rows[$staf2->satker->kode]->keluar, 'Surat keluar ikut pindah ke satker pegawai baru');
        $this->assertSame(1, $rows['DIR']->disposisi);
        $xlsx = $this->actingAs($admin)->get('/laporan/rekap-surat-satker/export?' . http_build_query($range));
        $xlsx->assertOk();
        $this->assertStringContainsString('spreadsheetml', $xlsx->headers->get('Content-Type'));
    }

    public function test_api_mobile_lengkap(): void
    {
        $this->postJson('/api/m/auth', ['username' => '10001', 'password' => 'salah'])->assertStatus(401);
        $auth = $this->postJson('/api/m/auth', ['username' => '10001', 'password' => 'Esurat123!', 'device' => 'Android Test'])->assertOk()->assertJsonPath('user.username', 'pimpinan');
        $h = ['Authorization' => 'Bearer ' . $auth->json('access_token')];
        $this->getJson('/api/m/satker')->assertStatus(401);
        $this->getJson('/api/m/satker', $h)->assertOk()->assertJsonFragment(['kode' => 'SKP']);
        $list = $this->getJson('/api/m/surat/masuk', $h)->assertOk()->assertJsonPath('meta.total', 1);
        $id = $list->json('data.0.id');
        $this->getJson("/api/m/surat/masuk/$id", $h)->assertOk()->assertJsonPath('data.perihal', 'Undangan Rapat Koordinasi Triwulan');
        $pdf = $this->get('/loadpdf?' . http_build_query(['type' => 'masuk', 'id' => $id, 'token' => $auth->json('access_token')]));
        $pdf->assertOk();

        $this->postJson('/api/m/disposisi/add', ['surat_masuk_id' => $id, 'ke_user_id' => [$this->u('kadiv')->id], 'instruksi' => 'Hadiri rapat'], $h)->assertOk()->assertJsonPath('status', true);
        $d = Disposisi::where('instruksi', 'Hadiri rapat')->firstOrFail();
        $this->postJson('/api/m/disposisi/edit', ['id' => $d->id, 'instruksi' => 'Hadiri & laporkan'], $h)->assertOk();
        $this->assertSame('Hadiri & laporkan', $d->fresh()->instruksi);
        $this->getJson('/api/m/disposisi?mode=keluar', $h)->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson("/api/m/disposisi/{$d->id}", $h)->assertOk()->assertJsonPath('data.bisa_diubah', true);
        $this->getJson('/api/m/disposisi/tujuan', $h)->assertOk();
        $this->getJson('/api/m/agenda', $h)->assertOk();
        $this->getJson('/api/m/agenda-akan-datang', $h)->assertOk();
        $this->getJson('/api/m/surat/masuk/tujuan-teruskan', $h)->assertForbidden();
        $this->postJson('/api/m/fcm-token', ['fcm_token' => 'fcm-abc'], $h)->assertOk();
        $this->assertSame('fcm-abc', MobileToken::first()->fcm_token);

        $kadiv = $this->postJson('/api/m/auth', ['username' => '10003', 'password' => 'Esurat123!'])->json();
        $hk = ['Authorization' => 'Bearer ' . $kadiv['access_token']];
        $this->getJson('/api/m/disposisi', $hk)->assertOk()->assertJsonPath('data.0.instruksi', 'Hadiri & laporkan');
        $antre = $this->getJson('/api/m/surat/keluar/verifikasi', $hk)->assertOk()->assertJsonCount(1, 'data');
        $sk = $antre->json('data.0.id');
        $this->getJson("/api/m/surat/keluar/$sk", $hk)->assertOk()->assertJsonPath('data.giliran_saya', 'verifikator');
        $this->postJson('/api/m/surat/keluar/revisi', ['id' => $sk], $hk)->assertStatus(422);
        $this->postJson('/api/m/surat/keluar/setuju', ['id' => $sk, 'catatan' => 'OK'], $hk)->assertOk()->assertJsonPath('status', true);
        $this->assertSame(SuratKeluar::TANDATANGAN, SuratKeluar::find($sk)->status);
        $this->getJson('/api/m/surat/keluar', $hk)->assertOk();
        $this->getJson('/api/m/surat/keluar/tandatangan', $h)->assertOk();

        $opAuth = $this->postJson('/api/m/auth', ['tipe' => 'admin', 'username' => 'operator', 'password' => 'Esurat123!'])->json();
        $ho = ['Authorization' => 'Bearer ' . $opAuth['access_token']];
        $this->getJson('/api/m/surat/masuk/tujuan-teruskan', $ho)->assertOk()->assertJsonStructure(['data' => ['direksi', 'divisi', 'jabatan']]);
        $this->postJson('/api/m/surat/masuk/teruskan', ['surat_masuk_id' => $id, 'tipe' => 'jabatan', 'target_id' => $this->u('staf')->id], $ho)->assertOk()->assertJsonPath('status', true);

        $ref = $this->postJson('/api/m/refresh-access', [], ['Authorization' => 'Bearer ' . $auth->json('refresh_token')])->assertOk();
        $this->getJson('/api/m/satker', $h)->assertStatus(401);
        $h2 = ['Authorization' => 'Bearer ' . $ref->json('access_token')];
        $this->getJson('/api/m/me', $h2)->assertOk()->assertJsonPath('user.nrk', '10001');
        $this->postJson('/api/m/logout', [], $h2)->assertOk();
        $this->getJson('/api/m/me', $h2)->assertStatus(401);
    }

    private function rsa(): array
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);
        $d = openssl_pkey_get_details($key)['rsa'];
        $b64 = fn ($v) => rtrim(strtr(base64_encode($v), '+/', '-_'), '=');
        return [$pem, ['kty' => 'RSA', 'kid' => 'k1', 'use' => 'sig', 'alg' => 'RS256', 'n' => $b64($d['n']), 'e' => $b64($d['e'])]];
    }

    public function test_push_notifikasi_firebase(): void
    {
        [$pem] = $this->rsa();
        $path = storage_path('framework/testing/firebase-test.json');
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, json_encode(['project_id' => 'esurat-test', 'client_email' => 'svc@esurat-test.iam.gserviceaccount.com', 'private_key' => $pem, 'token_uri' => 'https://oauth2.googleapis.com/token']));
        config(['services.firebase.credentials' => $path]);
        Http::fake(['oauth2.googleapis.com/*' => Http::response(['access_token' => 'ya29.test']), 'fcm.googleapis.com/*' => Http::response(['name' => 'ok'])]);
        [$row] = MobileToken::issue($this->u('kadiv'));
        $row->update(['fcm_token' => 'device-kadiv']);

        $this->actingAs($this->u('pimpinan'))->post('/surat-masuk/' . SuratMasuk::value('id') . '/disposisi', ['ke_user_id' => [$this->u('kadiv')->id], 'instruksi' => 'Push test'])->assertSessionHas('success');
        Http::assertSent(fn ($r) => str_contains($r->url(), 'projects/esurat-test/messages:send') && $r['message']['token'] === 'device-kadiv' && $r->hasHeader('Authorization', 'Bearer ya29.test'));
        @unlink($path);
    }

    public function test_sso_oauth_login_dan_backchannel(): void
    {
        [$pem, $jwk] = $this->rsa();
        config(['services.sso' => ['base_url' => 'https://sso.test', 'jwks_url' => null, 'client_id' => 'cid', 'client_secret' => 'sec', 'redirect_uri' => null]]);
        $idToken = JWT::encode(['sub' => '10004', 'nrk' => '10004', 'iat' => time(), 'exp' => time() + 300], $pem, 'RS256', 'k1');
        Http::fake([
            'sso.test/oauth/jwks' => Http::response(['keys' => [$jwk]]),
            'sso.test/oauth/token' => Http::response(['meta' => ['code' => 200], 'data' => ['id_token' => $idToken, 'access_token' => 'at', 'refresh_token' => 'rt']]),
            'sso.test/api/*' => Http::response(['meta' => ['code' => 200]]),
        ]);
        $this->get('/login')->assertSee('Masuk dengan SSO');
        $go = $this->get('/oauth/login');
        $this->assertStringStartsWith('https://sso.test/oauth/authorize?', $go->headers->get('Location'));
        $this->assertStringContainsString('code_challenge_method=S256', $go->headers->get('Location'));
        $state = session('oauth_state');
        $this->get('/oauth/callback?code=abc&state=salah')->assertStatus(400);
        $this->get("/oauth/callback?code=abc&state=$state")->assertOk()->assertSee('Memverifikasi akun SSO');
        $this->get("/oauth/verify?code=abc&state=$state")->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->u('staf'));
        $this->assertSame('sso', session('login_type'));
        $this->post('/logout')->assertRedirect(route('sso.logout'));
        $this->get('/oauth/logout')->assertRedirect(route('login'));
        $this->assertGuest();

        $logout = JWT::encode(['sub' => '10004', 'iat' => time(), 'exp' => time() + 300, 'events' => ['http://schemas.openid.net/event/backchannel-logout' => new \stdClass()]], $pem, 'RS256', 'k1');
        $this->post('/oauth/backchannel-logout', ['logout_token' => $logout])->assertOk()->assertJson(['status' => true]);
        $bad = JWT::encode(['sub' => '10004', 'exp' => time() + 300], $pem, 'RS256', 'k1');
        $this->post('/oauth/backchannel-logout', ['logout_token' => $bad])->assertStatus(400);
    }

    public function test_privasi_404_dan_cek_skema(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('Kebijakan Privasi');
        $this->get('/privacy.html')->assertOk();
        $this->actingAs($this->u('admin'))->get('/halaman-yang-tidak-ada')->assertNotFound()->assertSee('Halaman tidak ditemukan');

        config(['app.schema_check_in_tests' => true]);
        Schema::drop('mobile_tokens');
        $this->get('/login')->assertStatus(503)->assertSee('Sistem sedang disiapkan')->assertSee('php artisan migrate --seed');
    }
}

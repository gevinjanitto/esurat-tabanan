<?php

use App\Http\Controllers\AdministrasiController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\Mobile\ApiController as MobileApi;
use App\Http\Controllers\Mobile\AuthController as MobileAuth;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\TransferSuratController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PelengkapController;
use App\Http\Controllers\SuratKeluarExtraController;
use App\Http\Controllers\TemplateNomorController;
use App\Http\Controllers\TemplatePribadiController;
use App\Http\Controllers\TemplateSuratController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\SuratKeluarController;
use App\Http\Controllers\SuratMasukController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/oauth/login', [SsoController::class, 'login'])->name('sso.login');
    Route::get('/oauth/callback', [SsoController::class, 'callback'])->name('sso.callback');
    Route::get('/oauth/verify', [SsoController::class, 'verify'])->name('sso.verify');
});
Route::get('/oauth/logout', [SsoController::class, 'logout'])->name('sso.logout');
Route::post('/oauth/backchannel-logout', [SsoController::class, 'backchannel'])->name('sso.backchannel');
Route::view('/privacy', 'privacy')->name('privacy');
Route::view('/privacy.html', 'privacy');

// API aplikasi mobile
Route::prefix('api/m')->name('mobile.')->group(function () {
    Route::post('/auth', [MobileAuth::class, 'auth'])->name('auth');
    Route::post('/refresh-access', [MobileAuth::class, 'refresh'])->name('refresh');
    Route::middleware('mobile')->group(function () {
        Route::post('/fcm-token', [MobileAuth::class, 'fcm'])->name('fcm');
        Route::post('/logout', [MobileAuth::class, 'logout'])->name('logout');
        Route::get('/me', [MobileApi::class, 'me'])->name('me');
        Route::get('/satker', [MobileApi::class, 'satker'])->name('satker');
        Route::get('/surat/masuk', [MobileApi::class, 'suratMasukList'])->name('surat-masuk.index');
        Route::get('/surat/masuk/tujuan-teruskan', [MobileApi::class, 'tujuanTeruskan'])->name('surat-masuk.tujuan');
        Route::post('/surat/masuk/teruskan', [MobileApi::class, 'teruskan'])->name('surat-masuk.teruskan');
        Route::get('/surat/masuk/{suratMasuk}', [MobileApi::class, 'suratMasuk'])->whereNumber('suratMasuk')->name('surat-masuk.show');
        Route::get('/agenda', [MobileApi::class, 'agendaList'])->name('agenda.index');
        Route::get('/agenda/{id}', [MobileApi::class, 'agendaShow'])->whereNumber('id')->name('agenda.show');
        Route::get('/agenda-akan-datang', [MobileApi::class, 'agendaAkanDatang'])->name('agenda.upcoming');
        Route::get('/surat/keluar', [MobileApi::class, 'suratKeluarList'])->name('surat-keluar.index');
        Route::get('/surat/keluar/verifikasi', [MobileApi::class, 'antrean'])->defaults('peran', 'verifikator')->name('surat-keluar.verifikasi');
        Route::get('/surat/keluar/tandatangan', [MobileApi::class, 'antrean'])->defaults('peran', 'penandatangan')->name('surat-keluar.tandatangan');
        Route::post('/surat/keluar/setuju', [MobileApi::class, 'keputusan'])->defaults('action', 'setuju')->name('surat-keluar.setuju');
        Route::post('/surat/keluar/revisi', [MobileApi::class, 'keputusan'])->defaults('action', 'revisi')->name('surat-keluar.revisi');
        Route::post('/surat/keluar/simpan-tandatangan', [MobileApi::class, 'keputusan'])->defaults('action', 'tandatangan')->name('surat-keluar.tandatangan.simpan');
        Route::get('/surat/keluar/{suratKeluar}', [MobileApi::class, 'suratKeluar'])->whereNumber('suratKeluar')->name('surat-keluar.show');
        Route::get('/disposisi', [MobileApi::class, 'disposisiList'])->name('disposisi.index');
        Route::get('/disposisi/tujuan', [MobileApi::class, 'tujuanDisposisi'])->name('disposisi.tujuan');
        Route::post('/disposisi/add', [MobileApi::class, 'disposisiTambah'])->name('disposisi.add');
        Route::post('/disposisi/edit', [MobileApi::class, 'disposisiUbah'])->name('disposisi.edit');
        Route::get('/disposisi/{disposisi}', [MobileApi::class, 'disposisi'])->whereNumber('disposisi')->name('disposisi.show');
    });
});
Route::get('/loadpdf', [MobileApi::class, 'loadpdf'])->middleware('mobile')->name('mobile.loadpdf');

Route::get('/s/{token}', [PublicController::class, 'share'])->name('share.show');
Route::get('/s/{token}/{item}/file', [PublicController::class, 'shareFile'])->name('share.file');
Route::get('/verifikasi/{uuid}', [PublicController::class, 'verify'])->name('verifikasi.show');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Surat masuk & disposisi
    Route::middleware('role:operator,pimpinan,sekpri,user')->group(function () {
        Route::get('/surat-masuk', [SuratMasukController::class, 'index'])->name('surat-masuk.index');
        Route::get('/surat-masuk/disposisi', [SuratMasukController::class, 'inbox'])->name('disposisi.inbox');
        Route::get('/surat-masuk/disposisi-keluar', [SuratMasukController::class, 'outbox'])->name('disposisi.outbox');
        Route::get('/surat-masuk/riwayat-disposisi', [SuratMasukController::class, 'riwayatIndex'])->name('disposisi.riwayat-index');
    });
    Route::middleware('role:operator')->group(function () {
        Route::get('/surat-masuk/create', [SuratMasukController::class, 'create'])->name('surat-masuk.create');
        Route::post('/surat-masuk', [SuratMasukController::class, 'store'])->name('surat-masuk.store');
        Route::get('/surat-masuk/{suratMasuk}/edit', [SuratMasukController::class, 'edit'])->name('surat-masuk.edit');
        Route::put('/surat-masuk/{suratMasuk}', [SuratMasukController::class, 'update'])->name('surat-masuk.update');
        Route::delete('/surat-masuk/{suratMasuk}', [SuratMasukController::class, 'destroy'])->name('surat-masuk.destroy');
        Route::get('/surat-masuk/{suratMasuk}/teruskan', [SuratMasukController::class, 'teruskanForm'])->name('surat-masuk.teruskan.form');
        Route::post('/surat-masuk/{suratMasuk}/teruskan', [SuratMasukController::class, 'teruskan'])->name('surat-masuk.teruskan');
    });
    Route::delete('/surat-masuk/teruskan/{teruskan}', [SuratMasukController::class, 'batalTeruskan'])->name('surat-masuk.teruskan.batal');
    Route::post('/surat-masuk/{suratMasuk}/teruskan-sekpri', [SuratMasukController::class, 'teruskanSekpri'])->name('surat-masuk.teruskan-sekpri');
    Route::get('/surat-masuk/{suratMasuk}', [SuratMasukController::class, 'show'])->name('surat-masuk.show');
    Route::get('/surat-masuk/{suratMasuk}/file', [SuratMasukController::class, 'file'])->name('surat-masuk.file');
    Route::get('/surat-masuk/{suratMasuk}/riwayat-disposisi', [SuratMasukController::class, 'riwayat'])->name('disposisi.riwayat');
    Route::post('/surat-masuk/{suratMasuk}/disposisi', [SuratMasukController::class, 'disposisi'])->name('surat-masuk.disposisi');
    Route::put('/disposisi/{disposisi}', [SuratMasukController::class, 'updateDisposisi'])->name('disposisi.update');
    Route::delete('/disposisi/{disposisi}', [SuratMasukController::class, 'destroyDisposisi'])->name('disposisi.destroy');
    Route::post('/disposisi/{disposisi}/kirim-draft', [SuratMasukController::class, 'kirimDraft'])->name('disposisi.kirim-draft');
    Route::post('/disposisi/{disposisi}/tindak-lanjut', [SuratMasukController::class, 'tindakLanjut'])->name('disposisi.tindak-lanjut');
    Route::post('/surat-masuk/{suratMasuk}/selesai', [SuratMasukController::class, 'selesai'])->name('surat-masuk.selesai');
    Route::get('/surat-masuk/{suratMasuk}/lembar-disposisi', [PelengkapController::class, 'lembarDisposisi'])->name('disposisi.print');
    Route::post('/disposisi/{disposisi}/komentar', [PelengkapController::class, 'comment'])->name('disposisi.comment');
    Route::delete('/disposisi/komentar/{comment}', [PelengkapController::class, 'destroyComment'])->name('disposisi.comment.destroy');
    Route::post('/tags', [PelengkapController::class, 'saveTags'])->name('tags.save');
    Route::post('/template-tujuan-disposisi', [TemplatePribadiController::class, 'disposisiStore'])->name('tujuan-disposisi.store');
    Route::delete('/template-tujuan-disposisi/{tujuan}', [TemplatePribadiController::class, 'disposisiDestroy'])->name('tujuan-disposisi.destroy');

    // Agenda
    Route::middleware('role:admin,operator,pimpinan,sekpri,user')->group(function () {
        Route::resource('agenda', AgendaController::class)->parameters(['agenda' => 'agenda']);
    });

    // Surat keluar
    Route::get('/surat-keluar', [SuratKeluarController::class, 'index'])->name('surat-keluar.index');
    Route::get('/surat-keluar/verifikasi', [SuratKeluarController::class, 'queue'])->defaults('peran', 'verifikator')->name('surat-keluar.queue.verifikasi');
    Route::get('/surat-keluar/tandatangan', [SuratKeluarController::class, 'queue'])->defaults('peran', 'penandatangan')->name('surat-keluar.queue.tandatangan');
    Route::middleware('role:operator,pimpinan,sekpri,user')->group(function () {
        Route::get('/surat-keluar/daftar-nomor', [SuratKeluarExtraController::class, 'daftarNomor'])->name('surat-keluar.daftar-nomor');
        Route::get('/surat-keluar/daftar-nomor/export', [SuratKeluarExtraController::class, 'exportDaftarNomor'])->name('surat-keluar.daftar-nomor.export');
        Route::get('/surat-keluar/arsip/create', [SuratKeluarExtraController::class, 'arsipCreate'])->name('surat-keluar.arsip.create');
        Route::post('/surat-keluar/arsip', [SuratKeluarExtraController::class, 'arsipStore'])->name('surat-keluar.arsip.store');
        Route::get('/master/surat/alur', [TemplatePribadiController::class, 'alurIndex'])->name('alur-surat.index');
        Route::post('/master/surat/alur', [TemplatePribadiController::class, 'alurStore'])->name('alur-surat.store');
        Route::put('/master/surat/alur/{alur}', [TemplatePribadiController::class, 'alurUpdate'])->name('alur-surat.update');
        Route::delete('/master/surat/alur/{alur}', [TemplatePribadiController::class, 'alurDestroy'])->name('alur-surat.destroy');
    });
    Route::match(['post', 'put'], '/surat-keluar/template/preview', [SuratKeluarExtraController::class, 'preview'])->name('surat-keluar.template.preview');
    Route::post('/surat-keluar/autosave', [SuratKeluarController::class, 'autosave'])->name('surat-keluar.autosave');
    Route::delete('/surat-keluar/autosave', [SuratKeluarController::class, 'discardDraft'])->name('surat-keluar.autosave.discard');
    Route::get('/surat-keluar/lampiran/{lampiran}', [SuratKeluarController::class, 'lampiran'])->name('surat-keluar.lampiran.download');
    Route::delete('/surat-keluar/lampiran/{lampiran}', [SuratKeluarController::class, 'destroyLampiran'])->name('surat-keluar.lampiran.destroy');
    Route::get('/surat-keluar/create', [SuratKeluarController::class, 'create'])->name('surat-keluar.create');
    Route::post('/surat-keluar', [SuratKeluarController::class, 'store'])->name('surat-keluar.store');
    Route::get('/surat-keluar/{suratKeluar}', [SuratKeluarController::class, 'show'])->name('surat-keluar.show');
    Route::get('/surat-keluar/{suratKeluar}/edit', [SuratKeluarController::class, 'edit'])->name('surat-keluar.edit');
    Route::put('/surat-keluar/{suratKeluar}', [SuratKeluarController::class, 'update'])->name('surat-keluar.update');
    Route::delete('/surat-keluar/{suratKeluar}', [SuratKeluarController::class, 'destroy'])->name('surat-keluar.destroy');
    Route::post('/surat-keluar/{suratKeluar}/ajukan', [SuratKeluarController::class, 'submit'])->name('surat-keluar.submit');
    Route::post('/surat-keluar/{suratKeluar}/batal-kirim', [SuratKeluarController::class, 'batalKirim'])->name('surat-keluar.batal-kirim');
    Route::post('/surat-keluar/{suratKeluar}/upload', [SuratKeluarController::class, 'reupload'])->name('surat-keluar.reupload');
    Route::post('/surat-keluar/{suratKeluar}/lampiran', [SuratKeluarController::class, 'uploadLampiran'])->name('surat-keluar.lampiran.store');
    Route::post('/surat-keluar/{suratKeluar}/teruskan', [SuratKeluarController::class, 'teruskan'])->name('surat-keluar.teruskan');
    Route::post('/surat-keluar/{suratKeluar}/teruskan-sekpri', [SuratKeluarController::class, 'teruskanSekpri'])->name('surat-keluar.teruskan-sekpri');
    Route::get('/surat-keluar/{suratKeluar}/review', [SuratKeluarController::class, 'review'])->name('surat-keluar.review');
    Route::post('/surat-keluar/{suratKeluar}/keputusan', [SuratKeluarController::class, 'decide'])->name('surat-keluar.decide');
    Route::get('/surat-keluar/{suratKeluar}/tindak-lanjut', [SuratKeluarExtraController::class, 'tindakLanjut'])->name('surat-keluar.tindak-lanjut');
    Route::get('/surat-keluar/{suratKeluar}/file', [SuratKeluarController::class, 'file'])->name('surat-keluar.file');
    Route::get('/surat-keluar/{suratKeluar}/anotasi', [SuratKeluarController::class, 'annotations'])->name('surat-keluar.annotations');
    Route::post('/surat-keluar/{suratKeluar}/anotasi', [SuratKeluarController::class, 'saveAnnotations'])->name('surat-keluar.annotations.save');

    Route::get('/dokumen-saya', [DokumenController::class, 'index'])->name('dokumen.index');
    Route::get('/dokumen-saya/tautan', [DokumenController::class, 'links'])->name('dokumen.links');
    Route::post('/dokumen-saya/tautan', [DokumenController::class, 'share'])->name('dokumen.share');
    Route::delete('/dokumen-saya/tautan/{shareLink}', [DokumenController::class, 'revoke'])->name('dokumen.revoke');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::get('/profil/paraf', [ProfileController::class, 'paraf'])->name('profile.paraf');

    Route::get('/notifikasi/{notification}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::get('/log-aktivitas', [LogController::class, 'index'])->name('logs.index');

    Route::middleware('role:admin,operator')->group(function () {
        Route::get('/users/unique', [PelengkapController::class, 'uniqueUser'])->name('users.unique');
        Route::resource('users', UserController::class)->except(['show']);
    });

    Route::middleware('role:admin,operator,user')->group(function () {
        Route::get('/master/surat/template-tujuan-surat', [TemplatePribadiController::class, 'tujuanIndex'])->name('template-tujuan.index');
        Route::post('/master/surat/template-tujuan-surat', [TemplatePribadiController::class, 'tujuanStore'])->name('template-tujuan.store');
        Route::put('/master/surat/template-tujuan-surat/{tujuan}', [TemplatePribadiController::class, 'tujuanUpdate'])->name('template-tujuan.update');
        Route::delete('/master/surat/template-tujuan-surat/{tujuan}', [TemplatePribadiController::class, 'tujuanDestroy'])->name('template-tujuan.destroy');
    });

    Route::middleware('role:operator')->group(function () {
        Route::resource('master/surat/template-nomor', TemplateNomorController::class)->except(['show'])->parameters(['template-nomor' => 'templateNomor'])->names('template-nomor');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/laporan/rekap-surat-satker', [LaporanController::class, 'index'])->name('laporan.rekap');
        Route::get('/laporan/rekap-surat-satker/export', [LaporanController::class, 'export'])->name('laporan.rekap.export');
        Route::get('/master/administrasi/sdm', [AdministrasiController::class, 'sdm'])->name('sdm.index');
        Route::post('/master/administrasi/sdm/sync', [AdministrasiController::class, 'sync'])->name('sdm.sync');
        Route::get('/master/config', [AdministrasiController::class, 'setting'])->name('setting.index');
        Route::put('/master/config', [AdministrasiController::class, 'saveSetting'])->name('setting.update');
        Route::get('/transfer-surat/hitung', [TransferSuratController::class, 'hitung'])->name('transfer.hitung');
        Route::post('/transfer-surat', [TransferSuratController::class, 'proses'])->name('transfer.proses');
        Route::get('/transfer-surat/{jenis}', [TransferSuratController::class, 'index'])->name('transfer.index');
        Route::resource('master/surat/template', TemplateSuratController::class)->except(['show'])->parameters(['template' => 'templateSurat'])->names('template-surat');
        Route::get('/log-esign', [PelengkapController::class, 'esignLog'])->name('logs.esign');
        Route::get('/master/satker/{satker}/jabatan', [PelengkapController::class, 'satkerJabatan'])->name('satker.jabatan.index');
        Route::post('/master/satker/{satker}/jabatan', [PelengkapController::class, 'satkerJabatanStore'])->name('satker.jabatan.store');
        Route::put('/master/satker/{satker}/jabatan/{jabatan}', [PelengkapController::class, 'satkerJabatanUpdate'])->name('satker.jabatan.update');
        Route::delete('/master/satker/{satker}/jabatan/{jabatan}', [PelengkapController::class, 'satkerJabatanDestroy'])->name('satker.jabatan.destroy');
    });

    Route::middleware('role:admin,operator')->group(function () {
        Route::get('/master/{type}', [MasterController::class, 'index'])->name('master.index');
        Route::post('/master/{type}', [MasterController::class, 'store'])->name('master.store');
        Route::put('/master/{type}/{id}', [MasterController::class, 'update'])->whereNumber('id')->name('master.update');
        Route::delete('/master/{type}/{id}', [MasterController::class, 'destroy'])->whereNumber('id')->name('master.destroy');
    });
});

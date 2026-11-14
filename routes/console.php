<?php

use App\Http\Middleware\RateLimitRequests;
use App\Models\Setting;
use App\Services\NomorCadanganService;
use App\Services\SdmSync;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sync_sdm {--force : Jalankan sekarang tanpa menunggu jam sinkron}', function (SdmSync $sync) {
    $jam = (string) Setting::get('sdm_sync_time');
    if (!$this->option('force') && now()->format('H:i') !== substr($jam, 0, 5)) {
        return 0;
    }
    $res = $sync->run();
    DB::table('activity_logs')->insert(['aksi' => 'job.sync_sdm', 'deskripsi' => $res['message'] . (isset($res['stats']) ? ' ' . json_encode($res['stats']) : ''), 'created_at' => now(), 'updated_at' => now()]);
    $res['ok'] ? $this->info($res['message'] . ' ' . json_encode($res['stats'])) : $this->error($res['message']);
    return $res['ok'] ? 0 : 1;
})->purpose('Sinkronisasi data SDM dari HAIRISMA pada jam di Setting Sistem');

Artisan::command('catat_nomor_cadangan {tanggal? : Tanggal surat (Y-m-d), default kemarin}', function (NomorCadanganService $svc) {
    $tgl = $this->argument('tanggal') ? Carbon::parse($this->argument('tanggal')) : null;
    $res = $svc->catat($tgl);
    DB::table('activity_logs')->insert(['aksi' => 'job.nomor_cadangan', 'deskripsi' => json_encode($res), 'created_at' => now(), 'updated_at' => now()]);
    $this->info($res['status'] === 'libur' ? "{$res['tanggal']} hari libur, dilewati." : "Rentang nomor {$res['tanggal']} dicatat untuk {$res['dicatat']} satker.");
    return 0;
})->purpose('Catat rentang nomor cadangan hari sebelumnya (lewati Sabtu/Minggu/hari libur)');

Artisan::command('cleanup_rate_limits', function () {
    if (config('cache.default') !== 'database') {
        $this->info('Cache bukan database, kuota kedaluwarsa dibersihkan otomatis oleh driver.');
        return 0;
    }
    $n = DB::table(config('cache.stores.database.table', 'cache'))->where('key', 'like', '%' . RateLimitRequests::PREFIX . '%')->where('expiration', '<', time())->delete();
    $this->info(json_encode(['deleted' => $n, 'failed' => 0]));
    return 0;
})->purpose('Hapus data rate limit yang sudah kedaluwarsa');

Schedule::command('sync_sdm')->everyMinute()->withoutOverlapping();
Schedule::command('catat_nomor_cadangan')->dailyAt('00:10');
Schedule::command('cleanup_rate_limits')->hourly();

<?php

namespace Database\Seeders;

use App\Models\HariLibur;
use App\Models\NomorCadangan;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class Tahap3Seeder extends Seeder
{
    public function run(): void
    {
        foreach (Setting::DEFAULTS as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
        $sekper = Satker::where('kode', 'SKP')->first();

        $nrk = ['pimpinan' => '10001', 'sekpri' => '10002', 'kadiv' => '10003', 'staf' => '10004', 'staf2' => '10005'];
        foreach ($nrk as $username => $value) {
            User::where('username', $username)->whereNull('nrk')->update(['nrk' => $value]);
        }

        $year = now()->year;
        foreach (["$year-01-01" => 'Tahun Baru Masehi', "$year-05-01" => 'Hari Buruh Internasional', "$year-08-17" => 'Hari Kemerdekaan RI', "$year-12-25" => 'Hari Raya Natal'] as $tgl => $ket) {
            HariLibur::firstOrCreate(['tanggal' => $tgl], ['keterangan' => $ket]);
        }

        if ($sekper && !NomorCadangan::exists()) {
            NomorCadangan::create(['satker_id' => $sekper->id, 'template_surat_id' => null, 'bulan' => now()->month, 'jumlah' => 5]);
        }
    }
}

<?php

namespace App\Services;

use App\Models\HariLibur;
use App\Models\NomorCadangan;
use App\Models\NomorInisiasi;
use App\Models\RiwayatNomor;
use App\Models\Satker;
use App\Models\SuratKeluar;
use Carbon\Carbon;

/** Port Cli::catat_nomor_cadangan + Calculate_nomor_cadangan (kkgusnam). */
class NomorCadanganService
{
    public const DEFAULT_SPAN = 5;

    /** @return array{status: string, dicatat: int, tanggal: string} */
    public function catat(?Carbon $tanggal = null): array
    {
        $tgl = ($tanggal ?? now()->subDay())->copy()->startOfDay();
        if (HariLibur::isLibur($tgl)) {
            return ['status' => 'libur', 'dicatat' => 0, 'tanggal' => $tgl->toDateString()];
        }
        $count = 0;
        foreach (Satker::orderBy('id')->get() as $satker) {
            $inisiasi = NomorInisiasi::where('satker_id', $satker->id)->where('tahun', $tgl->year)->first();
            if (!$inisiasi || RiwayatNomor::where('satker_id', $satker->id)->whereDate('tanggal', $tgl->toDateString())->exists()) {
                continue;
            }
            $span = NomorCadangan::where('satker_id', $satker->id)->where('bulan', $tgl->month)->orderByRaw('CASE WHEN template_surat_id IS NULL THEN 0 ELSE 1 END')->value('jumlah') ?? self::DEFAULT_SPAN;
            $terakhir = (int) SuratKeluar::withTrashed()->where('satker_nomor_id', $satker->id)->whereYear('tanggal_surat', $tgl->year)->whereDate('tanggal_surat', '<=', $tgl->toDateString())->max('no_urut');
            $sebelum = RiwayatNomor::where('satker_id', $satker->id)->whereYear('tanggal', $tgl->year)->whereDate('tanggal', '<', $tgl->toDateString())->latest('tanggal')->first();
            $baseline = max($terakhir, (int) $sebelum?->no_max, (int) $inisiasi->nomor_awal - 1);
            RiwayatNomor::create(['satker_id' => $satker->id, 'tanggal' => $tgl->toDateString(), 'no_akhir' => $baseline, 'no_max' => $baseline + (int) $span]);
            $count++;
        }
        return ['status' => 'ok', 'dicatat' => $count, 'tanggal' => $tgl->toDateString()];
    }

    /** Nomor cadangan pertama yang masih kosong untuk surat bertanggal mundur. */
    public function reserved(int $satkerId, Carbon $tanggal): ?int
    {
        $riwayat = RiwayatNomor::where('satker_id', $satkerId)->whereYear('tanggal', $tanggal->year)->whereDate('tanggal', '<=', $tanggal->toDateString())->latest('tanggal')->first();
        if (!$riwayat || $riwayat->no_max <= $riwayat->no_akhir) {
            return null;
        }
        $used = SuratKeluar::withTrashed()->where('satker_nomor_id', $satkerId)->whereYear('tanggal_surat', $tanggal->year)
            ->whereBetween('no_urut', [$riwayat->no_akhir + 1, $riwayat->no_max])->pluck('no_urut')->all();
        for ($n = $riwayat->no_akhir + 1; $n <= $riwayat->no_max; $n++) {
            if (!in_array($n, $used)) {
                return $n;
            }
        }
        return null;
    }

    public function maxReserved(int $satkerId, int $year): int
    {
        return (int) RiwayatNomor::where('satker_id', $satkerId)->whereYear('tanggal', $year)->max('no_max');
    }
}

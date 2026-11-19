<?php

namespace App\Services;

use App\Models\Disposisi;
use App\Models\SuratKeluar;
use App\Models\SuratMasukTeruskan;
use App\Models\User;
use App\Support\Activity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Port Api_surat_controller::kalkulasi_transfer_surat / proses_transfer_surat (kkgusnam). */
class TransferSurat
{
    private function range(string $awal, string $akhir): array
    {
        return [Carbon::parse($awal)->startOfDay(), Carbon::parse($akhir)->endOfDay()];
    }

    public function hitung(string $jenis, int $asalId, string $awal, string $akhir): array
    {
        $r = $this->range($awal, $akhir);
        return $jenis === 'keluar'
            ? ['surat_keluar' => SuratKeluar::where('created_by', $asalId)->whereBetween('created_at', $r)->count()]
            : [
                'disposisi' => Disposisi::where('ke_user_id', $asalId)->whereBetween('created_at', $r)->count(),
                'surat_diteruskan' => SuratMasukTeruskan::where('user_id', $asalId)->where('status', 'aktif')->whereBetween('created_at', $r)->count(),
            ];
    }

    public function proses(string $jenis, User $asal, User $tujuan, string $awal, string $akhir): array
    {
        $r = $this->range($awal, $akhir);
        return DB::transaction(function () use ($jenis, $asal, $tujuan, $r) {
            if ($jenis === 'keluar') {
                $q = SuratKeluar::where('created_by', $asal->id)->whereBetween('created_at', $r);
                $hasil = ['surat_keluar' => (clone $q)->count()];
                $q->update(['created_by' => $tujuan->id] + ($asal->satker_id !== $tujuan->satker_id && $tujuan->satker_id ? ['satker_id' => $tujuan->satker_id] : []));
            } else {
                $hasil = [
                    'disposisi' => Disposisi::where('ke_user_id', $asal->id)->whereBetween('created_at', $r)->update(['ke_user_id' => $tujuan->id]),
                    'surat_diteruskan' => SuratMasukTeruskan::where('user_id', $asal->id)->where('status', 'aktif')->whereBetween('created_at', $r)
                        ->update(['user_id' => $tujuan->id] + ($tujuan->satker_id ? ['satker_id' => $tujuan->satker_id] : [])),
                ];
            }
            Activity::log('transfer_surat.' . $jenis, "Transfer surat {$jenis} dari {$asal->name} ke {$tujuan->name} (" . json_encode($hasil) . ')');
            return $hasil;
        });
    }
}

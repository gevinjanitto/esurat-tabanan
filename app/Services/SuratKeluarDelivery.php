<?php

namespace App\Services;

use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Facades\Storage;

class SuratKeluarDelivery
{
    public function send(SuratKeluar $surat): void
    {
        $surat->loadMissing(['tujuans', 'satker', 'creator.satker']);
        if ($surat->terkirim_at || $surat->status !== SuratKeluar::SELESAI || $surat->tujuans->isEmpty()) {
            return;
        }
        $pengirim = $surat->satker?->nama ?? $surat->creator->satker?->nama ?? $surat->creator->name;
        foreach ($surat->tujuans->unique('satker_id') as $tujuan) {
            $path = null;
            if ($surat->currentFile() && Storage::disk('local')->exists($surat->currentFile())) {
                $path = 'surat-masuk/internal-' . $surat->id . '-' . $tujuan->satker_id . '.pdf';
                Storage::disk('local')->put($path, Storage::disk('local')->get($surat->currentFile()));
            }
            $masuk = SuratMasuk::create([
                'nomor_agenda' => SuratMasuk::nextAgenda(), 'nomor_surat' => $surat->nomor_surat ?: '-',
                'tanggal_surat' => $surat->tanggal_surat ?: now(), 'tanggal_terima' => now(), 'pengirim' => $pengirim,
                'perihal' => $surat->perihal, 'ringkasan' => $surat->ringkasan, 'sifat_id' => $surat->sifat_id,
                'klasifikasi_id' => $surat->klasifikasi_id, 'klasifikasi_keamanan_id' => $surat->klasifikasi_keamanan_id,
                'file_path' => $path, 'file_name' => $path ? str($surat->perihal)->slug() . '.pdf' : null,
                'status' => 'baru', 'jenis' => 'internal', 'satker_id' => $tujuan->satker_id, 'surat_keluar_id' => $surat->id,
                'created_by' => $surat->created_by,
            ]);
            $teruskan = $masuk->teruskans()->create(['tipe' => 'divisi', 'satker_id' => $tujuan->satker_id, 'dari_user_id' => $surat->created_by]);
            $operators = User::where('role', 'operator')->where('satker_id', $tujuan->satker_id)->where('is_active', true)->pluck('id');
            Activity::notify(collect($teruskan->recipientIds())->merge($operators), 'Surat masuk internal', $surat->perihal, route('surat-masuk.show', $masuk));
        }
        $surat->update(['terkirim_at' => now()]);
        $surat->riwayats()->create(['user_id' => auth()->id(), 'aksi' => 'terkirim', 'catatan' => 'Dikirim ke ' . $surat->tujuans->pluck('satker.nama')->unique()->join(', '), 'revisi' => $surat->revisi]);
        Activity::log('surat_keluar.kirim', "Surat terkirim ke penerima internal: {$surat->perihal}");
    }
}

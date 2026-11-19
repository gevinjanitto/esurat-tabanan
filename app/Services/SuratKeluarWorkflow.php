<?php

namespace App\Services;

use App\Models\SuratKeluar;
use App\Models\SuratKeluarAlur;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Facades\DB;

class SuratKeluarWorkflow
{
    public function __construct(private TteService $tte, private SuratKeluarDelivery $delivery, private NomorSuratService $nomor, private TemplateSuratRenderer $renderer) {}

    public function submit(SuratKeluar $surat): void
    {
        DB::transaction(function () use ($surat) {
            $surat = SuratKeluar::lockForUpdate()->find($surat->id);
            $wasRevision = in_array($surat->status, [SuratKeluar::REVISI_VERIFIKASI, SuratKeluar::REVISI_TTD], true);
            if ($wasRevision) {
                $surat->revisi++;
                $surat->signed_path = null;
                $surat->signed_at = null;
            }
            $surat->alurs()->update(['status' => 0, 'acted_at' => null, 'catatan' => null, 'status_sekpri' => null]);
            $gated = User::where('role', 'sekpri')->where('is_active', true)->whereNotNull('atasan_id')->pluck('atasan_id');
            $surat->alurs()->whereIn('user_id', $gated)->update(['status_sekpri' => 1]);
            $verifiers = $surat->alurs()->where('peran', 'verifikator')->orderBy('urut')->get();
            $signers = $surat->alurs()->where('peran', 'penandatangan')->orderBy('urut')->get();

            if ($verifiers->isNotEmpty()) {
                $surat->status = SuratKeluar::VERIFIKASI;
                $surat->urut_alur = $verifiers->first()->urut;
                $recipients = $surat->metode_verifikasi === 'berjenjang' ? [$verifiers->first()->user_id] : $verifiers->pluck('user_id');
                $judul = 'Surat perlu diverifikasi';
            } else {
                $surat->status = SuratKeluar::TANDATANGAN;
                $surat->urut_alur = $signers->first()->urut;
                $recipients = [$signers->first()->user_id];
                $judul = 'Surat perlu ditandatangani';
            }
            $this->nomor->assign($surat);
            $surat->save();
            if ($surat->template_surat_id) {
                $this->renderer->render($surat);
            }
            $this->history($surat, $wasRevision ? 'diajukan_ulang' : 'diajukan');
            $this->notifyTurn($surat, $recipients, $judul);
            Activity::log('surat_keluar.ajukan', "Mengajukan surat keluar: {$surat->perihal}");
        });
    }

    public function withdraw(SuratKeluar $surat): void
    {
        DB::transaction(function () use ($surat) {
            $surat->alurs()->update(['status' => 0, 'acted_at' => null, 'catatan' => null, 'status_sekpri' => null]);
            $surat->update(['status' => SuratKeluar::DRAFT, 'urut_alur' => null]);
            $this->history($surat, 'batal_kirim');
            Activity::log('surat_keluar.batal_kirim', "Membatalkan pengiriman surat: {$surat->perihal}");
        });
    }

    public function turnRow(SuratKeluar $surat, int $userId): ?SuratKeluarAlur
    {
        $peran = match ($surat->status) {
            SuratKeluar::VERIFIKASI => 'verifikator',
            SuratKeluar::TANDATANGAN => 'penandatangan',
            default => null,
        };
        if (!$peran) {
            return null;
        }
        $row = $surat->alurs()->where('peran', $peran)->where('user_id', $userId)->where('status', 0)->orderBy('urut')->first();
        if (!$row) {
            return null;
        }
        $sequential = $peran === 'penandatangan' || $surat->metode_verifikasi === 'berjenjang';
        return (!$sequential || $row->urut === $surat->urut_alur) ? $row : null;
    }

    public function assignment(SuratKeluar $surat, User $user): ?SuratKeluarAlur
    {
        $row = $this->turnRow($surat, $user->id);
        return $row && $row->status_sekpri !== 1 ? $row : null;
    }

    public function sekpriAssignment(SuratKeluar $surat, User $user): ?SuratKeluarAlur
    {
        if (!$user->isSekpri()) {
            return null;
        }
        $row = $this->turnRow($surat, $user->atasan_id);
        return $row && $row->status_sekpri === 1 ? $row : null;
    }

    public function forwardFromSekpri(SuratKeluar $surat, SuratKeluarAlur $row, ?string $catatan): void
    {
        $row->update(['status_sekpri' => 2]);
        $this->history($surat, 'diteruskan_sekpri', $catatan);
        Activity::notify([$row->user_id], $row->peran === 'penandatangan' ? 'Surat perlu ditandatangani' : 'Surat perlu diverifikasi', $surat->perihal, route('surat-keluar.review', $surat));
        Activity::log('surat_keluar.teruskan_sekpri', "Meneruskan surat ke pimpinan: {$surat->perihal}");
    }

    public function forward(SuratKeluar $surat, SuratKeluarAlur $row, User $target, ?string $catatan): void
    {
        DB::transaction(function () use ($surat, $row, $target, $catatan) {
            $surat->alurs()->where('peran', 'verifikator')->where('urut', '>', $row->urut)->increment('urut');
            $hasSekpri = $target->sekpris()->exists();
            $new = $surat->alurs()->create([
                'user_id' => $target->id, 'peran' => 'verifikator', 'urut' => $row->urut + 1,
                'diteruskan_dari_id' => $row->id, 'status_sekpri' => $hasSekpri ? 1 : null,
            ]);
            $this->history($surat, 'diteruskan', trim("Kepada {$target->name}. " . ($catatan ?? '')));
            $this->verify($surat, $row, true, $catatan ?: "Diteruskan ke {$target->name}");
            if ($surat->metode_verifikasi !== 'berjenjang') {
                $this->notifyTurn($surat->fresh(), [$new->user_id], 'Surat perlu diverifikasi');
            }
        });
    }

    public function verify(SuratKeluar $surat, SuratKeluarAlur $row, bool $approve, ?string $catatan): void
    {
        DB::transaction(function () use ($surat, $row, $approve, $catatan) {
            $row->update(['status' => $approve ? 1 : 2, 'catatan' => $catatan, 'acted_at' => now()]);
            $this->history($surat, $approve ? 'disetujui' : 'revisi', $catatan);
            $verifiers = $surat->alurs()->where('peran', 'verifikator')->orderBy('urut')->get();
            $pending = $verifiers->where('status', 0);
            $hasRevision = $verifiers->where('status', 2)->isNotEmpty();
            $berjenjang = $surat->metode_verifikasi === 'berjenjang';

            if (!$approve && $berjenjang || ($pending->isEmpty() && $hasRevision)) {
                $surat->update(['status' => SuratKeluar::REVISI_VERIFIKASI, 'urut_alur' => null]);
                Activity::notify([$surat->created_by], 'Surat perlu direvisi', $surat->perihal, route('surat-keluar.show', $surat));
            } elseif ($pending->isNotEmpty()) {
                if ($berjenjang) {
                    $next = $pending->first();
                    $surat->update(['urut_alur' => $next->urut]);
                    $this->notifyTurn($surat, [$next->user_id], 'Surat perlu diverifikasi');
                }
            } else {
                $signer = $surat->alurs()->where('peran', 'penandatangan')->orderBy('urut')->first();
                $surat->update(['status' => SuratKeluar::TANDATANGAN, 'urut_alur' => $signer->urut]);
                $this->notifyTurn($surat, [$signer->user_id], 'Surat perlu ditandatangani');
            }
            Activity::log($approve ? 'surat_keluar.verifikasi' : 'surat_keluar.revisi', "{$surat->perihal}" . ($catatan ? " — {$catatan}" : ''));
        });
    }

    public function sign(SuratKeluar $surat, SuratKeluarAlur $row, User $signer, ?string $scanPath = null): void
    {
        DB::transaction(function () use ($surat, $row, $signer, $scanPath) {
            $next = $surat->alurs()->where('peran', 'penandatangan')->where('status', 0)->where('id', '!=', $row->id)->orderBy('urut')->first();
            if (!$next && !$surat->nomor_surat && !$this->nomor->withoutNumber($surat)) {
                $surat->nomor_surat = $this->nomor->assign($surat) ?: $this->generateNomor($surat);
            }
            $surat->tanggal_surat = $surat->tanggal_surat ?: now();
            $surat->signed_path = $scanPath ?: $this->tte->sign($surat, $signer);
            $surat->signed_at = now();
            $row->update(['status' => 1, 'acted_at' => now()]);
            if ($next) {
                $surat->urut_alur = $next->urut;
                $this->notifyTurn($surat, [$next->user_id], 'Surat perlu ditandatangani');
            } else {
                $surat->status = SuratKeluar::SELESAI;
                $surat->urut_alur = null;
                Activity::notify([$surat->created_by], 'Surat telah ditandatangani', $surat->perihal, route('surat-keluar.show', $surat));
            }
            $surat->save();
            $this->history($surat, 'ditandatangani');
            \App\Models\EsignLog::create(['surat_keluar_id' => $surat->id, 'user_id' => $signer->id, 'status' => 'berhasil', 'dokumen' => $surat->nomor_surat ?: $surat->perihal, 'keterangan' => ($scanPath ? 'Scan tanda tangan basah diunggah (' : 'TTE dibubuhkan (') . ($next ? 'menunggu penandatangan berikutnya' : 'penandatangan terakhir') . ')', 'ip' => request()->ip()]);
            Activity::log('tte.tandatangan', "Menandatangani surat: {$surat->perihal}");
            if (!$next) {
                $this->delivery->send($surat);
            }
        });
    }

    public function signerRevision(SuratKeluar $surat, SuratKeluarAlur $row, ?string $catatan): void
    {
        $row->update(['status' => 2, 'catatan' => $catatan, 'acted_at' => now()]);
        $surat->update(['status' => SuratKeluar::REVISI_TTD, 'urut_alur' => null]);
        $this->history($surat, 'revisi', $catatan);
        Activity::notify([$surat->created_by], 'Surat dikembalikan penandatangan', $surat->perihal, route('surat-keluar.show', $surat));
        Activity::log('surat_keluar.revisi', "{$surat->perihal} — {$catatan}");
    }

    public function history(SuratKeluar $surat, string $aksi, ?string $catatan = null): void
    {
        $surat->riwayats()->create(['user_id' => auth()->id(), 'aksi' => $aksi, 'catatan' => $catatan, 'revisi' => $surat->revisi]);
    }

    private function notifyTurn(SuratKeluar $surat, iterable $userIds, string $judul): void
    {
        foreach (collect($userIds) as $uid) {
            $gated = $surat->alurs()->where('user_id', $uid)->where('status', 0)->where('status_sekpri', 1)->exists();
            if ($gated) {
                $sekpri = User::where('atasan_id', $uid)->where('role', 'sekpri')->where('is_active', true)->pluck('id');
                Activity::notify($sekpri, 'Surat untuk pimpinan menunggu diteruskan', $surat->perihal, route('surat-keluar.show', $surat));
            } else {
                Activity::notify([$uid], $judul, $surat->perihal, route('surat-keluar.review', $surat));
            }
        }
    }

    private function generateNomor(SuratKeluar $surat): string
    {
        $seq = SuratKeluar::withTrashed()->whereYear('signed_at', now()->year)->whereNotNull('nomor_surat')->count() + 1;
        $parts = [str_pad((string) $seq, 3, '0', STR_PAD_LEFT), $surat->klasifikasi?->kode ?? 'UM', $surat->satker?->kode ?? 'TBN', now()->year];
        return implode('/', $parts);
    }
}

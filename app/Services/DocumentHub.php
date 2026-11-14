<?php

namespace App\Services;

use App\Models\Disposisi;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\User;
use Illuminate\Support\Collection;

class DocumentHub
{
    public const RELASI = [
        'dibuat' => 'Saya buat',
        'diverifikasi' => 'Saya verifikasi',
        'ditandatangani' => 'Saya tandatangani',
        'ditugaskan' => 'Menunggu saya',
        'disposisi' => 'Disposisi',
        'dicatat' => 'Saya catat',
    ];

    public function forUser(User $user): Collection
    {
        $items = collect();

        $keluar = SuratKeluar::with(['alurs', 'sifat'])->where(fn ($q) => $q->where('created_by', $user->id)
            ->orWhereHas('alurs', fn ($a) => $a->where('user_id', $user->id)))->latest()->get();
        foreach ($keluar as $s) {
            $rel = [];
            if ($s->created_by === $user->id) {
                $rel[] = 'dibuat';
            }
            foreach ($s->alurs->where('user_id', $user->id) as $a) {
                if ($a->status === 1) {
                    $rel[] = $a->peran === 'penandatangan' ? 'ditandatangani' : 'diverifikasi';
                } elseif ($a->status === 0 && in_array($s->status, [1, 10], true)) {
                    $rel[] = 'ditugaskan';
                } elseif ($a->status === 2) {
                    $rel[] = 'diverifikasi';
                }
            }
            $items->push($this->item('keluar', $s, $s->nomor_surat ?: 'Belum bernomor', $s->tanggal_surat ?? $s->created_at, $s->status_label, $s->status_color, array_values(array_unique($rel)), (bool) $s->currentFile()));
        }

        $masukIds = Disposisi::where('ke_user_id', $user->id)->orWhere('dari_user_id', $user->id)->pluck('surat_masuk_id');
        $masuk = SuratMasuk::with('sifat')->whereIn('id', $masukIds)->orWhere('created_by', $user->id)->latest()->get();
        foreach ($masuk as $s) {
            $rel = $masukIds->contains($s->id) ? ['disposisi'] : [];
            if ($s->created_by === $user->id) {
                $rel[] = 'dicatat';
            }
            $color = ['baru' => 'sky', 'disposisi' => 'amber', 'selesai' => 'ruby'][$s->status] ?? 'slate';
            $items->push($this->item('masuk', $s, $s->nomor_surat, $s->tanggal_surat, $s->status_label, $color, $rel, (bool) $s->file_path));
        }

        return $items->sortByDesc('sort')->values();
    }

    public function canAccess(User $user, string $type, int $id): bool
    {
        return $this->forUser($user)->contains(fn ($i) => $i['type'] === $type && $i['id'] === $id);
    }

    private function item(string $type, $model, ?string $nomor, $tanggal, string $status, string $color, array $relasi, bool $hasFile): array
    {
        return [
            'key' => "{$type}-{$model->id}",
            'type' => $type,
            'id' => $model->id,
            'perihal' => $model->perihal,
            'nomor' => $nomor,
            'tanggal' => $tanggal,
            'sort' => optional($model->updated_at)->timestamp,
            'status' => $status,
            'color' => $color,
            'relasi' => $relasi,
            'has_file' => $hasFile,
            'url' => $type === 'keluar' ? route('surat-keluar.show', $model) : route('surat-masuk.show', $model),
            'file_url' => $type === 'keluar' ? route('surat-keluar.file', $model) : route('surat-masuk.file', $model),
        ];
    }
}

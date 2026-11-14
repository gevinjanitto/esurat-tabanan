<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratKeluarAlur extends Model
{
    public const STATUS = [0 => 'Menunggu', 1 => 'Disetujui', 2 => 'Minta Revisi'];

    protected $fillable = ['surat_keluar_id', 'user_id', 'peran', 'urut', 'status', 'status_sekpri', 'diteruskan_dari_id', 'catatan', 'acted_at'];

    protected function casts(): array
    {
        return ['acted_at' => 'datetime', 'status' => 'integer', 'status_sekpri' => 'integer', 'urut' => 'integer'];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function surat() { return $this->belongsTo(SuratKeluar::class, 'surat_keluar_id'); }
}

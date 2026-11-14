<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratKeluarRiwayat extends Model
{
    protected $fillable = ['surat_keluar_id', 'user_id', 'aksi', 'catatan', 'revisi'];

    public function user() { return $this->belongsTo(User::class); }
}

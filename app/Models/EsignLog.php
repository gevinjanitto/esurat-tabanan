<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EsignLog extends Model
{
    protected $fillable = ['surat_keluar_id', 'user_id', 'status', 'dokumen', 'keterangan', 'ip'];

    public function surat() { return $this->belongsTo(SuratKeluar::class, 'surat_keluar_id')->withTrashed(); }
    public function user() { return $this->belongsTo(User::class); }
}

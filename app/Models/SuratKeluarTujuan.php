<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratKeluarTujuan extends Model
{
    protected $fillable = ['surat_keluar_id', 'satker_id', 'jenis'];

    public function satker() { return $this->belongsTo(Satker::class); }
}

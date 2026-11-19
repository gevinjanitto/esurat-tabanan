<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratKeluarLampiran extends Model
{
    protected $fillable = ['surat_keluar_id', 'file_path', 'file_name', 'size', 'uploaded_by'];

    public function surat() { return $this->belongsTo(SuratKeluar::class, 'surat_keluar_id'); }
}

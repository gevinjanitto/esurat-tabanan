<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NomorCadangan extends Model
{
    public const BULAN = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    protected $fillable = ['satker_id', 'template_surat_id', 'bulan', 'jumlah'];

    public function satker() { return $this->belongsTo(Satker::class); }
    public function templateSurat() { return $this->belongsTo(TemplateSurat::class); }
}

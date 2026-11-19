<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateSuratIsian extends Model
{
    protected $fillable = ['template_surat_id', 'nama', 'kode', 'jenis', 'urut'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratAnnotation extends Model
{
    protected $fillable = ['surat_keluar_id', 'user_id', 'revisi', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function user() { return $this->belongsTo(User::class); }
}

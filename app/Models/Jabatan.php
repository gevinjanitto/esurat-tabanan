<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jabatan extends Model
{
    protected $fillable = ['nama', 'satker_id', 'is_pimpinan', 'sync_sdm'];

    protected function casts(): array
    {
        return ['is_pimpinan' => 'boolean'];
    }

    public function satker() { return $this->belongsTo(Satker::class); }
}

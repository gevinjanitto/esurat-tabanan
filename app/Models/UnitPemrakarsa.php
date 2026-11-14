<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitPemrakarsa extends Model
{
    protected $fillable = ['satker_id', 'kode', 'nama', 'status'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function satker() { return $this->belongsTo(Satker::class); }
}

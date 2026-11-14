<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlurSurat extends Model
{
    protected $fillable = ['user_id', 'judul', 'metode_verifikasi', 'verifikator', 'penandatangan'];

    protected function casts(): array
    {
        return ['verifikator' => 'array', 'penandatangan' => 'array'];
    }

    public function user() { return $this->belongsTo(User::class); }
}

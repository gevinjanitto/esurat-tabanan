<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateTujuanSurat extends Model
{
    protected $fillable = ['user_id', 'nama', 'tujuan', 'tembusan'];

    protected function casts(): array
    {
        return ['tujuan' => 'array', 'tembusan' => 'array'];
    }
}

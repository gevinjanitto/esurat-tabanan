<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateTujuanDisposisi extends Model
{
    protected $fillable = ['user_id', 'nama', 'penerima'];

    protected function casts(): array
    {
        return ['penerima' => 'array'];
    }
}

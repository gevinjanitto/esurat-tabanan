<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormDraft extends Model
{
    protected $fillable = ['user_id', 'key', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}

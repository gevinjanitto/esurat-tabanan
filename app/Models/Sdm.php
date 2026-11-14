<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sdm extends Model
{
    protected $fillable = ['nrk', 'nama', 'unit', 'satuan_kerja', 'sub_unit', 'jabatan', 'email', 'status_cuti'];

    protected function casts(): array
    {
        return ['status_cuti' => 'boolean'];
    }
}

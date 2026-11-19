<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupOpd extends Model
{
    protected $fillable = ['nama'];

    public function satkers() { return $this->hasMany(Satker::class)->orderBy('nama'); }
}

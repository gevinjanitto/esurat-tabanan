<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NomorInisiasi extends Model
{
    protected $fillable = ['satker_id', 'tahun', 'nomor_awal'];

    public function satker() { return $this->belongsTo(Satker::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatNomor extends Model
{
    protected $fillable = ['satker_id', 'tanggal', 'no_akhir', 'no_max'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function setTanggalAttribute($value): void
    {
        $this->attributes['tanggal'] = \Carbon\Carbon::parse($value)->toDateString();
    }

    public function satker() { return $this->belongsTo(Satker::class); }
}

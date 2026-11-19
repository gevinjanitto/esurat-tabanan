<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    protected $fillable = ['tanggal', 'keterangan'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function setTanggalAttribute($value): void
    {
        $this->attributes['tanggal'] = \Carbon\Carbon::parse($value)->toDateString();
    }

    public static function isLibur(CarbonInterface $date): bool
    {
        return $date->isWeekend() || static::whereDate('tanggal', $date->toDateString())->exists();
    }
}

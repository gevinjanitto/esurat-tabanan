<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaptchaStat extends Model
{
    protected $fillable = ['tanggal', 'success', 'failed'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function setTanggalAttribute($value): void
    {
        $this->attributes['tanggal'] = \Carbon\Carbon::parse($value)->toDateString();
    }

    public static function record(bool $ok): void
    {
        $row = static::firstOrCreate(['tanggal' => now()->toDateString()], ['success' => 0, 'failed' => 0]);
        $row->increment($ok ? 'success' : 'failed');
    }
}

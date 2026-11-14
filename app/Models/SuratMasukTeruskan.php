<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratMasukTeruskan extends Model
{
    public const TIPE = ['direksi' => 'Direksi', 'divisi' => 'Divisi / Satker', 'jabatan' => 'Jabatan', 'sekpri' => 'Sekpri'];

    protected $fillable = ['surat_masuk_id', 'tipe', 'satker_id', 'user_id', 'dari_user_id', 'parent_id', 'status', 'dibaca_at'];

    protected function casts(): array
    {
        return ['dibaca_at' => 'datetime'];
    }

    public function surat() { return $this->belongsTo(SuratMasuk::class, 'surat_masuk_id'); }
    public function satker() { return $this->belongsTo(Satker::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function dari() { return $this->belongsTo(User::class, 'dari_user_id'); }

    public function scopeAktif($q) { return $q->where('status', 'aktif'); }

    public function getTargetLabelAttribute(): string
    {
        return $this->user?->name ?? $this->satker?->nama ?? '-';
    }

    public function recipientIds(): array
    {
        if ($this->user_id) {
            return [$this->user_id];
        }
        return User::where('satker_id', $this->satker_id)->where('disposisi_awal', true)->where('is_active', true)->pluck('id')->all();
    }
}

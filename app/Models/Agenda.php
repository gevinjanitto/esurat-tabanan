<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agenda extends Model
{
    public const METODE = ['offline' => 'Tatap muka', 'online' => 'Daring', 'hybrid' => 'Hybrid'];

    protected $fillable = ['surat_masuk_id', 'disposisi_id', 'user_id', 'judul', 'deskripsi', 'metode', 'tempat', 'link_meeting', 'mulai', 'selesai'];

    protected function casts(): array
    {
        return ['mulai' => 'datetime', 'selesai' => 'datetime'];
    }

    public function surat() { return $this->belongsTo(SuratMasuk::class, 'surat_masuk_id'); }
    public function disposisi() { return $this->belongsTo(Disposisi::class); }
    public function user() { return $this->belongsTo(User::class); }

    public function getSumberLabelAttribute(): string
    {
        return $this->disposisi_id ? 'Dari disposisi' : ($this->surat_masuk_id ? 'Dari surat masuk' : 'Agenda mandiri');
    }

    public function scopeVisibleTo($q, User $user)
    {
        return $q;
    }
}

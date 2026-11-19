<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disposisi extends Model
{
    public const STATUS = ['terkirim' => 'Belum dibaca', 'dibaca' => 'Dibaca', 'ditindaklanjuti' => 'Ditindaklanjuti'];

    protected $fillable = ['surat_masuk_id', 'parent_id', 'dari_user_id', 'ke_user_id', 'instruksi', 'catatan', 'batas_waktu', 'status', 'is_draft', 'drafted_by', 'dibaca_at', 'tindak_lanjut'];

    protected function casts(): array
    {
        return ['batas_waktu' => 'date', 'dibaca_at' => 'datetime', 'is_draft' => 'boolean'];
    }

    public function surat() { return $this->belongsTo(SuratMasuk::class, 'surat_masuk_id'); }
    public function dari() { return $this->belongsTo(User::class, 'dari_user_id'); }
    public function ke() { return $this->belongsTo(User::class, 'ke_user_id'); }
    public function drafter() { return $this->belongsTo(User::class, 'drafted_by'); }
    public function parent() { return $this->belongsTo(Disposisi::class, 'parent_id'); }
    public function children() { return $this->hasMany(Disposisi::class, 'parent_id')->where('is_draft', false); }

    public function comments() { return $this->hasMany(DisposisiComment::class)->orderBy('id'); }

    public function scopeTerkirim($q) { return $q->where('is_draft', false); }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_draft ? 'Draft Sekpri' : (self::STATUS[$this->status] ?? $this->status);
    }

    public function isMutable(): bool
    {
        return $this->status !== 'ditindaklanjuti' && !$this->children()->exists();
    }

    public function canManage(User $user): bool
    {
        return $this->dari_user_id === $user->id || ($this->is_draft && $this->drafted_by === $user->id);
    }
}

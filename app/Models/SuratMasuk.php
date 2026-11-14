<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuratMasuk extends Model
{
    use SoftDeletes;

    public const STATUS = ['baru' => 'Baru', 'disposisi' => 'Didisposisi', 'selesai' => 'Selesai'];

    protected $fillable = ['nomor_agenda', 'nomor_surat', 'tanggal_surat', 'tanggal_terima', 'pengirim', 'perihal', 'ringkasan', 'sifat_id', 'klasifikasi_id', 'klasifikasi_keamanan_id', 'file_path', 'file_name', 'status', 'jenis', 'satker_id', 'surat_keluar_id', 'created_by'];

    protected function casts(): array
    {
        return ['tanggal_surat' => 'date', 'tanggal_terima' => 'date'];
    }

    public function sifat() { return $this->belongsTo(Sifat::class); }
    public function klasifikasi() { return $this->belongsTo(Klasifikasi::class); }
    public function keamanan() { return $this->belongsTo(KlasifikasiKeamanan::class, 'klasifikasi_keamanan_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function satker() { return $this->belongsTo(Satker::class); }
    public function suratKeluar() { return $this->belongsTo(SuratKeluar::class); }
    public function disposisis() { return $this->hasMany(Disposisi::class)->orderBy('created_at')->orderBy('id'); }
    public function agendas() { return $this->hasMany(Agenda::class)->orderBy('mulai'); }
    public function teruskans() { return $this->hasMany(SuratMasukTeruskan::class)->orderBy('id'); }

    public static function nextAgenda(): string
    {
        return sprintf('AG-%s-%04d', now()->format('Y'), static::withTrashed()->whereYear('created_at', now()->year)->count() + 1);
    }

    public function scopeTeruskanTo($q, User $user)
    {
        return $q->whereHas('teruskans', fn ($t) => $t->aktif()->where(fn ($w) => $w->where('user_id', $user->id)
            ->when($user->disposisi_awal && $user->satker_id, fn ($x) => $x->orWhere(fn ($y) => $y->whereNull('user_id')->where('satker_id', $user->satker_id)))));
    }

    public function scopeVisibleTo($q, User $user)
    {
        if ($user->hasRole('admin', 'operator')) {
            return $q;
        }
        return $q->where(fn ($w) => $w
            ->whereHas('disposisis', fn ($d) => $d->where(fn ($x) => $x->where('ke_user_id', $user->id)->orWhere('dari_user_id', $user->id)->orWhere('drafted_by', $user->id))
                ->where(fn ($x) => $x->where('is_draft', false)->orWhere('dari_user_id', $user->id)->orWhere('drafted_by', $user->id)))
            ->orWhere(fn ($x) => $x->teruskanTo($user)));
    }

    public function isVisibleTo(User $user): bool
    {
        return static::visibleTo($user)->whereKey($this->id)->exists();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }
}

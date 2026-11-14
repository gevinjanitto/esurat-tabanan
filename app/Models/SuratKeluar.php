<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SuratKeluar extends Model
{
    use SoftDeletes;

    public const DRAFT = 0;
    public const VERIFIKASI = 1;
    public const SELESAI = 2;
    public const TANDATANGAN = 10;
    public const REVISI_VERIFIKASI = 11;
    public const REVISI_TTD = 12;

    public const STATUS = [
        0 => ['Konsep', 'slate'],
        1 => ['Proses Verifikasi', 'sky'],
        10 => ['Menunggu Tanda Tangan', 'amber'],
        11 => ['Revisi dari Verifikator', 'rose'],
        12 => ['Revisi dari Penandatangan', 'rose'],
        2 => ['Selesai / Ditandatangani', 'ruby'],
    ];

    public const METODE_TTD = ['elektronik' => 'Tandatangan Elektronik', 'basah' => 'Tandatangan Basah'];

    protected $fillable = ['nomor_surat', 'tanggal_surat', 'perihal', 'tujuan', 'ringkasan', 'klasifikasi_id', 'sifat_id', 'klasifikasi_keamanan_id', 'satker_id', 'metode_verifikasi', 'metode_ttd', 'status', 'urut_alur', 'revisi', 'file_path', 'file_name', 'signed_path', 'signed_at', 'terkirim_at', 'created_by', 'template_surat_id', 'isian', 'lampiran_keterangan', 'template_nomor_id', 'unit_pemrakarsa_id', 'satker_nomor_id', 'no_urut', 'is_arsip'];

    protected function casts(): array
    {
        return ['tanggal_surat' => 'date', 'signed_at' => 'datetime', 'terkirim_at' => 'datetime', 'status' => 'integer', 'urut_alur' => 'integer', 'revisi' => 'integer', 'isian' => 'array', 'is_arsip' => 'boolean', 'no_urut' => 'integer'];
    }

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->uuid = $m->uuid ?: (string) Str::uuid());
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function klasifikasi() { return $this->belongsTo(Klasifikasi::class); }
    public function sifat() { return $this->belongsTo(Sifat::class); }
    public function keamanan() { return $this->belongsTo(KlasifikasiKeamanan::class, 'klasifikasi_keamanan_id'); }
    public function satker() { return $this->belongsTo(Satker::class); }
    public function alurs() { return $this->hasMany(SuratKeluarAlur::class)->orderBy('peran', 'desc')->orderBy('urut'); }
    public function riwayats() { return $this->hasMany(SuratKeluarRiwayat::class)->latest('id'); }
    public function annotations() { return $this->hasMany(SuratAnnotation::class); }
    public function lampirans() { return $this->hasMany(SuratKeluarLampiran::class)->orderBy('id'); }
    public function templateSurat() { return $this->belongsTo(TemplateSurat::class); }
    public function templateNomor() { return $this->belongsTo(TemplateNomor::class); }
    public function unitPemrakarsa() { return $this->belongsTo(UnitPemrakarsa::class); }
    public function suratMasuks() { return $this->hasMany(SuratMasuk::class); }
    public function tujuans() { return $this->hasMany(SuratKeluarTujuan::class)->orderBy('id'); }

    public function scopeVisibleTo($q, User $user)
    {
        if ($user->hasRole('admin', 'operator')) {
            return $q;
        }
        $ids = array_filter([$user->id, $user->isSekpri() ? $user->atasan_id : null]);
        return $q->where(fn ($w) => $w->where('created_by', $user->id)
            ->orWhereHas('alurs', fn ($a) => $a->whereIn('user_id', $ids)));
    }

    public function isVisibleTo(User $user): bool
    {
        return static::visibleTo($user)->whereKey($this->id)->exists();
    }

    public function getStatusLabelAttribute(): string { return $this->terkirim_at ? 'Selesai & Terkirim' : (self::STATUS[$this->status][0] ?? '-'); }
    public function getStatusColorAttribute(): string { return self::STATUS[$this->status][1] ?? 'slate'; }

    public function isWithdrawable(): bool
    {
        return in_array($this->status, [self::VERIFIKASI, self::TANDATANGAN], true)
            && !$this->alurs()->where('status', '!=', 0)->exists()
            && !$this->alurs()->where('status_sekpri', 2)->exists();
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::DRAFT, self::REVISI_VERIFIKASI, self::REVISI_TTD], true);
    }

    public function currentFile(): ?string
    {
        return $this->signed_path ?: $this->file_path;
    }
}

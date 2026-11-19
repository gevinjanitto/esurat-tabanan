<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateSurat extends Model
{
    public const COUNTER = ['satker_uker' => 'Satker/Uker', 'sekper' => 'Sekper', 'sisdur' => 'Sisdur', 'tanpa_nomor' => 'Tidak Ada Penomoran'];
    public const JENIS_ISIAN = ['teks' => 'Teks singkat', 'textarea' => 'Paragraf', 'tanggal' => 'Tanggal', 'angka' => 'Angka'];
    public const SYS = ['SYS_SIFAT' => 'Sifat surat', 'SYS_KLASIFIKASI' => 'Klasifikasi surat', 'SYS_NO_SURAT' => 'Nomor surat', 'SYS_TGL_SURAT' => 'Tanggal surat', 'SYS_LAMPIRAN' => 'Lampiran', 'SYS_PERIHAL' => 'Perihal', 'SYS_TUJUAN' => 'Tujuan surat'];

    protected $fillable = ['nama', 'deskripsi', 'counter_penomoran', 'kop', 'isi', 'status'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function isians() { return $this->hasMany(TemplateSuratIsian::class)->orderBy('urut')->orderBy('id'); }
    public function scopeAktif($q) { return $q->where('status', true); }
}

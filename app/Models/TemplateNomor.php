<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateNomor extends Model
{
    public const SEGMENTS = [
        'klasifikasi_keamanan' => ['Klasifikasi Keamanan', 'B'],
        'nomor' => ['Nomor Urut', '0001'],
        'klasifikasi' => ['Klasifikasi', 'UM'],
        'unit_kerja' => ['Kode Identitas Unit Kerja', 'DIV-UMM'],
        'unit_pemrakarsa' => ['Unit Pemrakarsa', 'HUMAS'],
        'bulan' => ['Bulan (Romawi)', 'VI'],
        'tahun' => ['Tahun', '2026'],
        'custom_i' => ['Kustom I', 'KUSTOM'],
        'custom_ii' => ['Kustom II', 'KUSTOM'],
    ];
    public const TAMBAHAN = ['angka' => 'Angka (0001.1)', 'romawi' => 'Romawi (0001.I)', 'huruf' => 'Huruf (0001.A)'];
    public const FREE_TEXT = ['unit_kerja', 'custom_i', 'custom_ii'];

    protected $fillable = ['nama', 'template_surat_id', 'satker_id', 'segments', 'nomor_tambahan', 'padding', 'created_by'];

    protected function casts(): array
    {
        return ['segments' => 'array', 'padding' => 'integer'];
    }

    public static function samples(): array
    {
        return array_merge(array_map(fn ($s) => $s[1], self::SEGMENTS), ['bulan' => \App\Services\NomorSuratService::roman(now()->month), 'tahun' => (string) now()->year]);
    }

    public function templateSurat() { return $this->belongsTo(TemplateSurat::class); }
    public function satker() { return $this->belongsTo(Satker::class); }

    public function getSampleAttribute(): string
    {
        $samples = self::samples();
        return collect($this->segments)->map(fn ($s) => in_array($s['type'], self::FREE_TEXT, true) ? ($s['value'] ?? '') : ($samples[$s['type']] ?? ''))
            ->map(fn ($v, $i) => $this->segments[$i]['type'] === 'nomor' ? str_pad('1', $this->padding ?: 4, '0', STR_PAD_LEFT) : $v)->filter(fn ($v) => $v !== '')->join('/');
    }
}

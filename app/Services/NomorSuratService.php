<?php

namespace App\Services;

use App\Models\NomorInisiasi;
use App\Models\Setting;
use App\Models\SuratKeluar;
use App\Models\TemplateNomor;

class NomorSuratService
{
    public function __construct(private NomorCadanganService $cadangan) {}

    /** Satker counter: Sekper/Sisdur terpusat (Setting Sistem) atau satker pembuat. */
    public function satkerPenomoran(SuratKeluar $surat): ?int
    {
        $own = $surat->satker_id ?: $surat->creator?->satker_id;
        $counter = $surat->templateSurat?->counter_penomoran;
        $central = in_array($counter, ['sekper', 'sisdur'], true) ? Setting::get("satker_penomoran_{$counter}") : null;
        return $central ? (int) $central : $own;
    }

    public function templateFor(SuratKeluar $surat): ?TemplateNomor
    {
        if ($surat->template_nomor_id) {
            return $surat->templateNomor;
        }
        $satker = $surat->satker_id ?: $surat->creator?->satker_id;
        return TemplateNomor::query()
            ->where(fn ($q) => $q->whereNull('template_surat_id')->when($surat->template_surat_id, fn ($w) => $w->orWhere('template_surat_id', $surat->template_surat_id)))
            ->where(fn ($q) => $q->whereNull('satker_id')->when($satker, fn ($w) => $w->orWhere('satker_id', $satker)))
            ->orderByRaw('CASE WHEN template_surat_id IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN satker_id IS NULL THEN 1 ELSE 0 END')
            ->first();
    }

    public function withoutNumber(SuratKeluar $surat): bool
    {
        return $surat->templateSurat?->counter_penomoran === 'tanpa_nomor';
    }

    public function assign(SuratKeluar $surat): ?string
    {
        if ($surat->nomor_surat || $this->withoutNumber($surat)) {
            return $surat->nomor_surat;
        }
        $tpl = $this->templateFor($surat);
        if (!$tpl) {
            return null;
        }
        $satkerId = $this->satkerPenomoran($surat);
        $tanggal = $surat->tanggal_surat ?: now();
        $year = (int) $tanggal->format('Y');
        $next = $tanggal->lt(now()->startOfDay()) ? $this->cadangan->reserved($satkerId, $tanggal->copy()) : null;
        if (!$next) {
            $max = SuratKeluar::withTrashed()->where('satker_nomor_id', $satkerId)->whereYear('tanggal_surat', $year)->max('no_urut');
            $awal = NomorInisiasi::where('satker_id', $satkerId)->where('tahun', $year)->value('nomor_awal') ?? 1;
            $next = max(((int) $max) + 1, (int) $awal, $this->cadangan->maxReserved($satkerId, $year) + 1);
        }

        $surat->fill(['tanggal_surat' => $tanggal, 'no_urut' => $next, 'satker_nomor_id' => $satkerId, 'template_nomor_id' => $tpl->id]);
        $surat->nomor_surat = $this->format($tpl, $surat, $next);
        return $surat->nomor_surat;
    }

    public function format(TemplateNomor $tpl, SuratKeluar $surat, int $urut): string
    {
        $tanggal = $surat->tanggal_surat ?: now();
        return collect($tpl->segments)->map(fn ($s) => match ($s['type']) {
            'nomor' => str_pad((string) $urut, $tpl->padding ?: 4, '0', STR_PAD_LEFT),
            'klasifikasi_keamanan' => $surat->keamanan?->kode,
            'klasifikasi' => $surat->klasifikasi?->kode,
            'unit_pemrakarsa' => $surat->unitPemrakarsa?->kode,
            'bulan' => self::roman((int) $tanggal->format('n')),
            'tahun' => $tanggal->format('Y'),
            default => $s['value'] ?? null,
        })->filter(fn ($v) => filled($v))->join('/');
    }

    public static function roman(int $n): string
    {
        $map = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
        $out = '';
        foreach ($map as $r => $v) {
            while ($n >= $v) {
                $out .= $r;
                $n -= $v;
            }
        }
        return $out;
    }
}

<?php

namespace App\Services;

use App\Models\SuratKeluar;
use App\Models\TemplateSurat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class TemplateSuratRenderer
{
    public function values(SuratKeluar $surat, TemplateSurat $template): array
    {
        $isian = $surat->isian ?? [];
        $vals = [];
        foreach ($template->isians as $f) {
            $v = (string) ($isian[$f->kode] ?? '');
            $vals[$f->kode] = match ($f->jenis) {
                'tanggal' => $v !== '' ? Carbon::parse($v)->translatedFormat('d F Y') : '',
                'textarea' => nl2br(e($v)),
                default => e($v),
            };
        }
        $sys = [
            'SYS_SIFAT' => $surat->sifat?->nama, 'SYS_KLASIFIKASI' => $surat->klasifikasi?->nama,
            'SYS_NO_SURAT' => $surat->nomor_surat ?: '……………………', 'SYS_TGL_SURAT' => ($surat->tanggal_surat ?: now())->translatedFormat('d F Y'),
            'SYS_LAMPIRAN' => $surat->lampiran_keterangan ?: '-', 'SYS_PERIHAL' => $surat->perihal, 'SYS_TUJUAN' => $surat->tujuan,
        ];
        foreach ($sys as $k => $v) {
            $vals[$k] = nl2br(e((string) $v));
        }
        return $vals;
    }

    public function fill(?string $html, array $vals): string
    {
        return preg_replace_callback('/\$\{\s*([A-Za-z0-9_]+)\s*\}/', fn ($m) => $vals[$m[1]] ?? $m[0], (string) $html);
    }

    public function html(SuratKeluar $surat, TemplateSurat $template): string
    {
        $vals = $this->values($surat, $template->loadMissing('isians'));
        return view('pdf.template-surat', ['kop' => $this->fill($template->kop, $vals), 'isi' => $this->fill($template->isi, $vals), 'judul' => $surat->perihal])->render();
    }

    public function render(SuratKeluar $surat): void
    {
        $template = $surat->templateSurat;
        if (!$template) {
            return;
        }
        $pdf = Pdf::loadHTML($this->html($surat->loadMissing(['sifat', 'klasifikasi']), $template))->setPaper('a4')->output();
        $old = $surat->file_path;
        $path = 'surat-keluar/source/template-' . $surat->id . '-' . now()->format('YmdHis') . '-' . str()->random(5) . '.pdf';
        Storage::disk('local')->put($path, $pdf);
        $surat->forceFill(['file_path' => $path, 'file_name' => str($surat->perihal)->slug() . '.pdf'])->save();
        if ($old && $old !== $path && str_contains($old, '/template-')) {
            Storage::disk('local')->delete($old);
        }
    }
}

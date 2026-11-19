<?php

namespace App\Http\Controllers;

use App\Models\Klasifikasi;
use App\Models\KlasifikasiKeamanan;
use App\Models\Satker;
use App\Models\Sifat;
use App\Models\SuratKeluar;
use App\Models\TemplateSurat;
use App\Models\UnitPemrakarsa;
use App\Services\TemplateSuratRenderer;
use App\Support\Activity;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SuratKeluarExtraController extends Controller
{
    public function preview(Request $request, TemplateSuratRenderer $renderer)
    {
        $data = $request->validate(['template_surat_id' => 'required|exists:template_surats,id', 'isian' => 'nullable|array', 'perihal' => 'nullable|string|max:255', 'tujuan' => 'nullable|string|max:2000',
            'sifat_id' => 'nullable|exists:sifats,id', 'klasifikasi_id' => 'nullable|exists:klasifikasis,id', 'lampiran_keterangan' => 'nullable|string|max:100', 'tanggal_surat' => 'nullable|date']);
        $template = TemplateSurat::with('isians')->findOrFail($data['template_surat_id']);
        $surat = new SuratKeluar(collect($data)->except(['isian', 'template_surat_id'])->all() + ['perihal' => '']);
        $surat->isian = collect($data['isian'] ?? [])->map(fn ($v) => (string) $v)->all();
        return Pdf::loadHTML($renderer->html($surat, $template))->setPaper('a4')->stream('pratinjau-' . str($template->nama)->slug() . '.pdf');
    }

    private function nomorQuery(Request $request)
    {
        $user = $request->user();
        $q = SuratKeluar::with(['creator', 'satker', 'klasifikasi', 'sifat'])->visibleTo($user)->whereNotNull('nomor_surat')->orderByDesc('tanggal_surat')->orderByDesc('id');
        if ($from = $request->date('dari')) {
            $q->whereDate('tanggal_surat', '>=', $from);
        }
        if ($to = $request->date('sampai')) {
            $q->whereDate('tanggal_surat', '<=', $to);
        }
        if ($s = $request->string('q')->trim()->value()) {
            $q->where(fn ($w) => $w->where('nomor_surat', 'like', "%$s%")->orWhere('perihal', 'like', "%$s%"));
        }
        return $q;
    }

    public function daftarNomor(Request $request)
    {
        return view('surat-keluar.daftar-nomor', ['surats' => $this->nomorQuery($request)->paginate(20)->withQueryString()]);
    }

    public function exportDaftarNomor(Request $request)
    {
        $rows = $this->nomorQuery($request)->get();
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('Daftar Nomor');
        $sheet->fromArray(['No', 'Nomor Surat', 'Tanggal Surat', 'Perihal', 'Tujuan', 'Klasifikasi', 'Sifat', 'Satker', 'Pembuat', 'Status'], null, 'A1');
        foreach ($rows as $i => $s) {
            $sheet->fromArray([$i + 1, $s->nomor_surat, $s->tanggal_surat?->format('d/m/Y'), $s->perihal, $s->tujuan, $s->klasifikasi?->nama, $s->sifat?->nama, $s->satker?->nama, $s->creator?->name, $s->is_arsip ? 'Arsip' : $s->status_label], null, 'A' . ($i + 2));
        }
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        Activity::log('surat_keluar.export_nomor', 'Mengekspor daftar nomor surat keluar');
        $name = 'daftar-nomor-surat-keluar-' . now()->format('Ymd-His') . '.xlsx';
        return response()->streamDownload(fn () => (new Xlsx($book))->save('php://output'), $name, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function tindakLanjut(Request $request, SuratKeluar $suratKeluar)
    {
        abort_unless($suratKeluar->isVisibleTo($request->user()), 403);
        $suratKeluar->load(['tujuans.satker', 'suratMasuks.satker', 'suratMasuks.teruskans.user', 'suratMasuks.teruskans.satker', 'suratMasuks.disposisis.dari', 'suratMasuks.disposisis.ke']);
        return view('surat-keluar.tindak-lanjut', ['surat' => $suratKeluar]);
    }

    public function arsipCreate(Request $request)
    {
        abort_if($request->user()->hasRole('admin'), 403);
        return view('surat-keluar.arsip', [
            'sifats' => Sifat::orderBy('nama')->get(), 'klasifikasis' => Klasifikasi::orderBy('kode')->get(), 'keamanans' => KlasifikasiKeamanan::orderBy('kode')->get(),
            'satkers' => Satker::orderBy('nama')->get(), 'unitPemrakarsas' => UnitPemrakarsa::where('status', true)->orderBy('nama')->get(),
        ]);
    }

    public function arsipStore(Request $request)
    {
        abort_if($request->user()->hasRole('admin'), 403);
        $data = $request->validate([
            'nomor_surat' => 'required|string|max:255', 'tanggal_surat' => 'required|date|before_or_equal:today', 'perihal' => 'required|string|max:255',
            'tujuan' => 'required|string|max:2000', 'ringkasan' => 'nullable|string|max:5000', 'klasifikasi_id' => 'nullable|exists:klasifikasis,id',
            'sifat_id' => 'nullable|exists:sifats,id', 'klasifikasi_keamanan_id' => 'nullable|exists:klasifikasi_keamanans,id',
            'satker_id' => 'nullable|exists:satkers,id', 'unit_pemrakarsa_id' => 'nullable|exists:unit_pemrakarsas,id', 'file' => 'required|file|mimes:pdf|max:20480',
        ]);
        $file = $request->file('file');
        $path = $file->store('surat-keluar/arsip', 'local');
        $surat = SuratKeluar::create(collect($data)->except('file')->all() + [
            'status' => SuratKeluar::SELESAI, 'is_arsip' => true, 'revisi' => 0, 'created_by' => $request->user()->id, 'metode_verifikasi' => 'bersamaan',
            'file_path' => $path, 'file_name' => $file->getClientOriginalName(), 'signed_path' => $path, 'signed_at' => $data['tanggal_surat'],
        ]);
        $surat->riwayats()->create(['user_id' => $request->user()->id, 'aksi' => 'arsip', 'catatan' => 'Diinput sebagai arsip surat keluar', 'revisi' => 0]);
        Activity::log('surat_keluar.arsip', "Menginput arsip surat keluar: {$surat->nomor_surat}");
        return redirect()->route('surat-keluar.show', $surat)->with('success', 'Arsip surat keluar disimpan.');
    }
}

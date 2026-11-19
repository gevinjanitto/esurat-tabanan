<?php

namespace App\Http\Controllers;

use App\Models\Disposisi;
use App\Models\Satker;
use App\Models\SuratKeluar;
use App\Models\SuratMasukTeruskan;
use App\Support\Activity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanController extends Controller
{
    private function periode(Request $request): array
    {
        $request->validate(['tgl_awal' => 'nullable|date', 'tgl_akhir' => 'nullable|date|after_or_equal:tgl_awal']);
        return [
            Carbon::parse($request->input('tgl_awal', now()->startOfMonth()->toDateString()))->startOfDay(),
            Carbon::parse($request->input('tgl_akhir', now()->toDateString()))->endOfDay(),
        ];
    }

    /** Rekap per satker: surat masuk (diterima satker), surat keluar (dibuat satker), disposisi (dikirim pegawai satker). */
    private function rekap(array $r)
    {
        $masuk = SuratMasukTeruskan::where('status', 'aktif')->whereBetween('created_at', $r)->whereNotNull('satker_id')
            ->groupBy('satker_id')->select('satker_id', DB::raw('COUNT(DISTINCT surat_masuk_id) as n'))->pluck('n', 'satker_id');
        $keluar = SuratKeluar::where('status', '!=', SuratKeluar::DRAFT)->whereBetween('created_at', $r)->whereNotNull('satker_id')
            ->groupBy('satker_id')->select('satker_id', DB::raw('COUNT(*) as n'))->pluck('n', 'satker_id');
        $disposisi = Disposisi::terkirim()->whereBetween('disposisis.created_at', $r)->join('users', 'users.id', '=', 'disposisis.dari_user_id')
            ->whereNotNull('users.satker_id')->groupBy('users.satker_id')->select('users.satker_id', DB::raw('COUNT(*) as n'))->pluck('n', 'satker_id');
        return Satker::orderBy('nama')->get()->map(fn ($s) => (object) [
            'satker' => $s->nama, 'kode' => $s->kode,
            'masuk' => (int) ($masuk[$s->id] ?? 0), 'keluar' => (int) ($keluar[$s->id] ?? 0), 'disposisi' => (int) ($disposisi[$s->id] ?? 0),
        ]);
    }

    public function index(Request $request)
    {
        $r = $this->periode($request);
        $rows = $this->rekap($r);
        return view('laporan.rekap', ['rows' => $rows, 'awal' => $r[0], 'akhir' => $r[1]]);
    }

    public function export(Request $request)
    {
        $r = $this->periode($request);
        $rows = $this->rekap($r);
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('Rekap Surat');
        $sheet->setCellValue('A1', 'Rekap Jumlah Surat per Satuan Kerja');
        $sheet->setCellValue('A2', 'Periode ' . $r[0]->format('d/m/Y') . ' s.d. ' . $r[1]->format('d/m/Y'));
        $sheet->fromArray(['No', 'Kode', 'Satuan Kerja', 'Surat Masuk', 'Surat Keluar', 'Disposisi', 'Total'], null, 'A4');
        foreach ($rows->values() as $i => $x) {
            $sheet->fromArray([$i + 1, $x->kode, $x->satker, $x->masuk, $x->keluar, $x->disposisi, $x->masuk + $x->keluar + $x->disposisi], null, 'A' . ($i + 5));
        }
        $end = $rows->count() + 5;
        $sheet->fromArray(['', '', 'TOTAL', $rows->sum('masuk'), $rows->sum('keluar'), $rows->sum('disposisi'), $rows->sum(fn ($x) => $x->masuk + $x->keluar + $x->disposisi)], null, 'A' . $end);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A4:G4')->getFont()->setBold(true);
        $sheet->getStyle("A{$end}:G{$end}")->getFont()->setBold(true);
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        Activity::log('laporan.export', 'Mengekspor rekap jumlah surat per satker');
        $name = 'rekap-surat-satker-' . $r[0]->format('Ymd') . '-' . $r[1]->format('Ymd') . '.xlsx';
        return response()->streamDownload(fn () => (new Xlsx($book))->save('php://output'), $name, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}

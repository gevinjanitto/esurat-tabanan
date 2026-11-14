<?php

namespace App\Http\Controllers;

use App\Models\TemplateSurat;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TemplateSuratController extends Controller
{
    public function index(Request $request)
    {
        $q = TemplateSurat::withCount('isians')->orderBy('nama');
        if ($s = $request->string('q')->trim()->value()) {
            $q->where('nama', 'like', "%$s%");
        }
        return view('master.template-surat.index', ['templates' => $q->paginate(15)->withQueryString()]);
    }

    public function create()
    {
        return view('master.template-surat.form', ['template' => new TemplateSurat(['status' => true, 'counter_penomoran' => 'satker_uker'])]);
    }

    public function store(Request $request)
    {
        $template = DB::transaction(function () use ($request) {
            [$data, $isian] = $this->validated($request);
            $template = TemplateSurat::create($data);
            $this->syncIsian($template, $isian);
            return $template;
        });
        Activity::log('master.template_surat.tambah', "Menambah template surat {$template->nama}");
        return redirect()->route('template-surat.index')->with('success', 'Template surat ditambahkan.');
    }

    public function edit(TemplateSurat $templateSurat)
    {
        return view('master.template-surat.form', ['template' => $templateSurat->load('isians')]);
    }

    public function update(Request $request, TemplateSurat $templateSurat)
    {
        DB::transaction(function () use ($request, $templateSurat) {
            [$data, $isian] = $this->validated($request);
            $templateSurat->update($data);
            $this->syncIsian($templateSurat, $isian);
        });
        Activity::log('master.template_surat.ubah', "Mengubah template surat {$templateSurat->nama}");
        return redirect()->route('template-surat.index')->with('success', 'Template surat diperbarui.');
    }

    public function destroy(TemplateSurat $templateSurat)
    {
        $templateSurat->delete();
        Activity::log('master.template_surat.hapus', "Menghapus template surat {$templateSurat->nama}");
        return back()->with('success', 'Template surat dihapus.');
    }

    private function validated(Request $request): array
    {
        $request->merge(['isian' => collect($request->input('isian', []))->map(fn ($r) => ['nama' => $r['nama'] ?? null, 'kode' => strtoupper(trim((string) ($r['kode'] ?? ''))), 'jenis' => $r['jenis'] ?? 'teks'])->filter(fn ($r) => filled($r['nama']) || filled($r['kode']))->values()->all()]);
        $data = $request->validate([
            'nama' => 'required|string|max:200',
            'deskripsi' => 'nullable|string|max:2000',
            'counter_penomoran' => ['required', Rule::in(array_keys(TemplateSurat::COUNTER))],
            'kop' => 'nullable|string|max:200000',
            'isi' => 'required|string|max:500000',
            'isian' => 'array|max:60',
            'isian.*.nama' => 'required|string|max:150',
            'isian.*.kode' => ['required', 'distinct', 'regex:/^[A-Z0-9_]+$/', 'not_regex:/^SYS_/', 'max:60'],
            'isian.*.jenis' => ['required', Rule::in(array_keys(TemplateSurat::JENIS_ISIAN))],
        ], ['isian.*.kode.regex' => 'Kode isian hanya huruf besar, angka, dan garis bawah.', 'isian.*.kode.not_regex' => 'Awalan SYS_ dipakai untuk kode isian sistem.', 'isian.*.kode.distinct' => 'Kode isian tidak boleh sama.']);
        $base = collect($data)->only(['nama', 'deskripsi', 'counter_penomoran', 'kop', 'isi'])->all() + ['status' => $request->boolean('status')];
        return [$base, $data['isian'] ?? []];
    }

    private function syncIsian(TemplateSurat $template, array $isian): void
    {
        $template->isians()->delete();
        foreach ($isian as $i => $row) {
            $template->isians()->create($row + ['urut' => $i + 1]);
        }
    }
}

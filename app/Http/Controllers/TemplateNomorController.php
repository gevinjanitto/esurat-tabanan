<?php

namespace App\Http\Controllers;

use App\Models\Satker;
use App\Models\TemplateNomor;
use App\Models\TemplateSurat;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TemplateNomorController extends Controller
{
    public function index()
    {
        return view('master.template-nomor.index', ['rows' => TemplateNomor::with(['templateSurat', 'satker'])->latest('id')->paginate(15)]);
    }

    public function create()
    {
        return view('master.template-nomor.form', ['row' => new TemplateNomor(['segments' => [['type' => 'nomor'], ['type' => 'klasifikasi'], ['type' => 'bulan'], ['type' => 'tahun']], 'padding' => 4, 'nomor_tambahan' => 'angka'])] + $this->options());
    }

    public function store(Request $request)
    {
        $row = TemplateNomor::create($this->validated($request) + ['created_by' => $request->user()->id]);
        Activity::log('master.template_nomor.tambah', "Menambah template nomor {$row->nama}");
        return redirect()->route('template-nomor.index')->with('success', 'Template nomor surat ditambahkan.');
    }

    public function edit(TemplateNomor $templateNomor)
    {
        return view('master.template-nomor.form', ['row' => $templateNomor] + $this->options());
    }

    public function update(Request $request, TemplateNomor $templateNomor)
    {
        $templateNomor->update($this->validated($request));
        Activity::log('master.template_nomor.ubah', "Mengubah template nomor {$templateNomor->nama}");
        return redirect()->route('template-nomor.index')->with('success', 'Template nomor surat diperbarui.');
    }

    public function destroy(TemplateNomor $templateNomor)
    {
        $templateNomor->delete();
        Activity::log('master.template_nomor.hapus', "Menghapus template nomor {$templateNomor->nama}");
        return back()->with('success', 'Template nomor dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'nama' => 'required|string|max:150',
            'template_surat_id' => 'nullable|exists:template_surats,id',
            'satker_id' => 'nullable|exists:satkers,id',
            'nomor_tambahan' => ['required', Rule::in(array_keys(TemplateNomor::TAMBAHAN))],
            'padding' => 'required|integer|between:1,8',
            'segments' => 'required|array|min:1|max:12',
            'segments.*.type' => ['required', 'distinct', Rule::in(array_keys(TemplateNomor::SEGMENTS))],
            'segments.*.value' => 'nullable|string|max:40',
        ], ['segments.*.type.distinct' => 'Setiap bagian nomor hanya boleh dipakai sekali.']);
        $segments = collect($data['segments'])->map(fn ($s) => in_array($s['type'], TemplateNomor::FREE_TEXT, true) ? ['type' => $s['type'], 'value' => trim((string) ($s['value'] ?? ''))] : ['type' => $s['type']])->values();
        abort_unless($segments->contains('type', 'nomor'), 422, 'Format wajib memuat Nomor Urut.');
        $data['segments'] = $segments->all();
        return $data;
    }

    private function options(): array
    {
        return ['templates' => TemplateSurat::orderBy('nama')->get(), 'satkers' => Satker::orderBy('nama')->get()];
    }
}

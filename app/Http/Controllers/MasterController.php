<?php

namespace App\Http\Controllers;

use App\Models\HariLibur;
use App\Models\Jabatan;
use App\Models\NomorCadangan;
use App\Models\TemplateSurat;
use App\Models\NomorInisiasi;
use App\Models\UnitPemrakarsa;
use App\Models\Klasifikasi;
use App\Models\KlasifikasiKeamanan;
use App\Models\Satker;
use App\Models\Sifat;
use App\Models\SubjekDisposisi;
use App\Support\Activity;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    private function config(string $type): array
    {
        $types = [
            'satker' => ['Satuan Kerja', Satker::class, ['kode' => 'Kode', 'nama' => 'Nama Satker', 'kategori' => 'Kategori', 'parent_id' => 'Induk']],
            'jabatan' => ['Jabatan', Jabatan::class, ['nama' => 'Nama Jabatan', 'satker_id' => 'Satker', 'is_pimpinan' => 'Pimpinan?']],
            'klasifikasi' => ['Klasifikasi Surat', Klasifikasi::class, ['kode' => 'Kode', 'nama' => 'Nama Klasifikasi']],
            'sifat' => ['Sifat Surat', Sifat::class, ['nama' => 'Nama Sifat', 'warna' => 'Warna']],
            'klasifikasi-keamanan' => ['Klasifikasi Keamanan', KlasifikasiKeamanan::class, ['kode' => 'Kode', 'nama' => 'Nama']],
            'subjek-disposisi' => ['Subjek Disposisi', SubjekDisposisi::class, ['nama' => 'Subjek / Instruksi Baku']],
            'unit-pemrakarsa' => ['Unit Pemrakarsa', UnitPemrakarsa::class, ['kode' => 'Kode', 'nama' => 'Nama Unit Pemrakarsa', 'satker_id' => 'Satker', 'status' => 'Aktif?']],
            'hari-libur' => ['Hari Libur', HariLibur::class, ['tanggal' => 'Tanggal', 'keterangan' => 'Keterangan'], ['admin'], 'keterangan', 'tanggal'],
            'nomor-cadangan' => ['Nomor Cadangan', NomorCadangan::class, ['satker_id' => 'Satker', 'template_surat_id' => 'Jenis Surat', 'bulan' => 'Bulan', 'jumlah' => 'Jumlah Nomor Cadangan'], ['operator'], 'bulan'],
            'nomor-inisiasi' => ['Nomor Inisiasi', NomorInisiasi::class, ['satker_id' => 'Satker', 'tahun' => 'Tahun', 'nomor_awal' => 'Nomor Awal'], ['admin', 'operator'], 'tahun'],
        ];
        abort_unless(isset($types[$type]), 404);
        abort_unless(auth()->user()->hasRole(...($types[$type][3] ?? ['admin'])), 403);
        return $types[$type];
    }

    public function index(Request $request, string $type)
    {
        $cfg = $this->config($type);
        [$title, $model, $fields] = $cfg;
        $q = $model::query()->latest($cfg[5] ?? 'id');
        if ($s = $request->string('q')->trim()->value()) {
            $q->where($cfg[4] ?? 'nama', 'like', "%$s%");
        }
        return view('master.index', [
            'type' => $type, 'title' => $title, 'fields' => $fields, 'rows' => $q->paginate(15)->withQueryString(),
            'satkers' => Satker::orderBy('nama')->get(),
            'templates' => $type === 'nomor-cadangan' ? TemplateSurat::orderBy('nama')->get() : collect(),
        ]);
    }

    public function store(Request $request, string $type)
    {
        [$title, $model] = $this->config($type);
        $model::create($this->rules($request, $type));
        Activity::log('master.tambah', "Menambah data {$title}");
        return back()->with('success', "{$title} ditambahkan.");
    }

    public function update(Request $request, string $type, int $id)
    {
        [$title, $model] = $this->config($type);
        $model::findOrFail($id)->update($this->rules($request, $type));
        Activity::log('master.ubah', "Mengubah data {$title} #{$id}");
        return back()->with('success', "{$title} diperbarui.");
    }

    public function destroy(string $type, int $id)
    {
        [$title, $model] = $this->config($type);
        try {
            $model::findOrFail($id)->delete();
        } catch (\Illuminate\Database\QueryException) {
            return back()->with('error', 'Data masih dipakai dan tidak bisa dihapus.');
        }
        Activity::log('master.hapus', "Menghapus data {$title} #{$id}");
        return back()->with('success', "{$title} dihapus.");
    }

    private function rules(Request $request, string $type): array
    {
        $data = $request->validate(match ($type) {
            'satker' => ['kode' => 'required|string|max:30', 'nama' => 'required|string|max:255', 'kategori' => 'required|in:' . implode(',', array_keys(Satker::KATEGORI)), 'parent_id' => 'nullable|exists:satkers,id'],
            'subjek-disposisi' => ['nama' => 'required|string|max:100'],
            'jabatan' => ['nama' => 'required|string|max:255', 'satker_id' => 'nullable|exists:satkers,id', 'is_pimpinan' => 'nullable|boolean'],
            'sifat' => ['nama' => 'required|string|max:255', 'warna' => 'required|in:ruby,sky,amber,rose,slate'],
            'unit-pemrakarsa' => ['kode' => 'required|string|max:30', 'nama' => 'required|string|max:255', 'satker_id' => 'nullable|exists:satkers,id'],
            'hari-libur' => ['tanggal' => ['required', 'date', \Illuminate\Validation\Rule::unique('hari_liburs')->ignore($request->route('id'))], 'keterangan' => 'required|string|max:255'],
            'nomor-cadangan' => ['satker_id' => ['required', 'exists:satkers,id', \Illuminate\Validation\Rule::unique('nomor_cadangans')->where('bulan', $request->input('bulan'))->where('template_surat_id', $request->input('template_surat_id') ?: null)->ignore($request->route('id'))], 'template_surat_id' => 'nullable|exists:template_surats,id', 'bulan' => 'required|integer|between:1,12', 'jumlah' => 'required|integer|between:0,500'],
            'nomor-inisiasi' => ['satker_id' => ['required', 'exists:satkers,id', \Illuminate\Validation\Rule::unique('nomor_inisiasis')->where('tahun', $request->input('tahun'))->ignore($request->route('id'))], 'tahun' => 'required|integer|between:2000,2100', 'nomor_awal' => 'required|integer|min:1|max:999999'],
            default => ['kode' => 'required|string|max:30', 'nama' => 'required|string|max:255'],
        });
        if ($type === 'jabatan') {
            $data['is_pimpinan'] = $request->boolean('is_pimpinan');
        }
        if ($type === 'unit-pemrakarsa') {
            $data['status'] = $request->boolean('status');
        }
        return $data;
    }
}

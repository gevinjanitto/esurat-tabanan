<?php

namespace App\Http\Controllers;

use App\Models\AlurSurat;
use App\Models\Satker;
use App\Models\TemplateTujuanDisposisi;
use App\Models\TemplateTujuanSurat;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\Request;

class TemplatePribadiController extends Controller
{
    public function alurIndex(Request $request)
    {
        $rows = AlurSurat::where('user_id', $request->user()->id)->orderBy('judul')->get();
        return view('master.alur-surat', ['rows' => $rows, 'users' => $this->signers()]);
    }

    public function alurStore(Request $request)
    {
        AlurSurat::create($this->alurData($request) + ['user_id' => $request->user()->id]);
        Activity::log('alur_surat.tambah', 'Menyimpan template alur surat');
        return back()->with('success', 'Alur surat disimpan.');
    }

    public function alurUpdate(Request $request, AlurSurat $alur)
    {
        abort_unless($alur->user_id === $request->user()->id, 403);
        $alur->update($this->alurData($request, $alur->id));
        return back()->with('success', 'Alur surat diperbarui.');
    }

    public function alurDestroy(Request $request, AlurSurat $alur)
    {
        abort_unless($alur->user_id === $request->user()->id, 403);
        $alur->delete();
        return back()->with('success', 'Alur surat dihapus.');
    }

    public function tujuanIndex(Request $request)
    {
        $rows = TemplateTujuanSurat::where('user_id', $request->user()->id)->orderBy('nama')->get();
        return view('master.template-tujuan', ['rows' => $rows, 'satkers' => Satker::orderBy('nama')->get()]);
    }

    public function tujuanStore(Request $request)
    {
        TemplateTujuanSurat::create($this->tujuanData($request) + ['user_id' => $request->user()->id]);
        Activity::log('template_tujuan.tambah', 'Menyimpan template tujuan surat');
        return back()->with('success', 'Template tujuan surat disimpan.');
    }

    public function tujuanUpdate(Request $request, TemplateTujuanSurat $tujuan)
    {
        abort_unless($tujuan->user_id === $request->user()->id, 403);
        $tujuan->update($this->tujuanData($request));
        return back()->with('success', 'Template tujuan surat diperbarui.');
    }

    public function tujuanDestroy(Request $request, TemplateTujuanSurat $tujuan)
    {
        abort_unless($tujuan->user_id === $request->user()->id, 403);
        $tujuan->delete();
        return back()->with('success', 'Template tujuan surat dihapus.');
    }

    public function disposisiStore(Request $request)
    {
        $data = $request->validate(['nama' => 'required|string|max:150', 'penerima' => 'required|array|min:1|max:50', 'penerima.*' => 'integer|exists:users,id']);
        $row = TemplateTujuanDisposisi::create(['user_id' => $request->user()->id, 'nama' => $data['nama'], 'penerima' => array_values(array_unique(array_map('intval', $data['penerima'])))]);
        return response()->json(['id' => $row->id, 'nama' => $row->nama, 'penerima' => $row->penerima]);
    }

    public function disposisiDestroy(Request $request, TemplateTujuanDisposisi $tujuan)
    {
        abort_unless($tujuan->user_id === $request->user()->id, 403);
        $tujuan->delete();
        return response()->json(['ok' => true]);
    }

    private function alurData(Request $request, ?int $ignore = null): array
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:150', \Illuminate\Validation\Rule::unique('alur_surats')->where('user_id', $request->user()->id)->ignore($ignore)],
            'metode_verifikasi' => 'required|in:bersamaan,berjenjang',
            'verifikator' => 'nullable|array|max:15', 'verifikator.*' => 'distinct|exists:users,id',
            'penandatangan' => 'required|array|min:1|max:5', 'penandatangan.*' => 'distinct|exists:users,id',
        ], ['judul.unique' => 'Judul alur sudah dipakai.', 'penandatangan.required' => 'Pilih minimal satu penandatangan.']);
        return ['judul' => $data['judul'], 'metode_verifikasi' => $data['metode_verifikasi'], 'verifikator' => array_map('intval', $data['verifikator'] ?? []), 'penandatangan' => array_map('intval', $data['penandatangan'])];
    }

    private function tujuanData(Request $request): array
    {
        $data = $request->validate([
            'nama' => 'required|string|max:150',
            'tujuan' => 'required|array|min:1', 'tujuan.*' => 'integer|exists:satkers,id',
            'tembusan' => 'nullable|array', 'tembusan.*' => 'integer|exists:satkers,id',
        ], ['tujuan.required' => 'Pilih minimal satu satker tujuan.']);
        $tujuan = array_values(array_unique(array_map('intval', $data['tujuan'])));
        return ['nama' => $data['nama'], 'tujuan' => $tujuan, 'tembusan' => array_values(array_diff(array_unique(array_map('intval', $data['tembusan'] ?? [])), $tujuan))];
    }

    private function signers()
    {
        return User::with('jabatan')->where('is_active', true)->where('role', '!=', 'admin')->orderBy('name')->get();
    }
}

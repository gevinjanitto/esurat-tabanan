<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TransferSurat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransferSuratController extends Controller
{
    public function index(string $jenis)
    {
        abort_unless(in_array($jenis, ['disposisi', 'keluar'], true), 404);
        $users = User::with(['jabatan', 'satker'])->whereNotIn('role', ['admin'])->orderBy('name')->get();
        return view('transfer.index', ['jenis' => $jenis, 'users' => $users]);
    }

    private function rules(Request $request, bool $withTujuan): array
    {
        return $request->validate([
            'jenis' => 'required|in:disposisi,keluar',
            'asal_id' => 'required|exists:users,id',
            'tujuan_id' => $withTujuan ? ['required', 'different:asal_id', Rule::exists('users', 'id')->where('is_active', true)] : 'nullable',
            'tgl_awal' => 'required|date',
            'tgl_akhir' => 'required|date|after_or_equal:tgl_awal',
        ], ['tujuan_id.different' => 'Pegawai tujuan harus berbeda dengan pegawai asal.', 'tujuan_id.exists' => 'Pegawai tujuan tidak aktif / tidak ditemukan.']);
    }

    public function hitung(Request $request, TransferSurat $svc)
    {
        $d = $this->rules($request, false);
        return response()->json(['status' => true, 'jumlah' => $svc->hitung($d['jenis'], (int) $d['asal_id'], $d['tgl_awal'], $d['tgl_akhir'])]);
    }

    public function proses(Request $request, TransferSurat $svc)
    {
        $d = $this->rules($request, true);
        $hasil = $svc->proses($d['jenis'], User::findOrFail($d['asal_id']), User::findOrFail($d['tujuan_id']), $d['tgl_awal'], $d['tgl_akhir']);
        $ringkas = collect($hasil)->map(fn ($n, $k) => "$n " . str_replace('_', ' ', $k))->join(', ');
        return redirect()->route('transfer.index', $d['jenis'])->with('success', "Data berhasil ditransfer: {$ringkas}.");
    }
}

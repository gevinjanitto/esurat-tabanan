<?php

namespace App\Http\Controllers;

use App\Models\Disposisi;
use App\Models\DisposisiComment;
use App\Models\EsignLog;
use App\Models\Jabatan;
use App\Models\Satker;
use App\Models\SuratMasuk;
use App\Models\User;
use App\Models\UserTag;
use App\Support\Activity;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PelengkapController extends Controller
{
    public function lembarDisposisi(Request $request, SuratMasuk $suratMasuk)
    {
        abort_unless($suratMasuk->isVisibleTo($request->user()), 403);
        $q = $suratMasuk->disposisis()->terkirim()->with(['dari.jabatan', 'ke.jabatan']);
        if ($id = $request->integer('disposisi')) {
            $q->where('id', $id);
        }
        $html = view('pdf.lembar-disposisi', ['surat' => $suratMasuk->load(['sifat', 'klasifikasi', 'keamanan']), 'disposisis' => $q->get()])->render();
        Activity::log('disposisi.cetak', "Mencetak lembar disposisi: {$suratMasuk->perihal}");
        return Pdf::loadHTML($html)->setPaper('a4')->stream('lembar-disposisi-' . str($suratMasuk->nomor_agenda)->slug() . '.pdf');
    }

    public function comment(Request $request, Disposisi $disposisi)
    {
        $me = $request->user();
        abort_unless(!$disposisi->is_draft && in_array($me->id, [$disposisi->dari_user_id, $disposisi->ke_user_id], true), 403, 'Hanya pengirim dan penerima disposisi yang dapat menanggapi.');
        $data = $request->validate(['komentar' => 'required|string|max:2000']);
        $disposisi->comments()->create(['user_id' => $me->id, 'komentar' => $data['komentar']]);
        $other = $me->id === $disposisi->dari_user_id ? $disposisi->ke_user_id : $disposisi->dari_user_id;
        Activity::notify([$other], 'Tanggapan baru pada disposisi', $disposisi->surat->perihal, route('surat-masuk.show', $disposisi->surat_masuk_id) . '#disposisi-' . $disposisi->id);
        Activity::log('disposisi.komentar', "Menanggapi disposisi: {$disposisi->surat->perihal}");
        return back()->with('success', 'Tanggapan dikirim.');
    }

    public function destroyComment(Request $request, DisposisiComment $comment)
    {
        abort_unless($comment->user_id === $request->user()->id, 403);
        $comment->delete();
        return back()->with('success', 'Tanggapan dihapus.');
    }

    public function saveTags(Request $request)
    {
        $data = $request->validate(['type' => ['required', Rule::in(array_keys(UserTag::TYPES))], 'id' => 'required|integer', 'tags' => 'nullable|string|max:500']);
        $model = UserTag::TYPES[$data['type']]::findOrFail($data['id']);
        $me = $request->user();
        $visible = match ($data['type']) {
            'disposisi' => in_array($me->id, [$model->dari_user_id, $model->ke_user_id, $model->drafted_by], true),
            default => $model->isVisibleTo($me),
        };
        abort_unless($visible, 403);
        $tags = collect(preg_split('/[,;]+/', (string) ($data['tags'] ?? '')))->map(fn ($t) => mb_substr(trim($t), 0, 40))->filter()->unique(fn ($t) => mb_strtolower($t))->take(15)->values()->all();
        $key = ['user_id' => $me->id, 'taggable_type' => $data['type'], 'taggable_id' => $model->id];
        $tags ? UserTag::updateOrCreate($key, ['tags' => $tags]) : UserTag::where($key)->delete();
        return $request->expectsJson() ? response()->json(['tags' => $tags]) : back()->with('success', 'Tag disimpan.');
    }

    public function uniqueUser(Request $request)
    {
        $data = $request->validate(['field' => 'required|in:username,nip', 'value' => 'nullable|string|max:60', 'ignore' => 'nullable|integer']);
        $value = trim((string) ($data['value'] ?? ''));
        $taken = $value !== '' && User::where($data['field'], $value)->when($data['ignore'] ?? null, fn ($q, $id) => $q->where('id', '!=', $id))->exists();
        return response()->json(['available' => !$taken, 'message' => $taken ? ($data['field'] === 'nip' ? 'NIP sudah terdaftar.' : 'Username sudah dipakai.') : 'Tersedia']);
    }

    public function esignLog(Request $request)
    {
        $q = EsignLog::with(['surat', 'user'])->latest('id');
        if ($status = $request->get('status')) {
            $q->where('status', $status);
        }
        if ($s = $request->string('q')->trim()->value()) {
            $q->where(fn ($w) => $w->where('dokumen', 'like', "%$s%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$s%")));
        }
        return view('logs.esign', ['logs' => $q->paginate(25)->withQueryString()]);
    }

    public function satkerJabatan(Satker $satker)
    {
        return view('master.satker-jabatan', ['satker' => $satker->load('jabatans'), 'counts' => User::where('satker_id', $satker->id)->selectRaw('jabatan_id, count(*) as total')->groupBy('jabatan_id')->pluck('total', 'jabatan_id')]);
    }

    public function satkerJabatanStore(Request $request, Satker $satker)
    {
        $data = $request->validate(['nama' => 'required|string|max:255']);
        $satker->jabatans()->create($data + ['is_pimpinan' => $request->boolean('is_pimpinan')]);
        Activity::log('master.jabatan.tambah', "Menambah jabatan {$data['nama']} di {$satker->nama}");
        return back()->with('success', 'Jabatan ditambahkan.');
    }

    public function satkerJabatanUpdate(Request $request, Satker $satker, Jabatan $jabatan)
    {
        abort_unless($jabatan->satker_id === $satker->id, 404);
        $jabatan->update($request->validate(['nama' => 'required|string|max:255']) + ['is_pimpinan' => $request->boolean('is_pimpinan')]);
        return back()->with('success', 'Jabatan diperbarui.');
    }

    public function satkerJabatanDestroy(Satker $satker, Jabatan $jabatan)
    {
        abort_unless($jabatan->satker_id === $satker->id, 404);
        if (User::where('jabatan_id', $jabatan->id)->exists()) {
            return back()->with('error', 'Jabatan masih dipakai pengguna.');
        }
        $jabatan->delete();
        return back()->with('success', 'Jabatan dihapus.');
    }
}

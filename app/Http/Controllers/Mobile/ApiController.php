<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SuratKeluarController as WebSuratKeluar;
use App\Http\Controllers\SuratMasukController as WebSuratMasuk;
use App\Models\Agenda;
use App\Models\Disposisi;
use App\Models\Satker;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\User;
use App\Services\SuratKeluarWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** API aplikasi mobile /api/m/... — aksi memakai ulang logika controller web, hasilnya JSON. */
class ApiController extends Controller
{
    public function __construct(private SuratKeluarWorkflow $workflow) {}

    private function delegate(Request $request, callable $fn)
    {
        $res = $fn();
        if (!$res instanceof RedirectResponse) {
            return $res;
        }
        $s = $request->session();
        if ($errors = $s->get('errors')) {
            return response()->json(['status' => false, 'message' => $errors->first()], 422);
        }
        $err = $s->get('error');
        return response()->json(['status' => !$err, 'message' => $err ?: $s->get('success')], $err ? 422 : 200);
    }

    private function page($p, callable $map): array
    {
        return ['status' => true, 'data' => collect($p->items())->map($map)->values(), 'meta' => ['page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'total' => $p->total()]];
    }

    private function sm(SuratMasuk $s): array
    {
        return ['id' => $s->id, 'nomor_agenda' => $s->nomor_agenda, 'nomor_surat' => $s->nomor_surat, 'tanggal_surat' => $s->tanggal_surat?->toDateString(),
            'tanggal_terima' => $s->tanggal_terima?->toDateString(), 'pengirim' => $s->pengirim, 'perihal' => $s->perihal, 'ringkasan' => $s->ringkasan,
            'sifat' => $s->sifat?->nama, 'status' => $s->status, 'status_label' => $s->status_label,
            'file_url' => $s->file_path ? route('mobile.loadpdf', ['type' => 'masuk', 'id' => $s->id]) : null];
    }

    private function sk(SuratKeluar $s): array
    {
        return ['id' => $s->id, 'uuid' => $s->uuid, 'nomor_surat' => $s->nomor_surat, 'tanggal_surat' => $s->tanggal_surat?->toDateString(), 'perihal' => $s->perihal,
            'tujuan' => $s->tujuan, 'status' => $s->status, 'status_label' => $s->status_label, 'revisi' => $s->revisi, 'pembuat' => $s->creator?->name,
            'sifat' => $s->sifat?->nama, 'updated_at' => $s->updated_at?->toIso8601String(),
            'file_url' => $s->currentFile() ? route('mobile.loadpdf', ['type' => 'keluar', 'id' => $s->id]) : null];
    }

    private function dp(Disposisi $d): array
    {
        return ['id' => $d->id, 'surat_masuk_id' => $d->surat_masuk_id, 'perihal' => $d->surat?->perihal, 'nomor_surat' => $d->surat?->nomor_surat,
            'dari' => $d->dari?->name, 'ke' => $d->ke?->name, 'instruksi' => $d->instruksi, 'catatan' => $d->catatan, 'batas_waktu' => $d->batas_waktu?->toDateString(),
            'status' => $d->status, 'status_label' => $d->status_label, 'tindak_lanjut' => $d->tindak_lanjut, 'created_at' => $d->created_at?->toIso8601String()];
    }

    private function userRow(User $u): array
    {
        return ['id' => $u->id, 'nama' => $u->name, 'jabatan' => $u->jabatan?->nama, 'satker' => $u->satker?->nama];
    }

    public function me(Request $request)
    {
        return response()->json(['status' => true, 'user' => AuthController::user($request->user())]);
    }

    public function satker()
    {
        return response()->json(['status' => true, 'data' => Satker::orderBy('nama')->get(['id', 'kode', 'nama', 'kategori', 'parent_id'])]);
    }

    public function suratMasukList(Request $request)
    {
        $u = $request->user();
        $q = SuratMasuk::with('sifat')->latest('tanggal_terima')->latest('id');
        $u->hasRole('admin', 'operator') ? $q : $q->teruskanTo($u);
        if ($s = $request->string('q')->trim()->value()) {
            $q->where(fn ($w) => $w->where('perihal', 'like', "%$s%")->orWhere('nomor_surat', 'like', "%$s%")->orWhere('pengirim', 'like', "%$s%"));
        }
        return response()->json($this->page($q->paginate(20), fn ($s) => $this->sm($s)));
    }

    public function suratMasuk(Request $request, SuratMasuk $suratMasuk)
    {
        $me = $request->user();
        abort_unless($suratMasuk->isVisibleTo($me), 403);
        Disposisi::where('surat_masuk_id', $suratMasuk->id)->where('ke_user_id', $me->id)->where('is_draft', false)->where('status', 'terkirim')->update(['status' => 'dibaca', 'dibaca_at' => now()]);
        $suratMasuk->load(['sifat', 'disposisis.dari', 'disposisis.ke', 'agendas']);
        return response()->json(['status' => true, 'data' => $this->sm($suratMasuk) + [
            'disposisi' => $suratMasuk->disposisis->where('is_draft', false)->map(fn ($d) => $this->dp($d->setRelation('surat', $suratMasuk)))->values(),
            'agenda' => $suratMasuk->agendas->map(fn ($a) => ['id' => $a->id, 'judul' => $a->judul, 'mulai' => $a->mulai?->toIso8601String(), 'tempat' => $a->tempat])->values(),
        ]]);
    }

    public function tujuanTeruskan(Request $request)
    {
        abort_unless($request->user()->hasRole('operator'), 403);
        $s = $request->string('q')->trim()->value();
        $users = fn ($q) => $q->with(['jabatan', 'satker'])->where('is_active', true)->when($s, fn ($w) => $w->where('name', 'like', "%$s%"))->orderBy('name')->get()->map(fn ($u) => $this->userRow($u));
        return response()->json(['status' => true, 'data' => [
            'direksi' => $users(User::where('role', 'pimpinan')->whereHas('satker', fn ($q) => $q->where('kategori', 'direksi'))),
            'divisi' => Satker::where('kategori', '!=', 'direksi')->when($s, fn ($w) => $w->where('nama', 'like', "%$s%"))->orderBy('nama')->get(['id', 'kode', 'nama']),
            'jabatan' => $users(User::whereNotIn('role', ['admin', 'operator'])->whereNotNull('jabatan_id')),
        ]]);
    }

    public function teruskan(Request $request)
    {
        abort_unless($request->user()->hasRole('operator'), 403);
        $surat = SuratMasuk::findOrFail($request->validate(['surat_masuk_id' => 'required|integer'])['surat_masuk_id']);
        return $this->delegate($request, fn () => app(WebSuratMasuk::class)->teruskan($request, $surat));
    }

    public function agendaList(Request $request)
    {
        $q = Agenda::with('surat')->visibleTo($request->user());
        $request->get('waktu') === 'lalu' ? $q->where('mulai', '<', now()->startOfDay())->orderByDesc('mulai') : $q->where('mulai', '>=', now()->startOfDay())->orderBy('mulai');
        return response()->json($this->page($q->paginate(20), fn ($a) => $this->agenda($a)));
    }

    public function agendaShow(Request $request, int $id)
    {
        $row = Agenda::with('surat')->visibleTo($request->user())->findOrFail($id);
        return response()->json(['status' => true, 'data' => $this->agenda($row)]);
    }

    private function agenda(Agenda $a): array
    {
        return ['id' => $a->id, 'judul' => $a->judul, 'deskripsi' => $a->deskripsi, 'metode' => $a->metode, 'tempat' => $a->tempat, 'link_meeting' => $a->link_meeting,
            'mulai' => $a->mulai?->toIso8601String(), 'selesai' => $a->selesai?->toIso8601String(), 'surat_masuk_id' => $a->surat_masuk_id, 'perihal_surat' => $a->surat?->perihal];
    }

    public function agendaAkanDatang(Request $request)
    {
        $rows = Agenda::with('surat')->visibleTo($request->user())->where('mulai', '>=', now())->orderBy('mulai')->take(5)->get();
        return response()->json(['status' => true, 'data' => $rows->map(fn ($a) => $this->agenda($a))->values()]);
    }

    public function suratKeluarList(Request $request)
    {
        $q = SuratKeluar::with(['creator', 'sifat'])->visibleTo($request->user())->latest('updated_at');
        if ($request->filled('status')) {
            $q->where('status', (int) $request->get('status'));
        }
        if ($s = $request->string('q')->trim()->value()) {
            $q->where(fn ($w) => $w->where('perihal', 'like', "%$s%")->orWhere('nomor_surat', 'like', "%$s%"));
        }
        return response()->json($this->page($q->paginate(20), fn ($s) => $this->sk($s)));
    }

    public function suratKeluar(Request $request, SuratKeluar $suratKeluar)
    {
        $me = $request->user();
        abort_unless($suratKeluar->isVisibleTo($me), 403);
        $suratKeluar->load(['creator', 'sifat', 'alurs.user', 'riwayats.user']);
        $row = $this->workflow->assignment($suratKeluar, $me);
        return response()->json(['status' => true, 'data' => $this->sk($suratKeluar) + [
            'ringkasan' => $suratKeluar->ringkasan, 'metode_verifikasi' => $suratKeluar->metode_verifikasi,
            'giliran_saya' => $row ? $row->peran : null,
            'alur' => $suratKeluar->alurs->map(fn ($a) => ['peran' => $a->peran, 'urut' => $a->urut, 'nama' => $a->user?->name, 'status' => $a->status, 'catatan' => $a->catatan, 'acted_at' => $a->acted_at?->toIso8601String()])->values(),
            'riwayat' => $suratKeluar->riwayats->take(20)->map(fn ($r) => ['aksi' => $r->aksi, 'oleh' => $r->user?->name, 'catatan' => $r->catatan, 'revisi' => $r->revisi, 'waktu' => $r->created_at?->toIso8601String()])->values(),
        ]]);
    }

    public function antrean(Request $request, string $peran)
    {
        $me = $request->user();
        $status = $peran === 'penandatangan' ? SuratKeluar::TANDATANGAN : SuratKeluar::VERIFIKASI;
        $rows = SuratKeluar::with(['creator', 'sifat'])->where('status', $status)
            ->whereHas('alurs', fn ($a) => $a->where('user_id', $me->id)->where('peran', $peran)->where('status', 0))->latest('updated_at')->get()
            ->filter(fn ($s) => $this->workflow->assignment($s, $me));
        return response()->json(['status' => true, 'data' => $rows->map(fn ($s) => $this->sk($s))->values()]);
    }

    public function keputusan(Request $request, string $action)
    {
        $surat = SuratKeluar::findOrFail($request->validate(['id' => 'required|integer'])['id']);
        $request->merge(['action' => $action]);
        return $this->delegate($request, fn () => app(WebSuratKeluar::class)->decide($request, $surat));
    }

    public function disposisiList(Request $request)
    {
        $q = Disposisi::with(['surat', 'dari', 'ke'])->terkirim()->whereHas('surat')->latest('id');
        $request->get('mode') === 'keluar' ? $q->where('dari_user_id', $request->user()->id) : $q->where('ke_user_id', $request->user()->id);
        if ($status = $request->get('status')) {
            $q->where('status', $status);
        }
        return response()->json($this->page($q->paginate(20), fn ($d) => $this->dp($d)));
    }

    public function disposisi(Request $request, Disposisi $disposisi)
    {
        abort_unless($disposisi->surat?->isVisibleTo($request->user()), 403);
        $disposisi->load(['dari', 'ke', 'comments.user']);
        return response()->json(['status' => true, 'data' => $this->dp($disposisi) + [
            'bisa_diubah' => $disposisi->canManage($request->user()) && $disposisi->isMutable(),
            'tanggapan' => $disposisi->comments->map(fn ($c) => ['oleh' => $c->user?->name, 'isi' => $c->komentar, 'waktu' => $c->created_at?->toIso8601String()])->values(),
        ]]);
    }

    public function disposisiTambah(Request $request)
    {
        $surat = SuratMasuk::findOrFail($request->validate(['surat_masuk_id' => 'required|integer'])['surat_masuk_id']);
        return $this->delegate($request, fn () => app(WebSuratMasuk::class)->disposisi($request, $surat));
    }

    public function disposisiUbah(Request $request)
    {
        $d = Disposisi::findOrFail($request->validate(['id' => 'required|integer'])['id']);
        return $this->delegate($request, fn () => app(WebSuratMasuk::class)->updateDisposisi($request, $d));
    }

    public function tujuanDisposisi(Request $request)
    {
        $rows = User::with(['jabatan', 'satker'])->where('is_active', true)->where('id', '!=', $request->user()->id)->where('role', '!=', 'admin')->orderBy('name')->get();
        return response()->json(['status' => true, 'data' => $rows->map(fn ($u) => $this->userRow($u))->values()]);
    }

    public function loadpdf(Request $request)
    {
        $d = $request->validate(['type' => 'required|in:masuk,keluar', 'id' => 'required|integer']);
        return $d['type'] === 'masuk'
            ? app(WebSuratMasuk::class)->file($request, SuratMasuk::findOrFail($d['id']))
            : app(WebSuratKeluar::class)->file($request, SuratKeluar::findOrFail($d['id']));
    }
}

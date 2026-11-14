<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Disposisi;
use App\Models\Klasifikasi;
use App\Models\KlasifikasiKeamanan;
use App\Models\Satker;
use App\Models\Sifat;
use App\Models\SubjekDisposisi;
use App\Models\SuratMasuk;
use App\Models\SuratMasukTeruskan;
use App\Models\User;
use App\Support\Activity;
use App\Services\EmailNotifier;
use App\Models\TemplateTujuanDisposisi;
use App\Models\UserTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SuratMasukController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $q = SuratMasuk::with(['sifat', 'disposisis', 'teruskans.user', 'teruskans.satker'])->latest('tanggal_terima')->latest('id');
        $user->hasRole('admin', 'operator') ? $q : $q->teruskanTo($user);
        if ($s = $request->string('q')->trim()->value()) {
            $q->where(fn ($w) => $w->where('perihal', 'like', "%$s%")->orWhere('nomor_surat', 'like', "%$s%")->orWhere('pengirim', 'like', "%$s%"));
        }
        if ($status = $request->get('status')) {
            $q->where('status', $status);
        }
        if ($tag = $request->string('tag')->trim()->value()) {
            $q->whereIn('id', UserTag::idsWithTag($user, 'surat_masuk', $tag));
        }
        $surats = $q->paginate(12)->withQueryString();
        return view('surat-masuk.index', compact('surats'));
    }

    public function create(Request $request)
    {
        return view('surat-masuk.form', ['surat' => new SuratMasuk(['tanggal_terima' => now(), 'tanggal_surat' => now()])] + $this->options());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $data['created_by'] = $request->user()->id;
        $data['status'] = 'baru';
        $data['satker_id'] = $request->user()->satker_id;
        $data['nomor_agenda'] = ($data['nomor_agenda'] ?? null) ?: SuratMasuk::nextAgenda();
        $surat = SuratMasuk::create($data);
        Activity::log('surat_masuk.tambah', "Mencatat surat masuk: {$surat->perihal}");
        return redirect()->route('surat-masuk.teruskan.form', $surat)->with('success', 'Surat masuk dicatat. Silakan teruskan ke tujuan.');
    }

    public function show(Request $request, SuratMasuk $suratMasuk)
    {
        $me = $request->user();
        abort_unless($suratMasuk->isVisibleTo($me), 403);
        Disposisi::where('surat_masuk_id', $suratMasuk->id)->where('ke_user_id', $me->id)->where('is_draft', false)->where('status', 'terkirim')
            ->update(['status' => 'dibaca', 'dibaca_at' => now()]);
        $suratMasuk->teruskans()->where('user_id', $me->id)->whereNull('dibaca_at')->update(['dibaca_at' => now()]);
        $suratMasuk->load(['disposisis.dari.jabatan', 'disposisis.ke.jabatan', 'disposisis.drafter', 'disposisis.comments.user', 'agendas', 'teruskans.user.jabatan', 'teruskans.satker', 'teruskans.dari', 'sifat', 'klasifikasi', 'keamanan', 'creator', 'suratKeluar']);
        $users = User::with('jabatan')->where('is_active', true)->where('id', '!=', $me->id)->where('role', '!=', 'admin')->orderBy('name')->get();
        $sent = $suratMasuk->disposisis->where('is_draft', false);
        $myDisposisi = $sent->where('ke_user_id', $me->id)->last();
        $isRecipient = $myDisposisi || SuratMasuk::teruskanTo($me)->whereKey($suratMasuk->id)->exists();
        $sekpriMode = $me->isSekpri() && $suratMasuk->teruskans->where('status', 'aktif')->where('tipe', 'sekpri')->where('user_id', $me->id)->isNotEmpty();
        $drafts = $suratMasuk->disposisis->where('is_draft', true)->filter(fn ($d) => $d->dari_user_id === $me->id || $d->drafted_by === $me->id);
        $myTeruskan = $suratMasuk->teruskans->where('status', 'aktif')->where('user_id', $me->id)->first();
        $sekpriSent = $me->sekpris()->pluck('id')->intersect($suratMasuk->teruskans->where('status', 'aktif')->pluck('user_id'))->isNotEmpty();
        return view('surat-masuk.show', [
            'surat' => $suratMasuk, 'users' => $users, 'myDisposisi' => $myDisposisi, 'subjeks' => SubjekDisposisi::orderBy('nama')->get(),
            'canDispose' => !$me->hasRole('admin') && ($isRecipient || $me->hasRole('operator')), 'sekpriMode' => $sekpriMode, 'drafts' => $drafts,
            'canTeruskanSekpri' => $myTeruskan && $me->sekpris()->exists() && !$sekpriSent, 'disposisiList' => $sent,
            'tags' => UserTag::for($me, 'surat_masuk', $suratMasuk->id), 'myDisposisiTags' => $myDisposisi ? UserTag::for($me, 'disposisi', $myDisposisi->id) : [],
            'tujuanDisposisi' => TemplateTujuanDisposisi::where('user_id', $me->id)->orderBy('nama')->get(),
        ]);
    }

    public function edit(SuratMasuk $suratMasuk)
    {
        return view('surat-masuk.form', ['surat' => $suratMasuk] + $this->options());
    }

    public function update(Request $request, SuratMasuk $suratMasuk)
    {
        $data = $this->validated($request, false);
        if ($request->hasFile('file') && $suratMasuk->file_path) {
            Storage::disk('local')->delete($suratMasuk->file_path);
        }
        $suratMasuk->update($data);
        Activity::log('surat_masuk.ubah', "Mengubah surat masuk: {$suratMasuk->perihal}");
        return redirect()->route('surat-masuk.show', $suratMasuk)->with('success', 'Surat masuk diperbarui.');
    }

    public function destroy(SuratMasuk $suratMasuk)
    {
        $suratMasuk->delete();
        Activity::log('surat_masuk.hapus', "Menghapus surat masuk: {$suratMasuk->perihal}");
        return redirect()->route('surat-masuk.index')->with('success', 'Surat masuk dihapus.');
    }

    public function file(Request $request, SuratMasuk $suratMasuk)
    {
        abort_unless($suratMasuk->isVisibleTo($request->user()) && $suratMasuk->file_path && Storage::disk('local')->exists($suratMasuk->file_path), 404);
        return Storage::disk('local')->response($suratMasuk->file_path, $suratMasuk->file_name ?: 'surat.pdf', [], $request->boolean('download') ? 'attachment' : 'inline');
    }

    public function teruskanForm(Request $request, SuratMasuk $suratMasuk)
    {
        $tab = in_array($request->get('tab'), ['direksi', 'divisi', 'jabatan'], true) ? $request->get('tab') : 'direksi';
        $s = $request->string('q')->trim()->value();
        $rows = match ($tab) {
            'direksi' => User::with(['jabatan', 'satker'])->where('is_active', true)->where('role', 'pimpinan')
                ->whereHas('satker', fn ($q) => $q->where('kategori', 'direksi')),
            'divisi' => Satker::withCount(['users as penerima_count' => fn ($q) => $q->where('disposisi_awal', true)->where('is_active', true)])->where('kategori', '!=', 'direksi'),
            'jabatan' => User::with(['jabatan', 'satker'])->where('is_active', true)->whereNotIn('role', ['admin', 'operator'])->whereNotNull('jabatan_id'),
        };
        if ($s) {
            $tab === 'divisi'
                ? $rows->where('nama', 'like', "%$s%")
                : $rows->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhereHas('jabatan', fn ($j) => $j->where('nama', 'like', "%$s%")));
        }
        $rows = $tab === 'divisi' ? $rows->orderBy('nama')->get() : $rows->orderBy('name')->get();
        $suratMasuk->load(['teruskans' => fn ($q) => $q->aktif()->with(['user.jabatan', 'satker', 'dari'])]);
        return view('surat-masuk.teruskan', ['surat' => $suratMasuk, 'tab' => $tab, 'rows' => $rows]);
    }

    public function teruskan(Request $request, SuratMasuk $suratMasuk)
    {
        $data = $request->validate(['tipe' => 'required|in:direksi,divisi,jabatan', 'target_id' => 'required|integer']);
        $isSatker = $data['tipe'] === 'divisi';
        $target = $isSatker ? Satker::findOrFail($data['target_id']) : User::where('is_active', true)->findOrFail($data['target_id']);
        $key = $isSatker ? ['satker_id' => $target->id, 'user_id' => null] : ['user_id' => $target->id];
        if ($suratMasuk->teruskans()->aktif()->where($key)->exists()) {
            throw ValidationException::withMessages(['target_id' => 'Surat sudah diteruskan ke tujuan ini.']);
        }
        $t = $suratMasuk->teruskans()->create($key + ['tipe' => $data['tipe'], 'satker_id' => $isSatker ? $target->id : $target->satker_id, 'dari_user_id' => $request->user()->id]);
        Activity::notify($t->recipientIds(), 'Surat masuk diteruskan kepada Anda', $suratMasuk->perihal, route('surat-masuk.show', $suratMasuk));
        $this->emailTeruskan($suratMasuk, $t->recipientIds());
        Activity::log('surat_masuk.teruskan', "Meneruskan surat {$suratMasuk->perihal} ke " . ($target->name ?? $target->nama));
        return back()->with('success', 'Surat berhasil diteruskan.');
    }

    public function batalTeruskan(Request $request, SuratMasukTeruskan $teruskan)
    {
        $me = $request->user();
        abort_unless($me->hasRole('operator') || $teruskan->dari_user_id === $me->id, 403);
        $acted = Disposisi::where('surat_masuk_id', $teruskan->surat_masuk_id)->whereIn('dari_user_id', $teruskan->recipientIds())->exists();
        if ($acted) {
            return back()->with('error', 'Tidak bisa dibatalkan: penerima sudah membuat disposisi.');
        }
        $teruskan->update(['status' => 'batal']);
        Activity::log('surat_masuk.batal_teruskan', "Membatalkan teruskan surat {$teruskan->surat->perihal} ke {$teruskan->target_label}");
        return back()->with('success', 'Teruskan surat dibatalkan.');
    }

    public function teruskanSekpri(Request $request, SuratMasuk $suratMasuk)
    {
        $me = $request->user();
        $mine = $suratMasuk->teruskans()->aktif()->where('user_id', $me->id)->first();
        abort_unless($mine, 403, 'Surat ini tidak diteruskan kepada Anda.');
        $data = $request->validate(['sekpri_id' => 'required|integer']);
        $sekpri = $me->sekpris()->findOrFail($data['sekpri_id']);
        $suratMasuk->teruskans()->firstOrCreate(['user_id' => $sekpri->id, 'status' => 'aktif'], ['tipe' => 'sekpri', 'satker_id' => $sekpri->satker_id, 'dari_user_id' => $me->id, 'parent_id' => $mine->id]);
        Activity::notify([$sekpri->id], 'Surat pimpinan perlu disiapkan disposisinya', $suratMasuk->perihal, route('surat-masuk.show', $suratMasuk));
        $this->emailTeruskan($suratMasuk, [$sekpri->id]);
        Activity::log('surat_masuk.teruskan_sekpri', "Meneruskan surat {$suratMasuk->perihal} ke Sekpri {$sekpri->name}");
        return back()->with('success', "Surat diteruskan ke Sekpri {$sekpri->name}.");
    }

    public function disposisi(Request $request, SuratMasuk $suratMasuk)
    {
        $user = $request->user();
        abort_unless($suratMasuk->isVisibleTo($user) && !$user->hasRole('admin'), 403);
        $data = $this->disposisiData($request, true);
        $agendaData = $request->boolean('ke_agenda') ? $request->validate([
            'agenda_judul' => 'required|string|max:255',
            'agenda_mulai' => 'required|date',
            'agenda_selesai' => 'nullable|date|after_or_equal:agenda_mulai',
            'agenda_metode' => 'required|in:' . implode(',', array_keys(Agenda::METODE)),
            'agenda_tempat' => 'required_unless:agenda_metode,online|nullable|string|max:255',
            'agenda_link' => 'required_unless:agenda_metode,offline|nullable|url|max:255',
        ], ['agenda_tempat.required_unless' => 'Tempat agenda wajib diisi untuk tatap muka/hybrid.', 'agenda_link.required_unless' => 'Tautan rapat wajib diisi untuk daring/hybrid.']) : null;
        $asDraft = $request->boolean('as_draft') && $user->isSekpri();
        $from = $asDraft ? $user->atasan_id : $user->id;
        $parent = $suratMasuk->disposisis()->where('ke_user_id', $from)->where('is_draft', false)->latest('id')->first();
        $first = null;
        foreach ($data['ke_user_id'] as $to) {
            $created = $suratMasuk->disposisis()->create([
                'parent_id' => $parent?->id, 'dari_user_id' => $from, 'ke_user_id' => $to, 'is_draft' => $asDraft, 'drafted_by' => $asDraft ? $user->id : null,
                'instruksi' => $data['instruksi'], 'catatan' => $data['catatan'] ?? null, 'batas_waktu' => $data['batas_waktu'] ?? null,
            ]);
            $first ??= $created;
        }
        if ($agendaData) {
            $agenda = Agenda::create(['surat_masuk_id' => $suratMasuk->id, 'disposisi_id' => $first->id, 'user_id' => $user->id, 'judul' => $agendaData['agenda_judul'], 'mulai' => $agendaData['agenda_mulai'], 'selesai' => $agendaData['agenda_selesai'] ?? null,
                'metode' => $agendaData['agenda_metode'], 'tempat' => $agendaData['agenda_tempat'] ?? null, 'link_meeting' => $agendaData['agenda_link'] ?? null, 'deskripsi' => 'Dari disposisi: ' . $data['instruksi'] . ($data['catatan'] ?? null ? "\n" . $data['catatan'] : '')]);
            Activity::log('agenda.tambah', "Menambah agenda dari disposisi: {$agenda->judul}");
        }
        if ($asDraft) {
            Activity::notify([$from], 'Draft disposisi menunggu persetujuan', $suratMasuk->perihal, route('surat-masuk.show', $suratMasuk));
            Activity::log('disposisi.draft', "Membuat draft disposisi untuk atasan: {$suratMasuk->perihal}");
            return back()->with('success', 'Draft disposisi dikirim ke pimpinan untuk disetujui.' . ($agendaData ? ' Jadwal juga ditambahkan ke Agenda.' : ''));
        }
        $this->afterSend($suratMasuk, $parent, $data['instruksi'], $data['ke_user_id']);
        Activity::log('disposisi.kirim', "Disposisi surat: {$suratMasuk->perihal}");
        return back()->with('success', 'Disposisi berhasil dikirim.' . ($agendaData ? ' Jadwal juga ditambahkan ke Agenda.' : ''));
    }

    public function updateDisposisi(Request $request, Disposisi $disposisi)
    {
        abort_unless($disposisi->canManage($request->user()) && $disposisi->isMutable(), 403, 'Disposisi tidak dapat diubah.');
        $data = $this->disposisiData($request, false);
        $disposisi->update(['instruksi' => $data['instruksi'], 'catatan' => $data['catatan'] ?? null, 'batas_waktu' => $data['batas_waktu'] ?? null]);
        Activity::log('disposisi.ubah', "Mengubah disposisi: {$disposisi->surat->perihal}");
        return back()->with('success', 'Disposisi diperbarui.');
    }

    public function destroyDisposisi(Request $request, Disposisi $disposisi)
    {
        abort_unless($disposisi->canManage($request->user()) && $disposisi->isMutable(), 403, 'Disposisi tidak dapat dihapus.');
        $surat = $disposisi->surat;
        $disposisi->delete();
        if (!$surat->disposisis()->where('is_draft', false)->exists() && $surat->status === 'disposisi') {
            $surat->update(['status' => 'baru']);
        }
        Activity::log('disposisi.hapus', "Menghapus disposisi: {$surat->perihal}");
        return back()->with('success', 'Disposisi dihapus.');
    }

    public function kirimDraft(Request $request, Disposisi $disposisi)
    {
        abort_unless($disposisi->is_draft && $disposisi->dari_user_id === $request->user()->id, 403);
        if ($request->filled('instruksi') || $request->has('subjek')) {
            $data = $this->disposisiData($request, false);
            $disposisi->fill(['instruksi' => $data['instruksi'], 'catatan' => $data['catatan'] ?? null, 'batas_waktu' => $data['batas_waktu'] ?? null]);
        }
        $disposisi->is_draft = false;
        $disposisi->created_at = now();
        $disposisi->save();
        $this->afterSend($disposisi->surat, $disposisi->parent, $disposisi->instruksi, [$disposisi->ke_user_id]);
        Activity::log('disposisi.setujui_draft', "Menyetujui draft disposisi: {$disposisi->surat->perihal}");
        return back()->with('success', 'Draft disposisi disetujui dan dikirim.');
    }

    public function tindakLanjut(Request $request, Disposisi $disposisi)
    {
        abort_unless($disposisi->ke_user_id === $request->user()->id && !$disposisi->is_draft, 403);
        $data = $request->validate(['tindak_lanjut' => 'required|string|max:2000']);
        $disposisi->update(['status' => 'ditindaklanjuti', 'tindak_lanjut' => $data['tindak_lanjut']]);
        Activity::notify([$disposisi->dari_user_id], 'Disposisi ditindaklanjuti', $disposisi->surat->perihal, route('surat-masuk.show', $disposisi->surat_masuk_id));
        Activity::log('disposisi.tindak_lanjut', "Menindaklanjuti disposisi: {$disposisi->surat->perihal}");
        return back()->with('success', 'Tindak lanjut disimpan.');
    }

    public function selesai(Request $request, SuratMasuk $suratMasuk)
    {
        abort_unless($request->user()->hasRole('admin', 'operator', 'pimpinan', 'sekpri') && $suratMasuk->isVisibleTo($request->user()), 403);
        $suratMasuk->update(['status' => 'selesai']);
        Activity::log('surat_masuk.selesai', "Menyelesaikan surat: {$suratMasuk->perihal}");
        return back()->with('success', 'Surat ditandai selesai.');
    }

    public function inbox(Request $request)
    {
        $me = $request->user();
        $q = Disposisi::with(['surat.sifat', 'dari.jabatan'])->terkirim()->where('ke_user_id', $me->id)->whereHas('surat')->latest('id');
        if ($status = $request->get('status')) {
            $q->where('status', $status);
        }
        if ($tag = $request->string('tag')->trim()->value()) {
            $q->whereIn('id', UserTag::idsWithTag($me, 'disposisi', $tag));
        }
        $this->searchSurat($q, $request);
        return view('disposisi.index', ['items' => $q->paginate(15)->withQueryString(), 'mode' => 'masuk']);
    }

    public function outbox(Request $request)
    {
        $me = $request->user();
        $ids = array_filter([$me->id, $me->isSekpri() ? $me->atasan_id : null]);
        $q = Disposisi::with(['surat.sifat', 'ke.jabatan', 'dari', 'drafter'])->whereHas('surat')
            ->where(fn ($w) => $w->whereIn('dari_user_id', $ids)->orWhere('drafted_by', $me->id))->latest('id');
        if ($status = $request->get('status')) {
            $status === 'draft' ? $q->where('is_draft', true) : $q->where('is_draft', false)->where('status', $status);
        }
        $this->searchSurat($q, $request);
        return view('disposisi.index', ['items' => $q->paginate(15)->withQueryString(), 'mode' => 'keluar']);
    }

    public function riwayatIndex(Request $request)
    {
        $q = SuratMasuk::with(['sifat'])->withCount(['disposisis as jumlah_disposisi' => fn ($d) => $d->where('is_draft', false)])
            ->visibleTo($request->user())->whereHas('disposisis', fn ($d) => $d->where('is_draft', false))->latest('updated_at');
        if ($s = $request->string('q')->trim()->value()) {
            $q->where(fn ($w) => $w->where('perihal', 'like', "%$s%")->orWhere('nomor_surat', 'like', "%$s%"));
        }
        return view('disposisi.riwayat-index', ['surats' => $q->paginate(15)->withQueryString()]);
    }

    public function riwayat(Request $request, SuratMasuk $suratMasuk)
    {
        abort_unless($suratMasuk->isVisibleTo($request->user()), 403);
        $all = $suratMasuk->disposisis()->terkirim()->with(['dari.jabatan', 'ke.jabatan', 'ke.satker'])->get();
        $byParent = $all->groupBy(fn ($d) => $d->parent_id ?? 0);
        $roots = $byParent->get(0, collect());
        $satkerId = $request->integer('satker') ?: null;
        $rootId = $request->integer('root') ?: null;
        if ($rootId) {
            $roots = $roots->where('id', $rootId);
        }
        $keep = $all->pluck('id')->flip();
        if ($satkerId) {
            $keep = collect();
            $mark = function ($node) use (&$mark, $byParent, $satkerId, &$keep): bool {
                $hit = $node->ke?->satker_id === $satkerId;
                foreach ($byParent->get($node->id, []) as $child) {
                    $hit = $mark($child) || $hit;
                }
                if ($hit) {
                    $keep[$node->id] = true;
                }
                return $hit;
            };
            $roots->each(fn ($r) => $mark($r));
        }
        $satkers = Satker::whereIn('id', $all->pluck('ke.satker_id')->filter()->unique())->orderBy('nama')->get();
        $allRoots = $byParent->get(0, collect());
        $suratMasuk->load('teruskans.user', 'teruskans.satker');
        return view('disposisi.riwayat', compact('suratMasuk', 'roots', 'byParent', 'keep', 'satkers', 'satkerId', 'rootId', 'allRoots'));
    }

    private function afterSend(SuratMasuk $surat, ?Disposisi $parent, string $instruksi, array $to): void
    {
        $parent?->update(['status' => 'ditindaklanjuti', 'tindak_lanjut' => $parent->tindak_lanjut ?: 'Diteruskan: ' . $instruksi]);
        if ($surat->status === 'baru') {
            $surat->update(['status' => 'disposisi']);
        }
        Activity::notify($to, 'Disposisi baru', $surat->perihal, route('surat-masuk.show', $surat));
        EmailNotifier::send($to, 'Disposisi baru: ' . $surat->perihal, 'Anda menerima disposisi', [
            'Perihal' => $surat->perihal, 'Nomor Surat' => $surat->nomor_surat, 'Pengirim Surat' => $surat->pengirim,
            'Dari' => auth()->user()->name, 'Instruksi' => $instruksi,
        ], route('surat-masuk.show', $surat));
    }

    private function emailTeruskan(SuratMasuk $surat, array $to): void
    {
        EmailNotifier::send($to, 'Surat masuk diteruskan: ' . $surat->perihal, 'Surat masuk diteruskan kepada Anda', [
            'Perihal' => $surat->perihal, 'Nomor Surat' => $surat->nomor_surat, 'Pengirim' => $surat->pengirim,
            'Tanggal Diterima' => $surat->tanggal_terima?->translatedFormat('d F Y'), 'Diteruskan oleh' => auth()->user()->name,
        ], route('surat-masuk.show', $surat));
    }

    private function disposisiData(Request $request, bool $withRecipients): array
    {
        $data = $request->validate(array_filter([
            'ke_user_id' => $withRecipients ? 'required|array|min:1' : null,
            'ke_user_id.*' => $withRecipients ? 'exists:users,id' : null,
            'subjek' => 'nullable|array',
            'subjek.*' => 'string|max:100',
            'instruksi' => 'nullable|string|max:255',
            'catatan' => 'nullable|string|max:2000',
            'batas_waktu' => 'nullable|date',
        ]), ['ke_user_id.required' => 'Pilih minimal satu penerima disposisi.']);
        $data['instruksi'] = collect($data['subjek'] ?? [])->push($data['instruksi'] ?? null)->filter()->unique()->join(', ');
        if ($data['instruksi'] === '') {
            throw ValidationException::withMessages(['instruksi' => 'Pilih subjek atau tulis instruksi disposisi.']);
        }
        $data['instruksi'] = mb_substr($data['instruksi'], 0, 255);
        return $data;
    }

    private function searchSurat($q, Request $request): void
    {
        if ($s = $request->string('q')->trim()->value()) {
            $q->whereHas('surat', fn ($w) => $w->where('perihal', 'like', "%$s%")->orWhere('nomor_surat', 'like', "%$s%")->orWhere('pengirim', 'like', "%$s%"));
        }
    }

    private function validated(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'nomor_agenda' => 'nullable|string|max:50',
            'nomor_surat' => 'required|string|max:255',
            'tanggal_surat' => 'required|date',
            'tanggal_terima' => 'required|date',
            'pengirim' => 'required|string|max:255',
            'perihal' => 'required|string|max:255',
            'ringkasan' => 'nullable|string|max:5000',
            'sifat_id' => 'nullable|exists:sifats,id',
            'klasifikasi_id' => 'nullable|exists:klasifikasis,id',
            'klasifikasi_keamanan_id' => 'nullable|exists:klasifikasi_keamanans,id',
            'file' => ($creating ? 'required' : 'nullable') . '|file|mimes:pdf|max:20480',
        ]);
        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('surat-masuk', 'local');
            $data['file_name'] = $request->file('file')->getClientOriginalName();
        }
        unset($data['file']);
        return $data;
    }

    private function options(): array
    {
        return ['sifats' => Sifat::orderBy('nama')->get(), 'klasifikasis' => Klasifikasi::orderBy('kode')->get(), 'keamanans' => KlasifikasiKeamanan::orderBy('kode')->get()];
    }
}

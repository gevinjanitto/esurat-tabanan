<?php

namespace App\Http\Controllers;

use App\Models\FormDraft;
use App\Models\Klasifikasi;
use App\Models\KlasifikasiKeamanan;
use App\Models\Satker;
use App\Models\Sifat;
use App\Models\SuratAnnotation;
use App\Models\SuratKeluar;
use App\Models\SuratKeluarLampiran;
use App\Models\User;
use App\Services\SuratKeluarWorkflow;
use App\Services\TemplateSuratRenderer;
use App\Models\AlurSurat;
use App\Models\EsignLog;
use App\Models\TemplateNomor;
use App\Models\TemplateSurat;
use App\Models\TemplateTujuanSurat;
use App\Models\UnitPemrakarsa;
use App\Models\UserTag;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SuratKeluarController extends Controller
{
    public function __construct(private SuratKeluarWorkflow $workflow, private TemplateSuratRenderer $renderer) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $tab = $request->get('tab', 'tugas');
        $q = SuratKeluar::with(['creator', 'sifat', 'alurs.user'])->visibleTo($user)->latest('updated_at');
        match ($tab) {
            'tugas' => $q->whereIn('status', [1, 10])->whereHas('alurs', fn ($a) => $a->whereIn('user_id', array_filter([$user->id, $user->isSekpri() ? $user->atasan_id : null]))->where('status', 0)),
            'konsep' => $q->where('created_by', $user->id)->whereIn('status', [0, 11, 12]),
            'proses' => $q->whereIn('status', [1, 10]),
            'selesai' => $q->where('status', 2),
            default => null,
        };
        if ($s = $request->string('q')->trim()->value()) {
            $q->where(fn ($w) => $w->where('perihal', 'like', "%$s%")->orWhere('nomor_surat', 'like', "%$s%")->orWhere('tujuan', 'like', "%$s%"));
        }
        if ($tag = $request->string('tag')->trim()->value()) {
            $q->whereIn('id', UserTag::idsWithTag($user, 'surat_keluar', $tag));
        }
        $surats = $q->paginate(12)->withQueryString();
        if ($tab === 'tugas') {
            $surats->setCollection($surats->getCollection()->filter(fn ($s) => $this->workflow->assignment($s, $user) || $this->workflow->sekpriAssignment($s, $user)));
        }
        return view('surat-keluar.index', compact('surats', 'tab'));
    }

    public function create(Request $request)
    {
        abort_if($request->user()->hasRole('admin'), 403);
        $draft = $this->draft($request, 'baru');
        $surat = new SuratKeluar(array_merge(['metode_verifikasi' => 'bersamaan', 'metode_ttd' => 'elektronik', 'satker_id' => $request->user()->satker_id], collect($draft?->data ?? [])->only((new SuratKeluar)->getFillable())->all()));
        return view('surat-keluar.form', ['surat' => $surat, 'draft' => $draft] + $this->options());
    }

    public function store(Request $request)
    {
        abort_if($request->user()->hasRole('admin'), 403);
        $data = $this->validated($request, true);
        $surat = DB::transaction(function () use ($data, $request) {
            $surat = SuratKeluar::create($data['surat'] + ['created_by' => $request->user()->id, 'status' => 0, 'revisi' => 0]);
            $this->syncAlur($surat, $data);
            $this->syncTujuan($surat, $data);
            $this->storeLampiran($request, $surat);
            $this->workflow->history($surat, 'dibuat');
            return $surat;
        });
        $this->renderer->render($surat);
        FormDraft::where('user_id', $request->user()->id)->where('key', 'surat-keluar-baru')->delete();
        Activity::log('surat_keluar.tambah', "Membuat konsep surat: {$surat->perihal}");
        if ($request->input('action') === 'ajukan') {
            $this->workflow->submit($surat);
            return redirect()->route('surat-keluar.show', $surat)->with('success', 'Surat berhasil diajukan ke alur verifikasi.');
        }
        return redirect()->route('surat-keluar.show', $surat)->with('success', 'Konsep surat disimpan.');
    }

    public function show(Request $request, SuratKeluar $suratKeluar)
    {
        abort_unless($suratKeluar->isVisibleTo($request->user()), 403);
        $me = $request->user();
        $suratKeluar->load(['alurs.user.jabatan', 'riwayats.user', 'creator.jabatan', 'klasifikasi', 'sifat', 'keamanan', 'satker', 'lampirans', 'tujuans.satker']);
        $assignment = $this->workflow->assignment($suratKeluar, $me);
        $sekpriRow = $this->workflow->sekpriAssignment($suratKeluar, $me);
        $verifikasiInfo = $suratKeluar->riwayats->whereIn('aksi', ['disetujui', 'revisi', 'diteruskan', 'diteruskan_sekpri', 'ditandatangani'])->groupBy('revisi')->sortKeysDesc();
        $revisiInfo = $suratKeluar->riwayats->where('aksi', 'revisi')->groupBy('revisi')->sortKeysDesc();
        $forwardUsers = $assignment && $assignment->peran === 'verifikator'
            ? User::with('jabatan')->where('is_active', true)->whereNotIn('role', ['admin'])->whereNotIn('id', $suratKeluar->alurs->pluck('user_id')->push($suratKeluar->created_by))->orderBy('name')->get()
            : collect();
        $notes = SuratAnnotation::with('user')->where('surat_keluar_id', $suratKeluar->id)->where('revisi', $suratKeluar->revisi)->get()
            ->flatMap(fn ($a) => collect($a->data['notes'] ?? [])->map(fn ($n) => $n + ['author' => $a->user->name]));
        return view('surat-keluar.show', ['surat' => $suratKeluar, 'assignment' => $assignment, 'notes' => $notes, 'sekpriRow' => $sekpriRow,
            'verifikasiInfo' => $verifikasiInfo, 'revisiInfo' => $revisiInfo, 'forwardUsers' => $forwardUsers, 'tags' => UserTag::for($me, 'surat_keluar', $suratKeluar->id)]);
    }

    public function edit(Request $request, SuratKeluar $suratKeluar)
    {
        $this->authorizeOwner($request, $suratKeluar);
        $suratKeluar->load(['alurs', 'tujuans', 'lampirans']);
        $draft = $this->draft($request, (string) $suratKeluar->id);
        if ($draft) {
            $suratKeluar->fill(collect($draft->data)->only($suratKeluar->getFillable())->except(['status', 'created_by', 'file_path', 'file_name', 'signed_path'])->all());
        }
        return view('surat-keluar.form', ['surat' => $suratKeluar, 'draft' => $draft] + $this->options());
    }

    public function update(Request $request, SuratKeluar $suratKeluar)
    {
        $this->authorizeOwner($request, $suratKeluar);
        $data = $this->validated($request, false);
        DB::transaction(function () use ($suratKeluar, $data, $request) {
            $suratKeluar->update($data['surat']);
            $this->syncAlur($suratKeluar, $data);
            $this->syncTujuan($suratKeluar, $data);
            $this->storeLampiran($request, $suratKeluar);
        });
        $this->renderer->render($suratKeluar->fresh());
        FormDraft::where('user_id', $request->user()->id)->where('key', 'surat-keluar-' . $suratKeluar->id)->delete();
        Activity::log('surat_keluar.ubah', "Mengubah surat: {$suratKeluar->perihal}");
        if ($request->input('action') === 'ajukan') {
            $this->workflow->submit($suratKeluar);
            return redirect()->route('surat-keluar.show', $suratKeluar)->with('success', 'Surat berhasil diajukan.');
        }
        return redirect()->route('surat-keluar.show', $suratKeluar)->with('success', 'Perubahan disimpan.');
    }

    public function destroy(Request $request, SuratKeluar $suratKeluar)
    {
        $this->authorizeOwner($request, $suratKeluar);
        abort_unless($suratKeluar->status === 0, 422);
        $suratKeluar->delete();
        Activity::log('surat_keluar.hapus', "Menghapus konsep: {$suratKeluar->perihal}");
        return redirect()->route('surat-keluar.index', ['tab' => 'konsep'])->with('success', 'Konsep dihapus.');
    }

    public function submit(Request $request, SuratKeluar $suratKeluar)
    {
        $this->authorizeOwner($request, $suratKeluar);
        abort_unless($suratKeluar->file_path && $suratKeluar->alurs()->where('peran', 'penandatangan')->exists(), 422, 'Lengkapi file dan penandatangan terlebih dahulu.');
        $this->workflow->submit($suratKeluar);
        return back()->with('success', 'Surat berhasil diajukan.');
    }

    public function review(Request $request, SuratKeluar $suratKeluar)
    {
        abort_unless($suratKeluar->isVisibleTo($request->user()), 403);
        $suratKeluar->load(['alurs.user', 'creator']);
        $assignment = $this->workflow->assignment($suratKeluar, $request->user());
        return view('surat-keluar.review', ['surat' => $suratKeluar, 'assignment' => $assignment, 'user' => $request->user()]);
    }

    public function decide(Request $request, SuratKeluar $suratKeluar)
    {
        $user = $request->user();
        $row = $this->workflow->assignment($suratKeluar, $user);
        abort_unless($row, 403, 'Bukan giliran Anda pada surat ini.');
        $data = $request->validate(['action' => 'required|in:setuju,revisi,tandatangan', 'catatan' => 'nullable|string|max:3000', 'passphrase' => 'nullable|string']);

        if ($data['action'] === 'revisi' && blank($data['catatan'] ?? null)) {
            throw ValidationException::withMessages(['catatan' => 'Catatan revisi wajib diisi.']);
        }
        if ($row->peran === 'verifikator') {
            abort_if($data['action'] === 'tandatangan', 422);
            $this->workflow->verify($suratKeluar, $row, $data['action'] === 'setuju', $data['catatan'] ?? null);
            $msg = $data['action'] === 'setuju' ? 'Surat disetujui.' : 'Permintaan revisi dikirim ke pembuat surat.';
        } elseif ($data['action'] === 'revisi') {
            $this->workflow->signerRevision($suratKeluar, $row, $data['catatan']);
            $msg = 'Surat dikembalikan untuk revisi.';
        } elseif ($suratKeluar->metode_ttd === 'basah') {
            $request->validate(['scan_ttd' => 'required|file|mimes:pdf|max:20480'], ['scan_ttd.required' => 'Unggah scan PDF surat yang sudah ditandatangani basah.']);
            $this->workflow->sign($suratKeluar, $row, $user, $request->file('scan_ttd')->store('surat-keluar/signed', 'local'));
            $msg = 'Scan surat bertanda tangan basah berhasil disimpan.';
        } else {
            if (!Hash::check((string) ($data['passphrase'] ?? ''), $user->password)) {
                $this->esignFail($suratKeluar, 'Passphrase TTE tidak valid');
                throw ValidationException::withMessages(['passphrase' => 'Passphrase TTE tidak valid.']);
            }
            try {
                $this->workflow->sign($suratKeluar, $row, $user);
            } catch (\Throwable $e) {
                report($e);
                $this->esignFail($suratKeluar, $e->getMessage());
                throw ValidationException::withMessages(['passphrase' => 'Gagal membubuhkan TTE pada PDF: ' . $e->getMessage()]);
            }
            $msg = 'Tanda tangan elektronik berhasil dibubuhkan.';
        }
        return redirect()->route('surat-keluar.show', $suratKeluar)->with('success', $msg);
    }

    public function file(Request $request, SuratKeluar $suratKeluar)
    {
        abort_unless($suratKeluar->isVisibleTo($request->user()) && $suratKeluar->currentFile(), 404);
        $path = $request->boolean('original') ? $suratKeluar->file_path : $suratKeluar->currentFile();
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        $name = str($suratKeluar->perihal)->slug() . '.pdf';
        return Storage::disk('local')->response($path, $name, [], $request->boolean('download') ? 'attachment' : 'inline');
    }

    public function annotations(Request $request, SuratKeluar $suratKeluar)
    {
        abort_unless($suratKeluar->isVisibleTo($request->user()), 403);
        $revisi = (int) $request->get('revisi', $suratKeluar->revisi);
        return SuratAnnotation::with('user:id,name')->where('surat_keluar_id', $suratKeluar->id)->where('revisi', $revisi)->get()
            ->map(fn ($a) => ['user_id' => $a->user_id, 'author' => $a->user->name, 'mine' => $a->user_id === $request->user()->id, 'data' => $a->data, 'updated_at' => $a->updated_at->toIso8601String()]);
    }

    public function saveAnnotations(Request $request, SuratKeluar $suratKeluar)
    {
        abort_unless($this->workflow->assignment($suratKeluar, $request->user()), 403, 'Anotasi hanya bisa disimpan saat giliran Anda.');
        $data = $request->validate([
            'strokes' => 'array|max:2000',
            'strokes.*.page' => 'required|integer|min:1',
            'strokes.*.tool' => 'required|in:pen,highlighter',
            'strokes.*.color' => 'required|string|max:20',
            'strokes.*.size' => 'required|numeric|min:0.0005|max:0.1',
            'strokes.*.points' => 'required|array|max:4000',
            'notes' => 'array|max:200',
            'notes.*.page' => 'required|integer|min:1',
            'notes.*.x' => 'required|numeric|between:0,1',
            'notes.*.y' => 'required|numeric|between:0,1',
            'notes.*.text' => 'required|string|max:1000',
            'parafs' => 'array|max:50',
            'parafs.*.page' => 'required|integer|min:1',
            'parafs.*.x' => 'required|numeric|between:0,1',
            'parafs.*.y' => 'required|numeric|between:0,1',
            'parafs.*.w' => 'required|numeric|between:0.02,0.6',
            'parafs.*.h' => 'required|numeric|between:0.01,0.4',
        ]);
        $payload = ['strokes' => $data['strokes'] ?? [], 'notes' => $data['notes'] ?? [], 'parafs' => $data['parafs'] ?? []];
        SuratAnnotation::updateOrCreate(
            ['surat_keluar_id' => $suratKeluar->id, 'user_id' => $request->user()->id, 'revisi' => $suratKeluar->revisi],
            ['data' => $payload]
        );
        return response()->json(['ok' => true, 'saved_at' => now()->toIso8601String()]);
    }

    public function queue(Request $request, string $peran)
    {
        $user = $request->user();
        $status = $peran === 'penandatangan' ? SuratKeluar::TANDATANGAN : SuratKeluar::VERIFIKASI;
        $ids = array_filter([$user->id, $user->isSekpri() ? $user->atasan_id : null]);
        $q = SuratKeluar::with(['creator', 'sifat', 'alurs.user'])->where('status', $status)
            ->whereHas('alurs', fn ($a) => $a->whereIn('user_id', $ids)->where('peran', $peran)->where('status', 0))->latest('updated_at');
        if ($s = $request->string('q')->trim()->value()) {
            $q->where(fn ($w) => $w->where('perihal', 'like', "%$s%")->orWhere('nomor_surat', 'like', "%$s%"));
        }
        $surats = $q->get()->map(function ($s) use ($user) {
            $s->setAttribute('queue_mode', $this->workflow->assignment($s, $user) ? 'self' : ($this->workflow->sekpriAssignment($s, $user) ? 'sekpri' : null));
            return $s;
        })->filter(fn ($s) => $s->queue_mode);
        return view('surat-keluar.queue', ['surats' => $surats, 'peran' => $peran]);
    }

    public function autosave(Request $request)
    {
        $data = $request->validate(['key' => 'required|string|max:20', 'data' => 'required|array']);
        $payload = collect($data['data'])->except(['_token', '_method', 'file', 'lampiran', 'action'])->all();
        FormDraft::updateOrCreate(['user_id' => $request->user()->id, 'key' => 'surat-keluar-' . $data['key']], ['data' => $payload]);
        return response()->json(['ok' => true, 'saved_at' => now()->format('H:i:s')]);
    }

    public function discardDraft(Request $request)
    {
        FormDraft::where('user_id', $request->user()->id)->where('key', 'surat-keluar-' . $request->string('key'))->delete();
        return back()->with('success', 'Draft otomatis dibuang.');
    }

    public function uploadLampiran(Request $request, SuratKeluar $suratKeluar)
    {
        $this->authorizeOwner($request, $suratKeluar);
        $request->validate(['lampiran' => 'required|array|max:10', 'lampiran.*' => 'file|max:20480|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip']);
        $this->storeLampiran($request, $suratKeluar);
        Activity::log('surat_keluar.lampiran', "Menambah lampiran surat: {$suratKeluar->perihal}");
        return back()->with('success', 'Lampiran ditambahkan.');
    }

    public function lampiran(Request $request, SuratKeluarLampiran $lampiran)
    {
        abort_unless($lampiran->surat && $lampiran->surat->isVisibleTo($request->user()) && Storage::disk('local')->exists($lampiran->file_path), 404);
        return Storage::disk('local')->download($lampiran->file_path, $lampiran->file_name);
    }

    public function destroyLampiran(Request $request, SuratKeluarLampiran $lampiran)
    {
        $this->authorizeOwner($request, $lampiran->surat);
        Storage::disk('local')->delete($lampiran->file_path);
        $lampiran->delete();
        Activity::log('surat_keluar.lampiran_hapus', "Menghapus lampiran {$lampiran->file_name}");
        return back()->with('success', 'Lampiran dihapus.');
    }

    public function reupload(Request $request, SuratKeluar $suratKeluar)
    {
        $this->authorizeOwner($request, $suratKeluar);
        $request->validate(['file' => 'required|file|mimes:pdf|max:20480']);
        $old = $suratKeluar->file_path;
        $suratKeluar->update(['file_path' => $request->file('file')->store('surat-keluar/source', 'local'), 'file_name' => $request->file('file')->getClientOriginalName()]);
        if ($old && $suratKeluar->status === SuratKeluar::DRAFT) {
            Storage::disk('local')->delete($old);
        }
        $this->workflow->history($suratKeluar, 'unggah_ulang', $suratKeluar->file_name);
        Activity::log('surat_keluar.unggah_ulang', "Mengunggah ulang file surat: {$suratKeluar->perihal}");
        return back()->with('success', 'File surat diganti.');
    }

    public function batalKirim(Request $request, SuratKeluar $suratKeluar)
    {
        abort_unless($suratKeluar->created_by === $request->user()->id, 403);
        if (!$suratKeluar->isWithdrawable()) {
            return back()->with('error', 'Surat sudah diproses sehingga tidak bisa ditarik kembali.');
        }
        $this->workflow->withdraw($suratKeluar);
        return back()->with('success', 'Pengiriman dibatalkan. Surat kembali menjadi konsep.');
    }

    public function teruskan(Request $request, SuratKeluar $suratKeluar)
    {
        $row = $this->workflow->assignment($suratKeluar, $request->user());
        abort_unless($row && $row->peran === 'verifikator', 403, 'Hanya verifikator yang sedang bertugas yang dapat meneruskan.');
        $data = $request->validate(['user_id' => 'required|exists:users,id', 'catatan' => 'nullable|string|max:2000']);
        abort_if($suratKeluar->alurs()->where('user_id', $data['user_id'])->exists(), 422, 'Pejabat tersebut sudah ada di alur surat.');
        $this->workflow->forward($suratKeluar, $row, User::findOrFail($data['user_id']), $data['catatan'] ?? null);
        return redirect()->route('surat-keluar.show', $suratKeluar)->with('success', 'Surat disetujui dan diteruskan.');
    }

    public function teruskanSekpri(Request $request, SuratKeluar $suratKeluar)
    {
        $row = $this->workflow->sekpriAssignment($suratKeluar, $request->user());
        abort_unless($row, 403, 'Surat ini tidak menunggu tindakan Sekpri.');
        $this->workflow->forwardFromSekpri($suratKeluar, $row, $request->input('catatan'));
        return back()->with('success', 'Surat diteruskan ke pimpinan.');
    }

    private function draft(Request $request, string $key): ?FormDraft
    {
        return FormDraft::where('user_id', $request->user()->id)->where('key', 'surat-keluar-' . $key)->first();
    }

    private function storeLampiran(Request $request, SuratKeluar $surat): void
    {
        foreach ((array) $request->file('lampiran', []) as $file) {
            $surat->lampirans()->create(['file_path' => $file->store('surat-keluar/lampiran', 'local'), 'file_name' => $file->getClientOriginalName(), 'size' => $file->getSize(), 'uploaded_by' => $request->user()->id]);
        }
    }

    private function syncTujuan(SuratKeluar $surat, array $data): void
    {
        $surat->tujuans()->delete();
        foreach (['tujuan' => $data['tujuan_satker'], 'tembusan' => $data['tembusan_satker']] as $jenis => $ids) {
            foreach ($ids as $id) {
                $surat->tujuans()->create(['satker_id' => $id, 'jenis' => $jenis]);
            }
        }
    }

    private function authorizeOwner(Request $request, SuratKeluar $surat): void
    {
        abort_unless($surat->created_by === $request->user()->id && $surat->isEditable(), 403, 'Surat tidak dapat diubah pada status ini.');
    }

    private function validated(Request $request, bool $creating): array
    {
        foreach (['tujuan_satker', 'tembusan_satker'] as $k) {
            $request->merge([$k => array_values(array_filter((array) $request->input($k, [])))]);
        }
        $data = $request->validate([
            'perihal' => 'required|string|max:255',
            'metode_ttd' => 'nullable|in:' . implode(',', array_keys(SuratKeluar::METODE_TTD)),
            'tujuan' => 'required_without:tujuan_satker|nullable|string|max:2000',
            'tujuan_satker' => 'nullable|array|max:50',
            'tujuan_satker.*' => 'distinct|exists:satkers,id',
            'tembusan_satker' => 'nullable|array|max:50',
            'tembusan_satker.*' => 'distinct|exists:satkers,id',
            'lampiran' => 'nullable|array|max:10',
            'lampiran.*' => 'file|max:20480|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip',
            'ringkasan' => 'nullable|string|max:5000',
            'tanggal_surat' => 'nullable|date',
            'klasifikasi_id' => 'nullable|exists:klasifikasis,id',
            'sifat_id' => 'nullable|exists:sifats,id',
            'klasifikasi_keamanan_id' => 'nullable|exists:klasifikasi_keamanans,id',
            'satker_id' => 'nullable|exists:satkers,id',
            'metode_verifikasi' => 'required|in:bersamaan,berjenjang',
            'verifikator' => 'nullable|array|max:15',
            'verifikator.*' => 'distinct|exists:users,id',
            'penandatangan' => 'required|array|min:1|max:5',
            'penandatangan.*' => 'distinct|exists:users,id',
            'template_surat_id' => ['nullable', \Illuminate\Validation\Rule::exists('template_surats', 'id')->where('status', true)],
            'isian' => 'nullable|array',
            'isian.*' => 'nullable|string|max:10000',
            'lampiran_keterangan' => 'nullable|string|max:100',
            'unit_pemrakarsa_id' => 'nullable|exists:unit_pemrakarsas,id',
            'template_nomor_id' => 'nullable|exists:template_nomors,id',
            'file' => ($creating && !$request->filled('template_surat_id') ? 'required' : 'nullable') . '|file|mimes:pdf|max:20480',
        ], ['penandatangan.required' => 'Pilih minimal satu penandatangan.', 'tujuan.required_without' => 'Isi tujuan surat atau pilih satker tujuan internal.']);
        if (blank($data['tujuan'] ?? null)) {
            $data['tujuan'] = \App\Models\Satker::whereIn('id', $data['tujuan_satker'])->pluck('nama')->join(', ');
        }
        $data['metode_ttd'] = $data['metode_ttd'] ?? 'elektronik';
        $surat = collect($data)->only(['perihal', 'tujuan', 'ringkasan', 'tanggal_surat', 'klasifikasi_id', 'sifat_id', 'klasifikasi_keamanan_id', 'satker_id', 'metode_verifikasi', 'metode_ttd', 'template_surat_id', 'lampiran_keterangan', 'unit_pemrakarsa_id', 'template_nomor_id'])->all();
        $surat += ['template_surat_id' => null, 'unit_pemrakarsa_id' => null, 'template_nomor_id' => null];
        if (!$creating && !$surat['template_surat_id'] && !$request->hasFile('file') && !$request->route('suratKeluar')?->file_path) {
            throw ValidationException::withMessages(['file' => 'Unggah PDF surat atau pilih template surat.']);
        }
        $surat['isian'] = $surat['template_surat_id'] ? $this->isianFor((int) $surat['template_surat_id'], $data['isian'] ?? []) : null;
        if ($request->hasFile('file')) {
            $surat['file_path'] = $request->file('file')->store('surat-keluar/source', 'local');
            $surat['file_name'] = $request->file('file')->getClientOriginalName();
        }
        return ['surat' => $surat, 'tujuan_satker' => array_values(array_filter($data['tujuan_satker'] ?? [])), 'tembusan_satker' => array_values(array_diff(array_filter($data['tembusan_satker'] ?? []), $data['tujuan_satker'] ?? [])), 'verifikator' => array_values(array_filter($data['verifikator'] ?? [])), 'penandatangan' => array_values(array_filter($data['penandatangan']))];
    }

    private function syncAlur(SuratKeluar $surat, array $data): void
    {
        $surat->alurs()->delete();
        foreach (['verifikator', 'penandatangan'] as $peran) {
            foreach ($data[$peran] as $i => $userId) {
                $surat->alurs()->create(['user_id' => $userId, 'peran' => $peran, 'urut' => $i + 1]);
            }
        }
    }

    private function isianFor(int $templateId, array $input): array
    {
        $fields = TemplateSurat::with('isians')->find($templateId)?->isians ?? collect();
        return $fields->mapWithKeys(fn ($f) => [$f->kode => (string) ($input[$f->kode] ?? '')])->all();
    }

    private function esignFail(SuratKeluar $surat, string $why): void
    {
        EsignLog::create(['surat_keluar_id' => $surat->id, 'user_id' => auth()->id(), 'status' => 'gagal', 'dokumen' => $surat->nomor_surat ?: $surat->perihal, 'keterangan' => mb_substr($why, 0, 500), 'ip' => request()->ip()]);
    }

    private function options(): array
    {
        $uid = auth()->id();
        return [
            'templates' => TemplateSurat::aktif()->with('isians')->orderBy('nama')->get(),
            'unitPemrakarsas' => UnitPemrakarsa::where('status', true)->orderBy('nama')->get(),
            'templateNomors' => TemplateNomor::orderBy('nama')->get(),
            'alurTemplates' => AlurSurat::where('user_id', $uid)->orderBy('judul')->get(),
            'tujuanTemplates' => TemplateTujuanSurat::where('user_id', $uid)->orderBy('nama')->get(),
            'sifats' => Sifat::orderBy('nama')->get(),
            'klasifikasis' => Klasifikasi::orderBy('kode')->get(),
            'keamanans' => KlasifikasiKeamanan::orderBy('kode')->get(),
            'satkers' => Satker::orderBy('nama')->get(),
            'users' => User::with('jabatan')->where('is_active', true)->where('role', '!=', 'admin')->orderBy('name')->get(),
        ];
    }
}

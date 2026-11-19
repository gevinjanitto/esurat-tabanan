<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\SuratMasuk;
use App\Support\Activity;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $waktu = $request->get('waktu', 'mendatang');
        $q = Agenda::with(['surat', 'user'])->visibleTo($user);
        $waktu === 'lalu' ? $q->where('mulai', '<', now()->startOfDay())->orderByDesc('mulai') : $q->where('mulai', '>=', now()->startOfDay())->orderBy('mulai');
        if ($s = $request->string('q')->trim()->value()) {
            $q->where(fn ($w) => $w->where('judul', 'like', "%$s%")->orWhere('tempat', 'like', "%$s%"));
        }
        return view('agenda.index', ['agendas' => $q->paginate(15)->withQueryString(), 'waktu' => $waktu]);
    }

    public function create(Request $request)
    {
        $surat = $request->integer('surat') ? SuratMasuk::findOrFail($request->integer('surat')) : null;
        abort_if($surat && !$surat->isVisibleTo($request->user()), 403);
        $agenda = new Agenda(['surat_masuk_id' => $surat?->id, 'judul' => $surat?->perihal, 'metode' => 'offline', 'mulai' => now()->addDay()->setTime(9, 0)]);
        return view('agenda.form', ['agenda' => $agenda, 'surat' => $surat, 'suratOptions' => $this->suratOptions($request)]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if ($data['surat_masuk_id'] ?? null) {
            abort_unless(SuratMasuk::findOrFail($data['surat_masuk_id'])->isVisibleTo($request->user()), 403);
        }
        $agenda = Agenda::create($data + ['user_id' => $request->user()->id]);
        Activity::log('agenda.tambah', "Menambah agenda: {$agenda->judul}");
        return redirect()->route('agenda.show', $agenda)->with('success', 'Agenda disimpan.');
    }

    public function show(Request $request, Agenda $agenda)
    {
        $this->authorizeView($request, $agenda);
        return view('agenda.show', ['agenda' => $agenda->load(['surat.sifat', 'user', 'disposisi.dari', 'disposisi.ke'])]);
    }

    public function edit(Request $request, Agenda $agenda)
    {
        $this->authorizeOwner($request, $agenda);
        return view('agenda.form', ['agenda' => $agenda, 'surat' => $agenda->surat, 'suratOptions' => $this->suratOptions($request)]);
    }

    public function update(Request $request, Agenda $agenda)
    {
        $this->authorizeOwner($request, $agenda);
        $data = $this->validated($request);
        if (!$request->has('sumber')) {
            unset($data['surat_masuk_id']);
        }
        if (($data['surat_masuk_id'] ?? null) && (int) $data['surat_masuk_id'] !== $agenda->surat_masuk_id) {
            abort_unless(SuratMasuk::findOrFail($data['surat_masuk_id'])->isVisibleTo($request->user()), 403);
        }
        if (array_key_exists('surat_masuk_id', $data) && $data['surat_masuk_id'] != $agenda->surat_masuk_id) {
            $data['disposisi_id'] = null;
        }
        $agenda->update($data);
        Activity::log('agenda.ubah', "Mengubah agenda: {$agenda->judul}");
        return redirect()->route('agenda.show', $agenda)->with('success', 'Agenda diperbarui.');
    }

    public function destroy(Request $request, Agenda $agenda)
    {
        $this->authorizeOwner($request, $agenda);
        $agenda->delete();
        Activity::log('agenda.hapus', "Menghapus agenda: {$agenda->judul}");
        return redirect()->route('agenda.index')->with('success', 'Agenda dihapus.');
    }

    private function authorizeView(Request $request, Agenda $agenda): void
    {
        abort_unless(Agenda::visibleTo($request->user())->whereKey($agenda->id)->exists(), 403);
    }

    private function authorizeOwner(Request $request, Agenda $agenda): void
    {
        abort_unless($agenda->user_id === $request->user()->id, 403, 'Hanya pembuat agenda yang dapat mengubah.');
    }

    private function suratOptions(Request $request)
    {
        return SuratMasuk::visibleTo($request->user())->latest('tanggal_terima')->take(100)->get(['id', 'nomor_surat', 'perihal', 'pengirim']);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'sumber' => 'nullable|in:surat,mandiri',
            'surat_masuk_id' => 'required_if:sumber,surat|nullable|exists:surat_masuks,id',
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:5000',
            'metode' => 'required|in:' . implode(',', array_keys(Agenda::METODE)),
            'tempat' => 'required_unless:metode,online|nullable|string|max:255',
            'link_meeting' => 'required_unless:metode,offline|nullable|url|max:255',
            'mulai' => 'required|date',
            'selesai' => 'nullable|date|after_or_equal:mulai',
        ], ['surat_masuk_id.required_if' => 'Pilih surat masuk asal agenda.', 'tempat.required_unless' => 'Tempat wajib diisi untuk agenda tatap muka/hybrid.', 'link_meeting.required_unless' => 'Tautan rapat wajib diisi untuk agenda daring/hybrid.']);
        if (($data['sumber'] ?? null) === 'mandiri') {
            $data['surat_masuk_id'] = null;
        }
        unset($data['sumber']);
        return $data;
    }
}

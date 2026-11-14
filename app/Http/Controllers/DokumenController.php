<?php

namespace App\Http\Controllers;

use App\Models\ShareLink;
use App\Services\DocumentHub;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DokumenController extends Controller
{
    public function __construct(private DocumentHub $hub) {}

    public function index(Request $request)
    {
        $items = $this->hub->forUser($request->user());
        $counts = collect(DocumentHub::RELASI)->map(fn ($l, $k) => $items->filter(fn ($i) => in_array($k, $i['relasi'], true))->count());
        if ($rel = $request->get('relasi')) {
            $items = $items->filter(fn ($i) => in_array($rel, $i['relasi'], true));
        }
        if ($type = $request->get('jenis')) {
            $items = $items->where('type', $type);
        }
        if ($s = Str::lower($request->string('q')->trim()->value())) {
            $items = $items->filter(fn ($i) => Str::contains(Str::lower($i['perihal'] . ' ' . $i['nomor']), $s));
        }
        return view('dokumen.index', ['items' => $items->values(), 'counts' => $counts, 'total' => $this->hub->forUser($request->user())->count()]);
    }

    public function links(Request $request)
    {
        $links = ShareLink::withCount('items')->where('user_id', $request->user()->id)->latest()->get();
        return view('dokumen.links', compact('links'));
    }

    public function share(Request $request)
    {
        $data = $request->validate([
            'judul' => 'nullable|string|max:150',
            'items' => 'required|array|min:1|max:50',
            'items.*' => ['string', 'regex:/^(masuk|keluar)-\d+$/'],
            'permission' => 'required|in:view,download',
            'durasi' => 'required|in:1h,1d,7d,30d,never,custom',
            'expires_at' => 'nullable|required_if:durasi,custom|date|after:now',
        ]);
        $user = $request->user();
        $allowed = $this->hub->forUser($user)->pluck('key');
        $keys = collect($data['items'])->unique();
        if ($keys->diff($allowed)->isNotEmpty()) {
            throw ValidationException::withMessages(['items' => 'Ada dokumen yang tidak dapat Anda bagikan.']);
        }
        $expires = match ($data['durasi']) {
            '1h' => now()->addHour(), '1d' => now()->addDay(), '7d' => now()->addDays(7), '30d' => now()->addDays(30),
            'custom' => \Carbon\Carbon::parse($data['expires_at']), default => null,
        };
        $link = ShareLink::create([
            'token' => Str::random(40), 'user_id' => $user->id, 'permission' => $data['permission'], 'expires_at' => $expires,
            'judul' => $data['judul'] ?: 'Berkas dari ' . $user->name . ' (' . $keys->count() . ' dokumen)',
        ]);
        foreach ($keys as $key) {
            [$type, $id] = explode('-', $key);
            $link->items()->create(['doc_type' => $type, 'doc_id' => (int) $id]);
        }
        Activity::log('share.buat', "Membuat tautan berbagi: {$link->judul}");
        $url = route('share.show', $link->token);
        if ($request->expectsJson()) {
            return response()->json(['url' => $url, 'expires_at' => $expires?->translatedFormat('d M Y H:i'), 'permission' => $link->permission]);
        }
        return redirect()->route('dokumen.links')->with('success', 'Tautan dibuat: ' . $url);
    }

    public function revoke(Request $request, ShareLink $shareLink)
    {
        abort_unless($shareLink->user_id === $request->user()->id, 403);
        $shareLink->update(['revoked_at' => now()]);
        Activity::log('share.cabut', "Menonaktifkan tautan: {$shareLink->judul}");
        return back()->with('success', 'Tautan dinonaktifkan.');
    }
}

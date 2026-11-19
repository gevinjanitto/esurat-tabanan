<?php

namespace App\Http\Controllers;

use App\Models\ShareLink;
use App\Models\ShareLinkItem;
use App\Models\SuratKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicController extends Controller
{
    public function share(string $token)
    {
        $link = ShareLink::with(['items', 'user'])->where('token', $token)->firstOrFail();
        if (!$link->isActive()) {
            return response()->view('share.expired', ['link' => $link], 410);
        }
        $link->increment('views');
        $link->update(['last_viewed_at' => now()]);
        $docs = $link->items->map(fn ($item) => ['item' => $item, 'doc' => $item->document()])->filter(fn ($d) => $d['doc']);
        return view('share.show', compact('link', 'docs'));
    }

    public function shareFile(Request $request, string $token, ShareLinkItem $item)
    {
        $link = ShareLink::where('token', $token)->firstOrFail();
        abort_unless($link->isActive() && $item->share_link_id === $link->id, 404);
        $doc = $item->document();
        $path = $doc instanceof SuratKeluar ? $doc->currentFile() : $doc?->file_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        $download = $request->boolean('download');
        abort_if($download && !$link->canDownload(), 403, 'Tautan ini hanya untuk dilihat.');
        $headers = ['Cache-Control' => 'no-store', 'X-Robots-Tag' => 'noindex'];
        return Storage::disk('local')->response($path, str($doc->perihal)->slug() . '.pdf', $headers, $download ? 'attachment' : 'inline');
    }

    public function verify(string $uuid)
    {
        $surat = SuratKeluar::with(['alurs.user.jabatan', 'satker'])->where('uuid', $uuid)->first();
        return view('verify.show', ['surat' => $surat]);
    }
}

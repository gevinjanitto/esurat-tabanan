<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Agenda;
use App\Models\Disposisi;
use App\Models\ShareLink;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Services\SuratKeluarWorkflow;

class DashboardController extends Controller
{
    public function index(SuratKeluarWorkflow $workflow)
    {
        $user = auth()->user();
        $masuk = SuratMasuk::visibleTo($user);
        $keluar = SuratKeluar::visibleTo($user);

        $ids = array_filter([$user->id, $user->isSekpri() ? $user->atasan_id : null]);
        $tugas = SuratKeluar::with('creator')->whereIn('status', [1, 10])
            ->whereHas('alurs', fn ($a) => $a->whereIn('user_id', $ids)->where('status', 0))
            ->latest('updated_at')->get()->filter(fn ($s) => $workflow->assignment($s, $user) || $workflow->sekpriAssignment($s, $user))->take(6);
        $isAdmin = $user->hasRole('admin');
        $masukUrl = $isAdmin ? route('logs.index') : ($user->hasRole('operator') || $user->disposisi_awal ? route('surat-masuk.index') : route('disposisi.inbox'));
        $keluarUrl = $isAdmin ? route('logs.index') : route('surat-keluar.index');
        $tugasUrl = $isAdmin ? route('logs.index') : ($user->hasRole('operator') ? route('surat-keluar.index', ['tab' => 'tugas']) : route('surat-keluar.queue.verifikasi'));

        $stats = [
            ['label' => 'Surat Masuk', 'value' => (clone $masuk)->count(), 'icon' => 'inbox', 'url' => $masukUrl],
            ['label' => 'Surat Keluar', 'value' => (clone $keluar)->count(), 'icon' => 'send', 'url' => $keluarUrl],
            ['label' => 'Perlu Tindakan', 'value' => $tugas->count() + Disposisi::terkirim()->where('ke_user_id', $user->id)->where('status', '!=', 'ditindaklanjuti')->count(), 'icon' => 'bell-ring', 'url' => $tugasUrl],
            ['label' => 'Tautan Aktif', 'value' => ShareLink::where('user_id', $user->id)->whereNull('revoked_at')->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->count(), 'icon' => 'link-2', 'url' => route('dokumen.links')],
        ];

        $months = collect(range(5, 0))->map(function ($i) use ($user) {
            $d = now()->startOfMonth()->subMonths($i);
            return [
                'label' => $d->translatedFormat('M'),
                'masuk' => SuratMasuk::visibleTo($user)->whereYear('created_at', $d->year)->whereMonth('created_at', $d->month)->count(),
                'keluar' => SuratKeluar::visibleTo($user)->whereYear('created_at', $d->year)->whereMonth('created_at', $d->month)->count(),
            ];
        });

        $disposisi = Disposisi::with(['surat', 'dari'])->terkirim()->whereHas('surat')->where('ke_user_id', $user->id)->where('status', '!=', 'ditindaklanjuti')->latest()->take(5)->get();
        $activity = ActivityLog::where('user_id', $user->id)->latest()->take(6)->get();
        $statusBreakdown = (clone $keluar)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $agendas = $user->hasRole('admin', 'operator', 'pimpinan', 'sekpri', 'user')
            ? Agenda::with('surat')->visibleTo($user)->where('mulai', '>=', now()->startOfDay())->orderBy('mulai')->take(4)->get()
            : null;

        return view('dashboard', compact('stats', 'months', 'tugas', 'disposisi', 'activity', 'statusBreakdown', 'agendas'));
    }
}

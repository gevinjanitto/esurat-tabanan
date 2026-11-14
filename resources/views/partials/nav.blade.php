@php
    $u = auth()->user();
    $m = fn ($type) => ['master.index', $type];
    $groups = ['Menu' => [['Dashboard', 'layout-dashboard', 'dashboard', 'dashboard']]];
    if ($u->hasRole('operator')) {
        $groups['Menu'][] = ['Surat', 'mails', null, null, [
            ['Surat Masuk', 'inbox', 'surat-masuk.index', 'surat-masuk.*'],
            ['Surat Keluar', 'send', 'surat-keluar.index', 'surat-keluar.index'],
            ['Daftar Nomor', 'list-ordered', 'surat-keluar.daftar-nomor', 'surat-keluar.daftar-nomor*'],
        ]];
    }
    if ($u->hasRole('pimpinan', 'sekpri', 'user')) {
        $groups['Menu'][] = ['Surat Masuk', 'inbox', null, null, array_values(array_filter([
            $u->disposisi_awal ? ['Surat Masuk', 'mail-open', 'surat-masuk.index', 'surat-masuk.*'] : null,
            ['Disposisi', 'git-pull-request-arrow', 'disposisi.inbox', 'disposisi.inbox'],
            $u->isSekpriDireksi() ? ['Disposisi Keluar', 'forward', 'disposisi.outbox', 'disposisi.outbox'] : null,
            ['Riwayat Disposisi', 'git-fork', 'disposisi.riwayat-index', 'disposisi.riwayat*'],
        ]))];
        $groups['Menu'][] = ['Surat Keluar', 'send', null, null, [
            ['Surat Keluar', 'send-horizontal', 'surat-keluar.index', 'surat-keluar.index'],
            ['Verifikasi', 'scan-search', 'surat-keluar.queue.verifikasi', 'surat-keluar.queue.verifikasi'],
            ['Tanda Tangan', 'signature', 'surat-keluar.queue.tandatangan', 'surat-keluar.queue.tandatangan'],
            ['Daftar Nomor', 'list-ordered', 'surat-keluar.daftar-nomor', 'surat-keluar.daftar-nomor*'],
        ]];
    }
    $groups['Menu'][] = ['Agenda', 'calendar-days', 'agenda.index', 'agenda.*'];
    if ($u->hasRole('admin')) {
        $groups['Menu'][] = ['Laporan', 'chart-column-big', null, null, [
            ['Rekap Jumlah Surat', 'sheet', 'laporan.rekap', 'laporan.rekap*'],
        ]];
    }
    $groups['Menu'][] = ['Dokumen Saya', 'folder-heart', 'dokumen.index', 'dokumen.index'];
    $groups['Menu'][] = ['Tautan Berbagi', 'link-2', 'dokumen.links', 'dokumen.links'];

    $groups['Setting'] = [['Profil', 'user-round-cog', null, null, [
        ['Profil & Paraf', 'circle-user-round', 'profile.edit', 'profile.edit'],
        ['Ubah Password', 'key-round', ['profile.edit', ['#password']], 'profile.password'],
    ]]];
    if ($u->hasRole('admin', 'operator')) {
        $groups['Setting'][] = ['Administrasi', 'building-2', null, null, array_values(array_filter([
            ['User', 'users-round', 'users.index', 'users.*'],
            $u->hasRole('admin') ? ['Satuan Kerja', 'landmark', $m('satker'), 'satker'] : null,
            $u->hasRole('admin') ? ['Jabatan', 'briefcase-business', $m('jabatan'), 'jabatan'] : null,
            $u->hasRole('admin') ? ['SDM', 'contact-round', 'sdm.index', 'sdm.*'] : null,
        ]))];
    }
    $setSurat = array_values(array_filter([
        $u->hasRole('admin') ? ['Klasifikasi Surat', 'tags', $m('klasifikasi'), 'klasifikasi'] : null,
        $u->hasRole('admin') ? ['Sifat Surat', 'gauge', $m('sifat'), 'sifat'] : null,
        $u->hasRole('admin') ? ['Klasifikasi Keamanan', 'shield-check', $m('klasifikasi-keamanan'), 'klasifikasi-keamanan'] : null,
        $u->hasRole('admin') ? ['Subjek Disposisi', 'list-checks', $m('subjek-disposisi'), 'subjek-disposisi'] : null,
        $u->hasRole('admin') ? ['Template Surat', 'file-type-2', 'template-surat.index', 'template-surat.*'] : null,
        $u->hasRole('admin') ? ['Unit Pemrakarsa', 'building', $m('unit-pemrakarsa'), 'unit-pemrakarsa'] : null,
        $u->hasRole('operator') ? ['Template Nomor Surat', 'hash', 'template-nomor.index', 'template-nomor.*'] : null,
        $u->hasRole('operator') ? ['Nomor Cadangan', 'list-plus', $m('nomor-cadangan'), 'nomor-cadangan'] : null,
        $u->hasRole('admin', 'operator') ? ['Nomor Inisiasi', 'list-start', $m('nomor-inisiasi'), 'nomor-inisiasi'] : null,
        $u->hasRole('user') ? ['Alur Surat', 'route', 'alur-surat.index', 'alur-surat.*'] : null,
        $u->hasRole('admin', 'operator', 'user') ? ['Template Tujuan Surat', 'users-round', 'template-tujuan.index', 'template-tujuan.*'] : null,
    ]));
    if ($setSurat) {
        $groups['Setting'][] = ['Surat', 'file-cog', null, null, $setSurat];
    }
    $groups['Setting'][] = ['Catatan Aktivitas', 'activity', 'logs.index', 'logs.index'];
    if ($u->hasRole('admin')) {
        $groups['Setting'][] = ['Catatan Aktivitas Esign', 'file-signature', 'logs.esign', 'logs.esign'];
        $groups['Setting'][] = ['Transfer Surat', 'arrow-right-left', null, null, [
            ['Disposisi Surat', 'git-pull-request-arrow', ['transfer.index', 'disposisi'], 'disposisi'],
            ['Surat Keluar', 'send', ['transfer.index', 'keluar'], 'keluar'],
        ]];
        $groups['Setting'][] = ['Setting Sistem', 'settings-2', 'setting.index', 'setting.*'];
        $groups['Setting'][] = ['Jadwal', 'calendar-range', null, null, [
            ['Hari Libur', 'calendar-x-2', $m('hari-libur'), 'hari-libur'],
        ]];
    }

    $href = function ($route) {
        if (is_array($route) && is_array($route[1])) {
            return route($route[0]) . $route[1][0];
        }
        return is_array($route) ? route($route[0], $route[1]) : route($route);
    };
    $isActive = fn ($route, $match) => is_array($route) && !is_array($route[1]) ? (request()->route('type') ?? request()->route('jenis')) === $match && request()->routeIs($route[0]) : request()->routeIs($match);
@endphp
<nav class="flex flex-col gap-5" aria-label="Navigasi utama">
    @foreach ($groups as $title => $items)
        <div>
            <p class="sb-label px-3 mb-2 text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">{{ $title }}</p>
            <div class="flex flex-col gap-0.5">
                @foreach ($items as $item)
                    @php [$label, $icon, $route, $match] = $item; $children = $item[4] ?? null; $slug = \Illuminate\Support\Str::slug($label); @endphp
                    @if ($children)
                        @php $open = collect($children)->contains(fn ($c) => $isActive($c[2], $c[3])); @endphp
                        <details class="nav-group group/nav" @if ($open) open @endif data-testid="{{ $prefix }}-nav-group-{{ $slug }}">
                            <summary class="nav-link cursor-pointer list-none {{ $open ? 'active' : '' }}" title="{{ $label }}" data-sb-expand data-testid="{{ $prefix }}-nav-toggle-{{ $slug }}">
                                <span class="nav-ico"><i data-lucide="{{ $icon }}" class="w-[18px] h-[18px]"></i></span>
                                <span class="sb-label flex-1">{{ $label }}</span>
                                <i data-lucide="chevron-down" class="sb-label w-4 h-4 mr-5 {{ $open ? 'text-white/80' : 'text-slate-400' }} transition-transform group-open/nav:rotate-180"></i>
                            </summary>
                            <div class="nav-children mt-1 mb-1 ml-[1.6rem] pl-3 border-l border-slate-200 flex flex-col gap-0.5">
                                @foreach ($children as [$cl, $ci, $cr, $cm])
                                    <a href="{{ $href($cr) }}" class="nav-sub {{ $isActive($cr, $cm) ? 'active' : '' }}" data-testid="{{ $prefix }}-nav-{{ $slug }}-{{ \Illuminate\Support\Str::slug($cl) }}">
                                        <i data-lucide="{{ $ci }}" class="w-4 h-4"></i><span>{{ $cl }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </details>
                    @else
                        <a href="{{ $href($route) }}" class="nav-link {{ $isActive($route, $match) ? 'active' : '' }}" title="{{ $label }}" data-testid="{{ $prefix }}-nav-{{ $slug }}">
                            <span class="nav-ico"><i data-lucide="{{ $icon }}" class="w-[18px] h-[18px]"></i></span>
                            <span class="sb-label">{{ $label }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach
</nav>

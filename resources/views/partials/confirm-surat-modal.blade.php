<div id="{{ $id ?? 'surat-confirm-modal' }}" class="modal-backdrop" data-testid="surat-confirm-modal">
    <div class="modal-panel">
        <div class="flex items-center gap-3">
            <span class="grid place-items-center w-12 h-12 rounded-2xl bg-ruby-50 text-ruby-700"><i data-lucide="file-check-2" class="w-6 h-6"></i></span>
            <div><p class="eyebrow">Konfirmasi</p><h3 class="font-display text-xl font-bold text-maroon-950" data-confirm-title>Simpan surat ini?</h3></div>
        </div>
        <p class="text-sm text-slate-500 mt-3">Periksa kembali ringkasan berikut sebelum melanjutkan.</p>
        <dl class="mt-4 space-y-2 max-h-[45vh] overflow-y-auto scrollbar-thin" data-confirm-summary data-testid="surat-confirm-summary"></dl>
        <div class="grid grid-cols-2 gap-2 mt-5">
            <button type="button" class="btn btn-ghost" data-modal-close data-testid="surat-confirm-cancel">Periksa lagi</button>
            <button type="button" class="btn btn-primary" data-confirm-ok data-testid="surat-confirm-ok"><i data-lucide="check" class="w-4 h-4"></i> Ya, lanjutkan</button>
        </div>
    </div>
</div>

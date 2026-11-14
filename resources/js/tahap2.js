const $ = (s, el = document) => el.querySelector(s);
const $$ = (s, el = document) => [...el.querySelectorAll(s)];
const csrf = () => $('meta[name="csrf-token"]')?.content;
const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

/* Rich text editor (contenteditable -> hidden textarea) */
$$('[data-rich-editor]').forEach((wrap) => {
    const area = $('[data-rich-area]', wrap);
    const out = $('textarea', wrap);
    area.innerHTML = out.value;
    const sync = () => { out.value = area.innerHTML; };
    area.addEventListener('input', sync);
    $$('[data-cmd]', wrap).forEach((b) => b.addEventListener('click', (e) => {
        e.preventDefault();
        area.focus();
        const [cmd, val] = b.dataset.cmd.split(':');
        document.execCommand(cmd, false, val || null);
        sync();
    }));
    wrap.insertCode = (code) => { area.focus(); document.execCommand('insertText', false, '${' + code + '}'); sync(); };
    wrap.closest('form')?.addEventListener('submit', sync);
});
let lastEditor = $('[data-rich-editor]');
$$('[data-rich-editor]').forEach((w) => w.addEventListener('focusin', () => { lastEditor = w; }));
document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-insert-code]');
    if (!b || !lastEditor) return;
    e.preventDefault();
    lastEditor.insertCode(b.dataset.insertCode);
});

/* Repeater rows (isian template, segmen nomor) */
$$('[data-repeater]').forEach((wrap) => {
    const list = $('[data-repeater-list]', wrap);
    const tpl = $('template', wrap);
    const renumber = () => $$('[data-repeater-row]', list).forEach((row, i) => {
        $$('[data-name]', row).forEach((f) => { f.name = f.dataset.name.replace('__i__', i); });
        const n = $('[data-row-no]', row); if (n) n.textContent = i + 1;
    });
    const add = () => { list.insertAdjacentHTML('beforeend', tpl.innerHTML); renumber(); window.refreshIcons?.(); wrap.dispatchEvent(new Event('repeater:change')); };
    $('[data-repeater-add]', wrap)?.addEventListener('click', (e) => { e.preventDefault(); add(); });
    list.addEventListener('click', (e) => {
        const b = e.target.closest('[data-repeater-act]');
        if (!b) return;
        e.preventDefault();
        const row = b.closest('[data-repeater-row]');
        if (b.dataset.repeaterAct === 'del') row.remove();
        if (b.dataset.repeaterAct === 'up' && row.previousElementSibling) row.parentNode.insertBefore(row, row.previousElementSibling);
        renumber();
        wrap.dispatchEvent(new Event('repeater:change'));
    });
    list.addEventListener('input', () => wrap.dispatchEvent(new Event('repeater:change')));
    list.addEventListener('change', () => wrap.dispatchEvent(new Event('repeater:change')));
    renumber();
});

/* Preview format nomor */
const nomorWrap = $('[data-nomor-builder]');
if (nomorWrap) {
    const samples = JSON.parse(nomorWrap.dataset.samples);
    const free = JSON.parse(nomorWrap.dataset.free);
    const out = $('[data-nomor-preview]');
    const pad = $('input[name=padding]');
    const update = () => {
        const parts = $$('[data-repeater-row]', nomorWrap).map((row) => {
            const type = $('select', row).value;
            const valInput = $('input[type=text]', row);
            valInput.classList.toggle('hidden', !free.includes(type));
            if (type === 'nomor') return '1'.padStart(Number(pad?.value || 4), '0');
            return free.includes(type) ? valInput.value.trim() : samples[type];
        }).filter(Boolean);
        out.textContent = parts.join('/') || '—';
    };
    nomorWrap.addEventListener('repeater:change', update);
    pad?.addEventListener('input', update);
    update();
}

/* Template surat dynamic isian in surat keluar form */
const tplSelect = $('[data-template-select]');
if (tplSelect) {
    const templates = JSON.parse(tplSelect.dataset.templates);
    const values = JSON.parse(tplSelect.dataset.values || '{}');
    const box = $('[data-template-fields]');
    const upload = $('[data-upload-box]');
    const fileInput = $('input[name=file]', upload);
    const render = () => {
        const t = templates.find((x) => String(x.id) === tplSelect.value);
        upload.classList.toggle('hidden', !!t);
        if (fileInput) fileInput.required = !t && fileInput.dataset.required === '1';
        $('[data-template-panel]').classList.toggle('hidden', !t);
        if (!t) { box.innerHTML = ''; return; }
        $('[data-template-desc]').textContent = t.deskripsi || '';
        box.innerHTML = t.isians.map((f) => {
            const v = esc(values[f.kode] ?? '');
            const name = `isian[${esc(f.kode)}]`;
            const tid = `isian-${esc(f.kode.toLowerCase())}-input`;
            const field = f.jenis === 'textarea' ? `<textarea name="${name}" rows="4" class="input" data-testid="${tid}">${v}</textarea>`
                : `<input type="${f.jenis === 'tanggal' ? 'date' : f.jenis === 'angka' ? 'number' : 'text'}" name="${name}" value="${v}" class="input" data-testid="${tid}">`;
            return `<div class="${f.jenis === 'textarea' ? 'sm:col-span-2' : ''}"><label class="label">${esc(f.nama)} <span class="font-mono text-[10px] text-slate-400">\${${esc(f.kode)}}</span></label>${field}</div>`;
        }).join('') || '<p class="text-sm text-slate-400">Template ini tidak memiliki isian tambahan.</p>';
    };
    tplSelect.addEventListener('change', () => { Object.keys(values).forEach((k) => delete values[k]); render(); });
    render();
}

/* Apply saved alur / tujuan templates */
document.addEventListener('change', (e) => {
    const sel = e.target.closest('[data-apply-alur]');
    if (sel && sel.value) {
        const a = JSON.parse(sel.selectedOptions[0].dataset.alur);
        $$('[data-alur-builder]').forEach((w) => w._render?.((a[w.dataset.alurBuilder] || []).map(String)));
        const radio = $(`input[name=metode_verifikasi][value=${a.metode_verifikasi}]`);
        if (radio) radio.checked = true;
        window.toast?.('Alur surat diterapkan');
        sel.value = '';
    }
    const tsel = e.target.closest('[data-apply-tujuan]');
    if (tsel && tsel.value) {
        const t = JSON.parse(tsel.selectedOptions[0].dataset.tujuan);
        ['tujuan', 'tembusan'].forEach((j) => $$(`[name="${j}_satker[]"]`).forEach((c) => {
            const ids = (t[j] || []).map(String);
            if (c.tagName === 'SELECT') c.value = ids[0] || ''; else c.checked = ids.includes(c.value);
        }));
        window.toast?.('Template tujuan diterapkan');
        tsel.value = '';
    }
    const dsel = e.target.closest('[data-apply-tujuan-disposisi]');
    if (dsel && dsel.value) {
        const ids = JSON.parse(dsel.selectedOptions[0].dataset.penerima).map(String);
        const target = $(dsel.dataset.applyTujuanDisposisi);
        [...target.options].forEach((o) => { o.selected = ids.includes(o.value); });
        dsel.value = '';
    }
});

/* Simpan grup penerima disposisi */
$$('[data-save-tujuan-disposisi]').forEach((b) => b.addEventListener('click', async (e) => {
    e.preventDefault();
    const select = $(b.dataset.saveTujuanDisposisi);
    const ids = [...select.selectedOptions].map((o) => o.value);
    if (!ids.length) { window.toast?.('Pilih penerima terlebih dahulu', 'error'); return; }
    const nama = prompt('Nama grup penerima disposisi:');
    if (!nama) return;
    const res = await fetch(b.dataset.url, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify({ nama, penerima: ids }) });
    if (!res.ok) { window.toast?.('Gagal menyimpan grup', 'error'); return; }
    const row = await res.json();
    const opt = Object.assign(document.createElement('option'), { value: row.id, textContent: row.nama });
    opt.dataset.penerima = JSON.stringify(row.penerima);
    $(`[data-apply-tujuan-disposisi="${b.dataset.saveTujuanDisposisi}"]`)?.appendChild(opt);
    window.toast?.('Grup penerima disimpan');
}));

/* Cek username / NIP unik */
$$('[data-unique-check]').forEach((input) => {
    const msg = Object.assign(document.createElement('p'), { className: 'text-[11px] mt-1' });
    msg.setAttribute('data-testid', `${input.name}-unique-status`);
    input.after(msg);
    let t = null;
    input.addEventListener('input', () => {
        clearTimeout(t);
        t = setTimeout(async () => {
            if (!input.value.trim()) { msg.textContent = ''; return; }
            const url = new URL(input.dataset.uniqueCheck, location.origin);
            url.searchParams.set('field', input.dataset.field);
            url.searchParams.set('value', input.value.trim());
            if (input.dataset.ignore) url.searchParams.set('ignore', input.dataset.ignore);
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            msg.textContent = data.message;
            msg.className = `text-[11px] mt-1 font-semibold ${data.available ? 'text-emerald-700' : 'text-rose-600'}`;
            input.setCustomValidity(data.available ? '' : data.message);
        }, 400);
    });
});

/* Tag widget */
$$('[data-tag-form]').forEach((form) => form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() } });
    if (!res.ok) { window.toast?.('Gagal menyimpan tag', 'error'); return; }
    const { tags } = await res.json();
    $('[data-tag-list]', form.parentElement).innerHTML = tags.map((t) => `<span class="badge badge-gold">#${esc(t)}</span>`).join('') || '<span class="text-xs text-slate-400">Belum ada tag</span>';
    window.toast?.('Tag disimpan');
}));

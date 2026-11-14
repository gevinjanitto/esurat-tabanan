import '@fontsource-variable/onest';
import '@fontsource-variable/sora';
import '@fontsource/jetbrains-mono/400.css';
import '@fontsource/jetbrains-mono/600.css';
import gsap from 'gsap';
import { createIcons, icons } from 'lucide';
import './tahap2';

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const $ = (s, el = document) => el.querySelector(s);
const $$ = (s, el = document) => [...el.querySelectorAll(s)];
const csrf = () => $('meta[name="csrf-token"]')?.content;

window.refreshIcons = () => createIcons({ icons, attrs: { 'stroke-width': 1.9 } });
window.refreshIcons();
if (reduced) document.documentElement.classList.add('reduced');

/* Toasts */
window.toast = (message, type = 'success') => {
    let box = $('#toast-stack');
    if (!box) {
        box = Object.assign(document.createElement('div'), { id: 'toast-stack', className: 'fixed z-[90] top-4 right-4 left-4 sm:left-auto flex flex-col gap-2 items-end pointer-events-none' });
        document.body.appendChild(box);
    }
    const colors = { success: 'bg-maroon-900 text-white', error: 'bg-rose-600 text-white', info: 'bg-white text-slate-800 border border-slate-200' };
    const icon = { success: 'circle-check', error: 'circle-alert', info: 'info' }[type] || 'info';
    const el = document.createElement('div');
    el.setAttribute('data-testid', `toast-${type}`);
    el.className = `pointer-events-auto flex items-start gap-3 rounded-2xl px-4 py-3 text-sm font-medium shadow-2xl max-w-sm w-full sm:w-auto ${colors[type] || colors.info}`;
    el.innerHTML = `<i data-lucide="${icon}" class="w-5 h-5 shrink-0"></i><span class="break-words"></span>`;
    el.querySelector('span').textContent = message;
    box.appendChild(el);
    window.refreshIcons();
    gsap.fromTo(el, { y: -20, opacity: 0, scale: .95 }, { y: 0, opacity: 1, scale: 1, duration: .45, ease: 'back.out(1.8)' });
    setTimeout(() => gsap.to(el, { x: 40, opacity: 0, duration: .35, onComplete: () => el.remove() }), 4200);
};
$$('[data-flash]').forEach((el) => window.toast(el.dataset.flash, el.dataset.type));

/* Reveal animations */
const revealEls = $$('[data-reveal]');
if (reduced) {
    revealEls.forEach((el) => (el.style.opacity = 1));
} else if (revealEls.length) {
    gsap.fromTo(revealEls, { y: 24, opacity: 0 }, { y: 0, opacity: 1, duration: .7, ease: 'power3.out', stagger: .06, clearProps: 'transform' });
}
$$('[data-count]').forEach((el) => {
    const target = Number(el.dataset.count) || 0;
    const obj = { v: 0 };
    gsap.to(obj, { v: target, duration: reduced ? 0 : 1.4, ease: 'power2.out', onUpdate: () => (el.textContent = Math.round(obj.v).toLocaleString('id-ID')) });
});
$$('[data-bar]').forEach((el, i) => gsap.fromTo(el, { scaleY: 0 }, { scaleY: 1, transformOrigin: 'bottom', duration: 1, delay: .3 + i * .05, ease: 'elastic.out(1, .7)' }));

/* Spotlight + magnetic */
document.addEventListener('pointermove', (e) => {
    const card = e.target.closest?.('.spotlight');
    if (card) {
        const r = card.getBoundingClientRect();
        card.style.setProperty('--mx', `${e.clientX - r.left}px`);
        card.style.setProperty('--my', `${e.clientY - r.top}px`);
    }
});
if (!reduced && matchMedia('(pointer:fine)').matches) {
    $$('[data-magnetic]').forEach((el) => {
        el.addEventListener('pointermove', (e) => {
            const r = el.getBoundingClientRect();
            gsap.to(el, { x: (e.clientX - r.left - r.width / 2) * .25, y: (e.clientY - r.top - r.height / 2) * .3, duration: .3 });
        });
        el.addEventListener('pointerleave', () => gsap.to(el, { x: 0, y: 0, duration: .5, ease: 'elastic.out(1,.4)' }));
    });
}

/* Dropdowns */
document.addEventListener('click', (e) => {
    const toggle = e.target.closest('[data-dropdown-toggle]');
    $$('[data-dropdown]').forEach((dd) => {
        if (toggle && dd.id === toggle.dataset.dropdownToggle) return;
        if (!dd.contains(e.target)) dd.classList.add('hidden');
    });
    if (toggle) {
        const dd = document.getElementById(toggle.dataset.dropdownToggle);
        dd.classList.toggle('hidden');
        if (!dd.classList.contains('hidden')) gsap.fromTo(dd, { y: -8, opacity: 0 }, { y: 0, opacity: 1, duration: .25, ease: 'power2.out' });
    }
});

/* Modals & drawer */
window.openModal = (id) => {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.add('open');
    gsap.fromTo(m.querySelector('.modal-panel'), { y: 60, opacity: 0, scale: .97 }, { y: 0, opacity: 1, scale: 1, duration: .45, ease: 'back.out(1.4)' });
};
window.closeModal = (m) => m?.classList.remove('open');
document.addEventListener('click', (e) => {
    const open = e.target.closest('[data-modal-open]');
    if (open) { e.preventDefault(); window.openModal(open.dataset.modalOpen); }
    const close = e.target.closest('[data-modal-close]');
    if (close) window.closeModal(close.closest('.modal-backdrop'));
    if (e.target.classList?.contains('modal-backdrop')) window.closeModal(e.target);
});
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') $$('.modal-backdrop.open').forEach(window.closeModal); });

const drawer = $('#mobile-drawer');
$$('[data-drawer-open]').forEach((b) => b.addEventListener('click', () => {
    drawer.classList.remove('hidden');
    gsap.fromTo(drawer.querySelector('aside'), { x: '-100%' }, { x: 0, duration: .45, ease: 'power3.out' });
    gsap.fromTo(drawer.querySelector('.drawer-bg'), { opacity: 0 }, { opacity: 1, duration: .3 });
}));
$$('[data-drawer-close]').forEach((b) => b.addEventListener('click', () => {
    gsap.to(drawer.querySelector('aside'), { x: '-100%', duration: .3, ease: 'power2.in', onComplete: () => drawer.classList.add('hidden') });
}));

/* Confirm + copy */
document.addEventListener('change', (e) => {
    const t = e.target.closest('[data-agenda-toggle]');
    if (t) t.closest('[data-agenda-toggle-wrap]').querySelector('[data-agenda-fields]').classList.toggle('hidden', !t.checked);
    const s = e.target.closest('[data-agenda-sumber]');
    if (s) $$('[data-agenda-surat-wrap]').forEach((w) => w.classList.toggle('hidden', s.value !== 'surat'));
});
document.addEventListener('submit', (e) => {
    const form = e.target;
    const modalId = form.dataset.confirmModal;
    const sub = e.submitter;
    if (modalId && !form._confirmed && !sub?.hasAttribute('formaction')) {
        const modal = document.getElementById(modalId);
        if (modal) {
            e.preventDefault();
            const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
            const rows = [];
            $$('[data-confirm-label]', form).forEach((el) => {
                if (el.type === 'radio' && !el.checked) return;
                let v = el.tagName === 'SELECT' ? (el.value ? el.selectedOptions[0].textContent : '') : (el.type === 'radio' ? el.closest('label')?.querySelector('.font-bold')?.textContent : el.value);
                rows.push([el.dataset.confirmLabel, (v || '').trim() || '—']);
            });
            $$('[data-confirm-list]', form).forEach((el) => {
                const items = $$('li', el).map((li) => li.querySelector('p')?.textContent || '').filter(Boolean);
                rows.push([el.dataset.confirmList, items.join(', ') || '—']);
            });
            const title = $('[data-confirm-title]', modal);
            if (title) title.textContent = sub?.value === 'draft' ? 'Simpan sebagai konsep?' : (sub?.value === 'ajukan' ? 'Simpan & ajukan surat ini?' : 'Simpan surat ini?');
            $('[data-confirm-summary]', modal).innerHTML = rows.map(([k, v]) => `<div class="rounded-2xl bg-slate-50 px-3.5 py-2.5"><dt class="text-[11px] font-bold uppercase tracking-wider text-slate-400">${esc(k)}</dt><dd class="mt-0.5 text-sm font-semibold text-slate-800 break-words">${esc(v)}</dd></div>`).join('');
            $('[data-confirm-ok]', modal).onclick = () => { form._confirmed = true; window.closeModal(modal); sub ? form.requestSubmit(sub) : form.requestSubmit(); };
            window.openModal(modal.id);
            return;
        }
    }
    const msg = form.dataset.confirm;
    if (msg && !confirm(msg)) e.preventDefault();
});
document.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-copy]');
    if (!b) return;
    try { await navigator.clipboard.writeText(b.dataset.copy); } catch { const t = Object.assign(document.createElement('textarea'), { value: b.dataset.copy }); document.body.append(t); t.select(); document.execCommand('copy'); t.remove(); }
    window.toast('Tautan disalin ke clipboard');
});

/* File drop zones */
$$('[data-dropzone]').forEach((zone) => {
    const input = $('input[type=file]', zone);
    const name = $('[data-file-name]', zone);
    const set = () => { if (input.files[0]) { name.textContent = input.files[0].name; zone.classList.add('border-ruby-500', 'bg-ruby-50/60'); } };
    input.addEventListener('change', set);
    ['dragenter', 'dragover'].forEach((ev) => zone.addEventListener(ev, (e) => { e.preventDefault(); zone.classList.add('border-ruby-500'); }));
    zone.addEventListener('dragleave', () => zone.classList.remove('border-ruby-500'));
    zone.addEventListener('drop', (e) => { e.preventDefault(); input.files = e.dataTransfer.files; set(); });
});

/* Alur builder (verifikator & penandatangan ordered lists) */
$$('[data-alur-builder]').forEach((wrap) => {
    const name = wrap.dataset.alurBuilder;
    const list = $('[data-alur-list]', wrap);
    const select = $('select', wrap);
    const users = JSON.parse(wrap.dataset.users);
    const render = (ids) => {
        list.innerHTML = '';
        ids.forEach((id, i) => {
            const u = users.find((x) => String(x.id) === String(id));
            if (!u) return;
            const li = document.createElement('li');
            li.className = 'flex items-center gap-3 rounded-2xl bg-white border border-slate-200 px-3 py-2.5';
            li.dataset.id = id;
            li.setAttribute('data-testid', `alur-${name}-item-${i}`);
            li.innerHTML = `<span class="grid place-items-center w-7 h-7 rounded-full bg-maroon-900 text-white text-xs font-bold">${i + 1}</span>
                <div class="min-w-0 flex-1"><p class="text-sm font-semibold truncate"></p><p class="text-xs text-slate-500 truncate"></p></div>
                <input type="hidden" name="${name}[]" value="${id}">
                <button type="button" data-act="up" class="btn-icon text-slate-400 hover:text-maroon-700" aria-label="Naik"><i data-lucide="chevron-up" class="w-4 h-4"></i></button>
                <button type="button" data-act="del" class="btn-icon text-slate-400 hover:text-rose-600" aria-label="Hapus" data-testid="alur-${name}-remove-${i}"><i data-lucide="x" class="w-4 h-4"></i></button>`;
            li.querySelector('p').textContent = u.name;
            li.querySelectorAll('p')[1].textContent = u.jabatan || '';
            list.appendChild(li);
        });
        window.refreshIcons();
    };
    const ids = () => $$('li', list).map((li) => li.dataset.id);
    wrap._render = render;
    render(JSON.parse(wrap.dataset.selected || '[]'));
    select.addEventListener('change', () => {
        if (select.value && !ids().includes(select.value)) { render([...ids(), select.value]); gsap.from(list.lastElementChild, { x: -20, opacity: 0, duration: .35 }); }
        select.value = '';
    });
    list.addEventListener('click', (e) => {
        const b = e.target.closest('button');
        if (!b) return;
        const cur = ids();
        const idx = cur.indexOf(b.closest('li').dataset.id);
        if (b.dataset.act === 'del') cur.splice(idx, 1);
        if (b.dataset.act === 'up' && idx > 0) [cur[idx - 1], cur[idx]] = [cur[idx], cur[idx - 1]];
        render(cur);
    });
});

/* Dokumen Saya: selection + share */
const hub = $('[data-doc-hub]');
if (hub) {
    const bar = $('#share-bar');
    const count = $('[data-selected-count]');
    const sync = () => {
        const n = $$('[data-doc-check]:checked').length;
        count.textContent = n;
        if (n && bar.classList.contains('translate-y-[160%]')) bar.classList.remove('translate-y-[160%]');
        if (!n) bar.classList.add('translate-y-[160%]');
    };
    hub.addEventListener('change', (e) => { if (e.target.matches('[data-doc-check]')) sync(); });
    $('#select-all')?.addEventListener('click', () => { const all = $$('[data-doc-check]'); const on = all.some((c) => !c.checked); all.forEach((c) => (c.checked = on)); sync(); });
    $$('[data-share-one]').forEach((b) => b.addEventListener('click', () => {
        $$('[data-doc-check]').forEach((c) => (c.checked = c.value === b.dataset.shareOne));
        sync(); window.openModal('share-modal');
    }));
    const form = $('#share-form');
    $$('input[name=durasi]', form).forEach((r) => r.addEventListener('change', () => $('#custom-expiry').classList.toggle('hidden', r.value !== 'custom' || !r.checked)));
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(form);
        $$('[data-doc-check]:checked').forEach((c) => fd.append('items[]', c.value));
        const btn = $('button[type=submit]', form);
        btn.disabled = true;
        try {
            const res = await fetch(form.action, { method: 'POST', body: fd, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() } });
            const data = await res.json();
            if (!res.ok) throw new Error(Object.values(data.errors || {})[0]?.[0] || data.message);
            $('#share-result').classList.remove('hidden');
            $('#share-url').value = data.url;
            $('#share-copy').dataset.copy = data.url;
            $('#share-wa').href = `https://wa.me/?text=${encodeURIComponent(data.url)}`;
            $('#share-meta').textContent = `${data.permission === 'download' ? 'Bisa lihat & unduh' : 'Hanya lihat'} · ${data.expires_at ? 'aktif s/d ' + data.expires_at : 'tanpa batas waktu'}`;
            gsap.fromTo('#share-result', { scale: .9, opacity: 0 }, { scale: 1, opacity: 1, duration: .5, ease: 'back.out(2)' });
            window.toast('Tautan berbagi berhasil dibuat');
        } catch (err) { window.toast(err.message || 'Gagal membuat tautan', 'error'); }
        btn.disabled = false;
    });
}

/* Signature pad (profil paraf) */
$$('[data-signature-pad]').forEach((wrap) => {
    const canvas = $('canvas', wrap);
    const out = $('input[name=paraf_drawn]', wrap);
    const ctx = canvas.getContext('2d');
    const fit = () => { const r = canvas.getBoundingClientRect(); canvas.width = r.width * 2; canvas.height = r.height * 2; ctx.scale(2, 2); ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.lineWidth = 2.6; ctx.strokeStyle = '#a3122f'; };
    fit();
    let drawing = false; let dirty = false;
    const pos = (e) => { const r = canvas.getBoundingClientRect(); return [e.clientX - r.left, e.clientY - r.top]; };
    canvas.addEventListener('pointerdown', (e) => { drawing = true; canvas.setPointerCapture(e.pointerId); ctx.beginPath(); ctx.moveTo(...pos(e)); });
    canvas.addEventListener('pointermove', (e) => { if (!drawing) return; ctx.lineTo(...pos(e)); ctx.stroke(); dirty = true; });
    const end = () => { if (drawing && dirty) out.value = canvas.toDataURL('image/png'); drawing = false; };
    canvas.addEventListener('pointerup', end); canvas.addEventListener('pointercancel', end);
    $('[data-clear]', wrap).addEventListener('click', () => { ctx.clearRect(0, 0, canvas.width, canvas.height); out.value = ''; dirty = false; });
});

/* Page leave transition */
document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href]');
    if (!a || reduced || a.target || a.hasAttribute('download') || e.metaKey || e.ctrlKey || a.href.includes('#') || a.origin !== location.origin || a.dataset.noTransition !== undefined || a.href.includes('/file')) return;
    const main = $('#page-main');
    if (main) gsap.to(main, { opacity: .35, y: 6, duration: .18 });
});
window.addEventListener('pageshow', () => { const m = $('#page-main'); if (m) gsap.set(m, { opacity: 1, y: 0 }); });

/* Autosave draft form surat keluar */
$$('form[data-autosave-url]').forEach((form) => {
    const status = $('[data-autosave-status]', form);
    let timer = null; let last = '';
    const collect = () => {
        const data = {};
        new FormData(form).forEach((v, k) => {
            if (v instanceof File || ['_token', '_method', 'action'].includes(k)) return;
            if (k.endsWith('[]')) { const key = k.slice(0, -2); (data[key] ||= []).push(v); } else data[k] = v;
        });
        return data;
    };
    const save = async () => {
        const data = collect();
        const json = JSON.stringify(data);
        if (json === last) return;
        last = json;
        if (status) status.textContent = 'Menyimpan draft…';
        try {
            const res = await fetch(form.dataset.autosaveUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify({ key: form.dataset.autosaveKey, data }) });
            const body = await res.json();
            if (status) status.textContent = res.ok ? `Draft tersimpan otomatis · ${body.saved_at}` : 'Gagal menyimpan draft';
        } catch { if (status) status.textContent = 'Gagal menyimpan draft (offline?)'; }
    };
    const schedule = () => { clearTimeout(timer); timer = setTimeout(save, 1500); };
    ['input', 'change', 'click'].forEach((ev) => form.addEventListener(ev, schedule));
    last = JSON.stringify(collect());
});

/* Sidebar ciutkan/perluas */
document.addEventListener('click', (e) => {
    const root = document.documentElement;
    if (e.target.closest('[data-sb-toggle]')) {
        root.classList.toggle('sb-collapsed');
        localStorage.setItem('sb-collapsed', root.classList.contains('sb-collapsed') ? '1' : '0');
        return;
    }
    const sum = e.target.closest('[data-sb-expand]');
    if (sum && root.classList.contains('sb-collapsed') && sum.closest('[data-testid="desktop-sidebar"]')) {
        e.preventDefault();
        root.classList.remove('sb-collapsed');
        localStorage.setItem('sb-collapsed', '0');
        sum.parentElement.open = true;
    }
});

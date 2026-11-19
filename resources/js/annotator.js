import gsap from 'gsap';
import { renderPdf } from './pdf-viewer';

const studio = document.getElementById('studio');
if (studio) initStudio(studio);

function initStudio(root) {
    const cfg = root.dataset;
    const editable = cfg.editable === '1';
    const canParaf = cfg.canParaf === '1';
    const viewer = root.querySelector('[data-studio-pages]');
    const status = root.querySelector('[data-save-status]');
    const notesPanel = document.querySelector('[data-notes-list]');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    let mine = { strokes: [], notes: [], parafs: [] };
    let others = [];
    let pages = [];
    let tool = editable ? 'pen' : 'hand';
    let color = '#e11d48';
    let size = 3;
    let dirty = false;
    let saveTimer = null;
    const undoStack = [];

    const snapshot = () => { undoStack.push(JSON.stringify(mine)); if (undoStack.length > 60) undoStack.shift(); };
    const markDirty = () => {
        dirty = true;
        if (status) status.textContent = 'Belum disimpan';
        clearTimeout(saveTimer);
        saveTimer = setTimeout(save, 2500);
        renderNotesPanel();
    };

    async function save() {
        if (!editable || !dirty) return true;
        clearTimeout(saveTimer);
        if (status) status.textContent = 'Menyimpan…';
        try {
            const res = await fetch(cfg.saveUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(mine) });
            if (!res.ok) throw new Error((await res.json()).message || 'Gagal menyimpan');
            dirty = false;
            if (status) status.textContent = 'Tersimpan ' + new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            return true;
        } catch (e) {
            if (status) status.textContent = 'Gagal menyimpan';
            window.toast?.(e.message, 'error');
            return false;
        }
    }
    window.studioSave = save;

    /* Toolbar */
    root.querySelectorAll('[data-tool]').forEach((b) => b.addEventListener('click', () => setTool(b.dataset.tool)));
    root.querySelectorAll('[data-color]').forEach((b) => b.addEventListener('click', () => {
        color = b.dataset.color;
        root.querySelectorAll('[data-color]').forEach((x) => x.classList.toggle('ring-2', x === b));
        if (!['pen', 'highlighter'].includes(tool)) setTool('pen');
    }));
    root.querySelector('[data-size]')?.addEventListener('input', (e) => (size = Number(e.target.value)));
    root.querySelector('[data-undo]')?.addEventListener('click', () => { if (!undoStack.length) return; mine = JSON.parse(undoStack.pop()); redrawAll(); markDirty(); });
    root.querySelector('[data-clear]')?.addEventListener('click', () => { if (!confirm('Hapus semua coretan, catatan, dan paraf Anda pada dokumen ini?')) return; snapshot(); mine = { strokes: [], notes: [], parafs: [] }; redrawAll(); markDirty(); });
    root.querySelector('[data-save]')?.addEventListener('click', async () => { dirty = true; if (await save()) window.toast?.('Anotasi tersimpan'); });

    function setTool(t) {
        tool = t;
        root.querySelectorAll('[data-tool]').forEach((b) => {
            const on = b.dataset.tool === t;
            b.classList.toggle('bg-maroon-900', on);
            b.classList.toggle('text-white', on);
            b.setAttribute('aria-pressed', on);
        });
        pages.forEach((p) => p.layer.classList.toggle('drawing', t !== 'hand'));
    }

    /* Load */
    async function load() {
        const res = await fetch(cfg.loadUrl, { headers: { Accept: 'application/json' } });
        const list = res.ok ? await res.json() : [];
        list.forEach((a) => {
            const data = { strokes: a.data.strokes || [], notes: a.data.notes || [], parafs: a.data.parafs || [] };
            if (a.mine && editable) mine = data;
            else others.push({ author: a.author, data });
        });
    }

    async function build() {
        const result = await renderPdf(viewer, cfg.src, {
            onPage: (p) => {
                const layer = document.createElement('div');
                layer.className = 'anno-layer';
                const c = document.createElement('canvas');
                layer.appendChild(c);
                p.wrap.appendChild(layer);
                p.layer = layer;
                p.canvas = c;
                const ratio = Math.min(window.devicePixelRatio || 1, 2);
                c.width = p.width * ratio;
                c.height = p.height * ratio;
                p.ctx = c.getContext('2d');
                p.ctx.scale(ratio, ratio);
                bindPage(p);
            },
        });
        pages = result.pages;
        setTool(tool);
        redrawAll();
        root.querySelector('[data-page-count]')?.replaceChildren(document.createTextNode(`${pages.length} halaman`));
    }

    /* Drawing */
    function drawStroke(ctx, s, w, h, alpha = 1) {
        if (!s.points.length) return;
        ctx.save();
        ctx.globalAlpha = (s.tool === 'highlighter' ? .32 : .95) * alpha;
        ctx.strokeStyle = s.color;
        ctx.lineWidth = s.size * w;
        ctx.lineCap = s.tool === 'highlighter' ? 'butt' : 'round';
        ctx.lineJoin = 'round';
        ctx.beginPath();
        ctx.moveTo(s.points[0][0] * w, s.points[0][1] * h);
        for (let i = 1; i < s.points.length; i++) {
            const [x0, y0] = s.points[i - 1];
            const [x1, y1] = s.points[i];
            ctx.quadraticCurveTo(x0 * w, y0 * h, ((x0 + x1) / 2) * w, ((y0 + y1) / 2) * h);
        }
        if (s.points.length === 1) ctx.lineTo(s.points[0][0] * w + .1, s.points[0][1] * h);
        ctx.stroke();
        ctx.restore();
    }

    function redrawPage(p) {
        p.ctx.clearRect(0, 0, p.width, p.height);
        others.forEach((o) => o.data.strokes.filter((s) => s.page === p.n).forEach((s) => drawStroke(p.ctx, s, p.width, p.height, .75)));
        mine.strokes.filter((s) => s.page === p.n).forEach((s) => drawStroke(p.ctx, s, p.width, p.height));
        p.layer.querySelectorAll('.anno-note, .anno-paraf').forEach((el) => el.remove());
        others.forEach((o) => {
            o.data.notes.filter((x) => x.page === p.n).forEach((n) => addNoteEl(p, n, false, o.author));
            o.data.parafs.filter((x) => x.page === p.n).forEach((pf) => addParafEl(p, pf, false, o.author));
        });
        mine.notes.filter((x) => x.page === p.n).forEach((n) => addNoteEl(p, n, true, cfg.userName));
        mine.parafs.filter((x) => x.page === p.n).forEach((pf) => addParafEl(p, pf, true, cfg.userName));
        window.refreshIcons?.();
    }
    const redrawAll = () => { pages.forEach(redrawPage); renderNotesPanel(); };

    function rel(p, e) {
        const r = p.layer.getBoundingClientRect();
        return [Math.min(1, Math.max(0, (e.clientX - r.left) / r.width)), Math.min(1, Math.max(0, (e.clientY - r.top) / r.height))];
    }

    function bindPage(p) {
        let current = null;
        p.layer.addEventListener('pointerdown', (e) => {
            if (!editable || tool === 'hand' || e.target.closest('.anno-note, .anno-paraf')) return;
            e.preventDefault();
            const [x, y] = rel(p, e);
            if (tool === 'note') return createNote(p, x, y);
            if (tool === 'paraf') {
                if (!canParaf) return window.toast?.('Paraf hanya untuk verifikator', 'info');
                snapshot();
                mine.parafs.push({ page: p.n, x: Math.min(x, .86), y: Math.min(y, .93), w: .14, h: .065 });
                redrawPage(p); markDirty();
                return;
            }
            p.layer.setPointerCapture(e.pointerId);
            snapshot();
            if (tool === 'eraser') { current = { erase: true }; erase(p, x, y); return; }
            const factor = tool === 'highlighter' ? 4.5 : 1;
            current = { page: p.n, tool, color: tool === 'highlighter' && color === '#e11d48' ? '#facc15' : color, size: Math.min(.1, (size * factor) / p.width), points: [[x, y]] };
            mine.strokes.push(current);
        });
        p.layer.addEventListener('pointermove', (e) => {
            if (!current) return;
            const pts = e.getCoalescedEvents ? e.getCoalescedEvents() : [e];
            pts.forEach((ev) => {
                const [x, y] = rel(p, ev);
                if (current.erase) erase(p, x, y);
                else current.points.push([+x.toFixed(4), +y.toFixed(4)]);
            });
            redrawPage(p);
        });
        const end = () => { if (current) { current = null; markDirty(); } };
        p.layer.addEventListener('pointerup', end);
        p.layer.addEventListener('pointercancel', end);
    }

    function erase(p, x, y) {
        const r = 14 / p.width;
        const before = mine.strokes.length;
        mine.strokes = mine.strokes.filter((s) => s.page !== p.n || !s.points.some(([px, py]) => Math.hypot(px - x, (py - y) * (p.height / p.width)) < r));
        if (before !== mine.strokes.length) redrawPage(p);
    }

    /* Notes */
    function askNote(initial = '') {
        const modal = document.getElementById('note-modal');
        if (!modal) return Promise.resolve(prompt('Tulis catatan:', initial));
        const input = modal.querySelector('textarea');
        input.value = initial;
        window.openModal('note-modal');
        setTimeout(() => input.focus(), 150);
        return new Promise((resolve) => {
            const done = (val) => { modal.querySelector('[data-note-ok]').onclick = null; modal.querySelector('[data-note-cancel]').onclick = null; window.closeModal(modal); resolve(val); };
            modal.querySelector('[data-note-ok]').onclick = () => done(input.value);
            modal.querySelector('[data-note-cancel]').onclick = () => done(null);
        });
    }

    async function createNote(p, x, y) {
        const text = await askNote();
        if (!text?.trim()) return;
        snapshot();
        mine.notes.push({ page: p.n, x, y, text: text.trim().slice(0, 1000) });
        redrawPage(p); markDirty();
        setTool('hand');
    }

    function addNoteEl(p, n, own, author) {
        const el = document.createElement('div');
        el.className = 'anno-note group';
        el.style.left = `${n.x * 100}%`;
        el.style.top = `${n.y * 100}%`;
        el.setAttribute('data-testid', 'annotation-note-pin');
        el.innerHTML = `<button type="button" class="relative grid place-items-center w-7 h-7 rounded-full rounded-bl-none ${own ? 'bg-gold-500 text-maroon-950' : 'bg-sky-600 text-white'} shadow-lg ring-2 ring-white transition-transform hover:scale-110"><i data-lucide="message-square-text" class="w-3.5 h-3.5"></i></button>
            <div class="hidden group-hover:block group-focus-within:block absolute left-8 top-0 z-20 w-56 rounded-2xl bg-white p-3 text-xs shadow-2xl border border-slate-100">
            <p class="font-bold text-maroon-900 mb-1"></p><p class="text-slate-600 whitespace-pre-wrap" data-text></p>
            ${own ? '<div class="flex gap-2 mt-2"><button type="button" data-edit class="text-ruby-700 font-semibold">Ubah</button><button type="button" data-del class="text-rose-600 font-semibold">Hapus</button></div>' : ''}</div>`;
        el.querySelector('p').textContent = author;
        el.querySelector('[data-text]').textContent = n.text;
        el.querySelector('[data-edit]')?.addEventListener('click', async () => { const t = await askNote(n.text); if (t?.trim()) { snapshot(); n.text = t.trim(); redrawPage(p); markDirty(); } });
        el.querySelector('[data-del]')?.addEventListener('click', () => { snapshot(); mine.notes.splice(mine.notes.indexOf(n), 1); redrawPage(p); markDirty(); });
        p.layer.appendChild(el);
    }

    /* Paraf */
    function addParafEl(p, pf, own, author) {
        const el = document.createElement('div');
        el.className = `anno-paraf ${own ? '' : 'locked'}`;
        el.setAttribute('data-testid', 'annotation-paraf');
        Object.assign(el.style, { left: `${pf.x * 100}%`, top: `${pf.y * 100}%`, width: `${pf.w * 100}%`, height: `${pf.h * 100}%` });
        el.title = `Paraf: ${author}`;
        if (own && cfg.parafUrl) el.innerHTML = `<img src="${cfg.parafUrl}" alt="paraf">`;
        else el.innerHTML = `<span class="font-display font-bold italic text-maroon-800 text-xs sm:text-sm">${initials(author)}</span>`;
        if (own) {
            el.insertAdjacentHTML('beforeend', '<span class="rs"></span><button type="button" class="absolute -top-2.5 -right-2.5 grid place-items-center w-5 h-5 rounded-full bg-rose-600 text-white text-[10px]" data-del aria-label="Hapus paraf">✕</button>');
            el.querySelector('[data-del]').addEventListener('click', () => { snapshot(); mine.parafs.splice(mine.parafs.indexOf(pf), 1); redrawPage(p); markDirty(); });
            let mode = null; let start = null;
            el.addEventListener('pointerdown', (e) => {
                if (e.target.closest('[data-del]')) return;
                e.stopPropagation(); e.preventDefault();
                mode = e.target.classList.contains('rs') ? 'resize' : 'move';
                start = { pt: rel(p, e), x: pf.x, y: pf.y, w: pf.w, h: pf.h };
                el.setPointerCapture(e.pointerId);
                snapshot();
            });
            el.addEventListener('pointermove', (e) => {
                if (!mode) return;
                const [x, y] = rel(p, e);
                const dx = x - start.pt[0]; const dy = y - start.pt[1];
                if (mode === 'move') { pf.x = clamp(start.x + dx, 0, 1 - pf.w); pf.y = clamp(start.y + dy, 0, 1 - pf.h); }
                else { pf.w = clamp(start.w + dx, .04, .5); pf.h = clamp(start.h + dy * 1, .02, .3); }
                Object.assign(el.style, { left: `${pf.x * 100}%`, top: `${pf.y * 100}%`, width: `${pf.w * 100}%`, height: `${pf.h * 100}%` });
            });
            const end = () => { if (mode) { mode = null; markDirty(); } };
            el.addEventListener('pointerup', end);
            el.addEventListener('pointercancel', end);
        }
        p.layer.appendChild(el);
        if (own) gsap.fromTo(el, { scale: .6, opacity: 0 }, { scale: 1, opacity: 1, duration: .35, ease: 'back.out(2)' });
    }

    function renderNotesPanel() {
        if (!notesPanel) return;
        const all = [...others.flatMap((o) => o.data.notes.map((n) => ({ ...n, author: o.author, own: false }))), ...mine.notes.map((n) => ({ ...n, author: cfg.userName, own: true }))];
        notesPanel.innerHTML = all.length ? '' : '<p class="text-sm text-slate-400 py-6 text-center" data-testid="notes-empty">Belum ada catatan pada dokumen.</p>';
        all.sort((a, b) => a.page - b.page || a.y - b.y).forEach((n, i) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.setAttribute('data-testid', `notes-list-item-${i}`);
            b.className = 'w-full text-left rounded-2xl border border-slate-100 bg-white px-3.5 py-3 hover:border-ruby-300 transition-colors';
            b.innerHTML = `<div class="flex items-center justify-between gap-2 mb-1"><span class="text-xs font-bold ${n.own ? 'text-gold-600' : 'text-sky-700'}"></span><span class="text-[10px] font-mono text-slate-400">Hal. ${n.page}</span></div><p class="text-sm text-slate-700 line-clamp-3"></p>`;
            b.querySelector('span').textContent = n.author;
            b.querySelector('p').textContent = n.text;
            b.addEventListener('click', () => pages[n.page - 1]?.wrap.scrollIntoView({ behavior: 'smooth', block: 'start' }));
            notesPanel.appendChild(b);
        });
    }

    const clamp = (v, a, b) => Math.min(b, Math.max(a, v));
    const initials = (name) => (name || '').split(/\s+/).slice(0, 2).map((s) => s[0]).join('').toUpperCase();

    /* Decision forms wait for save */
    document.querySelectorAll('[data-decision-form]').forEach((form) => form.addEventListener('submit', async (e) => {
        if (form.dataset.ready) return;
        e.preventDefault();
        if (!(await save())) return;
        form.dataset.ready = '1';
        const submitter = e.submitter;
        // defer: requestSubmit() is ignored while the original submit event is still dispatching
        setTimeout(() => form.requestSubmit(submitter), 0);
    }));
    window.addEventListener('beforeunload', (e) => { if (dirty && editable) { e.preventDefault(); e.returnValue = ''; } });

    let lastW = viewer.clientWidth;
    let rt;
    window.addEventListener('resize', () => {
        clearTimeout(rt);
        rt = setTimeout(() => { if (Math.abs(viewer.clientWidth - lastW) > 40) { lastW = viewer.clientWidth; build(); } }, 400);
    });

    load().then(build).catch(() => (viewer.innerHTML = '<p class="py-16 text-center text-rose-600 text-sm" data-testid="pdf-error">Dokumen gagal dimuat.</p>'));
}

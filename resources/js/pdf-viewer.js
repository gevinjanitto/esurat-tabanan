import * as pdfjsLib from 'pdfjs-dist';
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;

export async function renderPdf(container, url, { onPage, scale = 1 } = {}) {
    container.innerHTML = '<div class="py-20 text-center text-sm text-slate-500" data-testid="pdf-loading"><span class="inline-block w-8 h-8 rounded-full border-[3px] border-ruby-200 border-t-ruby-600 animate-spin mb-3"></span><br>Memuat dokumen…</div>';
    const pdf = await pdfjsLib.getDocument({ url, withCredentials: true }).promise;
    container.innerHTML = '';
    const width = Math.min(container.clientWidth || 800, 980) * scale;
    const pages = [];
    for (let n = 1; n <= pdf.numPages; n++) {
        const page = await pdf.getPage(n);
        const base = page.getViewport({ scale: 1 });
        const vp = page.getViewport({ scale: width / base.width });
        const ratio = Math.min(window.devicePixelRatio || 1, 2);
        const wrap = document.createElement('div');
        wrap.className = 'pdf-page';
        wrap.dataset.page = n;
        wrap.style.width = `${vp.width}px`;
        wrap.setAttribute('data-testid', `pdf-page-${n}`);
        const canvas = document.createElement('canvas');
        canvas.className = 'pdf-canvas';
        canvas.width = vp.width * ratio;
        canvas.height = vp.height * ratio;
        wrap.appendChild(canvas);
        container.appendChild(wrap);
        await page.render({ canvasContext: canvas.getContext('2d'), viewport: vp, transform: ratio !== 1 ? [ratio, 0, 0, ratio, 0, 0] : null }).promise;
        pages.push({ n, wrap, width: vp.width, height: vp.height });
        onPage?.(pages[pages.length - 1]);
    }
    return { pdf, pages };
}

document.querySelectorAll('[data-pdf-viewer]').forEach((el) => {
    let scale = 1;
    const draw = () => renderPdf(el, el.dataset.src, { scale }).catch(() => {
        el.innerHTML = '<div class="py-16 text-center text-sm text-rose-600" data-testid="pdf-error">Dokumen tidak dapat ditampilkan.</div>';
    });
    draw();
    const zoomWrap = el.closest('[data-pdf-shell]');
    zoomWrap?.querySelector('[data-zoom-in]')?.addEventListener('click', () => { scale = Math.min(scale + .2, 2.2); draw(); });
    zoomWrap?.querySelector('[data-zoom-out]')?.addEventListener('click', () => { scale = Math.max(scale - .2, .6); draw(); });
    if (el.dataset.noContext !== undefined) el.addEventListener('contextmenu', (e) => e.preventDefault());
});

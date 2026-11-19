// Service worker PWA E-Surat Tabanan: membuat aplikasi bisa dipasang dari browser (Android & iOS)
// tanpa APK. Aplikasi butuh internet (data surat selalu terbaru); data API tidak pernah disimpan di cache.
// BASE = folder aplikasi: "/" (pratinjau), "/m/" (server E-Surat), atau ?app=/m/ untuk salinan
// di root (/esurat-sw.js) yang dipasang halaman login web agar tombol "Pasang aplikasi" langsung menginstal.
const CACHE = "esurat-pwa-v4";
const BASE = new URL(self.location.href).searchParams.get("app") || new URL(self.registration.scope).pathname;
const SHELL = [BASE, BASE + "manifest.json", BASE + "icons/icon-192.png", BASE + "icons/icon-512.png"];

self.addEventListener("install", (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(SHELL)).catch(() => {}));
  self.skipWaiting();
});

self.addEventListener("activate", (e) => {
  e.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))).then(() => self.clients.claim())
  );
});

self.addEventListener("fetch", (e) => {
  const req = e.request;
  const url = new URL(req.url);
  if (req.method !== "GET" || url.origin !== self.location.origin || !url.pathname.startsWith(BASE) || url.pathname.includes("/api/")) return;
  // Halaman: ambil dari jaringan dulu, pakai salinan terakhir bila offline.
  if (req.mode === "navigate") {
    e.respondWith(
      fetch(req)
        .then((res) => {
          const copy = res.clone();
          caches.open(CACHE).then((c) => c.put(BASE, copy));
          return res;
        })
        .catch(() => caches.match(BASE))
    );
    return;
  }
  // Ikon & aset statis: cache dulu.
  if (url.pathname.startsWith(BASE + "icons/") || url.pathname.startsWith(BASE + "assets/") || url.pathname === BASE + "manifest.json") {
    e.respondWith(
      caches.match(req).then(
        (hit) =>
          hit ||
          fetch(req).then((res) => {
            if (res.ok) {
              const copy = res.clone();
              caches.open(CACHE).then((c) => c.put(req, copy));
            }
            return res;
          })
      )
    );
  }
});

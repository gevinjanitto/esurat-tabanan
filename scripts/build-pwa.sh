#!/usr/bin/env bash
# Membangun PWA (aplikasi mobile versi web, bisa dipasang tanpa APK) ke public/m.
# Dilayani Laravel di /m pada domain yang sama, jadi API & PDF langsung ke server ini.
# Pemakaian: bash scripts/build-pwa.sh [FOLDER_MOBILE]   (default: ./mobile)
set -euo pipefail
cd "$(dirname "$0")/.."
ROOT="$(pwd)"
MOBILE="${1:-mobile}"
OUT="$ROOT/public/m"
echo "==> Membangun PWA dari $MOBILE ke public/m"
(cd "$MOBILE" && EXPO_BASE_URL=/m EXPO_PUBLIC_ESURAT_URL= EXPO_PUBLIC_BACKEND_URL= CI=1 npx expo export -p web --output-dir "$OUT")
sed -i -e 's#href="/manifest.json"#href="/m/manifest.json"#' -e 's#href="/icons/#href="/m/icons/#g' \
    -e 's#register("/sw.js")#register("/m/sw.js", { scope: "/m/" })#' "$OUT/index.html"
# Halaman login web (di luar /m) boleh menawarkan instal langsung: cakupan manifest "/" + service worker di root.
sed -i 's#"scope": "./"#"scope": "/"#' "$OUT/manifest.json"
cp "$OUT/sw.js" "$ROOT/public/esurat-sw.js"
echo "OK: PWA siap di /m (tombol \"Instal aplikasi E-Surat\" di halaman login)"

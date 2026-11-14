#!/usr/bin/env bash
# E-Surat Tabanan - menjalankan aplikasi di lokal (http://localhost:8000)
cd "$(dirname "$0")"
PORT="${PORT:-8000}"
PHP="php"; for c in php8.4 php8.3 php8.2; do command -v $c >/dev/null 2>&1 && { PHP=$c; break; }; done
mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
grep -q '^APP_KEY=base64' .env || $PHP artisan key:generate --force
$PHP artisan migrate --force || { echo "!! Migrasi gagal, cek koneksi database di .env"; exit 1; }
if [ ! -f storage/.seeded ]; then
  $PHP artisan db:seed --force && touch storage/.seeded
fi
if [ "${NO_SCHEDULER:-0}" != "1" ]; then
  $PHP artisan schedule:work > storage/logs/scheduler.log 2>&1 &
  SCHED_PID=$!; trap 'kill $SCHED_PID 2>/dev/null' EXIT INT TERM
  echo "==> Penjadwal (sync_sdm, catat_nomor_cadangan, cleanup_rate_limits) berjalan, log: storage/logs/scheduler.log"
fi
echo "==> E-Surat berjalan di http://localhost:$PORT  (Ctrl+C untuk berhenti)"
$PHP artisan serve --host=127.0.0.1 --port="$PORT"

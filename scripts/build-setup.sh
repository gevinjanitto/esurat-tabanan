#!/usr/bin/env bash
# Membuat public/setup.sh (installer lokal satu file) dari isi project saat ini.
set -euo pipefail
cd "$(dirname "$0")/.."
OUT="public/setup.sh"
cat > "$OUT" <<'HEAD'
#!/usr/bin/env bash
# E-Surat Tabanan - installer lokal: membuat ulang seluruh file project, instal dependency, siapkan database & .env.
# Pemakaian: bash setup.sh [FOLDER_TUJUAN]   (default: ./esurat-tabanan)
set -euo pipefail
TARGET="${1:-esurat-tabanan}"
echo "==> Mengekstrak project ke: $TARGET"
mkdir -p "$TARGET"
sed -n '/^__PAYLOAD_BELOW__$/,$p' "$0" | tail -n +2 | base64 -d | tar -xzf - -C "$TARGET"
cd "$TARGET"
ROOT="$(pwd)"

DB_USER="${DB_USER:-esurat}"; DB_PASS="${DB_PASS:-esuratpass}"; DB_NAME="${DB_NAME:-esurat}"
rand() { (openssl rand -hex 24 2>/dev/null || date +%s%N | sha256sum | cut -c1-48); }

if [ ! -f .env ]; then
  echo "==> Membuat .env untuk lokal"
  sed -e "s#^APP_URL=.*#APP_URL=http://localhost:8000#" \
      -e "s#^DB_HOST=.*#DB_HOST=127.0.0.1#" \
      -e "s#^DB_PORT=.*#DB_PORT=3306#" \
      -e "s#^DB_DATABASE=.*#DB_DATABASE=$DB_NAME#" \
      -e "s#^DB_USERNAME=.*#DB_USERNAME=$DB_USER#" \
      -e "s#^DB_PASSWORD=.*#DB_PASSWORD=$DB_PASS#" \
      -e "s#^SEED_PASSWORD=.*#SEED_PASSWORD=Esurat123!#" .env.example > .env
fi
mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

echo "==> Menyiapkan database MySQL/MariaDB lokal"
SQL="CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
CREATE USER IF NOT EXISTS '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';
GRANT ALL ON $DB_NAME.* TO '$DB_USER'@'localhost'; GRANT ALL ON $DB_NAME.* TO '$DB_USER'@'127.0.0.1'; FLUSH PRIVILEGES;"
if command -v mysql >/dev/null 2>&1; then
  (mysql -uroot -e "$SQL" 2>/dev/null || sudo mysql -uroot -e "$SQL" 2>/dev/null) && echo "    database $DB_NAME siap" \
    || echo "!! Gagal membuat database otomatis. Jalankan SQL ini sebagai root MySQL:"$'\n'"$SQL"
elif command -v docker >/dev/null 2>&1; then
  docker rm -f esurat-mariadb >/dev/null 2>&1 || true
  docker run -d --name esurat-mariadb -p 3306:3306 -e MARIADB_ROOT_PASSWORD="$(rand)" -e MARIADB_DATABASE="$DB_NAME" \
    -e MARIADB_USER="$DB_USER" -e MARIADB_PASSWORD="$DB_PASS" -v esurat-mariadb-data:/var/lib/mysql mariadb:11 >/dev/null \
    && echo "    MariaDB berjalan di Docker (container esurat-mariadb)"
else
  echo "!! MySQL/MariaDB maupun Docker tidak ditemukan. Instal MariaDB lalu jalankan ulang, atau buat manual:"$'\n'"$SQL"
fi

set +e
PHP=""
for c in php8.4 php8.3 php8.2 php; do
  if command -v $c >/dev/null 2>&1 && $c -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' >/dev/null 2>&1; then PHP="$c"; break; fi
done
if [ -n "$PHP" ]; then
  COMPOSER="composer"
  if ! command -v composer >/dev/null 2>&1; then
    echo "==> Composer tidak ditemukan, mengunduh composer.phar"
    $PHP -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && $PHP composer-setup.php --quiet && rm -f composer-setup.php
    COMPOSER="$PHP composer.phar"
  fi
  echo "==> Instal dependency backend ($PHP, composer install)"
  $COMPOSER install --no-interaction || echo "!! Sebagian paket PHP gagal diinstal (cek ekstensi: mbstring, xml, curl, zip, gd, intl, bcmath, pdo_mysql)"
  grep -q '^APP_KEY=base64' .env || $PHP artisan key:generate --force
else
  echo "!! PHP 8.2+ tidak ditemukan. Instal PHP (ekstensi mbstring, xml, curl, zip, gd, intl, bcmath, mysql) lalu jalankan ulang setup.sh"
fi

if ! command -v yarn >/dev/null 2>&1; then
  command -v corepack >/dev/null 2>&1 && corepack enable >/dev/null 2>&1
  command -v yarn >/dev/null 2>&1 || { command -v npm >/dev/null 2>&1 && npm install -g yarn >/dev/null 2>&1; }
fi
if command -v yarn >/dev/null 2>&1; then
  echo "==> Instal dependency frontend (yarn install && yarn build)"
  (yarn install --network-timeout 600000 && yarn build) || echo "!! yarn install/build gagal, cek pesan di atas"
else
  echo "!! yarn/Node.js tidak ditemukan. Instal Node.js 20/22 lalu: yarn install && yarn build"
fi
if [ -d mobile ]; then
  [ -f mobile/.env ] || cp mobile/.env.example mobile/.env
  if command -v yarn >/dev/null 2>&1; then
    echo "==> Instal dependency aplikasi mobile (mobile/, yarn install)"
    (cd mobile && yarn install --network-timeout 600000) || echo "!! yarn install aplikasi mobile gagal, cek pesan di atas"
  fi
fi
if [ -d mobile-relay ] && [ ! -f mobile-relay/.env ]; then
  sed -e "s#^PUSH_RELAY_SECRET=.*#PUSH_RELAY_SECRET=\"$(rand)\"#" mobile-relay/.env.example > mobile-relay/.env
fi
chmod +x start-local.sh 2>/dev/null

cat <<MSG

Selesai. Jalankan aplikasi: cd $TARGET && ./start-local.sh   (Windows CMD: start-local.bat)
  Aplikasi: http://localhost:8000   (migrasi & data awal dijalankan otomatis saat start pertama)
  Login awal: admin / Esurat123!  (tab Admin / Operator; SEED_PASSWORD di .env, ganti setelah login)
  Deploy (Docker): docker build -t esurat-tabanan . && docker run -p 8080:8080 --env-file .env esurat-tabanan
  Aplikasi mobile (Android/iOS, Expo): cd $TARGET/mobile && npx expo start   -> scan QR pakai Expo Go
    Server tujuan aplikasi: EXPO_PUBLIC_ESURAT_URL di mobile/.env (lokal: http://IP-KOMPUTER:8000)
  Relay notifikasi (opsional): cd $TARGET/mobile-relay && pip install -r requirements.txt && uvicorn server:app --port 8001
MSG
exit 0
__PAYLOAD_BELOW__
HEAD
MOBILE_SRC="${MOBILE_SRC:-../frontend}"; RELAY_SRC="${RELAY_SRC:-../backend}"
STAGE="$(mktemp -d)"; trap 'rm -rf "$STAGE"' EXIT
tar --exclude=./vendor --exclude=./node_modules --exclude=./public/build --exclude=./public/hot --exclude=./public/storage \
    --exclude=./public/setup.sh --exclude=./.env --exclude=./preview.sh --exclude=./storage/.seeded \
    --exclude='./storage/logs/*.log' --exclude='./storage/framework/sessions/*' --exclude='./storage/framework/views/*' \
    --exclude='./storage/framework/cache/data' --exclude='./storage/app/private/*' --exclude='./storage/app/public/*' \
    --exclude=./.phpunit.result.cache --exclude=./composer.phar \
    -cf - . | tar -xf - -C "$STAGE"
if [ -d "$MOBILE_SRC/app" ]; then
  mkdir -p "$STAGE/mobile"
  (cd "$MOBILE_SRC" && tar --exclude=./node_modules --exclude=./.expo --exclude=./.metro-cache --exclude=./dist \
      --exclude=./.env --exclude=./eas.json --exclude=./google-services.json -cf - .) | tar -xf - -C "$STAGE/mobile"
  printf 'EXPO_PUBLIC_ESURAT_URL=https://esurat-tabanan-production.up.railway.app\nEXPO_PUBLIC_BACKEND_URL=http://localhost:8001\n' > "$STAGE/mobile/.env.example"
fi
if [ -f "$RELAY_SRC/server.py" ]; then
  mkdir -p "$STAGE/mobile-relay"
  cp "$RELAY_SRC/server.py" "$STAGE/mobile-relay/"
  printf 'fastapi\nuvicorn\nhttpx\npython-dotenv\npydantic\n' > "$STAGE/mobile-relay/requirements.txt"
  printf 'ESURAT_API_URL="http://localhost:8000"\nPUSH_RELAY_SECRET=""\nEMERGENT_PUSH_KEY=placeholder\nSETUP_SH_PATH=""\n' > "$STAGE/mobile-relay/.env.example"
fi
(cd "$STAGE" && tar -czf - .) | base64 -w 76 >> "$OUT"
chmod +x "$OUT"
echo "OK: $OUT ($(du -h "$OUT" | cut -f1))"

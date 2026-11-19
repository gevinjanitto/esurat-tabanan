@echo off
REM E-Surat Tabanan - menjalankan aplikasi di lokal (http://localhost:8000)
cd /d "%~dp0"
if not exist storage\framework\views mkdir storage\framework\views
if not exist storage\framework\sessions mkdir storage\framework\sessions
if not exist storage\framework\cache\data mkdir storage\framework\cache\data
if not exist storage\app\private mkdir storage\app\private
if not exist storage\logs mkdir storage\logs
findstr /b "APP_KEY=base64" .env >nul || php artisan key:generate --force
php artisan migrate --force
if not exist storage\.seeded (php artisan db:seed --force && type nul > storage\.seeded)
start "E-Surat Scheduler" /min php artisan schedule:work
echo ==^> E-Surat berjalan di http://localhost:8000  (Ctrl+C untuk berhenti)
php artisan serve --host=127.0.0.1 --port=8000

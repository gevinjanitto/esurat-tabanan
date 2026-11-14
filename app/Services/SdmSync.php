<?php

namespace App\Services;

use App\Models\Jabatan;
use App\Models\Satker;
use App\Models\Sdm;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/** Sinkronisasi pegawai dari layanan HAIRISMA, aturan disalin dari SDM_model::sync_sdm (kkgusnam). */
class SdmSync
{
    private const SKIP_JABATAN = ['Sopir', 'Satpam'];

    /** @return array{ok: bool, message: string, stats?: array} */
    public function run(): array
    {
        $url = config('services.hairisma.url');
        if (blank($url)) {
            return ['ok' => false, 'message' => 'Alamat layanan HAIRISMA (HAIRISMA_URL) belum diatur di .env.'];
        }
        try {
            $res = Http::withoutVerifying()->timeout((int) config('services.hairisma.timeout'))->get($url);
        } catch (\Throwable $e) {
            report($e);
            return ['ok' => false, 'message' => 'Gagal menghubungi HAIRISMA: ' . $e->getMessage()];
        }
        $rows = $res->successful() ? $res->json() : null;
        if (!is_array($rows)) {
            return ['ok' => false, 'message' => 'Respons HAIRISMA tidak valid (HTTP ' . $res->status() . ').'];
        }
        return ['ok' => true, 'message' => 'Sinkronisasi SDM selesai.', 'stats' => $this->import($rows)];
    }

    public function import(array $rows): array
    {
        $first = Sdm::count() < 1;
        $stats = ['sdm' => 0, 'satker_baru' => 0, 'jabatan_baru' => 0, 'user_baru' => 0];
        foreach ($rows as $row) {
            $get = fn ($k) => trim((string) ($row[$k] ?? ''));
            $nrk = $get('NRK');
            if ($nrk === '') {
                continue;
            }
            $unit = $get('UNIT');
            $satuanKerja = $get('SATUAN_KERJA');
            $subUnit = $get('SUB_UNIT');
            $jabatan = $get('JABATAN');
            $nama = $get('NAMA');
            Sdm::updateOrCreate(['nrk' => $nrk], [
                'nama' => $nama, 'unit' => $unit, 'satuan_kerja' => $satuanKerja, 'sub_unit' => $subUnit,
                'jabatan' => $jabatan, 'email' => $get('EMAIL') ?: null, 'status_cuti' => !empty($row['STATUS_CUTI']),
            ]);
            $stats['sdm']++;
            if (($row['SUB_UNIT'] ?? '-') !== '-') {
                continue;
            }

            $satker = Satker::where('nama', $unit)->first();
            if (!$satker) {
                $parent = $unit !== $satuanKerja ? Satker::where('nama', $satuanKerja)->first() : null;
                $isCabang = stripos($unit, 'cabang') !== false || stripos($satuanKerja, 'cabang') !== false;
                $satker = Satker::create(['kode' => $this->kode($unit), 'nama' => $unit, 'kategori' => $isCabang ? 'cabang' : 'divisi', 'parent_id' => $parent?->id]);
                $stats['satker_baru']++;
            }

            $skip = in_array($jabatan, self::SKIP_JABATAN, true);
            $jabatanId = null;
            if (!Jabatan::where('nama', $jabatan)->exists()) {
                $j = Jabatan::create(['nama' => $jabatan, 'satker_id' => $first && !$skip ? $satker->id : null, 'sync_sdm' => true]);
                $stats['jabatan_baru']++;
                $jabatanId = $first && !$skip ? $j->id : null;
            }
            if ($first && !$skip && !$jabatanId) {
                $jabatanId = Jabatan::firstOrCreate(['nama' => $jabatan, 'satker_id' => $satker->id], ['sync_sdm' => true])->id;
            }

            $user = User::where('nrk', $nrk)->orWhere('username', $nrk)->first();
            if (!$user && !$skip) {
                User::create([
                    'name' => $nama, 'username' => $nrk, 'nrk' => $nrk, 'email' => $get('EMAIL') ?: null, 'role' => 'user',
                    'satker_id' => $satker->id, 'jabatan_id' => $jabatanId, 'is_active' => true, 'password' => Hash::make(Str::random(40)),
                ]);
                $stats['user_baru']++;
            } elseif ($user) {
                $user->update(['name' => $nama] + ($user->nrk ? [] : ['nrk' => $nrk]));
            }
        }
        return $stats;
    }

    private function kode(string $nama): string
    {
        $base = strtoupper(collect(preg_split('/\s+/', preg_replace('/[^A-Za-z0-9 ]/', ' ', $nama)))->filter()->map(fn ($w) => $w[0])->join('')) ?: 'SKR';
        $base = substr($base, 0, 24);
        $kode = $base;
        for ($i = 2; Satker::where('kode', $kode)->exists(); $i++) {
            $kode = $base . $i;
        }
        return $kode;
    }
}

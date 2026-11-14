<?php

namespace App\Services;

use App\Models\SuratAnnotation;
use App\Models\SuratKeluar;
use App\Models\User;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class TteService
{
    public function sign(SuratKeluar $surat, User $signer): string
    {
        $disk = Storage::disk('local');
        $firstSign = !$surat->signed_path;
        $source = $disk->path($surat->currentFile());
        $signIndex = $surat->alurs()->where('peran', 'penandatangan')->where('status', 1)->count();

        $qrFile = tempnam(sys_get_temp_dir(), 'qr') . '.png';
        $opts = new QROptions(['outputInterface' => QRGdImagePNG::class, 'scale' => 6, 'outputBase64' => false, 'quietzoneSize' => 1]);
        file_put_contents($qrFile, (new QRCode($opts))->render(route('verifikasi.show', $surat->uuid)));

        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false);
        $count = $pdf->setSourceFile($source);
        $parafs = $firstSign ? $this->approvedParafs($surat) : [];
        $temps = [$qrFile];

        for ($p = 1; $p <= $count; $p++) {
            $tpl = $pdf->importPage($p);
            $size = $pdf->getTemplateSize($tpl);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl);
            $w = $size['width'];
            $h = $size['height'];

            foreach ($parafs[$p] ?? [] as $paraf) {
                $this->drawParaf($pdf, $paraf, $w, $h, $temps);
            }
            if ($firstSign) {
                $pdf->SetFont('Helvetica', 'I', 6.5);
                $pdf->SetTextColor(153, 27, 27);
                $pdf->SetXY(8, $h - 8);
                $pdf->Cell($w - 16, 4, 'Dokumen ini telah ditandatangani secara elektronik melalui E-Surat Tabanan. Keaslian dapat diverifikasi dengan memindai QR Code.', 0, 0, 'C');
            }
            if ($p === $count) {
                $bw = 62;
                $x = $w - 12 - $bw - ($signIndex * ($bw + 4));
                $y = $h - 44;
                $pdf->SetDrawColor(196, 56, 72);
                $pdf->SetFillColor(253, 242, 243);
                $pdf->Rect($x, $y, $bw, 30, 'DF');
                $pdf->Image($qrFile, $x + 2, $y + 2, 26, 26);
                $pdf->SetTextColor(92, 15, 23);
                $pdf->SetFont('Helvetica', 'B', 7);
                $pdf->SetXY($x + 29, $y + 3);
                $pdf->MultiCell($bw - 30, 3.4, 'Ditandatangani secara elektronik oleh:', 0, 'L');
                $pdf->SetFont('Helvetica', 'B', 7.5);
                $pdf->SetX($x + 29);
                $pdf->MultiCell($bw - 30, 3.6, $this->latin($signer->name), 0, 'L');
                $pdf->SetFont('Helvetica', '', 6.5);
                $pdf->SetX($x + 29);
                $pdf->MultiCell($bw - 30, 3.2, $this->latin(($signer->jabatan?->nama ?? $signer->role_label) . "\n" . now()->format('d/m/Y H:i')), 0, 'L');
            }
        }

        $path = 'surat-keluar/signed/' . $surat->id . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.pdf';
        $disk->put($path, $pdf->Output('S'));
        foreach ($temps as $t) {
            @unlink($t);
        }
        return $path;
    }

    private function approvedParafs(SuratKeluar $surat): array
    {
        $approved = $surat->alurs()->where('peran', 'verifikator')->where('status', 1)->pluck('user_id');
        $pages = [];
        SuratAnnotation::with('user')->where('surat_keluar_id', $surat->id)->where('revisi', $surat->revisi)
            ->whereIn('user_id', $approved)->get()->each(function ($a) use (&$pages) {
                foreach ($a->data['parafs'] ?? [] as $paraf) {
                    $pages[(int) $paraf['page']][] = $paraf + ['user' => $a->user];
                }
            });
        return $pages;
    }

    private function drawParaf(Fpdi $pdf, array $paraf, float $w, float $h, array &$temps): void
    {
        $x = $paraf['x'] * $w;
        $y = $paraf['y'] * $h;
        $pw = ($paraf['w'] ?? 0.12) * $w;
        $ph = ($paraf['h'] ?? 0.06) * $h;
        $user = $paraf['user'];
        if ($user->paraf_path && Storage::disk('local')->exists($user->paraf_path)) {
            $tmp = tempnam(sys_get_temp_dir(), 'pf') . '.png';
            $img = @imagecreatefromstring(Storage::disk('local')->get($user->paraf_path));
            if ($img) {
                imagesavealpha($img, true);
                imagepng($img, $tmp);
                $temps[] = $tmp;
                $pdf->Image($tmp, $x, $y, $pw, $ph);
                return;
            }
        }
        $pdf->SetDrawColor(153, 27, 27);
        $pdf->SetTextColor(153, 27, 27);
        $pdf->Rect($x, $y, $pw, $ph);
        $pdf->SetFont('Helvetica', 'BI', min(14, $ph * 1.6));
        $pdf->SetXY($x, $y + 0.5);
        $pdf->Cell($pw, $ph * 0.65, $user->initials, 0, 2, 'C');
        $pdf->SetFont('Helvetica', '', 5);
        $pdf->Cell($pw, $ph * 0.3, $this->latin(Str::limit($user->name, 24, '')), 0, 0, 'C');
    }

    private function latin(string $text): string
    {
        return iconv('UTF-8', 'windows-1252//TRANSLIT', $text) ?: $text;
    }
}

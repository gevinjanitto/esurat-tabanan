<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Lembar Disposisi</title>
<style>
    @page { margin: 1.54cm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #111; }
    table { width: 100%; border-collapse: collapse; }
    td { border: 1px solid #111; padding: 6px 9px; vertical-align: top; }
    .lbl { font-weight: bold; font-size: 9pt; display: block; margin-bottom: 2px; }
    .head td { text-align: center; }
    .muted { color: #555; font-size: 8.5pt; }
    .pre { white-space: pre-line; }
</style>
</head>
<body>
<table>
    <tr class="head"><td colspan="4">
        <div style="font-size:14pt;font-weight:bold;text-decoration:underline">PT BANK PEMBANGUNAN DAERAH BALI</div>
        <div style="font-size:12pt;font-weight:bold;margin-top:4px">LEMBAR DISPOSISI</div>
    </td></tr>
    <tr>
        <td><span class="lbl">No. Agenda</span>{{ $surat->nomor_agenda }}</td>
        <td><span class="lbl">Tanggal Penyelesaian</span>&nbsp;</td>
        <td><span class="lbl">Kode</span>{{ $surat->klasifikasi?->kode ?? '-' }}</td>
        <td><span class="lbl">Sifat</span>{{ $surat->sifat?->nama ?? '-' }}</td>
    </tr>
    <tr><td colspan="4"><span class="lbl">Perihal</span>{{ $surat->perihal }}</td></tr>
    <tr>
        <td colspan="2"><span class="lbl">Asal Surat</span>{{ $surat->pengirim }}</td>
        <td><span class="lbl">Tanggal Surat</span>{{ $surat->tanggal_surat->translatedFormat('d F Y') }}</td>
        <td><span class="lbl">Nomor Surat</span>{{ $surat->nomor_surat }}</td>
    </tr>
    <tr><td colspan="2"><span class="lbl">Diterima Tanggal</span>{{ $surat->tanggal_terima->translatedFormat('d F Y') }}</td><td colspan="2"><span class="lbl">Klasifikasi Keamanan</span>{{ $surat->keamanan?->nama ?? '-' }}</td></tr>
    <tr><td style="width:30%"><span class="lbl">Dari</span></td><td style="width:25%"><span class="lbl">Kepada</span></td><td colspan="2"><span class="lbl">Isi Disposisi / Instruksi</span></td></tr>
    @forelse ($disposisis as $d)
        <tr>
            <td>{{ $d->dari->name }}<br><span class="muted">{{ $d->dari->jabatan?->nama }}</span><br><span class="muted">{{ $d->created_at->translatedFormat('d M Y H:i') }}</span></td>
            <td>{{ $d->ke->name }}<br><span class="muted">{{ $d->ke->jabatan?->nama }}</span></td>
            <td colspan="2"><b>{{ $d->instruksi }}</b>@if ($d->catatan)<div class="pre">{{ $d->catatan }}</div>@endif
                @if ($d->batas_waktu)<div class="muted">Batas waktu: {{ $d->batas_waktu->translatedFormat('d F Y') }}</div>@endif
                @if ($d->tindak_lanjut)<div class="muted">Tindak lanjut: {{ $d->tindak_lanjut }}</div>@endif
            </td>
        </tr>
    @empty
        <tr><td colspan="4" style="height:120px">&nbsp;</td></tr>
    @endforelse
</table>
<p class="muted" style="margin-top:10px">Dicetak dari E-Surat pada {{ now()->translatedFormat('d F Y H:i') }} oleh {{ auth()->user()->name }}</p>
</body>
</html>

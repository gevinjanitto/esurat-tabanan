<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>{{ $judul }}</title>
<style>
    @page { margin: 1.8cm 2cm 2.2cm 2.2cm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; line-height: 1.45; color: #111; }
    .kop { text-align: center; border-bottom: 3px double #111; padding-bottom: 8px; margin-bottom: 18px; }
    .kop p, .isi p { margin: 0 0 6px; }
    .isi table { border-collapse: collapse; }
    .isi td, .isi th { vertical-align: top; padding: 2px 4px; }
    .text-left { text-align: left; } .text-center { text-align: center; } .text-right { text-align: right; } .text-justify { text-align: justify; }
    h1 { font-size: 16pt; margin: 0 0 4px; } h2 { font-size: 13pt; margin: 0 0 4px; } h3 { font-size: 12pt; margin: 0 0 4px; }
    ul, ol { margin: 0 0 6px 18px; padding: 0; }
</style>
</head>
<body>
    @if (trim(strip_tags($kop, '<img>')) !== '')<div class="kop">{!! $kop !!}</div>@endif
    <div class="isi">{!! $isi !!}</div>
</body>
</html>

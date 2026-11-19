<!DOCTYPE html>
<html lang="id">
<body style="margin:0;background:#f4eeec;font-family:Arial,Helvetica,sans-serif;color:#1f2937">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:28px 12px">
    <tr><td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:18px;overflow:hidden">
            <tr><td style="background:#220a0f;padding:22px 28px;color:#fff">
                <div style="font-size:11px;letter-spacing:3px;color:#ff9aab;font-weight:bold">E-SURAT TABANAN</div>
                <div style="font-size:20px;font-weight:bold;margin-top:6px">{{ $judul }}</div>
            </td></tr>
            <tr><td style="padding:26px 28px">
                <p style="margin:0 0 14px">Yth. {{ $nama }},</p>
                <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #f9dbe1;border-radius:12px">
                    @foreach ($rows as $label => $value)
                        <tr><td style="padding:9px 14px;font-size:12px;color:#bd1838;font-weight:bold;width:34%;border-bottom:1px solid #fde9ec">{{ $label }}</td><td style="padding:9px 14px;font-size:13px;border-bottom:1px solid #fde9ec">{{ $value }}</td></tr>
                    @endforeach
                </table>
                <p style="margin:22px 0 0"><a href="{{ $url }}" style="display:inline-block;background:#a3122f;color:#fff;text-decoration:none;padding:11px 22px;border-radius:999px;font-weight:bold;font-size:13px">Buka di E-Surat</a></p>
                <p style="margin:22px 0 0;font-size:11px;color:#94a3b8">Email ini dikirim otomatis oleh sistem E-Surat. Mohon tidak membalas email ini.</p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · E-Surat Tabanan</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:radial-gradient(circle at 20% 15%,#fde4e8,transparent 55%),radial-gradient(circle at 85% 90%,#f8ecd0,transparent 50%),#fbf6f4;color:#220a0f;padding:24px}
        .box{max-width:520px;width:100%;background:#fff;border-radius:32px;padding:44px 36px;box-shadow:0 30px 60px -30px rgba(163,18,47,.35);text-align:left;position:relative;overflow:hidden}
        .box:after{content:"";position:absolute;right:-60px;top:-60px;width:200px;height:200px;border-radius:50%;background:linear-gradient(135deg,#d11f43,#a3122f);opacity:.08}
        .code{font-size:88px;line-height:1;font-weight:900;letter-spacing:-.04em;background:linear-gradient(120deg,#a3122f,#d11f43 55%,#c9a23a);-webkit-background-clip:text;background-clip:text;color:transparent;margin:0}
        h1{font-size:24px;margin:12px 0 8px}p{color:#64748b;line-height:1.6;margin:0}
        .hint{margin-top:18px;font-family:ui-monospace,monospace;font-size:13px;background:#fdf3f4;border:1px solid #fbe3e7;color:#bd1838;border-radius:14px;padding:12px 14px}
        a.btn{display:inline-flex;margin-top:28px;background:#a3122f;color:#fff;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:999px;transition:background-color .2s}a.btn:hover{background:#bd1838}
        .brand{font-size:11px;font-weight:800;letter-spacing:.22em;color:#d11f43;margin-bottom:18px}
    </style>
</head>
<body>
    <main class="box" data-testid="error-page-@yield('code')">
        <div class="brand">E-SURAT · TABANAN</div>
        <p class="code">@yield('code')</p>
        <h1>@yield('heading')</h1>
        <p>@yield('message')</p>
        @yield('extra')
        <a class="btn" href="{{ url('/') }}" data-testid="error-home-link">Kembali ke beranda</a>
    </main>
</body>
</html>

{{--
    Kerangka halaman error. Sengaja berdiri sendiri (CSS di dalam file, tanpa @vite dan tanpa layout aplikasi)
    supaya tetap tampil walaupun aset belum di-build atau aplikasi sedang bermasalah.
    Pesan selalu teks tetap: isi exception tidak pernah ditampilkan ke pengunjung.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f1f5f9; color: #0f172a; -webkit-font-smoothing: antialiased;
        }
        .card {
            width: 100%; max-width: 440px; padding: 40px 32px; text-align: center; background: #fff;
            border: 1px solid #e2e8f0; border-radius: 20px; box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
        }
        .brand { margin: 0 0 28px; font-size: 14px; font-weight: 600; color: #2563eb; }
        .code { margin: 0; font-size: 64px; font-weight: 800; line-height: 1; letter-spacing: -.04em; color: #2563eb; }
        h1 { margin: 16px 0 8px; font-size: 22px; font-weight: 700; }
        p.message { margin: 0 0 28px; font-size: 15px; line-height: 1.6; color: #64748b; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        .btn {
            display: inline-block; padding: 11px 20px; font-size: 15px; font-weight: 600; text-decoration: none;
            border-radius: 12px; border: 1px solid transparent; cursor: pointer; font-family: inherit;
        }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-outline { background: #fff; color: #334155; border-color: #cbd5e1; }
        .btn-outline:hover { background: #f8fafc; }
        .btn:focus-visible { outline: 2px solid #60a5fa; outline-offset: 2px; }
        @media (max-width: 420px) { .card { padding: 32px 22px; } .code { font-size: 54px; } .btn { flex: 1 1 100%; } }
    </style>
</head>
<body>
    <main class="card">
        <p class="brand">{{ config('app.name') }}</p>
        <p class="code">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p class="message">@yield('message')</p>
        <div class="actions">
            @section('actions')
                <a href="{{ url('/') }}" class="btn btn-primary">Kembali ke beranda</a>
            @show
        </div>
    </main>
</body>
</html>

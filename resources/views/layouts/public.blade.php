<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Catálogo')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-red:      #e10600;
            --brand-red-dark: #b30500;
            --brand-black:    #0a0a0a;
            --surface:        #f5f6f8;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: var(--surface);
            color: #1a1a1a;
            margin: 0;
            -webkit-font-smoothing: antialiased;
        }
        .cat-topbar {
            background: var(--brand-black);
            color: #fff;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 2px 14px rgba(0,0,0,.18);
        }
        .cat-topbar .brand-accent { color: var(--brand-red); }
        .cat-logo {
            width: 40px; height: 40px; border-radius: 9px;
            background: #000; display: flex; align-items: center; justify-content: center;
            overflow: hidden; flex-shrink: 0; border: 1px solid rgba(255,255,255,.08);
        }
        .cat-logo img { width: 100%; height: 100%; object-fit: contain; padding: 3px; }
        .cat-footer {
            color: #8a8a8a; font-size: .74rem; text-align: center;
            padding: 26px 12px 34px; letter-spacing: .03em;
        }
        @media print {
            .cat-topbar, .no-print { display: none !important; }
            body { background: #fff; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <header class="cat-topbar">
        <div class="container py-2 d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2 min-w-0">
                <div class="cat-logo">
                    @if($company?->logo)
                        <img src="{{ asset('storage/' . $company->logo) }}" alt="{{ $company->name }}">
                    @else
                        <i class="bi bi-shop text-white"></i>
                    @endif
                </div>
                <div class="min-w-0">
                    <div class="fw-bold lh-1 text-truncate" style="font-size:1rem;">{{ $company?->name ?? 'Catálogo' }}</div>
                    @isset($branch)
                    <div class="text-truncate" style="font-size:.76rem;color:#c4c4c4;">
                        <i class="bi bi-geo-alt-fill brand-accent me-1"></i>{{ $branch->name }}
                    </div>
                    @endisset
                </div>
            </div>
            @yield('topbar-actions')
        </div>
    </header>

    @yield('content')

    <div class="cat-footer">
        {{ $company?->name ?? '' }} · Catálogo de consulta — precios oficiales
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>

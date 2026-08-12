<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #1a1a1a; font-size: 10px; margin: 0; }
        .head { border-bottom: 2px solid #e10600; padding-bottom: 8px; margin-bottom: 10px; }
        .head h1 { font-size: 16px; margin: 0 0 2px; }
        .head .sub { color: #555; font-size: 10px; }
        .head .meta { color: #888; font-size: 9px; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px 6px; border-bottom: 1px solid #e6e6e6; text-align: left; vertical-align: top; }
        thead th { background: #0a0a0a; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: .03em; }
        tbody tr:nth-child(even) { background: #f7f7f8; }
        .price { text-align: right; font-weight: bold; white-space: nowrap; }
        .br { text-align: center; width: 46px; font-size: 9px; }
        .ok  { color: #16a34a; font-weight: bold; }
        .no  { color: #bbb; }
        .cat { color: #666; font-size: 9px; }
        .foot { margin-top: 12px; color: #999; font-size: 8px; text-align: center; }
    </style>
</head>
<body>
    <div class="head">
        <h1>{{ $company?->name ?? 'Catálogo' }}</h1>
        <div class="sub">Catálogo de productos · Sucursal: <strong>{{ $branch->name }}</strong></div>
        <div class="meta">
            Generado: {{ now()->format('d/m/Y H:i') }}
            @if($categoryId && ($cat = $categories->firstWhere('id', (int) $categoryId)))
                · Categoría: {{ $cat->name }}
            @endif
            · {{ $products->count() }} productos · Precios oficiales
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoría</th>
                <th class="price">Precio (Bs)</th>
                @foreach($branches as $b)
                    <th class="br">{{ \Illuminate\Support\Str::limit($b->name, 8, '') }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
            <tr>
                <td>
                    {{ $product->name }}
                    @if($product->brand)<div class="cat">{{ $product->brand->name }}</div>@endif
                </td>
                <td class="cat">{{ $product->category?->name ?? '—' }}</td>
                <td class="price">{{ number_format($product->price, 2) }}</td>
                @foreach($branches as $b)
                    @php $ok = ($stock[$b->warehouse_id][$product->id] ?? 0) > 0; @endphp
                    <td class="br">{!! $ok ? '<span class="ok">Sí</span>' : '<span class="no">—</span>' !!}</td>
                @endforeach
            </tr>
            @empty
            <tr><td colspan="{{ 3 + $branches->count() }}" style="text-align:center;color:#999;padding:16px;">Sin productos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="foot">
        {{ $company?->name ?? '' }} — Disponibilidad: <span class="ok">Sí</span> = en existencia, — = agotado. Documento de consulta.
    </div>
</body>
</html>

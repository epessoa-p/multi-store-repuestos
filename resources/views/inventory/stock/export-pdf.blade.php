<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventario — {{ $companyName }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            color: #1a1a1a;
            margin: 0;
            padding: 24px 28px;
            font-size: 12px;
        }
        .header {
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 3px solid #e10600;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header img { height: 70px; width: auto; }
        .header .title { flex: 1; }
        .header h1 { margin: 0; font-size: 18px; }
        .header .meta { color: #666; font-size: 11px; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: #0a0a0a;
            color: #fff;
            text-align: left;
            padding: 7px 9px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        thead th.num { text-align: right; }
        tbody td { padding: 6px 9px; border-bottom: 1px solid #eee; }
        tbody td.num { text-align: right; font-variant-numeric: tabular-nums; }
        tbody tr:nth-child(even) { background: #fafafa; }
        tfoot td {
            padding: 8px 9px;
            border-top: 2px solid #0a0a0a;
            font-weight: bold;
        }
        tfoot td.num { text-align: right; }
        .code { color: #555; font-family: "Consolas", monospace; }
        .print-bar {
            position: fixed; top: 12px; right: 16px;
        }
        .print-bar button {
            background: #e10600; color: #fff; border: 0;
            padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px;
        }
        @media print {
            .print-bar { display: none; }
            body { padding: 0; }
            thead { display: table-header-group; }
        }
    </style>
</head>
<body>
    <div class="print-bar">
        <button onclick="window.print()">🖨 Imprimir / Guardar PDF</button>
    </div>

    <div class="header">
        @if(file_exists(public_path('images/logo_blanco.jpeg')))
        <img src="{{ asset('images/logo_blanco.jpeg') }}" alt="{{ $companyName }}">
        @endif
        <div class="title">
            <h1>{{ $companyName }} — Inventario</h1>
            <div class="meta">Almacén: {{ $warehouseLabel }} · Generado: {{ $generatedAt }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Código</th>
                <th class="num">Precio (Bs.)</th>
                <th class="num">Costo (Bs.)</th>
                <th class="num">Cantidad disponible</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                <td>{{ $row['name'] }}</td>
                <td class="code">{{ $row['code'] }}</td>
                <td class="num">{{ number_format($row['price'], 2) }}</td>
                <td class="num">{{ number_format($row['cost'], 2) }}</td>
                <td class="num">{{ number_format($row['stock'], 0) }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;padding:24px;color:#888;">No hay productos en el inventario.</td></tr>
            @endforelse
        </tbody>
        @if(count($rows))
        <tfoot>
            <tr>
                <td colspan="4">Total de productos: {{ count($rows) }}</td>
                <td class="num">{{ number_format(collect($rows)->sum('stock'), 0) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <script>
        // Abrir el diálogo de impresión automáticamente al cargar.
        window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 350); });
    </script>
</body>
</html>

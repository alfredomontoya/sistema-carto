<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de comunicaciones detallado</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; margin: 0 0 32px 0; }
        .header { border-bottom: 3px solid #1e40af; padding-bottom: 10px; margin-bottom: 12px; }
        .header h1 { font-size: 18px; margin: 0 0 2px 0; color: #1e40af; text-transform: uppercase; }
        .header .app { font-size: 11px; color: #555; margin: 0; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta td { padding: 3px 6px; font-size: 10px; vertical-align: top; }
        .meta .label { font-weight: bold; color: #444; white-space: nowrap; width: 130px; }
        .summary { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 4px; padding: 6px 10px; margin-bottom: 12px; font-size: 10px; }
        table.rows { width: 100%; border-collapse: collapse; }
        table.rows th { background: #1e40af; color: #fff; font-size: 9px; padding: 4px; text-align: left; text-transform: uppercase; }
        table.rows td { border: 1px solid #cbd5e1; padding: 3px 4px; font-size: 9px; vertical-align: top; }
        table.rows tr:nth-child(even) td { background: #f8fafc; }
        .center { text-align: center; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .badge-activo { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .badge-anulado { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .empty { text-align: center; padding: 24px; color: #666; font-size: 11px; border: 1px dashed #cbd5e1; }
        .footer { position: fixed; bottom: -22px; left: 0; right: 0; text-align: center; font-size: 9px; color: #666; border-top: 1px solid #cbd5e1; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de comunicacion detallados</h1>
        <p class="app">{{ $appName }}</p>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Fecha:</td>
            <td>Desde {{ $from }} hasta {{ $to }}</td>
            <td class="label">Generado por:</td>
            <td>{{ $generatedBy }}</td>
        </tr>
        <tr>
            <td class="label">Área:</td>
            <td>{{ $areaName ?? 'Todas las áreas' }}</td>
            <td class="label">Fecha de generación:</td>
            <td>{{ $generatedAt }}</td>
        </tr>
        <tr>
            <td class="label">Estado:</td>
            <td>{{ $includeAnnulled ? 'Activas y anuladas' : 'Solo activas' }}</td>
            <td class="label">Total de registros:</td>
            <td>{{ $total }}</td>
        </tr>
    </table>

    @if ($rows->isEmpty())
        <div class="empty">No se encontraron comunicaciones para el periodo seleccionado.</div>
    @else
        <table class="rows">
            <thead>
                <tr>
                    <th class="center" style="width: 28px;">N°</th>
                    <th>Número</th>
                    <th style="width: 92px;">Fecha</th>
                    <th class="center" style="width: 36px;">Tipo</th>
                    <th>Remitente</th>
                    <th>Destinatario</th>
                    <th>Área destino</th>
                    <th class="center" style="width: 58px;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $i => $row)
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td>{{ $row->number }}</td>
                        <td>{{ $row->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="center">{{ strtoupper($row->type) }}</td>
                        <td>{{ $row->user?->name ?? '—' }}</td>
                        <td>{{ $row->recipient_name }}</td>
                        <td>{{ $row->area_destino_nombre ?? $row->areaDestino?->name ?? '—' }}</td>
                        <td class="center">
                            <span class="badge {{ $row->status === 'anulado' ? 'badge-anulado' : 'badge-activo' }}">{{ $row->status }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        {{ $appName }} · Reporte generado por {{ $generatedBy }} el {{ $generatedAt }} · Total de registros: {{ $total }}
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
            $pdf->page_text(700, 592, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 9, [0.4, 0.4, 0.4]);
        }
    </script>
</body>
</html>

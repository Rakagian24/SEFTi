<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>List Bayar</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        .meta { margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; }
        th { background: #f5f5f5; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>List Bayar</h1>
    <div class="meta">Periode: {{ $period }}</div>

    <table>
        <thead>
            <tr>
                <th>Supplier</th>
                <th>Departemen</th>
                <th>Tanggal PV</th>
                <th>No. PV</th>
                <th class="right">Nominal</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @php($grand = 0)
            @forelse($rows as $r)
                @php($grand += (float)($r['nominal'] ?? 0))
                <tr>
                    <td>{{ $r['supplier'] ?? '-' }}</td>
                    <td>{{ $r['department'] ?? '-' }}</td>
                    <td>{{ $r['tanggal'] ?? '-' }}</td>
                    <td>{{ $r['no_pv'] ?? '-' }}</td>
                    <td class="right">{{ number_format((float)($r['nominal'] ?? 0), 0, ',', '.') }}</td>
                    <td>{{ $r['keterangan'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding: 12px;">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="right">Total</th>
                <th class="right">{{ number_format($grand, 0, ',', '.') }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>

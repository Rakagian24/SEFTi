<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>PO Outstanding</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h3>PO Outstanding</h3>
    <p>Generated at: {{ $generated_at }}</p>
    <table>
        <thead>
            <tr>
                <th>No. PO</th>
                <th>Departemen</th>
                <th>Tanggal</th>
                <th>Perihal</th>
                <th>Supplier</th>
                <th>Nominal</th>
                <th>Grand Total</th>
                <th>Outstanding</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr>
                <td>{{ $row['no_po'] }}</td>
                <td>{{ $row['department'] }}</td>
                <td>{{ \Carbon\Carbon::parse($row['tanggal'])->format('d/m/Y') }}</td>
                <td>{{ $row['perihal'] }}</td>
                <td>{{ $row['supplier'] }}</td>
                <td style="text-align:right;">{{ number_format($row['nominal'] ?? 0, 0, ',', '.') }}</td>
                <td style="text-align:right;">{{ number_format($row['grand_total'] ?? 0, 0, ',', '.') }}</td>
                <td style="text-align:right;">{{ number_format($row['outstanding'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

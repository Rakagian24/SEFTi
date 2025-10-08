<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>BPB</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #000; padding: 6px; }
        .text-center { text-align: center; }
        .mb-2 { margin-bottom: 8px; }
    </style>
    </head>
<body>
    <h2 class="text-center">Bukti Penerimaan Barang</h2>
    <table class="mb-2">
        <tr>
            <td>No. BPB</td>
            <td>: {{ $bpb->no_bpb ?? '-' }}</td>
        </tr>
        <tr>
            <td>Departemen</td>
            <td>: {{ $bpb->department->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>No. PO</td>
            <td>: {{ $bpb->purchaseOrder->no_po ?? '-' }}</td>
        </tr>
        <tr>
            <td>Supplier</td>
            <td>: {{ $bpb->supplier->nama_supplier ?? '-' }}</td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td>: {{ optional($bpb->tanggal)->format('d/m/Y') ?? '-' }}</td>
        </tr>
        <tr>
            <td>Status</td>
            <td>: {{ $bpb->status }}</td>
        </tr>
    </table>

    <p>Keterangan:</p>
    <p>{{ $bpb->keterangan }}</p>
</body>
</html>



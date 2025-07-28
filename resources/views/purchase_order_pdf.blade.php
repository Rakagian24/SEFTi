<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #222; }
        .header { text-align: center; margin-bottom: 16px; }
        .logo { width: 80px; float: left; }
        .company-info { text-align: center; }
        .title { font-size: 20px; font-weight: bold; margin: 16px 0 8px 0; }
        .meta, .meta th, .meta td { font-size: 12px; }
        .meta th { text-align: left; padding-right: 8px; }
        .meta td { padding-bottom: 2px; }
        .note { margin: 8px 0; font-size: 12px; }
        .table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        .table th, .table td { border: 1px solid #bbb; padding: 6px 8px; font-size: 12px; }
        .table th { background: #f5f5f5; }
        .summary { width: 100%; margin-top: 8px; }
        .summary td { padding: 2px 8px; font-size: 12px; }
        .summary .label { text-align: right; }
        .summary .value { text-align: right; font-weight: bold; }
        .footer { margin-top: 32px; font-size: 12px; }
        .signatures { width: 100%; margin-top: 32px; text-align: center; }
        .signatures td { padding: 8px; }
        .stamp { width: 70px; height: 70px; margin-bottom: 4px; }
        .role { font-size: 11px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('logo.png') }}" class="logo" alt="Logo" />
        <div class="company-info">
            <div style="font-size:18px;font-weight:bold;">PT. Singa Global Tekstil</div>
            <div>Soreang Simpang Selegong Muara, Kopo, Kec. Kutawaringin,<br>Kabupaten Bandung, Jawa Barat</div>
            <div>022-19838894</div>
        </div>
    </div>
    <div style="text-align:right; margin-bottom:8px;">Bandung, {{ $tanggal ?? date('d F Y') }}</div>
    <div class="title">Purchase Order</div>
    <table class="meta">
        <tr><th>Nomor PO</th><td>: {{ $po->no_po }}</td></tr>
        <tr><th>Departemen</th><td>: {{ $po->department->name ?? '-' }}</td></tr>
        <tr><th>Perihal</th><td>: {{ $po->perihal }}</td></tr>
        <tr><th>Note</th><td>: {{ $po->note }}</td></tr>
    </table>
    <div class="note">Berikut rincian pembelian barang atau jasa untuk keperluan {{ $po->department->name ?? '-' }} :</div>
    <div class="note">{{ $po->detail_keperluan }}</div>
    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Detail</th>
                <th>No. Invoice</th>
                <th>Harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach($po->items as $i => $item)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $item->nama_barang }}</td>
                <td>{{ $item->no_invoice ?? '-' }}</td>
                <td>Rp. {{ number_format($item->harga,0,',','.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <table class="summary">
        <tr><td class="label">Total</td><td class="value">Rp. {{ number_format($total,0,',','.') }}</td></tr>
        <tr><td class="label">Diskon</td><td class="value">Rp. {{ number_format($diskon,0,',','.') }}</td></tr>
        <tr><td class="label">PPN</td><td class="value">Rp. {{ number_format($ppn,0,',','.') }}</td></tr>
        <tr><td class="label">PPH {{ $pphPersen }}%</td><td class="value">Rp. {{ number_format($pph,0,',','.') }}</td></tr>
        <tr><td class="label">Grand Total</td><td class="value">Rp. {{ number_format($grandTotal,0,',','.') }}</td></tr>
    </table>
    <div class="footer">
        <div>Metode Pembayaran : {{ $po->no_po }}</div>
        <div>Nama Bank : {{ $po->nama_bank }}</div>
        <div>Nama Rekening : {{ $po->nama_rekening }}</div>
        <div>No. Rekening/VA : {{ $po->no_rekening }}</div>
    </div>
    <div class="footer" style="margin-top:16px;">Terima kasih atas perhatian dan kerjasamanya.</div>
    <table class="signatures">
        <tr>
            <td>Dibuat Oleh<br><img src="{{ public_path('signature.png') }}" class="stamp" /><br><span class="role">Staff Akunting</span></td>
            <td>Diperiksa Oleh<br><img src="{{ public_path('approved.png') }}" class="stamp" /><br><span class="role">Kabag Akunting</span></td>
            <td>Diketahui Oleh<br><img src="{{ public_path('approved.png') }}" class="stamp" /><br><span class="role">Kepala Divisi</span></td>
            <td>Disetujui Oleh<br><img src="{{ public_path('approved.png') }}" class="stamp" /><br><span class="role">Direksi</span></td>
        </tr>
    </table>
</body>
</html>

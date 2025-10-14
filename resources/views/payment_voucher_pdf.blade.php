<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Voucher</title>
    <style>
        body { 
            font-family: DejaVu Sans, sans-serif; 
            color:#1f2937; 
            background: #f8fafc;
            margin: 0;
            padding: 20px;
        }
        .container { 
            background: white;
            padding: 40px; 
            max-width: 900px;
            margin: 0 auto;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .header { 
            display:flex; 
            justify-content:space-between; 
            align-items:flex-start; 
            margin-bottom:32px;
            padding-bottom: 24px;
            border-bottom: 2px solid #e2e8f0;
        }
        .title { 
            font-size:28px; 
            font-weight:700;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .pv-no { 
            font-size:13px; 
            color:#64748b; 
            margin-top:4px; 
        }
        .meta-wrap { 
            display:flex; 
            align-items:flex-start; 
            gap:24px; 
        }
        .meta { 
            font-size:12px; 
            line-height:1.6; 
            text-align:right; 
        }
        .meta .label { 
            color:#64748b; 
            font-size:10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .meta strong {
            color: #1e293b;
            font-size: 13px;
        }
        .card { 
            background: #f8fafc;
            border:1px solid #e2e8f0; 
            border-radius:12px; 
            padding:20px; 
        }
        .grid { 
            display:grid; 
            grid-template-columns:1fr 1fr; 
            gap:20px; 
            margin-bottom: 32px;
        }
        .label { 
            font-size:10px; 
            color:#64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
        }
        .value { 
            font-size:13px; 
            font-weight:600;
            color: #1e293b;
        }
        table { 
            width:100%; 
            border-collapse:collapse; 
            font-size:13px; 
            margin-top:24px;
            background: white;
        }
        th, td { 
            padding:14px 16px; 
            border-bottom:1px solid #e2e8f0; 
            text-align:left; 
        }
        th { 
            background: #f8fafc;
            color:#475569; 
            font-weight:600; 
            font-size:11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        tbody tr:last-child td {
            border-bottom: none;
        }
        .right { text-align:right; }
        .summary { 
            width:50%; 
            margin-left:auto;
            margin-top: 24px;
        }
        .summary td { 
            border-bottom:none; 
            padding:8px 0;
            font-size: 13px;
        }
        .summary .label { 
            color:#64748b;
            text-transform: none;
        }
        .summary .total { 
            font-weight:700;
            font-size: 15px;
            padding-top: 12px;
            border-top: 2px solid #e2e8f0;
        }
        .stamp-grid { 
            display:grid; 
            grid-template-columns:repeat(4, 1fr); 
            gap:32px; 
            margin-top:48px; 
            text-align:center;
            padding-top: 32px;
            border-top: 1px solid #e2e8f0;
        }
        .stamp-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .stamp-label-top {
            font-size: 10px;
            color: #64748b;
            margin-bottom: 12px;
            text-transform: capitalize;
        }
        .stamp-label-bottom {
            font-size: 12px;
            color: #1e293b;
            font-weight: 600;
            margin-top: 12px;
        }
        .small { 
            font-size:11px; 
            color:#6b7280; 
            margin-top:8px; 
        }
        .muted { color:#94a3b8; }
        .mt-2 { margin-top:12px; }
        .mt-3 { margin-top:16px; }
        .mt-4 { margin-top:20px; }
    </style>
</head>
<body>
<div class="container">
  <div class="header">
    <div>
      <div class="title">Payment Voucher</div>
      <div class="pv-no">{{ $pv->no_pv ?? 'Draft' }}</div>
    </div>
    <div class="meta-wrap">
      <div class="meta">
        <div><span class="label">No. Referensi PO:</span><br><strong>{{ $pv->purchaseOrder->no_po ?? '-' }}</strong></div>
        <div class="mt-2"><span class="label">No. Bank Keluar:</span><br><strong>{{ $pv->no_bk ?? '-' }}</strong></div>
        <div class="mt-2"><span class="label">Tanggal:</span><br><strong>{{ $pv->tanggal ? \Carbon\Carbon::parse($pv->tanggal)->locale('id')->translatedFormat('d F Y') : '-' }}</strong></div>
      </div>
      <div style="text-align:right">
        <img src="{{ $logoSrc }}" alt="Logo" style="height:60px" />
      </div>
    </div>
  </div>

  <div class="grid mt-3">
    <div class="card">
      <div class="label">Tanggal:</div>
      <div class="value">{{ $pv->tanggal ? \Carbon\Carbon::parse($pv->tanggal)->format('d-m-Y') : '-' }}</div>
      <div class="label mt-2">Bayar Kepada:</div>
      <div class="value">{{ $pv->supplier->nama_supplier ?? '-' }}</div>
      <div class="label mt-2">No. Telp:</div>
      <div class="value">{{ $pv->supplier_phone ?? '-' }}</div>
      <div class="label mt-2">No. Fax:</div>
      <div class="value muted">-</div>
      <div class="label mt-2">Alamat:</div>
      <div class="value">{{ $pv->supplier_address ?? '-' }}</div>
    </div>

    <div class="card">
      <div class="label">Bank:</div>
      <div class="value">{{ optional(optional($pv->supplier)->bankAccounts->first())->bank?->nama_bank ?? '-' }}</div>
      <div class="label mt-2">Mata Uang:</div>
      <div class="value">Rupiah</div>
      <div class="label mt-2">No. Cek/Giro:</div>
      <div class="value">{{ $pv->no_giro ?? '-' }}</div>
      <div class="label mt-2">Tanggal Giro:</div>
      <div class="value">{{ $pv->tanggal_giro ? \Carbon\Carbon::parse($pv->tanggal_giro)->locale('id')->translatedFormat('d F Y') : '-' }}</div>
      <div class="label mt-2">Tanggal Cair:</div>
      <div class="value">{{ $pv->tanggal_cair ? \Carbon\Carbon::parse($pv->tanggal_cair)->locale('id')->translatedFormat('d F Y') : '-' }}</div>
    </div>
  </div>

  <table class="mt-4">
    <thead>
      <tr>
        <th>Referensi</th>
        <th>Tanggal</th>
        <th>Perihal</th>
        <th class="right">Qty</th>
        <th class="right">PPN</th>
        <th class="right">Harga</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>Pembelian / Biaya</td>
        <td>{{ optional($pv->purchaseOrder)->tanggal ? \Carbon\Carbon::parse($pv->purchaseOrder->tanggal)->format('d-m-Y') : '-' }}</td>
        <td>{{ $pv->purchaseOrder->perihal->nama ?? '-' }}</td>
        <td class="right">-</td>
        <td class="right">-</td>
        <td class="right">Rp. {{ number_format($total ?? ($pv->purchaseOrder->total ?? 0), 0, ',', '.') }}</td>
      </tr>
    </tbody>
  </table>

  <table class="summary" style="margin-top:10px">
    <tr><td style="width:70%"></td><td class="label right">Total</td><td class="right">Rp. {{ number_format($total ?? 0,0,',','.') }}</td></tr>
    <tr><td></td><td class="label right">Diskon</td><td class="right">Rp. {{ number_format($diskon ?? 0,0,',','.') }}</td></tr>
    <tr><td></td><td class="label right">PPH</td><td class="right">Rp. {{ number_format($pph ?? 0,0,',','.') }}</td></tr>
    <tr><td></td><td class="right total">Grand Total</td><td class="right total">Rp. {{ number_format($grandTotal ?? 0,0,',','.') }}</td></tr>
  </table>

  <div class="stamp-grid mt-4">
    <div class="stamp-item">
      <div class="stamp-label-top">Dibuat Oleh</div>
      <img src="{{ $approvedSrc }}" alt="Approved" style="height:80px"/>
      <div class="stamp-label-bottom">Staff Akunting</div>
    </div>
    <div class="stamp-item">
      <div class="stamp-label-top">Diperiksa Oleh</div>
      <img src="{{ $approvedSrc }}" alt="Approved" style="height:80px"/>
      <div class="stamp-label-bottom">Kabag Akunting</div>
    </div>
    <div class="stamp-item">
      <div class="stamp-label-top">Diketahui Oleh</div>
      <img src="{{ $approvedSrc }}" alt="Approved" style="height:80px"/>
      <div class="stamp-label-bottom">Kepala Divisi</div>
    </div>
    <div class="stamp-item">
      <div class="stamp-label-top">Disetujui Oleh</div>
      <img src="{{ $approvedSrc }}" alt="Approved" style="height:80px"/>
      <div class="stamp-label-bottom">Direksi</div>
    </div>
  </div>
</div>
</body>
</html>
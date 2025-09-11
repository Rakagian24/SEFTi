<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Voucher</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color:#1f2937; }
        .container { padding: 18px; }
        .header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px; }
        .title { font-size:22px; font-weight:700; }
        .pv-no { font-size:12px; color:#64748b; margin-top:2px; }
        .meta-wrap { display:flex; align-items:flex-start; gap:18px; }
        .meta { font-size:11px; line-height:1.4; text-align:right; }
        .meta .label { color:#64748b; font-size:10px; }
        .card { border:1px solid #e5e7eb; border-radius:10px; padding:12px; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        .label { font-size:10px; color:#6b7280; }
        .value { font-size:12px; font-weight:600; }
        table { width:100%; border-collapse:collapse; font-size:12px; margin-top:12px; }
        th, td { padding:10px 12px; border-bottom:1px solid #eef2f7; text-align:left; }
        th { color:#64748b; font-weight:600; font-size:11px; }
        .right { text-align:right; }
        .summary { width:60%; margin-left:auto; }
        .summary td { border-bottom:none; padding:6px 0; }
        .summary .label { color:#64748b; }
        .summary .total { font-weight:700; }
        .stamp-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:28px; margin-top:26px; text-align:center; }
        .small { font-size:11px; color:#6b7280; margin-top:6px; }
        .muted { color:#94a3b8; }
        .mt-2 { margin-top:8px; }
        .mt-3 { margin-top:12px; }
        .mt-4 { margin-top:16px; }
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
        <div><span class="label">No. Referensi PO:</span><br><strong>{{ optional($pv->purchaseOrders->first())->no_po ?? '-' }}</strong></div>
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
        <th>Nama Barang</th>
        <th class="right">Qty</th>
        <th class="right">PPN</th>
        <th class="right">Harga</th>
      </tr>
    </thead>
    <tbody>
    @foreach(($pv->purchaseOrders ?? []) as $po)
      <tr>
        <td>Pembelian / Biaya</td>
        <td>{{ $po->tanggal ? \Carbon\Carbon::parse($po->tanggal)->format('d-m-Y') : '-' }}</td>
        <td>{{ $po->perihal->nama ?? '-' }}</td>
        <td class="right">-</td>
        <td class="right">11%</td>
        <td class="right">Rp. {{ number_format($po->pivot->subtotal ?? 0, 0, ',', '.') }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>

  <table class="summary" style="margin-top:10px">
    <tr><td style="width:70%"></td><td class="label right">Total</td><td class="right">Rp. {{ number_format($total ?? 0,0,',','.') }}</td></tr>
    <tr><td></td><td class="label right">Diskon</td><td class="right">Rp. {{ number_format($diskon ?? 0,0,',','.') }}</td></tr>
    <tr><td></td><td class="label right">PPH</td><td class="right">Rp. {{ number_format($pph ?? 0,0,',','.') }}</td></tr>
    <tr><td></td><td class="right total">Grand Total</td><td class="right total">Rp. {{ number_format($grandTotal ?? 0,0,',','.') }}</td></tr>
  </table>

  <div class="stamp-grid mt-4">
    <div>
      <img src="{{ $approvedSrc }}" alt="Approved" style="height:64px"/>
      <div class="small">Staff Akunting</div>
    </div>
    <div>
      <img src="{{ $approvedSrc }}" alt="Approved" style="height:64px"/>
      <div class="small">Kabag Akunting</div>
    </div>
    <div>
      <img src="{{ $approvedSrc }}" alt="Approved" style="height:64px"/>
      <div class="small">Kepala Divisi</div>
    </div>
    <div>
      <img src="{{ $approvedSrc }}" alt="Approved" style="height:64px"/>
      <div class="small">Direksi</div>
    </div>
  </div>
</div>
</body>
</html>

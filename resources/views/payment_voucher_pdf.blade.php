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
        <div>
          @php
            $isMemo = ($pv->tipe_pv ?? '') === 'Lainnya';
            $refNo = $isMemo ? ($pv->memoPembayaran->no_mb ?? '-') : ($pv->purchaseOrder->no_po ?? '-');
          @endphp
          <span class="label">{{ $isMemo ? 'No. Referensi Memo' : 'No. Referensi PO' }}:</span><br>
          <strong>{{ $refNo }}</strong>
        </div>
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
      @php
        $supplierName = $pv->supplier->nama_supplier
          ?? $pv->purchaseOrder->supplier->nama_supplier
          ?? $pv->memoPembayaran->supplier->nama_supplier
          ?? $pv->manual_supplier
          ?? '-';
        $supplierPhone = $pv->supplier->no_telepon
          ?? $pv->purchaseOrder->supplier->no_telepon
          ?? $pv->memoPembayaran->supplier->no_telepon
          ?? $pv->manual_no_telepon
          ?? '-';
        $supplierAddress = $pv->supplier->alamat
          ?? $pv->purchaseOrder->supplier->alamat
          ?? $pv->memoPembayaran->supplier->alamat
          ?? $pv->manual_alamat
          ?? '-';
      @endphp
      <div class="value">{{ $supplierName }}</div>
      <div class="label mt-2">No. Telp:</div>
      <div class="value">{{ $supplierPhone }}</div>
      <div class="label mt-2">No. Fax:</div>
      <div class="value muted">-</div>
      <div class="label mt-2">Alamat:</div>
      <div class="value">{{ $supplierAddress }}</div>
    </div>

    <div class="card">
      @php
        $metode = $pv->metode_bayar ?? $pv->purchaseOrder->metode_pembayaran ?? $pv->memoPembayaran->metode_pembayaran;
        $bankName = $pv->bankSupplierAccount->bank->nama_bank
          ?? $pv->purchaseOrder->bankSupplierAccount->bank->nama_bank
          ?? $pv->memoPembayaran->bankSupplierAccount->bank->nama_bank
          ?? $pv->manual_nama_bank
          ?? '-';
        $noGiro = $pv->no_giro ?? $pv->purchaseOrder->no_giro ?? $pv->memoPembayaran->no_giro;
        $tglGiro = $pv->tanggal_giro ?? $pv->purchaseOrder->tanggal_giro ?? $pv->memoPembayaran->tanggal_giro;
        $tglCair = $pv->tanggal_cair ?? $pv->purchaseOrder->tanggal_cair ?? $pv->memoPembayaran->tanggal_cair;
        $mataUang = $pv->currency ?? 'Rupiah';
        $namaRekening = $pv->bankSupplierAccount->nama_rekening
          ?? $pv->purchaseOrder->bankSupplierAccount->nama_rekening
          ?? $pv->memoPembayaran->bankSupplierAccount->nama_rekening
          ?? $pv->manual_nama_pemilik_rekening;
        $noRekening = $pv->bankSupplierAccount->no_rekening
          ?? $pv->purchaseOrder->bankSupplierAccount->no_rekening
          ?? $pv->memoPembayaran->bankSupplierAccount->no_rekening
          ?? $pv->manual_no_rekening;
        $noKartu = $pv->creditCard->no_kartu_kredit ?? $pv->purchaseOrder->creditCard->no_kartu_kredit ?? null;
      @endphp
      <div class="label">Bank:</div>
      <div class="value">{{ $bankName }}</div>
      <div class="label mt-2">Mata Uang:</div>
      <div class="value">{{ $mataUang }}</div>
      @if($metode === 'Cek/Giro')
        <div class="label mt-2">No. Cek/Giro:</div>
        <div class="value">{{ $noGiro ?? '-' }}</div>
        <div class="label mt-2">Tanggal Giro:</div>
        <div class="value">{{ $tglGiro ? \Carbon\Carbon::parse($tglGiro)->locale('id')->translatedFormat('d F Y') : '-' }}</div>
        <div class="label mt-2">Tanggal Cair:</div>
        <div class="value">{{ $tglCair ? \Carbon\Carbon::parse($tglCair)->locale('id')->translatedFormat('d F Y') : '-' }}</div>
      @elseif($metode === 'Kartu Kredit')
        <div class="label mt-2">No. Kartu Kredit:</div>
        <div class="value">{{ $noKartu ?? '-' }}</div>
      @else
        <div class="label mt-2">Nama Rekening:</div>
        <div class="value">{{ $namaRekening ?? '-' }}</div>
        <div class="label mt-2">No. Rekening/VA:</div>
        <div class="value">{{ $noRekening ?? '-' }}</div>
      @endif
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
      @php
        $po = $pv->purchaseOrder;
        $memo = $pv->memoPembayaran;
      @endphp
      @if($po && ($po->items && count($po->items) > 0))
        @foreach($po->items as $item)
        <tr>
          <td>{{ $po->perihal->nama ?? 'Pembelian / Biaya' }}</td>
          <td>{{ $po->tanggal ? \Carbon\Carbon::parse($po->tanggal)->format('d-m-Y') : '-' }}</td>
          <td>{{ $item->nama ?? $item->nama_barang ?? '-' }}</td>
          <td class="right">{{ $item->qty ?? '-' }}</td>
          <td class="right">{{ ($po->ppn ?? false) ? '11%' : '-' }}</td>
          <td class="right">Rp. {{ number_format((($item->qty ?? 0) * ($item->harga ?? 0)), 0, ',', '.') }}</td>
        </tr>
        @endforeach
      @elseif(($pv->tipe_pv ?? '') === 'Lainnya' && $memo)
        <tr>
          <td>Termin</td>
          <td>{{ $memo->tanggal ? \Carbon\Carbon::parse($memo->tanggal)->format('d-m-Y') : '-' }}</td>
          <td>{{ $memo->purchaseOrder->termin?->no_referensi ?? 'Pembayaran Termin' }}</td>
          <td class="right">-</td>
          <td class="right">-</td>
          <td class="right">Rp. {{ number_format($memo->total ?? 0, 0, ',', '.') }}</td>
        </tr>
      @else
        <tr>
          <td>{{ $po->perihal->nama ?? 'Pembelian / Biaya' }}</td>
          <td>{{ $po?->tanggal ? \Carbon\Carbon::parse($po->tanggal)->format('d-m-Y') : '-' }}</td>
          <td>{{ $po->keterangan ?? '-' }}</td>
          <td class="right">-</td>
          <td class="right">{{ ($po->ppn ?? false) ? '11%' : '-' }}</td>
          <td class="right">Rp. {{ number_format($po->total ?? 0, 0, ',', '.') }}</td>
        </tr>
      @endif
    </tbody>
  </table>

  @php
    $calcTotal = $total ?? ($po->total ?? $memo->total ?? 0);
    $calcDiskon = $diskon ?? ($po->diskon ?? 0);
    $calcPpn = $ppn ?? ($po->ppn_nominal ?? 0);
    $calcPph = $pph ?? ($po->pph_nominal ?? 0);
    $calcGrand = $grandTotal ?? ($po->grand_total ?? max(($calcTotal - $calcDiskon) + $calcPpn + $calcPph, 0));
  @endphp
  <table class="summary" style="margin-top:10px">
    <tr><td style="width:70%"></td><td class="label right">Total</td><td class="right">Rp. {{ number_format($calcTotal,0,',','.') }}</td></tr>
    @if(($calcDiskon ?? 0) > 0)
      <tr><td></td><td class="label right">Diskon</td><td class="right">Rp. {{ number_format($calcDiskon,0,',','.') }}</td></tr>
    @endif
    @if(($calcPph ?? 0) > 0)
      <tr><td></td><td class="label right">PPH</td><td class="right">Rp. {{ number_format($calcPph,0,',','.') }}</td></tr>
    @endif
    <tr><td></td><td class="right total">Grand Total</td><td class="right total">Rp. {{ number_format($calcGrand,0,',','.') }}</td></tr>
  </table>

  @php
    $progress = app(\App\Services\ApprovalWorkflowService::class)->getApprovalProgressForPaymentVoucher($pv);
    $signatureBoxes = [];
    $signatureBoxes[] = [
      'title' => 'Dibuat Oleh',
      'stamp' => $signatureSrc ?? null,
      'name' => optional($pv->creator)->name ?? '',
      'role' => optional(optional($pv->creator)->role)->name ?? '-',
      'date' => $pv->created_at ? \Carbon\Carbon::parse($pv->created_at)->format('d-m-Y') : '',
    ];
    $labelMapPv = [ 'verified' => 'Diperiksa Oleh', 'approved' => 'Disetujui Oleh' ];
    foreach ($progress as $step) {
      $title = $labelMapPv[$step['step']] ?? ucfirst($step['step']);
      $signatureBoxes[] = [
        'title' => $title,
        'stamp' => ($step['status'] === 'completed' && !empty($approvedSrc)) ? $approvedSrc : null,
        'name' => $step['completed_by']['name'] ?? '',
        'role' => $step['role'] ?? '-',
        'date' => !empty($step['completed_at']) ? \Carbon\Carbon::parse($step['completed_at'])->format('d-m-Y') : '',
      ];
    }
  @endphp
  <div class="stamp-grid mt-4">
    @foreach ($signatureBoxes as $box)
      <div class="stamp-item">
        <div class="stamp-label-top">{{ $box['title'] }}</div>
        @if (!empty($box['stamp']))
          <img src="{{ $box['stamp'] }}" alt="Stamp" style="height:80px"/>
        @else
          <div style="height:80px"></div>
        @endif
        <div class="stamp-label-bottom">{{ $box['role'] }}</div>
        <div class="small">{{ $box['name'] }}</div>
        <div class="small">{{ $box['date'] }}</div>
      </div>
    @endforeach
  </div>
</div>
</body>
</html>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Voucher</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            background: white;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            padding: 0;
        }

        /* Header Section */
        .header {
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
        }

        .header-left {
            float: left;
            width: 40%;
        }

        .header-left .title {
            font-size: 28px;
            font-weight: bold;
            color: #475569;
            margin: 0 0 5px 0;
            line-height: 1.2;
        }

        .header-left .pv-no {
            font-size: 11px;
            color: #64748b;
            margin: 0;
        }

        .header-right {
            float: right;
            width: 58%;
            text-align: right;
        }

        .header-meta {
            display: inline-block;
            text-align: left;
            vertical-align: top;
            margin-right: 15px;
        }

        .header-meta .meta-row {
            margin-bottom: 6px;
            line-height: 1.3;
        }

        .header-meta .label {
            color: #64748b;
            font-size: 9px;
            display: block;
            margin-bottom: 2px;
        }

        .header-meta .value {
            color: #1e293b;
            font-weight: bold;
            font-size: 10px;
            display: block;
        }

        .header-logo {
            display: inline-block;
            vertical-align: top;
        }

        .header-logo img {
            width: 70px;
            height: 70px;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Info Grid Section */
        .info-grid {
            width: 100%;
            display: table;
            table-layout: fixed; /* penting: bagi ruang sama besar */
            border-collapse: separate;
            border-spacing: 10px 0; /* jarak antar box kiri-kanan */
            margin-bottom: 20px;
        }

        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px 18px;
            box-sizing: border-box;
            /* jangan pakai height tetap! */
        }

        .info-row {
            margin-bottom: 8px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-row .label {
            color: #64748b;
            font-size: 9px;
            display: block;
            margin-bottom: 2px;
        }

        .info-row .value {
            color: #1e293b;
            font-weight: 600;
            font-size: 10px;
            display: block;
        }

        /* Table Section */
        .table-wrapper {
            width: 100%;
            margin-top: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        table.items-table thead {
            background: #f8fafc;
        }

        table.items-table th {
            padding: 10px 8px;
            text-align: left;
            color: #64748b;
            font-weight: bold;
            font-size: 9px;
            border-bottom: 1px solid #e2e8f0;
        }

        table.items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
            font-size: 10px;
        }

        table.items-table tbody tr:last-child td {
            border-bottom: none;
        }

        table.items-table .text-right {
            text-align: right;
        }

        /* Summary Section */
        .summary {
            width: calc(100% - 30px); /* kompensasi padding kanan+ kiri */
            margin: 0 auto;
            background: #f8fafc;
            padding: 12px 15px;
            border-top: 1px solid #e2e8f0;
            box-sizing: border-box;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 3px 0;
            font-size: 10px;
        }

        .summary-table td:first-child {
            width: 50%;
        }

        .summary-table td:nth-child(2) {
            text-align: right;
            color: #64748b;
            width: 25%;
            padding-right: 15px;
        }

        .summary-table td:last-child {
            text-align: right;
            color: #1e293b;
            font-weight: bold;
            width: 25%;
        }

        .summary-table tr.total-row {
            border-top: 1px solid #cbd5e1;
        }

        .-table tr.total-row td {
            padding-top: 8px;
            font-weight: bold;
            font-size: 11px;
            color: #0f172a;
        }

        /* Signature Section */
        .signatures {
            margin-top: 50px;
            width: 100%;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .signatures-table td {
            text-align: center;
            vertical-align: top;
            padding: 0;
        }

        .signature-box {
            text-align: center;
            box-sizing: border-box;
            width: 100%;
            display: block;
        }

        .signature-box .sig-label {
            font-weight: bold;
            color: #475569;
            margin-bottom: 15px;
            font-size: 10px;
        }

        .signature-box .sig-stamp {
            width: 70px;
            height: 70px;
            margin: 0 auto 10px;
            border-radius: 50%;
            overflow: hidden;
            background: white;
            position: relative;
        }

        .signature-box .sig-stamp img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .signature-box .sig-name {
            font-size: 10px;
            font-weight: bold;
            color: #1e293b;
            margin: 5px 0 3px 0;
        }

        .signature-box .sig-role {
            font-size: 9px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 3px;
        }

        .signature-box .sig-date {
            font-size: 8px;
            color: #64748b;
            font-style: italic;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header Section -->
    <div class="header clearfix">
        <div class="header-left">
            <div class="title">Payment Voucher</div>
            <div class="pv-no">{{ $pv->no_pv ?? 'Draft' }}</div>
        </div>
        <div class="header-right">
            <div class="header-meta">
                @php
                    $isMemo = ($pv->tipe_pv ?? '') === 'Lainnya';
                    $refNo = $isMemo ? ($pv->memoPembayaran?->no_mb ?? '-') : ($pv->purchaseOrder?->no_po ?? '-');
                @endphp
                <div class="meta-row">
                    <span class="label">{{ $isMemo ? 'No. Referensi Memo' : 'No. Referensi PO' }}:</span>
                    <span class="value">{{ $refNo }}</span>
                </div>
                <div class="meta-row">
                    <span class="label">No. Bank Keluar:</span>
                    <span class="value">{{ $pv->no_bk ?? '-' }}</span>
                </div>
                <div class="meta-row">
                    <span class="label">Tanggal:</span>
                    <span class="value">{{ $pv->tanggal ? \Carbon\Carbon::parse($pv->tanggal)->locale('id')->translatedFormat('d F Y') : '-' }}</span>
                </div>
            </div>
            <div class="header-logo">
                <img src="{{ $logoSrc }}" alt="Logo" />
            </div>
        </div>
    </div>

    <!-- Info Grid Section -->
    <div class="info-grid clearfix">
        <div class="info-box">
            <div class="info-row">
                <span class="label">Tanggal:</span>
                <span class="value">{{ $pv->tanggal ? \Carbon\Carbon::parse($pv->tanggal)->format('d-m-Y') : '-' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Bayar Kepada:</span>
                @php
                    $supplierName = $pv->supplier?->nama_supplier
                        ?? $pv->purchaseOrder?->supplier?->nama_supplier
                        ?? $pv->memoPembayaran?->supplier?->nama_supplier
                        ?? $pv->manual_supplier
                        ?? '-';
                    $supplierPhone = $pv->supplier?->no_telepon
                        ?? $pv->purchaseOrder?->supplier?->no_telepon
                        ?? $pv->memoPembayaran?->supplier?->no_telepon
                        ?? $pv->manual_no_telepon
                        ?? '-';
                    $supplierAddress = $pv->supplier?->alamat
                        ?? $pv->purchaseOrder?->supplier?->alamat
                        ?? $pv->memoPembayaran?->supplier?->alamat
                        ?? $pv->manual_alamat
                        ?? '-';
                @endphp
                <span class="value">{{ $supplierName }}</span>
            </div>
            <div class="info-row">
                <span class="label">No. Telp:</span>
                <span class="value">{{ $supplierPhone }}</span>
            </div>
            {{-- <div class="info-row">
                <span class="label">No. Fax:</span>
                <span class="value">-</span>
            </div> --}}
            <div class="info-row">
                <span class="label">Alamat:</span>
                <span class="value">{{ $supplierAddress }}</span>
            </div>
        </div>

        <div class="info-box">
            @php
                $metode = $pv->metode_bayar ?? $pv->purchaseOrder?->metode_pembayaran ?? $pv->memoPembayaran?->metode_pembayaran;
                $bankName = $pv->bankSupplierAccount?->bank?->nama_bank
                    ?? $pv->purchaseOrder?->bankSupplierAccount?->bank?->nama_bank
                    ?? $pv->memoPembayaran?->bankSupplierAccount?->bank?->nama_bank
                    ?? $pv->manual_nama_bank
                    ?? '-';
                $noGiro = $pv->no_giro ?? $pv->purchaseOrder?->no_giro ?? $pv->memoPembayaran?->no_giro;
                $tglGiro = $pv->tanggal_giro ?? $pv->purchaseOrder?->tanggal_giro ?? $pv->memoPembayaran?->tanggal_giro;
                $tglCair = $pv->tanggal_cair ?? $pv->purchaseOrder?->tanggal_cair ?? $pv->memoPembayaran?->tanggal_cair;
                $mataUang = $pv->currency ?? 'Rupiah';
                $namaRekening = $pv->bankSupplierAccount?->nama_rekening
                    ?? $pv->purchaseOrder?->bankSupplierAccount?->nama_rekening
                    ?? $pv->memoPembayaran?->bankSupplierAccount?->nama_rekening
                    ?? $pv->manual_nama_pemilik_rekening;
                $noRekening = $pv->bankSupplierAccount?->no_rekening
                    ?? $pv->purchaseOrder?->bankSupplierAccount?->no_rekening
                    ?? $pv->memoPembayaran?->bankSupplierAccount?->no_rekening
                    ?? $pv->manual_no_rekening;
                $noKartu = $pv->creditCard?->no_kartu_kredit ?? $pv->purchaseOrder?->creditCard?->no_kartu_kredit ?? null;
            @endphp
            <div class="info-row">
                <span class="label">Bank:</span>
                <span class="value">{{ $bankName }}</span>
            </div>
            <div class="info-row">
                <span class="label">Mata Uang:</span>
                <span class="value">{{ $mataUang }}</span>
            </div>
            @if($metode === 'Cek/Giro')
                <div class="info-row">
                    <span class="label">No. Cek/Giro:</span>
                    <span class="value">{{ $noGiro ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Tanggal Giro:</span>
                    <span class="value">{{ $tglGiro ? \Carbon\Carbon::parse($tglGiro)->locale('id')->translatedFormat('d F Y') : '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Tanggal Cair:</span>
                    <span class="value">{{ $tglCair ? \Carbon\Carbon::parse($tglCair)->locale('id')->translatedFormat('d F Y') : '-' }}</span>
                </div>
            @elseif($metode === 'Kartu Kredit')
                <div class="info-row">
                    <span class="label">No. Kartu Kredit:</span>
                    <span class="value">{{ $noKartu ?? '-' }}</span>
                </div>
            @else
                <div class="info-row">
                    <span class="label">Nama Rekening:</span>
                    <span class="value">{{ $namaRekening ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="label">No. Rekening/VA:</span>
                    <span class="value">{{ $noRekening ?? '-' }}</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Items Table -->
    <div class="table-wrapper">
        <table class="items-table">
            <thead>
                <tr>
                    <th>Referensi</th>
                    <th>Tanggal</th>
                    <th>Nama Barang</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">PPN</th>
                    <th class="text-right">Harga</th>
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
                        <td>{{ $po->perihal?->nama ?? 'Pembelian / Biaya' }}</td>
                        <td>{{ $po->tanggal ? \Carbon\Carbon::parse($po->tanggal)->format('d-m-Y') : '-' }}</td>
                        <td>{{ $item->nama ?? $item->nama_barang ?? '-' }}</td>
                        <td class="text-right">{{ $item->qty ?? '-' }}</td>
                        <td class="text-right">{{ ($po->ppn ?? false) ? '11%' : '-' }}</td>
                        <td class="text-right">Rp. {{ number_format((($item->qty ?? 0) * ($item->harga ?? 0)), 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                @elseif(($pv->tipe_pv ?? '') === 'Lainnya' && $memo)
                    <tr>
                        <td>Termin</td>
                        <td>{{ $memo->tanggal ? \Carbon\Carbon::parse($memo->tanggal)->format('d-m-Y') : '-' }}</td>
                        <td>{{ $memo->purchaseOrder?->termin?->no_referensi ?? 'Pembayaran Termin' }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right">-</td>
                        <td class="text-right">Rp. {{ number_format($memo->total ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @else
                    <tr>
                        <td>{{ $po->perihal?->nama ?? 'Pembelian / Biaya' }}</td>
                        <td>{{ $po?->tanggal ? \Carbon\Carbon::parse($po->tanggal)->format('d-m-Y') : '-' }}</td>
                        <td>{{ $po->keterangan ?? '-' }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right">{{ ($po->ppn ?? false) ? '11%' : '-' }}</td>
                        <td class="text-right">Rp. {{ number_format($po->total ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- Summary Section -->
        @php
            $calcTotal = $total ?? ($po->total ?? $memo->total ?? 0);
            $calcDiskon = $diskon ?? ($po->diskon ?? 0);
            $calcPpn = $ppn ?? ($po->ppn_nominal ?? 0);
            $calcPph = $pph ?? ($po->pph_nominal ?? 0);
            $calcGrand = $grandTotal ?? ($po->grand_total ?? max(($calcTotal - $calcDiskon) + $calcPpn + $calcPph, 0));
        @endphp
        <div class="summary">
            <table class="summary-table">
                <tr>
                    <td></td>
                    <td>Total</td>
                    <td>Rp. {{ number_format($calcTotal, 0, ',', '.') }}</td>
                </tr>
                @if(($calcDiskon ?? 0) > 0)
                <tr>
                    <td></td>
                    <td>Diskon</td>
                    <td>Rp. {{ number_format($calcDiskon, 0, ',', '.') }}</td>
                </tr>
                @endif
                @if(($calcPph ?? 0) > 0)
                <tr>
                    <td></td>
                    <td>PPH {{ isset($pphPersen) && $pphPersen ? ($pphPersen . '%') : '' }}</td>
                    <td>Rp. {{ number_format($calcPph, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td></td>
                    <td>Grand Total</td>
                    <td>Rp. {{ number_format($calcGrand, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Signature Section -->
    @php
        $progress = app(\App\Services\ApprovalWorkflowService::class)->getApprovalProgressForPaymentVoucher($pv);
        $signatureBoxes = [];
        $signatureBoxes[] = [
            'title' => 'Dibuat Oleh',
            'stamp' => (!empty($pv->created_at) && !empty($signatureSrc)) ? $signatureSrc : null,
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
        // Only display up to 4 signature boxes and center them evenly
        $signatureBoxes = array_slice($signatureBoxes, 0, 4);
        $colCount = max(count($signatureBoxes), 1);
    @endphp
    <div class="signatures">
        <table class="signatures-table">
            <tr>
                @foreach ($signatureBoxes as $box)
                <td style="width: {{ number_format(100 / $colCount, 2, '.', '') }}%">
                    <div class="signature-box">
                        <div class="sig-label">{{ $box['title'] }}</div>
                        <div class="sig-stamp">
                            @if (!empty($box['stamp']))
                                <img src="{{ $box['stamp'] }}" alt="Stamp" />
                            @endif
                        </div>
                        <div class="sig-name">{{ $box['name'] }}</div>
                        <div class="sig-role">{{ $box['role'] }}</div>
                        <div class="sig-date">{{ $box['date'] }}</div>
                    </div>
                </td>
                @endforeach
            </tr>
        </table>
    </div>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Voucher</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #6b7280;
            background: #e8ecf1;
            padding: 24px;
            font-size: 13px;
        }
        .container {
            background: #e8ecf1;
            padding: 40px;
            max-width: 920px;
            margin: 0 auto;
            border-radius: 24px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
        }

        /* Header Section */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 32px;
            padding-bottom: 0;
            border-bottom: none;
        }
        .header-left .title {
            font-size: 32px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 2px;
            letter-spacing: -0.5px;
        }
        .header-left .pv-no {
            font-size: 13px;
            color: #64748b;
            font-weight: 400;
        }
        .header-right {
            display: flex;
            gap: 24px;
            align-items: flex-start;
        }
        .header-meta { text-align: right; font-size: 12px; line-height: 2.2; }
        .header-meta .meta-row {
            margin-bottom: 1px;
        }
        .header-meta .label {
            color: #64748b;
            font-size: 11px;
            font-weight: 400;
        }
        .header-meta .value {
            color: #1e293b;
            font-weight: 600;
            font-size: 12px;
            display: block;
        }
        .header-logo img {
            height: 90px;
            width: auto;
        }

        /* Info Grid Section */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 24px;
        }
        .card {
            background: #f8fafc;
            border: none;
            border-radius: 16px;
            padding: 20px 22px;
            box-shadow: none;
        }
        .info-box {
            font-size: 12px;
        }
        .info-box .info-row {
            margin-bottom: 12px;
        }
        .info-box .info-row:last-child {
            margin-bottom: 0;
        }
        .info-box .label {
            color: #64748b;
            font-size: 11px;
            display: block;
            margin-bottom: 4px;
            font-weight: 400;
        }
        .info-box .value {
            color: #1e293b;
            font-weight: 600;
            font-size: 13px;
        }
        .info-box .value.muted {
            color: #94a3b8;
        }

        /* Table Section */
        .table-card {
            background: #f8fafc;
            border: none;
            border-radius: 16px;
            padding: 0;
            box-shadow: none;
            overflow: hidden;
            margin-top: 0;
        }
        table.items-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        table.items-table thead { background: transparent; }
        table.items-table th {
            padding: 16px 14px;
            text-align: left;
            color: #64748b;
            font-weight: 600;
            font-size: 11px;
            border-bottom: 1px solid #cbd5e1;
        }
        table.items-table td {
            padding: 14px 14px;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
            font-size: 12px;
        }
        table.items-table tbody tr:last-child td { border-bottom: 0; }
        table.items-table .text-right { text-align: right; }

        /* Summary Section */
        .summary {
            width: 100%;
            margin-top: 0;
            padding: 18px 22px 16px;
            background: #f8fafc;
            border-radius: 0 0 16px 16px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table tr td {
            padding: 5px 0;
            font-size: 12px;
        }
        .summary-table tr td:first-child {
            width: 50%;
        }
        .summary-table tr td:nth-child(2) {
            text-align: right;
            color: #64748b;
            width: 25%;
            font-weight: 400;
        }
        .summary-table tr td:last-child {
            text-align: right;
            color: #1e293b;
            font-weight: 600;
            width: 25%;
        }
        .summary-table tr.total-row {
            border-top: 1px solid #cbd5e1;
        }
        .summary-table tr.total-row td {
            padding-top: 10px;
            font-weight: 700;
            font-size: 13px;
            color: #0f172a;
        }

        /* Signature Section */
        .signatures {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-top: 40px;
            padding-top: 0;
            border-top: none;
        }
        .signature-box {
            text-align: center;
        }
        .signature-box .sig-label {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 14px;
            font-weight: 400;
        }
        .signature-box .sig-stamp {
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }
        .signature-box .sig-stamp img {
            max-height: 85px;
            max-width: 100%;
        }
        .signature-box .sig-role {
            font-size: 13px;
            color: #0f172a;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .signature-box .sig-name {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 2px;
        }
        .signature-box .sig-date {
            font-size: 11px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header Section -->
    <div class="header">
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
    <div class="info-grid">
        <div class="info-box card">
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
            <div class="info-row">
                <span class="label">No. Fax:</span>
                <span class="value muted">-</span>
            </div>
            <div class="info-row">
                <span class="label">Alamat:</span>
                <span class="value">{{ $supplierAddress }}</span>
            </div>
        </div>

        <div class="info-box card">
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
    <div class="table-card">
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
    @endphp
    <div class="signatures">
        @foreach ($signatureBoxes as $box)
        <div class="signature-box">
            <div class="sig-label">{{ $box['title'] }}</div>
            <div class="sig-stamp">
                @if (!empty($box['stamp']))
                    <img src="{{ $box['stamp'] }}" alt="Stamp" />
                @endif
            </div>
            <div class="sig-role">{{ $box['role'] }}</div>
            <div class="sig-name">{{ $box['name'] }}</div>
            <div class="sig-date">{{ $box['date'] }}</div>
        </div>
        @endforeach
    </div>
</div>
</body>
</html>

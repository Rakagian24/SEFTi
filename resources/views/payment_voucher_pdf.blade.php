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
            color: #334155;
            background: #f5f7fb;
            padding: 24px;
            font-size: 12.5px;
        }
        .container {
            background: #ffffff;
            padding: 36px 36px 28px;
            max-width: 920px;
            margin: 0 auto;
            border-radius: 20px;
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.08);
        }

        /* Header Section */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 28px;
            padding-bottom: 18px;
            border-bottom: 1px solid #e5e7eb;
        }
        .header-left .title {
            font-size: 28px;
            font-weight: 800;
            color: #374151;
            margin-bottom: 4px;
        }
        .header-left .pv-no {
            font-size: 12px;
            color: #94a3b8;
        }
        .header-right {
            display: flex;
            gap: 22px;
            align-items: flex-start;
        }
        .header-meta { text-align: right; font-size: 11.5px; line-height: 1.9; }
        .header-meta .meta-row {
            margin-bottom: 6px;
        }
        .header-meta .label {
            color: #9ca3af;
            font-size: 10.5px;
        }
        .header-meta .value {
            color: #111827;
            font-weight: 700;
            font-size: 12.5px;
            display: block;
        }
        .header-logo img {
            height: 76px;
            width: auto;
        }

        /* Info Grid Section */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 22px;
        }
        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 16px 16px 14px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }
        .info-box {
            font-size: 12px;
        }
        .info-box .info-row {
            margin-bottom: 10px;
        }
        .info-box .info-row:last-child {
            margin-bottom: 0;
        }
        .info-box .label {
            color: #94a3b8;
            font-size: 10.5px;
            display: block;
            margin-bottom: 3px;
        }
        .info-box .value {
            color: #111827;
            font-weight: 600;
            font-size: 13px;
        }
        .info-box .value.muted {
            color: #9ca3af;
        }

        /* Table Section */
        .table-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 0;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
            overflow: hidden;
            margin-top: 10px;
        }
        table.items-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        table.items-table thead { background: #f8fafc; }
        table.items-table th {
            padding: 12px 12px;
            text-align: left;
            color: #64748b;
            font-weight: 700;
            font-size: 11.5px;
            border-bottom: 1px solid #e5e7eb;
        }
        table.items-table td {
            padding: 12px 12px;
            border-bottom: 1px solid #e9edf3;
            color: #334155;
        }
        table.items-table tbody tr:first-child td:first-child { border-top-left-radius: 12px; }
        table.items-table tbody tr:first-child td:last-child { border-top-right-radius: 12px; }
        table.items-table tbody tr:last-child td:first-child { border-bottom-left-radius: 12px; }
        table.items-table tbody tr:last-child td:last-child { border-bottom-right-radius: 12px; }
        table.items-table tbody tr:last-child td { border-bottom: 0; }
        table.items-table .text-right { text-align: right; }

        /* Summary Section */
        .summary {
            width: 100%;
            margin-top: 14px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table tr td {
            padding: 6px 10px;
            font-size: 12px;
        }
        .summary-table tr td:first-child {
            width: 50%;
        }
        .summary-table tr td:nth-child(2) {
            text-align: right;
            color: #64748b;
            width: 25%;
        }
        .summary-table tr td:last-child {
            text-align: right;
            color: #0f172a;
            font-weight: 700;
            width: 25%;
        }
        .summary-table tr.total-row {
            border-top: 1px solid #e5e7eb;
        }
        .summary-table tr.total-row td {
            padding-top: 12px;
            font-weight: 800;
            font-size: 13px;
            color: #111827;
        }

        /* Signature Section */
        .signatures {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-top: 34px;
            padding-top: 18px;
            border-top: 1px solid #e5e7eb;
        }
        .signature-box {
            text-align: center;
        }
        .signature-box .sig-label {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .signature-box .sig-stamp {
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        .signature-box .sig-stamp img {
            max-height: 82px;
            max-width: 100%;
        }
        .signature-box .sig-role {
            font-size: 12px;
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
            color: #9ca3af;
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
    </div>

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

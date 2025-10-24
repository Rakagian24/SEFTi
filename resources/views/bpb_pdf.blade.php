<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>BPB</title>
    <style>
        @page { size: A4 portrait; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #1f2937;
            background: white;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 190mm;
            padding: 30px;
            box-sizing: border-box;
            min-height: calc(297mm - 40mm);
        }
        /* Header */
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #1e3a5f;
        }
        .header-flex {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 10px;
        }
        .logo {
            flex-shrink: 0;
        }
        .logo img {
            height: 80px;
            width: 80px;
            object-fit: contain;
        }
        .header-text {
            text-align: center;
        }
        .title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 2px;
            color: #1e3a5f;
        }
        .company {
            font-size: 18px;
            font-weight: 700;
            color: #1e3a5f;
            margin-bottom: 0;
        }
        .doc-no {
            margin-top: 8px;
            font-size: 12px;
            color: #1e3a5f;
            font-weight: 500;
        }

        /* Info section */
        .info-section {
            margin: 25px 0;
        }
        .info-title {
            font-weight: 700;
            color: #000;
            margin-bottom: 8px;
            font-size: 12px;
        }
        .info-content {
            font-size: 12px;
            line-height: 1.6;
            color: #000;
            margin-bottom: 15px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 15px;
        }
        .info-row {
            margin-bottom: 8px;
            display: flex;
            font-size: 12px;
        }
        .info-row .label {
            color: #000;
            min-width: 80px;
            font-weight: 400;
        }
        .info-row .value {
            color: #000;
            font-weight: 400;
        }

        /* Table */
        .table-wrapper {
            margin: 25px 0;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            overflow: hidden;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
        }
        table.items thead th {
            background: #f3f4f6;
            color: #374151;
            text-align: center;
            font-weight: 600;
            font-size: 12px;
            padding: 12px 8px;
            border-bottom: 1px solid #d1d5db;
        }
        table.items tbody td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
            font-size: 12px;
            text-align: center;
        }
        table.items tbody tr:last-child td {
            border-bottom: 0;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }

        /* Summary */
        .summary {
            margin-top: 15px;
            text-align: right;
            padding: 12px 0;
        }
        .summary-row {
            font-size: 13px;
            font-weight: 700;
            color: #000;
        }
        .summary-row .label {
            margin-right: 30px;
        }

        /* Note */
        .note {
            margin-top: 25px;
            font-size: 12px;
            padding: 0;
        }
        .note .label {
            color: #000;
            margin-bottom: 5px;
            font-weight: 700;
        }
        .note .value {
            color: #000;
            line-height: 1.6;
        }

        /* Signatures */
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            margin-top: 50px;
            padding-top: 0;
        }
        .signature-box {
            text-align: center;
        }
        .sig-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 15px;
            font-weight: 500;
        }
        .sig-stamp {
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
        }
        .sig-stamp img {
            max-height: 90px;
            max-width: 90px;
            object-fit: contain;
        }
        .sig-role {
            font-size: 13px;
            color: #000;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .sig-name {
            font-size: 12px;
            color: #6b7280;
        }
        .sig-date {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 2px;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="header-flex">
            <div class="logo">
                @if(!empty($logoSrc))
                    <img src="{{ $logoSrc }}" alt="Logo" />
                @endif
            </div>
            <div class="header-text">
                <div class="title">Bukti Penerimaan Barang</div>
                <div class="company">PT. Singa Global Tekstil</div>
            </div>
        </div>
        <div class="doc-no">{{ $bpb->no_bpb ?? 'Draft' }}</div>
    </div>

    <!-- Info Section -->
    <div class="info-section">
        <div class="info-title">Telah Terima Dari :</div>
        <div class="info-content">
            <strong>{{ $bpb->supplier->nama_supplier ?? '-' }}</strong><br>
            {{ $bpb->supplier->alamat ?? '-' }}<br>
            TELP {{ $bpb->supplier->no_telepon ?? '-' }}
        </div>

        <div class="info-grid">
            <div>
                <div class="info-row">
                    <span class="label">Tanggal :</span>
                    <span class="value">{{ $bpb->tanggal ? \Carbon\Carbon::parse($bpb->tanggal)->format('d/m/Y') : '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="label">No. PO :</span>
                    <span class="value">{{ $bpb->purchaseOrder->no_po ?? '-' }}</span>
                </div>
            </div>
            <div>
                <div class="info-row">
                    <span class="label">No. PV :</span>
                    <span class="value">{{ $bpb->paymentVoucher->no_pv ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="label">No. BPB :</span>
                    <span class="value">{{ $bpb->no_bpb ?? '-' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="table-wrapper">
        <table class="items">
            <thead>
                <tr>
                    <th style="width:50px;">No</th>
                    <th style="width:auto;">Nama Barang</th>
                    <th style="width:80px;">Qty</th>
                    <th style="width:100px;">Satuan</th>
                    <th style="width:140px;">Harga</th>
                </tr>
            </thead>
            <tbody>
                @if($bpb->items && count($bpb->items) > 0)
                    @foreach($bpb->items as $i => $it)
                    <tr>
                        <td class="text-center">{{ $i+1 }}</td>
                        <td class="text-left">{{ $it->nama_barang ?? '-' }}</td>
                        <td class="text-center">{{ number_format((float)($it->qty ?? 0), 0, ',', '.') }}</td>
                        <td class="text-center">{{ $it->satuan ?? '-' }}</td>
                        <td class="text-right">Rp. {{ number_format((float)(($it->qty ?? 0) * ($it->harga ?? 0)), 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="text-center">1</td>
                        <td class="text-left">-</td>
                        <td class="text-center">0</td>
                        <td class="text-center">-</td>
                        <td class="text-right">Rp. 0</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Summary -->
    <div class="summary">
        <div class="summary-row">
            <span class="label">Total</span>
            <span class="value">Rp. {{ number_format($grandTotal ?? 0, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- Note -->
    <div class="note">
        <div class="label">Catatan :</div>
        <div class="value">{{ $bpb->keterangan ?? '-' }}</div>
    </div>

    <!-- Signatures -->
    @php
        $progress = app(\App\Services\ApprovalWorkflowService::class)->getApprovalProgressForBpb($bpb);
        $signatureBoxes = [];
        $signatureBoxes[] = [
            'title' => 'Dibuat Oleh',
            'stamp' => (!empty($bpb->created_at) && !empty($signatureSrc)) ? $signatureSrc : null,
            'name' => optional($bpb->creator)->name ?? '',
            'role' => optional(optional($bpb->creator)->role)->name ?? '-',
            'date' => $bpb->created_at ? \Carbon\Carbon::parse($bpb->created_at)->format('d-m-Y') : '',
        ];
        foreach ($progress as $step) {
            $title = 'Diperiksa Oleh';
            $signatureBoxes[] = [
                'title' => $title,
                'stamp' => ($step['status'] === 'completed' && !empty($approvedSrc)) ? $approvedSrc : null,
                'name' => $step['completed_by']['name'] ?? '',
                'role' => $step['role'] ?? '-',
                'date' => !empty($step['completed_at']) ? \Carbon\Carbon::parse($step['completed_at'])->format('d-m-Y') : '',
            ];
        }
        // Ensure two columns only (creator + first workflow/approver)
        $signatureBoxes = array_slice($signatureBoxes, 0, 2);
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

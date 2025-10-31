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
            color: #374151;
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
            background: #f3f4f6;
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 12px;
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
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #1f2937;
        }
        .company {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0;
        }
        .doc-no {
            margin-top: 12px;
            font-size: 13px;
            color: #6b7280;
            font-weight: 400;
        }

        /* Info section */
        .info-section {
            margin: 30px 0;
        }
        .info-title {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 10px;
            font-size: 13px;
        }
        .info-content {
            font-size: 12px;
            line-height: 1.6;
            color: #374151;
            margin-bottom: 20px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 20px;
        }
        .info-row {
            margin-bottom: 10px;
            display: flex;
            font-size: 12px;
        }
        .info-row .label {
            color: #1f2937;
            min-width: 90px;
            font-weight: 600;
        }
        .info-row .value {
            color: #374151;
            font-weight: 400;
        }

        /* Table */
        .table-wrapper {
            margin: 30px 0;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            background: white;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
        }
        table.items thead th {
            background: #f9fafb;
            color: #6b7280;
            text-align: center;
            font-weight: 600;
            font-size: 12px;
            padding: 14px 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        table.items tbody td {
            padding: 14px 10px;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
            font-size: 12px;
            text-align: center;
            background: white;
        }
        table.items tbody tr:last-child td {
            border-bottom: 0;
        }
        table.items tbody tr:nth-child(even) td {
            background: #fafafa;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }

        /* Summary */
        .summary {
            margin-top: 20px;
            text-align: right;
            padding: 15px 0;
            border-top: 2px solid #e5e7eb;
        }
        .summary-row {
            font-size: 14px;
            font-weight: 700;
            color: #1f2937;
        }
        .summary-row .label {
            margin-right: 40px;
        }

        /* Note */
        .note {
            margin-top: 30px;
            font-size: 12px;
            padding: 0;
        }
        .note .label {
            color: #1f2937;
            margin-bottom: 8px;
            font-weight: 700;
        }
        .note .value {
            color: #374151;
            line-height: 1.6;
        }

        /* Signatures */
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 100px;
            margin-top: 60px;
            padding-top: 0;
        }
        .signature-box {
            text-align: center;
        }
        .sig-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .sig-stamp {
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        .sig-stamp img {
            max-height: 90px;
            max-width: 90px;
            object-fit: contain;
        }
        .sig-role {
            font-size: 13px;
            color: #1f2937;
            font-weight: 700;
            margin-bottom: 3px;
        }
        .sig-name {
            font-size: 12px;
            color: #6b7280;
        }
        .sig-date {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 3px;
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

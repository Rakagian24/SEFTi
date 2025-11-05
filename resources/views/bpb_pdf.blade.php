<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>BPB</title>
    <style>
        @page { size: A4 portrait }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            background: white;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            padding: 40px 50px;
            box-sizing: border-box;
        }

        /* Header */
        .header {
            background: white;
            text-align: center;
            margin-bottom: 30px;
            padding: 20px 20px 15px 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        .header-flex {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .logo {
            display: table-cell;
            vertical-align: middle;
            width: 110px;
            text-align: center;
        }
        .logo img {
            height: 90px;
            width: 90px;
            object-fit: contain;
        }
        .header-text {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        .title {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #1a1a1a;
            letter-spacing: 0.5px;
        }
        .company {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
            letter-spacing: 0.3px;
        }
        .doc-no {
            margin-top: 12px;
            font-size: 13px;
            color: #666;
            font-weight: 400;
            letter-spacing: 0.5px;
        }

        /* Info section */
        .info-section {
            margin: 30px 0 30px 0;
        }
        .info-title {
            font-weight: 700;
            color: #000;
            margin-bottom: 10px;
            font-size: 11px;
        }
        .info-content {
            font-size: 11px;
            line-height: 1.6;
            color: #333;
            margin-bottom: 25px;
        }
        .info-grid {
            width: 100%;
            margin-top: 20px;
        }
        .info-grid table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-grid td {
            width: 50%;
            vertical-align: top;
            padding: 0 10px 0 0;
        }
        .info-row {
            margin-bottom: 10px;
            font-size: 11px;
            line-height: 1.5;
        }
        .info-row .label {
            color: #000;
            font-weight: 700;
            display: inline-block;
            min-width: 90px;
        }
        .info-row .value {
            color: #333;
            font-weight: 400;
        }

        /* Table */
        .table-wrapper {
            margin: 30px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
        }
        table.items thead th {
            background: #f5f5f5;
            color: #666;
            text-align: center;
            font-weight: 600;
            font-size: 11px;
            padding: 12px 8px;
            border-bottom: 1px solid #ddd;
        }
        table.items tbody td {
            padding: 15px 10px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
            font-size: 11px;
            text-align: center;
            background: white;
        }
        table.items tbody tr:last-child td {
            border-bottom: 0;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; padding-right: 15px !important; }
        .text-left { text-align: left; padding-left: 15px !important; }

        /* Summary */
        .summary {
            margin-top: 20px;
            text-align: right;
            padding: 15px 15px 10px 0;
            border-top: 1px solid #e0e0e0;
        }
        .summary-row {
            font-size: 12px;
            font-weight: 700;
            color: #000;
        }
        .summary-row .label {
            margin-right: 50px;
            display: inline-block;
        }

        /* Note */
        .note {
            margin-top: 35px;
            font-size: 11px;
            padding: 0;
        }
        .note .label {
            color: #000;
            margin-bottom: 8px;
            font-weight: 700;
        }
        .note .value {
            color: #333;
            line-height: 1.6;
        }

        /* Signatures */
        .signatures {
            margin-top: 60px;
            padding-top: 10px;
        }
        .signatures table {
            width: 100%;
            border-collapse: collapse;
        }
        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 30px;
        }
        .signature-box {
            text-align: center;
        }
        .sig-label {
            font-size: 11px;
            color: #888;
            margin-bottom: 20px;
            font-weight: 400;
        }
        .sig-stamp {
            height: 100px;
            display: block;
            margin: 0 auto 10px auto;
            text-align: center;
        }
        .sig-stamp img {
            max-height: 90px;
            max-width: 90px;
            object-fit: contain;
        }
        .sig-role {
            font-size: 12px;
            color: #000;
            font-weight: 700;
            margin-bottom: 3px;
            line-height: 1.3;
        }
        .sig-name {
            font-size: 11px;
            color: #333;
            line-height: 1.3;
        }
        .sig-date {
            font-size: 10px;
            color: #999;
            margin-top: 3px;
            line-height: 1.3;
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
            <table>
                <tr>
                    <td>
                        <div class="info-row">
                            <span class="label">Tanggal :</span>
                            <span class="value">{{ $bpb->tanggal ? \Carbon\Carbon::parse($bpb->tanggal)->format('d/m/Y') : '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">No. PO :</span>
                            <span class="value">{{ $bpb->purchaseOrder->no_po ?? '-' }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="info-row">
                            <span class="label">No. PV :</span>
                            <span class="value">{{ $bpb->paymentVoucher->no_pv ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">No. BPB :</span>
                            <span class="value">{{ $bpb->no_bpb ?? '-' }}</span>
                        </div>
                    </td>
                </tr>
            </table>
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
        <table>
            <tr>
                @foreach ($signatureBoxes as $box)
                <td>
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
                </td>
                @endforeach
            </tr>
        </table>
    </div>
</div>
</body>
</html>

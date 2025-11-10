<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>BPB</title>
    <style>
        @page { size: A4 portrait; margin: 40px 50px; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            box-sizing: border-box;
        }

        /* ===== HEADER ===== */
        .header {
            border-bottom: 1px solid #ccc;
            margin-bottom: 25px;
            padding-bottom: 10px;
        }

        .header-flex {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }

        .logo {
            width: 90px;
            flex-shrink: 0;
            text-align: center;
        }
        .logo img {
            height: 80px;
            width: auto;
            object-fit: contain;
        }

        .header-text {
            flex: 1;
            text-align: center;
        }
        .title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 3px;
            color: #000;
        }
        .company {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 6px;
            color: #000;
        }
        .doc-no {
            font-size: 12px;
            color: #555;
        }

        /* ===== INFO SECTION ===== */
        .info-section {
            margin-bottom: 20px;
        }

        .info-title {
            font-weight: 700;
            margin-bottom: 5px;
        }

        .info-content {
            line-height: 1.5;
            margin-bottom: 10px;
        }

        .info-grid {
            width: 100%;
        }

        .info-grid table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-grid td {
            width: 50%;
            vertical-align: top;
            padding: 3px 10px 3px 0;
        }

        .info-row {
            margin-bottom: 3px;
        }

        .label {
            display: inline-block;
            min-width: 80px;
            font-weight: 700;
        }

        .value {
            color: #333;
        }

        /* ===== TABEL BARANG ===== */
        .table-wrapper {
            margin: 25px 0;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #ccc;
        }

        table.items th, table.items td {
            border-bottom: 1px solid #ccc;
            text-align: center;
            padding: 8px 6px;
            font-size: 11px;
        }

        table.items th {
            background: #f5f5f5;
            font-weight: 600;
        }

        table.items td {
            color: #333;
        }

        table.items tr:last-child td {
            border-bottom: none;
        }

        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* ===== SUMMARY ===== */
        .summary {
            text-align: right;
            border-top: 1px solid #ccc;
            padding-top: 8px;
            margin-top: 8px;
            font-size: 12px;
        }

        .summary-row {
            font-weight: 700;
        }

        /* ===== NOTE ===== */
        .note {
            margin-top: 20px;
            font-size: 11px;
        }

        .note .label {
            font-weight: 700;
            margin-bottom: 4px;
        }

        /* ===== SIGNATURES ===== */
        .signatures {
            margin-top: 60px;
        }

        .signatures table {
            width: 100%;
            border-collapse: collapse;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }

        .sig-label {
            font-size: 11px;
            color: #555;
            margin-bottom: 10px;
        }

        .sig-stamp {
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
        }

        .sig-stamp img {
            max-height: 70px;
            max-width: 90px;
            object-fit: contain;
        }

        .sig-role {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .sig-name {
            font-size: 11px;
            color: #333;
            margin-bottom: 2px;
        }

        .sig-date {
            font-size: 10px;
            color: #888;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- HEADER -->
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
                <div class="doc-no">{{ $bpb->no_bpb ?? 'Draft' }}</div>
            </div>
        </div>
    </div>

    <!-- INFO -->
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

    <!-- TABEL BARANG -->
    <div class="table-wrapper">
        <table class="items">
            <thead>
                <tr>
                    <th style="width:40px;">No</th>
                    <th style="width:45%;">Nama Barang</th>
                    <th style="width:70px;">Qty</th>
                    <th style="width:80px;">Satuan</th>
                    <th style="width:100px;">Harga</th>
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
                        <td class="text-center" colspan="5">Tidak ada data</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- TOTAL -->
    <div class="summary">
        <div class="summary-row">
            Total&nbsp;&nbsp; Rp. {{ number_format($grandTotal ?? 0, 0, ',', '.') }}
        </div>
    </div>

    <!-- CATATAN -->
    <div class="note">
        <div class="label">Catatan :</div>
        <div class="value">{{ $bpb->keterangan ?? '-' }}</div>
    </div>

    <!-- TANDA TANGAN -->
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
            $signatureBoxes[] = [
                'title' => 'Diperiksa Oleh',
                'stamp' => ($step['status'] === 'completed' && !empty($approvedSrc)) ? $approvedSrc : null,
                'name' => $step['completed_by']['name'] ?? '',
                'role' => $step['role'] ?? '-',
                'date' => !empty($step['completed_at']) ? \Carbon\Carbon::parse($step['completed_at'])->format('d-m-Y') : '',
            ];
        }
        $signatureBoxes = array_slice($signatureBoxes, 0, 2);
    @endphp

    <div class="signatures">
        <table>
            <tr>
                @foreach($signatureBoxes as $box)
                <td>
                    <div class="sig-label">{{ $box['title'] }}</div>
                    <div class="sig-stamp">
                        @if (!empty($box['stamp']))
                            <img src="{{ $box['stamp'] }}" alt="Stamp" />
                        @endif
                    </div>
                    <div class="sig-role">{{ $box['role'] }}</div>
                    <div class="sig-name">{{ $box['name'] }}</div>
                    <div class="sig-date">{{ $box['date'] }}</div>
                </td>
                @endforeach
            </tr>
        </table>
    </div>
</div>
</body>
</html>

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

    /* ===================== HEADER ===================== */
    .header {
        text-align: center;
        border-bottom: 2px solid #e0e0e0;
        padding-bottom: 10px;
        margin-bottom: 25px;
    }

    .header-flex {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
    }

    .logo {
        width: 100px;
        flex-shrink: 0;
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
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 4px;
        color: #1a1a1a;
        letter-spacing: 0.3px;
    }
    .company {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 6px;
        color: #1a1a1a;
    }
    .doc-no {
        font-size: 12px;
        color: #666;
        margin-top: 6px;
    }

    /* ===================== INFO SECTION ===================== */
    .info-section {
        margin-bottom: 25px;
    }

    .info-title {
        font-weight: 700;
        margin-bottom: 8px;
    }

    .info-content {
        line-height: 1.5;
        margin-bottom: 12px;
    }

    .info-grid table {
        width: 100%;
        border-collapse: collapse;
    }
    .info-grid td {
        width: 50%;
        vertical-align: top;
        padding-right: 15px;
    }

    .info-row {
        margin-bottom: 6px;
    }
    .info-row .label {
        display: inline-block;
        min-width: 90px;
        font-weight: 700;
    }
    .info-row .value {
        color: #333;
    }

    /* ===================== TABLE BARANG ===================== */
    .table-wrapper {
        margin: 25px 0;
        border: 1px solid #ddd;
        border-radius: 6px;
        overflow: hidden;
    }

    table.items {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    table.items thead th {
        background: #f5f5f5;
        color: #555;
        text-align: center;
        font-weight: 600;
        padding: 10px 6px;
        border-bottom: 1px solid #ddd;
        font-size: 11px;
    }

    table.items th:nth-child(1) { width: 40px; }
    table.items th:nth-child(2) { width: 45%; text-align: left; }
    table.items th:nth-child(3) { width: 70px; }
    table.items th:nth-child(4) { width: 80px; }
    table.items th:nth-child(5) { width: 100px; text-align: right; }

    table.items td {
        padding: 8px 6px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 11px;
        color: #333;
        text-align: center;
        word-wrap: break-word;
    }
    table.items tr:last-child td { border-bottom: none; }

    .text-left { text-align: left !important; padding-left: 8px; }
    .text-right { text-align: right !important; padding-right: 8px; }
    .text-center { text-align: center !important; }

    /* ===================== SUMMARY ===================== */
    .summary {
        text-align: right;
        border-top: 1px solid #ccc;
        padding-top: 10px;
        margin-top: 15px;
        font-size: 12px;
    }
    .summary-row {
        font-weight: 700;
    }
    .summary-row .label {
        display: inline-block;
        margin-right: 40px;
    }

    /* ===================== NOTE ===================== */
    .note {
        margin-top: 20px;
        font-size: 11px;
    }
    .note .label {
        font-weight: 700;
        margin-bottom: 4px;
    }

    /* ===================== SIGNATURES ===================== */
    .signatures {
        margin-top: 50px;
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
    .signature-box {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        min-height: 160px;
    }
    .sig-label {
        font-size: 11px;
        color: #666;
        margin-bottom: 10px;
    }
    .sig-stamp {
        height: 90px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 5px;
    }
    .sig-stamp img {
        max-height: 80px;
        max-width: 90px;
        object-fit: contain;
    }
    .sig-role {
        font-weight: 700;
        font-size: 11px;
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

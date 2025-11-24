<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Realisasi</title>
    <style>
        @page {
            /* A4: 210mm × 297mm */
            size: A4 portrait;
        }

        /* Use system fonts for better PDF performance */
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            line-height: 1.4;
            background: white;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 170mm; /* page width 210 - margins (2*20) = 170mm */
            margin: 0;
            padding: 20px;
            min-height: calc(297mm - 40mm);
            box-sizing: border-box;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
        }

        .logo-container {
            display: table-cell;
            width: 100px;
            vertical-align: middle;
        }

        .logo {
            text-align: center;
        }

        .logo img {
            max-width: 90px;
            max-height: 90px;
            border-radius: 50%;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 20px;
            margin-top: 16px;
        }

        .company-info {
            display: table-cell;
            text-align: center;
            vertical-align: middle;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .company-address {
            font-size: 12px;
            color: #374151;
            margin-bottom: 4px;
        }

        .company-phone {
            font-size: 12px;
            color: #374151;
        }

        .header-spacer {
            display: table-cell;
            width: 100px;
        }

        .date-location {
            text-align: right;
            font-size: 12px;
            color: #374151;
            margin-bottom: 20px;
        }

        .title {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            margin: 40px 0;
        }

        .addressed-to {
            margin-bottom: 20px;
        }

        .addressed-to p {
            margin: 5px 0;
        }

        .purpose {
            margin-bottom: 20px;
        }

        .realisasi-details {
            margin-bottom: 20px;
        }

        .detail-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .detail-label {
            display: table-cell;
            width: 150px;
            font-weight: bold;
            color: #374151;
        }

        .detail-value {
            display: table-cell;
            color: #1a1a1a;
        }

        .note-section {
            text-align: justify;
            margin-bottom: 20px;
        }

        /* Items Table Styling optimized for PDF */
        .table-container {
            margin: 20px 0;
            padding: 0;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .items-table thead th {
            padding: 12px 16px;
            text-align: left;
            font-weight: bold;
            color: #9ca3af;
            background: #ffffff;
            border-bottom: 2px solid #d1d5db;
            font-size: 12px;
        }

        .items-table tbody tr {
            border: 1px solid #d1d5db;
        }

        .items-table tbody td {
            padding: 12px 16px;
            color: #374151;
            font-size: 12px;
            background: #ffffff;
            border-bottom: 1px solid #d1d5db;
        }

        .items-table th:first-child,
        .items-table td:first-child {
            width: 40px;
            text-align: center;
        }

        .items-table th:nth-child(2),
        .items-table td:nth-child(2) {
            width: 25%;
            white-space: normal;
            word-wrap: break-word;
        }

        .items-table th:nth-child(3),
        .items-table td:nth-child(3) {
            width: 25%;
            white-space: normal;
            word-wrap: break-word;
        }

        .items-table th:nth-child(4),
        .items-table td:nth-child(4) {
            width: 15%;
            text-align: right;
        }

        .items-table th:nth-child(5),
        .items-table td:nth-child(5) {
            width: 5%;
            text-align: center;
        }

        .items-table th:nth-child(6),
        .items-table td:nth-child(6) {
            width: 10%;
            text-align: center;
        }

        .items-table th:nth-child(7),
        .items-table td:nth-child(7) {
            width: 15%;
            text-align: right;
        }

        .items-table th:last-child,
        .items-table td:last-child {
            width: 15%;
            text-align: right;
        }

        /* Summary Section Styling */
        .summary-section {
            margin: 20px 0;
            width: 100%;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-left: auto;
        }

        .summary-table tr {
            border: none;
        }

        .summary-table td {
            padding: 6px 0;
            border: none;
            background: transparent;
        }

        .summary-table .summary-label {
            text-align: right;
            font-weight: normal;
            color: #9ca3af;
            width: 70%;
            padding-right: 40px;
            font-size: 12px;
            white-space: normal;
        }

        .summary-table .summary-value {
            text-align: right;
            font-weight: bold;
            color: #111827;
            width: 30%;
            font-size: 12px;
            white-space: nowrap;
        }

        .summary-table .grand-total-row {
            border-top: 1px solid #e5e7eb;
        }

        .summary-table .grand-total-row td {
            padding-top: 16px;
            margin-top: 8px;
        }

        .summary-table .grand-total-row .summary-label {
            font-weight: bold;
            color: #111827;
            font-size: 13px;
        }

        .summary-table .grand-total-row .summary-value {
            font-weight: bold;
            color: #111827;
            font-size: 13px;
        }

        .closing-remark {
            text-align: left;
            margin: 30px 0;
        }

        .signatures-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }

        .signature-box {
            display: table-cell;
            text-align: center;
            width: 25%;
            vertical-align: top;
        }

        .signature-title {
            font-weight: bold;
            color: #374151;
            margin-bottom: 15px;
            font-size: 11px;
        }

        .signature-stamp {
            width: 80px;
            height: 80px;
            margin: 0 auto 10px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
        }

        .signature-stamp img {
            width: 100%;
            height: auto;
            max-height: 100%;
            border-radius: 0;
        }

        .signature-name {
            font-size: 11px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 3px;
        }

        .signature-role {
            font-size: 11px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 5px;
        }

        .signature-date {
            font-size: 9px;
            color: #6b7280;
            font-style: italic;
        }

        /* Keep grouped sections together on the same page for PDF rendering */
        .keep-together {
            page-break-inside: avoid;
            break-inside: avoid;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo-container">
                <div class="logo">
                    <img src="{{ $logoSrc ?? '' }}" alt="Company Logo" />
                </div>
            </div>
            <div class="company-info">
                <div class="company-name">PT. Singa Global Tekstil</div>
                <div class="company-address">Soreang Simpang Selegong Muara, Kopo, Kec. Kutawaringin,<br>Kabupaten Bandung, Jawa Barat</div>
                <div class="company-phone">022-19838894</div>
            </div>
            <div class="header-spacer"></div>
        </div>

        <div class="date-location">Bandung, {{ $tanggal ?? date('d F Y') }}</div>

        <!-- Title -->
        <div class="title">Realisasi</div>

        <!-- Addressed To -->
        <div class="addressed-to">
            <p>Kepada Yth.</p>
            <p>Finance & Accounting PT. Singa Global Tekstil</p>
        </div>

        <!-- Purpose -->
        <div class="purpose">
            <p>Berikut laporan pengeluaran Realisasi Anggaran Dinas Luar untuk keperluan SGT :</p>
        </div>

        <!-- Realisasi Details -->
        <div class="realisasi-details">
            <div class="detail-row">
                <div class="detail-label">Nomor Realisasi</div>
                <div class="detail-value">: {{ $realisasi->no_realisasi ?? '-' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Nomor PO</div>
                <div class="detail-value">: {{ $realisasi->poAnggaran->no_po_anggaran ?? '-' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Departemen</div>
                <div class="detail-value">: {{ $realisasi->department->name ?? '-' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Total Anggaran</div>
                <div class="detail-value">: Rp. {{ number_format($realisasi->total_anggaran ?? 0, 0, ',', '.') }}</div>
            </div>
            @if(!empty($realisasi->note))
            <div class="detail-row">
                <div class="detail-label">Note</div>
                <div class="detail-value">: {{ $realisasi->note }}</div>
            </div>
            @endif
        </div>

        <!-- Note Section -->
        <div class="note-section">
            @if(!empty($realisasi->note))
                {{ $realisasi->note }}
            @endif
        </div>

        <!-- Rincian Realisasi Anggaran -->
        <div class="card">
        <div class="table-container">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Detail</th>
                        <th>Keterangan</th>
                        <th>Harga</th>
                        <th>Qty</th>
                        <th>Satuan</th>
                        <th>Subtotal</th>
                        <th>Realisasi</th>
                    </tr>
                </thead>
                <tbody>
                    @if($realisasi->items && count($realisasi->items) > 0)
                        @foreach($realisasi->items as $i => $item)
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td>{{ $item->jenis_pengeluaran_text ?? '-' }}</td>
                            <td>{{ $item->keterangan ?? '-' }}</td>
                            <td>Rp. {{ number_format($item->harga ?? 0, 0, ',', '.') }}</td>
                            <td>{{ $item->qty ?? '-' }}</td>
                            <td>{{ $item->satuan ?? '-' }}</td>
                            <td>Rp. {{ number_format($item->subtotal ?? 0, 0, ',', '.') }}</td>
                            <td>Rp. {{ number_format($item->realisasi ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="8" style="text-align: center;">Tidak ada data item</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        </div>

        <!-- Summary Section -->
        <div class="summary-section">
            <table class="summary-table">
                <tr>
                    <td class="summary-label">Total</td>
                    <td class="summary-value">Rp. {{ number_format($total ?? 0, 0, ',', '.') }}</td>
                </tr>
                @if(isset($sisa) && $sisa != 0)
                <tr>
                    <td class="summary-label">Sisa</td>
                    <td class="summary-value">Rp. {{ number_format($sisa ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endif
            </table>
        </div>

        <!-- Closing Remark -->
        <div class="closing-remark">Terima kasih atas perhatian dan kerjasamanya.</div>

        <!-- Signatures Section -->
        <div class="signatures-section">
            @foreach ($signatureBoxes as $box)
                <div class="signature-box">
                    <div class="signature-title">{{ $box['title'] }}</div>
                    <div class="signature-stamp">
                        @if (!empty($box['stamp']))
                            <img src="{{ $box['stamp'] }}" alt="Stamp" />
                        @endif
                    </div>
                    <div class="signature-name">{{ $box['name'] }}</div>
                    <div class="signature-role">{{ $box['role'] }}</div>
                    <div class="signature-date">{{ $box['date'] ? 'Tanggal: ' . $box['date'] : '' }}</div>
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>

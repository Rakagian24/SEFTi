<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        @page {
            /* F4: 210mm × 330mm */
            size: 210mm 330mm;
            margin: 20mm;
        }

        /* Local font files for PDF (place TTFs in public/fonts) */
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 400;
            src: url('{{ asset('fonts/Inter-Regular.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 600;
            src: url('{{ asset('fonts/Inter-SemiBold.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 700;
            src: url('{{ asset('fonts/Inter-Bold.ttf') }}') format('truetype');
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            line-height: 1.4;
            background: white;
        }

        .container {
            width: 100%;
            max-width: 170mm; /* page width 210 - margins (2*20) = 170mm */
            margin: 0 auto;
            border: 3px solid #1e3a8a;
            /* border-right: 3px solid #1e3a8a; */
            padding: 20px;
            min-height: calc(330mm - 40mm);
        }

        .header {
            display: grid;
            grid-template-columns: 100px 1fr 100px; /* left logo, centered info, right spacer */
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
        }

        .logo-container {
            flex-shrink: 0;
            margin-right: 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .logo::before {
            position: absolute;
            top: -5px;
            font-size: 8px;
            font-weight: bold;
        }

        .logo::after {
            position: absolute;
            bottom: -5px;
            font-size: 8px;
            font-weight: bold;
        }

        .company-info {
            flex-grow: 1;
            text-align: center
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
            margin: 30px 0;
        }

        .po-details {
            margin-bottom: 20px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 8px;
        }

        .detail-label {
            width: 120px;
            font-weight: bold;
            color: #374151;
        }

        .detail-value {
            flex-grow: 1;
            color: #1a1a1a;
        }

        .note-section {
            margin: 20px 0;
        }

        .note-text {
            font-style: italic;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .description-header {
            font-weight: bold;
            color: #374151;
            margin-bottom: 10px;
        }

        .specific-request {
            font-weight: bold;
            color: #1a1a1a;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb; /* abu soft */
            border-radius: 16px;
            padding: 20px;
            margin-top: 16px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05); /* soft shadow */
        }

        /* Updated Items Table Styling to match the image exactly */
        .table-container {
            margin: 20px 0;
            padding: 0;
        }

        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px; /* jarak antar row */
            font-size: 12px;
        }

        .items-table thead th {
            padding: 12px 16px;
            text-align: left;
            font-weight: 500;
            color: #9ca3af;
            background: #ffffff;
            border: none;
            font-size: 12px;
        }

        .items-table tbody {
            background: #ffffff;
        }

        .items-table tbody tr {
            background: #ffffff;
            border: 1px solid #d1d5db; /* border tipis di seluruh row */
            border-radius: 12px;
            overflow: hidden; /* biar radius kepotong rapi */
        }

        .items-table tbody tr + tr {
            margin-top: 8px;
        }

        .items-table tbody td {
            padding: 12px 16px;
            color: #374151;
            font-size: 12px;
            background: #ffffff;
            border-top: 1px solid #d1d5db;
            border-bottom: 1px solid #d1d5db;
        }

        .items-table tbody td:first-child {
            border-left: 1px solid #d1d5db;
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .items-table tbody td:last-child {
            border-right: 1px solid #d1d5db;
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            font-weight: 600;
            color: #111827;
        }

        .items-table tbody tr td:first-child {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .items-table tbody tr td:last-child {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            font-weight: 600;
            color: #111827;
        }

        .items-table th:first-child,
        .items-table td:first-child {
            width: 60px;
            text-align: left;
        }

        .items-table th:nth-child(2),
        .items-table td:nth-child(2) {
            width: auto;
            text-align: left;
        }

        .items-table th:nth-child(3),
        .items-table td:nth-child(3) {
            width: 180px;
            text-align: left;
        }

        .items-table th:last-child,
        .items-table td:last-child {
            width: 120px;
            text-align: left;
        }

        /* Updated Summary Section Styling to match the image exactly */
        .summary-section {
            margin: 40px 0 20px 0;
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
            font-weight: 400;
            color: #9ca3af;
            width: 70%;
            padding-right: 40px;
            font-size: 12px;
        }

        .summary-table .summary-value {
            text-align: right;
            font-weight: 600;
            color: #111827;
            width: 30%;
            font-size: 12px;
        }

        .summary-table .grand-total-row {
            border-top: 1px solid #e5e7eb;
        }

        .summary-table .grand-total-row td {
            padding-top: 16px;
            margin-top: 8px;
        }

        .summary-table .grand-total-row .summary-label {
            font-weight: 600;
            color: #111827;
            font-size: 13px;
        }

        .summary-table .grand-total-row .summary-value {
            font-weight: 600;
            color: #111827;
            font-size: 13px;
        }

        .payment-section {
            margin: 20px 0;
        }

        .payment-row {
            display: flex;
            margin-bottom: 5px;
        }

        .payment-label {
            width: 150px;
            font-weight: bold;
            color: #374151;
        }

        .payment-value {
            flex-grow: 1;
            color: #1a1a1a;
        }

        .closing-remark {
            text-align: left;
            margin: 30px 0;
        }

        .signatures-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            text-align: center;
            width: 22%;
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
            border: 2px solid #1e3a8a;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            position: relative;
            font-size: 8px;
            font-weight: bold;
        }

        .signature-stamp::before {
            content: attr(data-text);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            line-height: 1.2;
        }

        .signature-role {
            font-size: 10px;
            font-weight: bold;
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo-container">
                <div class="logo">
                    <img src="{{ $logoSrc ?? '' }}" alt="Company Logo" style="max-width: 70px; max-height: 70px; border-radius: 50%;" />
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
        <div class="title">Purchase Order</div>

        <!-- PO Details -->
        <div class="po-details">
            <div class="detail-row">
                <div class="detail-label">Nomor PO</div>
                <div class="detail-value">: {{ $po->no_po ?? '-' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Departemen</div>
                <div class="detail-value">: {{ $po->department->name ?? '-' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Perihal</div>
                <div class="detail-value">: {{ $po->perihal->nama ?? '-' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Note</div>
                <div class="detail-value">: {{ $po->keterangan ?? $po->note ?? '-' }}</div>
            </div>
        </div>

        <!-- Note Section -->
        <div class="note-section">
            <div class="description-header">Berikut rincian pembelian barang atau jasa untuk keperluan {{ $po->department->name ?? '-' }}:</div>
            @if(!empty($po->detail_keperluan))
            <div class="specific-request">{{ $po->detail_keperluan }}</div>
            @endif
        </div>

        <div class="card">
            <!-- Updated Items Table to match image styling exactly -->
            <div class="table-container">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Detail</th>
                            <th>No. Invoice</th>
                            <th>Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($po->items && count($po->items) > 0)
                            @foreach($po->items as $i => $item)
                            <tr>
                                <td>{{ $i+1 }}</td>
                                <td>{{ $item->nama ?? $item->nama_barang ?? 'Ongkir JNE Ziglo - BKR' }}</td>
                                <td>{{ $item->no_invoice ?? 'BDO/STD/03/2411007877' }}</td>
                                <td>Rp. {{ number_format($item->harga ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td>1</td>
                                <td>Ongkir JNE Ziglo - BKR</td>
                                <td>BDO/STD/03/2411007877</td>
                                <td>Rp. 100,000</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Ongkir JNE Ziglo - BKR</td>
                                <td>BDO/STD/03/2411007877</td>
                                <td>Rp. 200,000</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Updated Summary Section to match image styling exactly -->
            <div class="summary-section">
                <table class="summary-table">
                    <tr>
                        <td class="summary-label">Total</td>
                        <td class="summary-value">Rp. {{ number_format($total ?? 300000, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="summary-label">Diskon</td>
                        <td class="summary-value">Rp. {{ number_format($diskon ?? 10000, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="summary-label">PPN</td>
                        <td class="summary-value">Rp. {{ number_format($ppn ?? 12000, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="summary-label">PPH {{ $pphPersen ?? 2 }}%</td>
                        <td class="summary-value">Rp. {{ number_format($pph ?? 10000, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="grand-total-row">
                        <td class="summary-label">Grand Total</td>
                        <td class="summary-value">Rp. {{ number_format($grandTotal ?? 312000, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Payment Method -->
        <div class="payment-section">
            <div class="payment-row">
                <div class="payment-label">Metode Pembayaran</div>
                <div class="payment-value">: {{ $po->metode_pembayaran ?? '-' }}</div>
            </div>
            <div class="payment-row">
                <div class="payment-label">Nama Bank</div>
                <div class="payment-value">: {{ $po->bank->nama_bank ?? $po->department->name ?? '-' }}</div>
            </div>
            <div class="payment-row">
                <div class="payment-label">Nama Rekening</div>
                <div class="payment-value">: {{ $po->nama_rekening ?? $po->perihal->nama ?? '-' }}</div>
            </div>
            <div class="payment-row">
                <div class="payment-label">No. Rekening/VA</div>
                <div class="payment-value">: {{ $po->no_rekening ?? '2904480948' }}</div>
            </div>
        </div>

        <!-- Closing Remark -->
        <div class="closing-remark">Terima kasih atas perhatian dan kerjasamanya.</div>

        <!-- Signatures -->
        <div class="signatures-section">
            <div class="signature-box">
                <div class="signature-title">Dibuat Oleh</div>
                <div class="signature-stamp" data-text="SIGNATURE"></div>
                <div class="signature-role">Staff Akunting</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Diperiksa Oleh</div>
                <div class="signature-stamp" data-text="APPROVED"></div>
                <div class="signature-role">Kabag Akunting</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Diketahui Oleh</div>
                <div class="signature-stamp" data-text="APPROVED"></div>
                <div class="signature-role">Kepala Divisi</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Disetujui Oleh</div>
                <div class="signature-stamp" data-text="APPROVED"></div>
                <div class="signature-role">Direksi</div>
            </div>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order</title>
    <style>
        @page {
            margin: 20mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            line-height: 1.4;
            background: white;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            border-left: 3px solid #1e3a8a;
            border-right: 3px solid #1e3a8a;
            padding: 20px;
            min-height: 100vh;
        }

        .header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
        }

        .logo-container {
            flex-shrink: 0;
            margin-right: 20px;
        }

        .logo {
            width: 80px;
            height: 80px;
            border: 2px solid #1e3a8a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            position: relative;
        }

        .logo::before {
            content: "S.G. TEX";
            position: absolute;
            top: -5px;
            font-size: 8px;
            font-weight: bold;
            color: #1e3a8a;
        }

        .logo::after {
            content: "QUALITY SERVICE";
            position: absolute;
            bottom: -5px;
            font-size: 8px;
            font-weight: bold;
            color: #1e3a8a;
        }

        .company-info {
            flex-grow: 1;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1e3a8a;
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
            color: #1e3a8a;
            margin: 30px 0;
            text-transform: uppercase;
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
            padding: 15px;
            background: #f9fafb;
            border-left: 4px solid #1e3a8a;
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

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .items-table th {
            background: #f3f4f6;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .items-table td {
            padding: 10px 8px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
        }

        .items-table tr:nth-child(even) td {
            background: white;
        }

        .summary-section {
            margin: 20px 0;
            text-align: right;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding: 2px 0;
        }

        .summary-label {
            font-weight: bold;
            color: #374151;
        }

        .summary-value {
            font-weight: bold;
            color: #1a1a1a;
            min-width: 150px;
            text-align: right;
        }

        .grand-total {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a8a;
            border-top: 2px solid #1e3a8a;
            padding-top: 5px;
            margin-top: 5px;
        }

        .payment-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9fafb;
            border: 1px solid #d1d5db;
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
            text-align: center;
            margin: 30px 0;
            font-style: italic;
            color: #6b7280;
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
            color: #1e3a8a;
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
                    <!-- Lion head placeholder -->
                    <div style="font-size: 24px; color: #1e3a8a;">🦁</div>
                </div>
            </div>
            <div class="company-info">
                <div class="company-name">PT. Singa Global Tekstil</div>
                <div class="company-address">Soreang Simpang Selegong Muara, Kopo, Kec. Kutawaringin,<br>Kabupaten Bandung, Jawa Barat</div>
                <div class="company-phone">022-19838894</div>
            </div>
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
            <div class="note-text">{{ $po->keterangan ?? $po->note ?? 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s' }}</div>
            <div class="description-header">Berikut rincian pembelian barang atau jasa untuk keperluan {{ $po->department->name ?? '-' }}:</div>
            <div class="specific-request">{{ $po->detail_keperluan ?? 'Pengajuan Pembayaran Ongkir JNE Bulan Nov-Des 2024' }}</div>
        </div>

        <!-- Items Table -->
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

        <!-- Summary -->
        <div class="summary-section">
            <div class="summary-row">
                <div class="summary-label">Total</div>
                <div class="summary-value">Rp. {{ number_format($total ?? 300000, 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Diskon</div>
                <div class="summary-value">Rp. {{ number_format($diskon ?? 10000, 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">PPN</div>
                <div class="summary-value">Rp. {{ number_format($ppn ?? 12000, 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">PPH {{ $pphPersen ?? 2 }}%</div>
                <div class="summary-value">Rp. {{ number_format($pph ?? 10000, 0, ',', '.') }}</div>
            </div>
            <div class="summary-row grand-total">
                <div class="summary-label">Grand Total</div>
                <div class="summary-value">Rp. {{ number_format($grandTotal ?? 312000, 0, ',', '.') }}</div>
            </div>
        </div>

        <!-- Payment Method -->
        <div class="payment-section">
            <div class="payment-row">
                <div class="payment-label">Metode Pembayaran</div>
                <div class="payment-value">: {{ $po->no_po ?? '-' }}</div>
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

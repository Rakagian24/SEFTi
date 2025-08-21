<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order</title>
    <style>
        @page {
            /* F4: 210mm × 330mm */
            size: 210mm 330mm;
            margin: 20mm;
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
            margin: 0 auto;
            border: 3px solid #1e3a8a;
            padding: 20px;
            min-height: calc(330mm - 40mm);
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
            max-width: 70px;
            max-height: 70px;
            border-radius: 50%;
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
            margin: 30px 0;
        }

        .po-details {
            margin-bottom: 20px;
        }

        .detail-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .detail-label {
            display: table-cell;
            width: 120px;
            font-weight: bold;
            color: #374151;
        }

        .detail-value {
            display: table-cell;
            color: #1a1a1a;
        }

        .note-section {
            margin: 20px 0;
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
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 20px;
            margin-top: 16px;
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

        .items-table tbody td:last-child {
            font-weight: bold;
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

        /* Summary Section Styling */
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
            font-weight: normal;
            color: #9ca3af;
            width: 70%;
            padding-right: 40px;
            font-size: 12px;
        }

        .summary-table .summary-value {
            text-align: right;
            font-weight: bold;
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
            font-weight: bold;
            color: #111827;
            font-size: 13px;
        }

        .summary-table .grand-total-row .summary-value {
            font-weight: bold;
            color: #111827;
            font-size: 13px;
        }

        .payment-section {
            margin: 20px 0;
        }

        .payment-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }

        .payment-label {
            display: table-cell;
            width: 150px;
            font-weight: bold;
            color: #374151;
        }

        .payment-value {
            display: table-cell;
            color: #1a1a1a;
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
            text-align: center;
        }

        .signature-stamp img {
            width: 100%;
            height: 100%;
            object-fit: contain;
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
        <div class="title">Purchase Order</div>

        <!-- PO Details -->
        <div class="po-details">
            <div class="detail-row">
                <div class="detail-label">Nomor PO</div>
                <div class="detail-value">: {{ $po->no_po ?? '-' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Tipe PO</div>
                <div class="detail-value">: {{ $po->tipe_po ?? 'Reguler' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Departemen</div>
                <div class="detail-value">: {{ $po->department->name ?? '-' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Perihal</div>
                <div class="detail-value">: {{ $po->perihal->nama ?? '-' }}</div>
            </div>
            @if($po->tipe_po === 'Reguler' && !empty($po->no_invoice))
            <div class="detail-row">
                <div class="detail-label">No. Invoice</div>
                <div class="detail-value">: {{ $po->no_invoice }}</div>
            </div>
            @endif
            @if($po->tipe_po === 'Lainnya' && !empty($po->termin_id))
            <div class="detail-row">
                <div class="detail-label">No Ref Termin</div>
                <div class="detail-value">: {{ $po->termin_id ?? '-' }}</div>
            </div>
            @endif
            @if(!empty($po->keterangan) || !empty($po->note))
            <div class="detail-row">
                <div class="detail-label">Note</div>
                <div class="detail-value">: {{ $po->keterangan ?? $po->note ?? '-' }}</div>
            </div>
            @endif
        </div>

        <!-- Note Section -->
        <div class="note-section">
            <div class="description-header">Berikut rincian pembelian barang atau jasa untuk keperluan {{ $po->department->name ?? '-' }}:</div>
            @if(!empty($po->detail_keperluan))
            <div class="specific-request">{{ $po->detail_keperluan }}</div>
            @endif
        </div>

        <div class="card">
            <!-- Items Table -->
            <div class="table-container">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Detail</th>
                            @if($po->tipe_po === 'Reguler')
                            <th>No. Invoice</th>
                            @else
                            <th>Keterangan</th>
                            @endif
                            <th>Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($po->items && count($po->items) > 0)
                            @foreach($po->items as $i => $item)
                            <tr>
                                <td>{{ $i+1 }}</td>
                                <td>{{ $item->nama ?? $item->nama_barang ?? '-' }}</td>
                                @if($po->tipe_po === 'Reguler')
                                <td>{{ $item->no_invoice ?? '-' }}</td>
                                @else
                                <td>{{ $item->keterangan ?? '-' }}</td>
                                @endif
                                <td>Rp. {{ number_format($item->harga ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td>1</td>
                                <td>Ongkir JNE Ziglo - BKR</td>
                                @if($po->tipe_po === 'Reguler')
                                <td>BDO/STD/03/2411007877</td>
                                @else
                                <td>Ongkir</td>
                                @endif
                                <td>Rp. 100,000</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Summary Section -->
            <div class="summary-section">
                <table class="summary-table">
                    <tr>
                        <td class="summary-label">Total</td>
                        <td class="summary-value">Rp. {{ number_format($total ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    @if(isset($diskon) && $diskon > 0)
                    <tr>
                        <td class="summary-label">Diskon</td>
                        <td class="summary-value">Rp. {{ number_format($diskon, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if(isset($ppn) && $ppn > 0)
                    <tr>
                        <td class="summary-label">PPN</td>
                        <td class="summary-value">Rp. {{ number_format($ppn, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if(isset($pph) && $pph > 0)
                    <tr>
                        <td class="summary-label">PPH {{ $pphPersen ?? 2 }}%</td>
                        <td class="summary-value">Rp. {{ number_format($pph, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="grand-total-row">
                        <td class="summary-label">Grand Total</td>
                        <td class="summary-value">Rp. {{ number_format($grandTotal ?? 0, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Payment Method Section - Dynamic based on payment method -->
        <div class="payment-section">
            <div class="payment-row">
                <div class="payment-label">Metode Pembayaran</div>
                <div class="payment-value">: {{ $po->metode_pembayaran ?? '-' }}</div>
            </div>

            @if($po->metode_pembayaran === 'Transfer' || empty($po->metode_pembayaran))
                <!-- Transfer payment fields -->
                @if(!empty($po->bank))
                <div class="payment-row">
                    <div class="payment-label">Nama Bank</div>
                    <div class="payment-value">: {{ $po->bank->nama_bank ?? '-' }}</div>
                </div>
                @endif
                @if(!empty($po->nama_rekening))
                <div class="payment-row">
                    <div class="payment-label">Nama Rekening</div>
                    <div class="payment-value">: {{ $po->nama_rekening }}</div>
                </div>
                @endif
                @if(!empty($po->no_rekening))
                <div class="payment-row">
                    <div class="payment-label">No. Rekening/VA</div>
                    <div class="payment-value">: {{ $po->no_rekening }}</div>
                </div>
                @endif
            @elseif($po->metode_pembayaran === 'Cek/Giro')
                <!-- Cek/Giro payment fields -->
                @if(!empty($po->no_giro))
                <div class="payment-row">
                    <div class="payment-label">No. Cek/Giro</div>
                    <div class="payment-value">: {{ $po->no_giro }}</div>
                </div>
                @endif
                @if(!empty($po->tanggal_giro))
                <div class="payment-row">
                    <div class="payment-label">Tanggal Giro</div>
                    <div class="payment-value">: {{ \Carbon\Carbon::parse($po->tanggal_giro)->format('d F Y') }}</div>
                </div>
                @endif
                @if(!empty($po->tanggal_cair))
                <div class="payment-row">
                    <div class="payment-label">Tanggal Cair</div>
                    <div class="payment-value">: {{ \Carbon\Carbon::parse($po->tanggal_cair)->format('d F Y') }}</div>
                </div>
                @endif
            @elseif($po->metode_pembayaran === 'Kredit')
                <!-- Kredit payment fields -->
                @if(!empty($po->no_kartu_kredit))
                <div class="payment-row">
                    <div class="payment-label">No. Kartu Kredit</div>
                    <div class="payment-value">: {{ $po->no_kartu_kredit }}</div>
                </div>
                @endif
            @endif

            <!-- Additional payment info for Lainnya type -->
            @if($po->tipe_po === 'Lainnya' && isset($cicilan) && $cicilan > 0)
            <div class="payment-row">
                <div class="payment-label">Cicilan</div>
                <div class="payment-value">: Rp. {{ number_format($cicilan, 0, ',', '.') }}</div>
            </div>
            @endif
        </div>

        <!-- Closing Remark -->
        <div class="closing-remark">Terima kasih atas perhatian dan kerjasamanya.</div>

        <!-- Signatures -->
        <div class="signatures-section">
            <div class="signature-box">
                <div class="signature-title">Dibuat Oleh</div>
                <div class="signature-stamp">
                    <img src="{{ $signatureSrc ?? asset('images/signature.png') }}" alt="Signature Stamp" />
                </div>
                <div class="signature-role">Staff Akunting</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Diperiksa Oleh</div>
                <div class="signature-stamp">
                    <img src="{{ $approvedSrc ?? asset('images/approved.png') }}" alt="Approved Stamp" />
                </div>
                <div class="signature-role">Kabag Akunting</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Diketahui Oleh</div>
                <div class="signature-stamp">
                    <img src="{{ $approvedSrc ?? asset('images/approved.png') }}" alt="Approved Stamp" />
                </div>
                <div class="signature-role">Kepala Divisi</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Disetujui Oleh</div>
                <div class="signature-stamp">
                    <img src="{{ $approvedSrc ?? asset('images/approved.png') }}" alt="Approved Stamp" />
                </div>
                <div class="signature-role">Direksi</div>
            </div>
        </div>
    </div>
</body>
</html>

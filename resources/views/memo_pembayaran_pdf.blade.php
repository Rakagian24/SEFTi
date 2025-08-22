<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memo Pembayaran - {{ $memo->no_mb }}</title>
    <style>
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }

        .logo {
            max-width: 150px;
            max-height: 80px;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .document-title {
            font-size: 28px;
            font-weight: bold;
            margin: 20px 0;
            text-transform: uppercase;
        }

        .document-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .info-section {
            flex: 1;
        }

        .info-row {
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }

        .info-value {
            display: inline-block;
        }

        .content-section {
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        .detail-keperluan {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .payment-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .payment-section {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .payment-label {
            font-weight: bold;
        }

        .payment-value {
            text-align: right;
        }

        .calculation-section {
            margin: 20px 0;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }

        .calculation-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .calculation-row.total {
            border-top: 2px solid #333;
            padding-top: 10px;
            font-weight: bold;
            font-size: 16px;
        }

        .closing-remark {
            text-align: center;
            margin: 30px 0;
            font-style: italic;
        }

        .signatures-section {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }

        .signature-box {
            text-align: center;
            flex: 1;
            margin: 0 20px;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin: 50px 0 10px 0;
        }

        .signature-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .signature-title {
            font-size: 12px;
            color: #666;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <img src="data:image/png;base64,{{ $logoSrc }}" alt="Company Logo" class="logo">
        <div class="company-name">PT. SEFTI</div>
        <div class="document-title">Memo Pembayaran</div>
    </div>

    <!-- Document Information -->
    <div class="document-info">
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">No. MB:</span>
                <span class="info-value">{{ $memo->no_mb ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tanggal:</span>
                <span class="info-value">{{ $tanggal }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Departemen:</span>
                <span class="info-value">{{ $memo->department->name ?? '-' }}</span>
            </div>
        </div>
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value">{{ $memo->status ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Perihal:</span>
                <span class="info-value">{{ $memo->perihal->nama ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">No. PO:</span>
                <span class="info-value">
                    @if($memo->purchaseOrders->count() > 0)
                        {{ $memo->purchaseOrders->pluck('no_po')->implode(', ') }}
                    @else
                        -
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- Detail Keperluan -->
    <div class="content-section">
        <div class="section-title">Detail Keperluan</div>
        <div class="detail-keperluan">
            {{ $memo->detail_keperluan ?? '-' }}
        </div>
    </div>

    <!-- Payment Information -->
    <div class="content-section">
        <div class="section-title">Informasi Pembayaran</div>
        <div class="payment-details">
            <div class="payment-section">
                <div class="payment-row">
                    <div class="payment-label">Metode Pembayaran</div>
                    <div class="payment-value">{{ $memo->metode_pembayaran ?? '-' }}</div>
                </div>

                @if($memo->metode_pembayaran === 'Transfer' || empty($memo->metode_pembayaran))
                    @if(!empty($memo->supplier))
                    <div class="payment-row">
                        <div class="payment-label">Supplier</div>
                        <div class="payment-value">{{ $memo->supplier->nama_supplier ?? '-' }}</div>
                    </div>
                    @endif
                    @if(!empty($memo->bank))
                    <div class="payment-row">
                        <div class="payment-label">Bank</div>
                        <div class="payment-value">{{ $memo->bank->nama_bank ?? '-' }}</div>
                    </div>
                    @endif
                    @if(!empty($memo->nama_rekening))
                    <div class="payment-row">
                        <div class="payment-label">Nama Rekening</div>
                        <div class="payment-value">{{ $memo->nama_rekening }}</div>
                    </div>
                    @endif
                    @if(!empty($memo->no_rekening))
                    <div class="payment-row">
                        <div class="payment-label">No. Rekening</div>
                        <div class="payment-value">{{ $memo->no_rekening }}</div>
                    </div>
                    @endif
                @elseif($memo->metode_pembayaran === 'Cek/Giro')
                    @if(!empty($memo->no_giro))
                    <div class="payment-row">
                        <div class="payment-label">No. Cek/Giro</div>
                        <div class="payment-value">{{ $memo->no_giro }}</div>
                    </div>
                    @endif
                    @if(!empty($memo->tanggal_giro))
                    <div class="payment-row">
                        <div class="payment-label">Tanggal Giro</div>
                        <div class="payment-value">{{ \Carbon\Carbon::parse($memo->tanggal_giro)->format('d F Y') }}</div>
                    </div>
                    @endif
                    @if(!empty($memo->tanggal_cair))
                    <div class="payment-row">
                        <div class="payment-label">Tanggal Cair</div>
                        <div class="payment-value">{{ \Carbon\Carbon::parse($memo->tanggal_cair)->format('d F Y') }}</div>
                    </div>
                    @endif
                @elseif($memo->metode_pembayaran === 'Kredit')
                    @if(!empty($memo->no_kartu_kredit))
                    <div class="payment-row">
                        <div class="payment-label">No. Kartu Kredit</div>
                        <div class="payment-value">{{ $memo->no_kartu_kredit }}</div>
                    </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <!-- Calculation Section -->
    <div class="content-section">
        <div class="section-title">Perhitungan</div>
        <div class="calculation-section">
            <div class="calculation-row">
                <span>Total</span>
                <span>Rp. {{ number_format($memo->total, 0, ',', '.') }}</span>
            </div>
            <div class="calculation-row">
                <span>Diskon</span>
                <span>Rp. {{ number_format($memo->diskon, 0, ',', '.') }}</span>
            </div>
            @if($memo->ppn)
            <div class="calculation-row">
                <span>PPN (11%)</span>
                <span>Rp. {{ number_format($memo->ppn_nominal, 0, ',', '.') }}</span>
            </div>
            @endif
            @if($memo->pph)
            <div class="calculation-row">
                <span>PPH ({{ $memo->pph->persentase }}%)</span>
                <span>Rp. {{ number_format($memo->pph_nominal, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="calculation-row total">
                <span>Grand Total</span>
                <span>Rp. {{ number_format($memo->grand_total, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Keterangan -->
    @if(!empty($memo->keterangan))
    <div class="content-section">
        <div class="section-title">Keterangan</div>
        <div class="detail-keperluan">
            {{ $memo->keterangan }}
        </div>
    </div>
    @endif

    <!-- Closing Remark -->
    <div class="closing-remark">Terima kasih atas perhatian dan kerjasamanya.</div>

    <!-- Signatures -->
    <div class="signatures-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-name">{{ $memo->creator->name ?? 'Dibuat Oleh' }}</div>
            <div class="signature-title">Pembuat</div>
        </div>

        @if($memo->approver)
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-name">{{ $memo->approver->name ?? 'Disetujui Oleh' }}</div>
            <div class="signature-title">Penyetuju</div>
        </div>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Dokumen ini dibuat secara otomatis oleh sistem SEFTI</p>
        <p>Dicetak pada: {{ \Carbon\Carbon::now()->format('d F Y H:i:s') }}</p>
    </div>
</body>
</html>

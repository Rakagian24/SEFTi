<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Memo Pembayaran</title>
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
            margin: 40px 0;
        }

        .memo-details {
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

        .payment-section {
            margin: 20px 0;
        }

        .payment-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
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

        .calculation-section {
            margin: 20px 0;
            border: 1px solid #e5e7eb;
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

        .signature-name {
            font-size: 11px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 3px;
        }

        .signature-role {
            font-size: 10px;
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
        <div class="title">Memo Pembayaran</div>

        <!-- Memo Details -->
        <div class="memo-details">
            <div class="detail-row">
                <div class="detail-label">Nomor</div>
                <div class="detail-value">: {{ $memo->no_mb ?? '-' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Perihal</div>
                <div class="detail-value">: {{ optional($memo->purchaseOrders->first()->perihal ?? null)->nama ?? 'Permintaan Pembayaran' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Nominal</div>
                <div class="detail-value">: {{ number_format($memo->grand_total ?? $memo->total ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Note</div>
                <div class="detail-value">: {{ $memo->keterangan ?? 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.' }}</div>
            </div>
        </div>

        <!-- Note Section -->
        <div class="note-section">
            <div class="description-header">Kepada Yth.</div>
            <div class="specific-request">Finance PT. Singan Global Tekstil 2</div>
        </div>

        <div class="card">
            <!-- Payment Information -->
            <div class="payment-section">
                <div class="payment-row">
                    <div class="payment-label">Nama</div>
                    <div class="payment-value">: {{ $memo->nama_rekening ?? '-' }}</div>
                </div>
                <div class="payment-row">
                    <div class="payment-label">Bank</div>
                    <div class="payment-value">: {{ $memo->bank->nama_bank ?? '-' }}</div>
                </div>
                <div class="payment-row">
                    <div class="payment-label">No. Rekening/VA</div>
                    <div class="payment-value">: {{ $memo->no_rekening ?? '-' }}</div>
                </div>
            </div>

            <!-- Calculation Section -->
            <div class="calculation-section">
                <div class="calculation-row">
                    <span>Total</span>
                    <span>{{ number_format($memo->total ?? 0, 0, ',', '.') }}</span>
                </div>
                @if(($memo->diskon ?? 0) > 0)
                <div class="calculation-row">
                    <span>Diskon</span>
                    <span>{{ number_format($memo->diskon, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($memo->ppn)
                <div class="calculation-row">
                    <span>PPN (11%)</span>
                    <span>{{ number_format($memo->ppn_nominal ?? 0, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($memo->pph)
                <div class="calculation-row">
                    <span>PPH ({{ $memo->pph->persentase ?? 0 }}%)</span>
                    <span>{{ number_format($memo->pph_nominal ?? 0, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="calculation-row total">
                    <span>Grand Total</span>
                    <span>{{ number_format($memo->grand_total ?? $memo->total ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Closing Remark -->
        <div class="closing-remark">Terima kasih atas perhatian dan kerjasamanya.</div>

        <!-- Signatures -->
        @php
            $creatorRole = optional(optional($memo->creator)->role)->name;
            $deptName = optional($memo->department)->name;
            $hasVerifyStep = ($deptName !== 'Zi&Glo') && in_array($creatorRole, ['Staff Toko', 'Staff Akunting & Finance']);
            $hasValidateStep = ($creatorRole === 'Staff Toko') || ($creatorRole === 'Staff Digital Marketing') || ($deptName === 'Zi&Glo');
            $verifyRoleLabel = $creatorRole === 'Staff Akunting & Finance' ? 'Kabag' : 'Kepala Toko';
        @endphp
        <div class="signatures-section">
            <!-- 1. Dibuat Oleh - Always shown -->
            <div class="signature-box">
                <div class="signature-title">Dibuat Oleh</div>
                <div class="signature-stamp">
                    <img src="{{ $signatureSrc ?? asset('images/signature.png') }}" alt="Signature Stamp" />
                </div>
                @if($memo->created_by && $memo->creator)
                <div class="signature-name">{{ $memo->creator->name ?? 'User' }}</div>
                @endif
                <div class="signature-role">{{ $creatorRole ?? 'Staff Toko' }}</div>
                @if($memo->created_by && $memo->created_at)
                <div class="signature-date">{{ \Carbon\Carbon::parse($memo->created_at)->format('d/m/Y') }}</div>
                @endif
            </div>

            <!-- 2. Diverifikasi Oleh - Show only when workflow has verify step -->
            @if($hasVerifyStep)
            <div class="signature-box">
                <div class="signature-title">Diverifikasi Oleh</div>
                @if(in_array($memo->status, ['Verified', 'Validated', 'Approved']) && $memo->verified_by)
                <div class="signature-stamp">
                    <img src="{{ $approvedSrc ?? asset('images/approved.png') }}" alt="Verified Stamp" />
                </div>
                @else
                <div class="signature-stamp" style="height: 80px;"></div>
                @endif
                @if($memo->verified_by && $memo->verifier)
                <div class="signature-name">{{ $memo->verifier->name ?? 'User' }}</div>
                @endif
                <div class="signature-role">{{ $verifyRoleLabel }}</div>
                @if($memo->verified_at)
                <div class="signature-date">{{ \Carbon\Carbon::parse($memo->verified_at)->format('d/m/Y') }}</div>
                @endif
            </div>
            @endif

            <!-- 3. Divalidasi Oleh - Show only when workflow has validate step -->
            @if($hasValidateStep)
            <div class="signature-box">
                <div class="signature-title">Divalidasi Oleh</div>
                @if(in_array($memo->status, ['Validated', 'Approved']) && $memo->validated_by)
                <div class="signature-stamp">
                    <img src="{{ $approvedSrc ?? asset('images/approved.png') }}" alt="Validated Stamp" />
                </div>
                @else
                <div class="signature-stamp" style="height: 80px;"></div>
                @endif
                @if($memo->validated_by && $memo->validator)
                <div class="signature-name">{{ $memo->validator->name ?? 'User' }}</div>
                @endif
                <div class="signature-role">Kadiv</div>
                @if($memo->validated_at)
                <div class="signature-date">{{ \Carbon\Carbon::parse($memo->validated_at)->format('d/m/Y') }}</div>
                @endif
            </div>
            @endif

            <!-- 4. Disetujui Oleh - Always show box, stamp only if approved -->
            <div class="signature-box">
                <div class="signature-title">Disetujui Oleh</div>
                @if($memo->status === 'Approved' && $memo->approved_by)
                <div class="signature-stamp">
                    <img src="{{ $approvedSrc ?? asset('images/approved.png') }}" alt="Approved Stamp" />
                </div>
                @else
                <div class="signature-stamp" style="height: 80px;"></div>
                @endif
                @if($memo->approved_by && $memo->approver)
                <div class="signature-name">{{ $memo->approver->name ?? 'User' }}</div>
                @endif
                <div class="signature-role">Direksi</div>
                @if($memo->approved_at)
                <div class="signature-date">{{ \Carbon\Carbon::parse($memo->approved_at)->format('d/m/Y') }}</div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>

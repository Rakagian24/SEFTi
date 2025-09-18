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
            /* Soft blue gradient like the mock */
            background: white;
            margin: 0;
            padding: 0; /* create breathing room around rounded canvas */
        }

        .container {
            width: 100%;
            max-width: 170mm; /* page width 210 - margins (2*20) = 170mm */
            margin: 0 auto;
            padding: 28px;
            min-height: calc(297mm - 40mm);
            box-sizing: border-box;
            background: #ffffff;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 16px;
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
            margin-bottom: 6px;
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
            color: #6b7280;
            margin-bottom: 24px;
        }

        .title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin: 26px 0 22px 0;
        }

        .memo-details {
            margin-bottom: 20px;
        }

        .memo-details .detail-row:nth-child(4) {
            margin-bottom: 32px;
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
            border-radius: 18px;
            padding: 18px 20px;
            margin-top: 12px;
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
            position: absolute;
            bottom: 180px; /* jarak dari bawah halaman */
            left: 28px;
            right: 28px;

            display: table;
            width: calc(100% - 56px); /* biar ikut padding kiri-kanan */
        }

        .signature-box {
            display: table-cell;
            text-align: center;
            width: 50%;
            vertical-align: top;
        }

        .signature-title {
            font-weight: bold;
            color: #9ca3af;
            margin-bottom: 10px;
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

        <!-- Note Section -->
        <div class="note-section">
            <div class="description-header">Kepada Yth.</div>
            <div class="specific-request">Finance PT. Singan Global Tekstil 2</div>
        </div>

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
                <div class="detail-value">: {{ $memo->keterangan ?? '-' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Nama</div>
                <div class="detail-value">: {{ $memo->nama_rekening ?? '-' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Bank</div>
                <div class="detail-value">: {{ $memo->bank->nama_bank ?? '-' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">No. Rekening/VA</div>
                <div class="detail-value">: {{ $memo->no_rekening ?? '-' }}</div>
            </div>
        </div>

        <!-- Signatures -->
        @php
            $creatorRole = optional(optional($memo->creator)->role)->name;
            $deptName = optional($memo->department)->name;
            $hasVerifyStep = ($deptName !== 'Zi&Glo') && in_array($creatorRole, ['Staff Toko', 'Staff Akunting & Finance']);
            $hasValidateStep = ($creatorRole === 'Staff Toko') || ($creatorRole === 'Staff Digital Marketing') || ($deptName === 'Zi&Glo');
            $verifyRoleLabel = $creatorRole === 'Staff Akunting & Finance' ? 'Kabag' : 'Kepala Toko';
        @endphp
        <div class="signatures-section">
            <!-- Left: Dibuat Oleh -->
            <div class="signature-box">
                <div class="signature-title">Dibuat Oleh</div>
                <div class="signature-stamp">
                    <img src="{{ $signatureSrc ?? asset('images/signature.png') }}" alt="Signature Stamp" />
                </div>
                @if($memo->created_by && $memo->creator)
                <div class="signature-name">{{ $memo->creator->name ?? 'User' }}</div>
                @endif
                <div class="signature-role">{{ $     ?? 'Staff Toko' }}</div>
                @if($memo->created_by && $memo->created_at)
                <div class="signature-date">{{ \Carbon\Carbon::parse($memo->created_at)->format('d/m/Y') }}</div>
                @endif
            </div>

            <!-- Right: Diperiksa Oleh (use verify step when applicable, else approved) -->
            <div class="signature-box">
                <div class="signature-title">Diperiksa Oleh</div>
                @php
                    $showRightStamp = ($hasVerifyStep && in_array($memo->status, ['Verified','Validated','Approved']) && $memo->verified_by)
                        || (!$hasVerifyStep && $memo->status === 'Approved' && $memo->approved_by);
                    $rightName = $hasVerifyStep ? optional($memo->verifier)->name : optional($memo->approver)->name;
                    $rightRole = $hasVerifyStep ? $verifyRoleLabel : 'Kepala Toko';
                    $rightDate = $hasVerifyStep ? $memo->verified_at : $memo->approved_at;
                @endphp
                @if($showRightStamp)
                <div class="signature-stamp">
                    <img src="{{ $approvedSrc ?? asset('images/approved.png') }}" alt="Approved Stamp" />
                </div>
                @else
                <div class="signature-stamp" style="height: 80px;"></div>
                @endif
                @if($rightName)
                <div class="signature-name">{{ $rightName }}</div>
                @endif
                <div class="signature-role">{{ $rightRole }}</div>
                @if($rightDate)
                <div class="signature-date">{{ \Carbon\Carbon::parse($rightDate)->format('d/m/Y') }}</div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>

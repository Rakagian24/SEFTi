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
            $hasVerifyStep = ($deptName !== 'Zi&Glo' && $deptName !== 'Human Greatness') && in_array($creatorRole, ['Staff Toko', 'Staff Akunting & Finance']);
            $hasValidateStep = ($creatorRole === 'Staff Toko') || ($creatorRole === 'Staff Digital Marketing') || ($deptName === 'Zi&Glo' || $deptName === 'Human Greatness');
            $verifyRoleLabel = $creatorRole === 'Staff Akunting & Finance' ? 'Kabag' : 'Kepala Toko';
        @endphp
        <div class="signatures-section">
            {{-- Dibuat Oleh --}}
            <div class="signature-box">
                <div class="title">Dibuat Oleh</div>
                <div class="signature">
                    @if ($signatureSrc)
                        <img src="{{ $signatureSrc }}" alt="Tanda Tangan" class="signature-img">
                    @endif
                </div>
                <div class="name">{{ $memo->creator->name ?? '' }}</div>
                <div class="role">
                    @php
                        $creatorRole = $memo->creator->role->name ?? '';
                        if ($creatorRole === 'Staff Toko') {
                            $creatorRole = 'Staff Toko';
                        } elseif ($creatorRole === 'Staff Akunting & Finance') {
                            $creatorRole = 'Staff Akunting & Finance';
                        } elseif ($creatorRole === 'Admin') {
                            $creatorRole = 'Admin';
                        }
                    @endphp
                    {{ $creatorRole }}
                </div>
                <div class="date">Tanggal: {{ $memo->created_at->format('d-m-Y') }}</div>
            </div>

            {{-- Diperiksa atau Disetujui --}}
            <div class="signature-box">
                @if ($hasVerifyStep)
                    <div class="title">Diperiksa Oleh</div>
                    <div class="signature">
                        @if (in_array($memo->status, ['Verified', 'Validated', 'Approved']) && $memo->verified_by && $approvedSrc)
                            <img src="{{ $approvedSrc }}" alt="Approved Stamp" class="stamp-img">
                        @endif
                    </div>
                    <div class="name">{{ $memo->verifier->name ?? '' }}</div>
                    <div class="role">
                        @if ($memo->creator->role->name === 'Staff Akunting & Finance')
                            Kabag
                        @else
                            Kepala Toko
                        @endif
                    </div>
                    <div class="date">
                        Tanggal: {{ $memo->verified_at ? \Carbon\Carbon::parse($memo->verified_at)->format('d-m-Y') : '' }}
                    </div>
                @else
                    <div class="title">Disetujui Oleh</div>
                    <div class="signature">
                        @if ($memo->status === 'Approved' && $memo->approved_by && $approvedSrc)
                            <img src="{{ $approvedSrc }}" alt="Approved Stamp" class="stamp-img">
                        @endif
                    </div>
                    <div class="name">{{ $memo->approver->name ?? '' }}</div>
                    <div class="role">
                        @if ($memo->creator->role->name === 'Staff Akunting & Finance')
                            Kabag
                        @else
                            Kadiv
                        @endif
                    </div>
                    <div class="date">
                        Tanggal: {{ $memo->approved_at ? \Carbon\Carbon::parse($memo->approved_at)->format('d-m-Y') : '' }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>

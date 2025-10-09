<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Memo Pembayaran</title>
    <style>
        @page {
            size: A4 portrait;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            line-height: 1.4;
            background: #f3f4f6;
            margin: 0;
            padding: 20px 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .container {
            width: 210mm;
            max-width: 210mm;
            margin: 0 auto;
            padding: 20mm;
            min-height: 297mm;
            box-sizing: border-box;
            background: white;
            border: 1px solid #d1d5db;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
        }

        .note-section {
            margin: 20px 0;
        }
        .description-header {
            font-weight: bold;
            margin-bottom: 6px;
        }
        .specific-request {
            font-weight: bold;
        }

        .signatures-section {
            margin-top: 80px;
            display: table;
            width: 100%;
            page-break-inside: avoid;
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
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div class="logo-container">
                <div class="logo">
                    @if($logoSrc)
                        <img src="{{ $logoSrc }}" alt="Company Logo">
                    @endif
                </div>
            </div>
            <div class="company-info">
                <div class="company-name">PT. Singa Global Tekstil</div>
                <div class="company-address">Soreang Simpang Selegong Muara, Kopo, Kec. Kutawaringin,<br>Kabupaten Bandung, Jawa Barat</div>
                <div class="company-phone">022-19838894</div>
            </div>
            <div style="width:100px"></div>
        </div>

        <div class="date-location">Bandung, {{ $tanggal ?? now()->translatedFormat('d F Y') }}</div>

        {{-- Title --}}
        <div class="title">Memo Pembayaran</div>

        {{-- Kepada --}}
        <div class="note-section">
            <div class="description-header">Kepada Yth.</div>
            <div class="specific-request">Finance PT. Singa Global Tekstil 2</div>
        </div>

        {{-- Detail --}}
        <div class="memo-details">
            <div class="detail-row"><div class="detail-label">Nomor</div><div class="detail-value">: {{ $memo->no_mb ?? '-' }}</div></div>
            <div class="detail-row"><div class="detail-label">Perihal</div><div class="detail-value">: {{ $memo->purchaseOrders->first()->perihal ?? 'Permintaan Pembayaran' }}</div></div>
            <div class="detail-row"><div class="detail-label">Nominal</div><div class="detail-value">: {{ number_format($memo->grand_total ?? $memo->total ?? 0,0,',','.') }}</div></div>
            <div class="detail-row"><div class="detail-label">Note</div><div class="detail-value">: {{ $memo->keterangan ?? '-' }}</div></div>

            {{-- Metode Pembayaran Dinamis --}}
            <div class="detail-row"><div class="detail-label">Metode Pembayaran</div><div class="detail-value">: {{ $memo->metode_pembayaran ?? '-' }}</div></div>

            @if($memo->metode_pembayaran === 'Transfer' || empty($memo->metode_pembayaran))
                @if(!empty($memo->bank))
                <div class="detail-row"><div class="detail-label">Bank</div><div class="detail-value">: {{ $memo->bank->nama_bank ?? '-' }}</div></div>
                @endif
                @if(!empty($memo->nama_rekening))
                <div class="detail-row"><div class="detail-label">Nama Rekening</div><div class="detail-value">: {{ $memo->nama_rekening }}</div></div>
                @endif
                @if(!empty($memo->no_rekening))
                <div class="detail-row"><div class="detail-label">No. Rekening/VA</div><div class="detail-value">: {{ $memo->no_rekening }}</div></div>
                @endif
            @elseif($memo->metode_pembayaran === 'Cek/Giro')
                @if(!empty($memo->no_giro))
                <div class="detail-row"><div class="detail-label">No. Cek/Giro</div><div class="detail-value">: {{ $memo->no_giro }}</div></div>
                @endif
                @if(!empty($memo->tanggal_giro))
                <div class="detail-row"><div class="detail-label">Tanggal Giro</div><div class="detail-value">: {{ \Carbon\Carbon::parse($memo->tanggal_giro)->format('d F Y') }}</div></div>
                @endif
                @if(!empty($memo->tanggal_cair))
                <div class="detail-row"><div class="detail-label">Tanggal Cair</div><div class="detail-value">: {{ \Carbon\Carbon::parse($memo->tanggal_cair)->format('d F Y') }}</div></div>
                @endif
            @elseif($memo->metode_pembayaran === 'Kredit')
                @if(!empty($memo->no_kartu_kredit))
                <div class="detail-row"><div class="detail-label">No. Kartu Kredit</div><div class="detail-value">: {{ $memo->no_kartu_kredit }}</div></div>
                @endif
            @endif
        </div>

        {{-- Signatures --}}
        <div class="signatures-section">
            @php
                $progress = app(\App\Services\ApprovalWorkflowService::class)
                    ->getApprovalProgressForMemoPembayaran($memo);

                $signatureBoxes = [];

                // Cek apakah pembuat adalah kepala toko
                $isKepalaToko = str_contains(strtolower($memo->creator->display_role ?? ''), 'kepala toko')
                    || str_contains(strtolower(optional($memo->creator->role)->name ?? ''), 'kepala toko');

                // Selalu tambahkan 'Dibuat oleh'
                $signatureBoxes[] = [
                    'title' => 'Dibuat Oleh',
                    'stamp' => $signatureSrc ? $signatureSrc : null,
                    'name' => $memo->creator->name ?? '',
                    'role' => $memo->creator && $memo->creator->display_role ? $memo->creator->display_role : (optional($memo->creator)->role->name ?? '-'),
                    'date' => $memo->created_at?->format('d-m-Y'),
                ];

                if ($isKepalaToko) {
                    // Kepala toko → hanya ambil yang statusnya 'completed' terakhir (Disetujui Oleh)
                    $lastApproval = collect($progress)->where('status', 'completed')->last();
                    if ($lastApproval) {
                        $signatureBoxes[] = [
                            'title' => 'Disetujui Oleh',
                            'stamp' => $approvedSrc ?? null,
                            'name' => $lastApproval['completed_by']['name'] ?? '',
                            'role' => $lastApproval['role'] ?? '-',
                            'date' => $lastApproval['completed_at']
                                ? \Carbon\Carbon::parse($lastApproval['completed_at'])->format('d-m-Y')
                                : '',
                        ];
                    }
                } else {
                    // Non kepala toko → tampilkan semua step
                    foreach ($progress as $step) {
                        $signatureBoxes[] = [
                            'title' => $step['step'] === 'verified' ? 'Diverifikasi Oleh' : 'Disetujui Oleh',
                            'stamp' => ($step['status'] === 'completed' && $approvedSrc) ? $approvedSrc : null,
                            'name' => $step['completed_by']['name'] ?? '',
                            'role' => $step['role'] ?? '-',
                            'date' => $step['completed_at']
                                ? \Carbon\Carbon::parse($step['completed_at'])->format('d-m-Y')
                                : '',
                        ];
                    }
                }
            @endphp
            @foreach ($signatureBoxes as $box)
                <div class="signature-box">
                    <div class="signature-title">{{ $box['title'] }}</div>
                    <div class="signature-stamp">
                        @if ($box['stamp'])
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

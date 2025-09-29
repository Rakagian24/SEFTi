<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Memo Pembayaran</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .container {
            width: 100%;
            max-width: 170mm;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
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
            font-size: 20px;
            font-weight: bold;
        }
        .date-location {
            text-align: right;
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 24px;
        }
        .title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            margin: 24px 0;
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
            margin-top: 40px;
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
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Company Logo">
                @endif
            </div>
            <div class="company-info">
                <div class="company-name">PT. Singa Global Tekstil</div>
                <div>Soreang Simpang Selegong Muara, Kopo, Kec. Kutawaringin,<br>Kabupaten Bandung, Jawa Barat</div>
                <div>Telp: 022-19838894</div>
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
                // Dibuat oleh
                $signatureBoxes[] = [
                    'title' => 'Dibuat Oleh',
                    'stamp' => $signatureSrc ? $signatureSrc : null,
                    'name' => $memo->creator->name ?? '',
                    'role' => $memo->creator->display_role ?? '-',
                    'date' => $memo->created_at?->format('d-m-Y'),
                ];
                // Steps dari workflow
                foreach ($progress as $step) {
                    $signatureBoxes[] = [
                        'title' => $step['step'] === 'verified' ? 'Diverifikasi Oleh' : 'Disetujui Oleh',
                        'stamp' => ($step['status'] === 'completed' && $approvedSrc) ? $approvedSrc : null,
                        'name' => $step['completed_by']['name'] ?? '',
                        'role' => $step['role'] ?? '',
                        'date' => $step['completed_at'] ? \Carbon\Carbon::parse($step['completed_at'])->format('d-m-Y') : '',
                    ];
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

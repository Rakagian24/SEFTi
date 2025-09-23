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
            margin-top: 60px;
            display: table;
            width: 100%;
            page-break-inside: avoid;
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
        .signature-img,
        .stamp-img {
            max-width: 100px;
            max-height: 80px;
            margin-bottom: 8px;
        }
        .signature-name {
            font-size: 11px;
            font-weight: bold;
        }
        .signature-role {
            font-size: 10px;
            color: #374151;
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
            <div class="detail-row"><div class="detail-label">Nama</div><div class="detail-value">: {{ $memo->nama_rekening ?? '-' }}</div></div>
            <div class="detail-row"><div class="detail-label">Bank</div><div class="detail-value">: {{ $memo->bank->nama_bank ?? '-' }}</div></div>
            <div class="detail-row"><div class="detail-label">No. Rekening/VA</div><div class="detail-value">: {{ $memo->no_rekening ?? '-' }}</div></div>
        </div>

        {{-- Signatures --}}
        <div class="signatures-section">
            @php
                $progress = app(\App\Services\ApprovalWorkflowService::class)
                    ->getApprovalProgressForMemoPembayaran($memo);
            @endphp

            <table style="width:100%; text-align:center; border:0;">
                <tr>
                    {{-- Dibuat oleh (creator) selalu ditampilkan --}}
                    <td>
                        <div class="signature-title">Dibuat Oleh</div>
                        @if ($signatureSrc)
                            <img src="{{ $signatureSrc }}" class="signature-img" alt="Tanda Tangan">
                        @else
                            <div style="height:60px;"></div>
                        @endif
                        <div class="signature-name">{{ $memo->creator->name ?? '' }}</div>
                        <div class="signature-role">{{ $memo->creator->display_role ?? '-' }}</div>
                        <div class="signature-date">Tanggal: {{ $memo->created_at?->format('d-m-Y') }}</div>
                    </td>

                    {{-- Loop workflow steps (verifier/approver) --}}
                    @foreach ($progress as $step)
                        <td>
                            <div class="signature-title">
                                {{ $step['step'] === 'verified' ? 'Diperiksa Oleh' : 'Disetujui Oleh' }}
                            </div>

                            @if ($step['status'] === 'completed' && $approvedSrc)
                                <img src="{{ $approvedSrc }}" class="stamp-img" alt="Approved">
                            @else
                                <div style="height:60px;"></div>
                            @endif

                            <div class="signature-name">{{ $step['completed_by']['name'] ?? '' }}</div>
                            <div class="signature-role">{{ $step['role'] ?? '' }}</div>
                            <div class="signature-date">
                                Tanggal: {{ $step['completed_at'] ? \Carbon\Carbon::parse($step['completed_at'])->format('d-m-Y') : '' }}
                            </div>
                        </td>
                    @endforeach
                </tr>
            </table>
        </div>
    </div>
</body>
</html>

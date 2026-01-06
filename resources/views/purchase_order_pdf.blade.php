<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order</title>
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
            max-width: 90px;
            max-height: 90px;
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

        .note-section,
        .note-section .description-header,
        .note-section .specific-request {
            text-align: justify;
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
            margin: 0;
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
            width: 40px;
            text-align: center;
        }

        .items-table th:nth-child(2),
        .items-table td:nth-child(2) {
            max-width: 100px;   /* atur sesuai kebutuhan */
            white-space: normal;
            word-wrap: break-word;
        }

        /* Kolom 3: Bisnis Partner | No Rekening */
        .items-table th:nth-child(3),
        .items-table td:nth-child(3) {
            max-width: 140px;
            white-space: normal;
            word-wrap: break-word;
        }

        /* Kolom 4: Harga */
        .items-table th:nth-child(4),
        .items-table td:nth-child(4) {
            width: 90px;
            text-align: right;
        }

        /* Kolom 5: Qty */
        .items-table th:nth-child(5),
        .items-table td:nth-child(5) {
            width: 40px;
            text-align: center;
        }

        .items-table th:last-child,
        .items-table td:last-child {
            width: 120px;
            text-align: right;
            font-weight: bold;
            color: #111827;
        }

        /* Summary Section Styling */
        .summary-section {
            margin: 20px 0 20px 0;
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
            white-space: normal; /* boleh pecah baris */
            word-wrap: break-word;
        }

        .summary-table .summary-value {
            text-align: right;
            padding-right: 15px;
            font-weight: bold;
            color: #111827;
            width: 30%;
            font-size: 12px;
            white-space: nowrap; /* jangan pecah Rp dan nominal */
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

        .closing-remark {
            text-align: left;
            margin: 30px 0;
        }

        .signatures-section {
            margin-top: 70px;
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
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff; /* opsional, biar ada background putih */
        }

        .signature-stamp img {
            width: 100%;
            height: auto;      /* jaga proporsional */
            max-height: 100%;  /* biar gak keluar dari kotak */
            border-radius: 0;  /* gak perlu radius lagi di sini */
        }

        .signature-name {
            font-size: 11px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 3px;
        }

        .signature-role {
            font-size: 11px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 5px;
        }

        .signature-date {
            font-size: 9px;
            color: #6b7280;
            font-style: italic;
        }
        .signatures-section.kredit {
            display: block;
            width: 100%;
        }

        .signatures-section.kredit .signature-box {
            display: inline-block;
            text-align: center;
            width: 150px;
            vertical-align: top;
        }

        .signatures-section.kredit .signature-stamp {
            margin: 0 auto 10px;
            display: block;
        }

        .signatures-section.kredit .signature-title {
            text-align: center;
            margin-bottom: 15px;
        }

        .signatures-section.kredit .signature-name,
        .signatures-section.kredit .signature-role,
        .signatures-section.kredit .signature-date {
            text-align: center;
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
                <div class="company-address">Jl. Hasanudin No.9, Lebakgede, Kecamatan Coblong</div>
                <div class="company-address">Kota Bandung, Jawa Barat 40132</div>
                <div class="company-phone">0821-1399-9884</div>
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
                <div style="text-align: justify;" class="detail-value">: {{ $po->keterangan ?? $po->note ?? '-' }}</div>
            </div>
            @endif
        </div>

        <!-- Note Section -->
        <div class="note-section">
            <div class="description-header">Berikut rincian {{ $po->perihal->nama ?? '-' }} untuk keperluan {{ $po->department->name ?? '-' }}:</div>
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
                            {{-- <th>{{ strtolower($po->perihal->nama ?? '') === 'permintaan pembayaran jasa' ? 'Nama Jasa' : 'Nama Barang' }}</th> --}}
                            <th>Nama Item</th>
                            <th>Bisnis Partner</th>
                            <th>No Rekening</th>
                            <th>Harga</th>
                            <th>Qty</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($po->items && count($po->items) > 0)
                            @foreach($po->items as $i => $item)
                            @php
                                $bp = $item->bisnisPartner ?? null;
                                $bpName = $bp->nama_bp ?? $bp->nama_rekening ?? null;
                                $bpAccount = $bp->no_rekening_va ?? $bp->no_rekening ?? null;
                            @endphp
                            <tr>
                                <td>{{ $i+1 }}</td>
                                <td>{{ $item->nama ?? $item->nama_barang ?? '-' }}</td>
                                <td>
                                    {{ $bpName ?? '-' }}
                                </td>
                                <td>
                                    {{ $bpAccount ?? '-' }}
                                </td>
                                <td>Rp. {{ number_format($item->harga ?? 0, 0, ',', '.') }}</td>
                                <td>{{ $item->qty ?? '-' }}</td>
                                <td>Rp. {{ number_format(($item->qty ?? 0) * ($item->harga ?? 0), 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td>1</td>
                                <td>Ongkir JNE Ziglo - BKR</td>
                                <td>-</td>
                                <td>-</td>
                                <td>Rp. 100,000</td>
                                <td>1</td>
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
                    @if(!empty($po->dp_active) && ($po->dp_nominal ?? 0) > 0)
                    <tr>
                        <td class="summary-label">DP</td>
                        <td class="summary-value">Rp. {{ number_format($po->dp_nominal ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="grand-total-row">
                        <td class="summary-label">Grand Total</td>
                        <td class="summary-value">Rp. {{ number_format($grandTotal ?? 0, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Payment Method + Closing Remark + Signatures grouped to avoid page break splitting -->
        <div class="keep-together">
        <!-- Grouped tail to keep payment + signatures together -->
        <div class="keep-together">
        <!-- Payment Method Section - Dynamic based on payment method -->
        <div class="payment-section">
            <div class="payment-row">
                <div class="payment-label">Metode Pembayaran</div>
                <div class="payment-value">: {{ $po->metode_pembayaran ?? '-' }}</div>
            </div>

            @php
                $method = strtolower(trim($po->metode_pembayaran ?? ''));
            @endphp

            @if($method === '' || $method === 'transfer')
                <!-- Transfer payment fields -->
                @php
                    $bankName = data_get($po, 'bankSupplierAccount.bank.nama_bank')
                        ?? data_get($po, 'customerBank.nama_bank')
                        ?? data_get($po, 'bank.nama_bank');
                    $namaRekeningVal = data_get($po, 'bankSupplierAccount.nama_rekening')
                        ?? ($po->customer_nama_rekening ?? null)
                        ?? ($po->nama_rekening ?? null);
                    $noRekeningVal = data_get($po, 'bankSupplierAccount.no_rekening')
                        ?? ($po->customer_no_rekening ?? null)
                        ?? ($po->no_rekening ?? null);
                @endphp
                @if(!empty($bankName))
                <div class="payment-row">
                    <div class="payment-label">Nama Bank</div>
                    <div class="payment-value">: {{ $bankName }}</div>
                </div>
                @endif
                @if(!empty($namaRekeningVal))
                <div class="payment-row">
                    <div class="payment-label">Nama Rekening</div>
                    <div class="payment-value">: {{ $namaRekeningVal }}</div>
                </div>
                @endif
                @if(!empty($noRekeningVal))
                <div class="payment-row">
                    <div class="payment-label">No. Rekening/VA</div>
                    <div class="payment-value">: {{ $noRekeningVal }}</div>
                </div>
                @endif
            @elseif($method === 'cek/giro' || $method === 'cek' || $method === 'giro')
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
            @elseif($method === 'kredit' || $method === 'credit')
                <!-- Kredit payment fields -->
                @if(!empty(optional($po->creditCard)->nama_pemilik))
                <div class="payment-row">
                    <div class="payment-label">Nama Pemilik</div>
                    <div class="payment-value">: {{ optional($po->creditCard)->nama_pemilik }}</div>
                </div>
                @endif
                @if(!empty(optional($po->creditCard)->no_kartu_kredit))
                <div class="payment-row">
                    <div class="payment-label">No. Kartu Kredit</div>
                    <div class="payment-value">: {{ optional($po->creditCard)->no_kartu_kredit }}</div>
                </div>
                @endif
            @endif

            <!-- Additional payment info for Lainnya type -->
            @if($po->tipe_po === 'Lainnya')
                @if(!empty($po->termin_id) && !empty($po->termin))
                <div class="payment-row">
                    <div class="payment-label">No Ref Termin</div>
                    <div class="payment-value">: {{ $po->termin->no_referensi ?? '-' }}</div>
                </div>
                <div class="payment-row">
                    <div class="payment-label">Termin Ke</div>
                    <div class="payment-value">: {{ $po->termin ?? '-' }}</div>
                </div>
                @endif
                @php
                    $cicilanValue = $po->cicilan ?? ($cicilan ?? 0);
                @endphp
                @if(!empty($cicilanValue) && $cicilanValue > 0)
                <div class="payment-row">
                    <div class="payment-label">Cicilan</div>
                    <div class="payment-value">: Rp. {{ number_format($cicilanValue, 0, ',', '.') }}</div>
                </div>
                @endif
            @endif
        </div>

        <!-- Closing Remark -->
        <div class="closing-remark">Terima kasih atas perhatian dan kerjasamanya.</div>

        <!-- Signatures (dynamic based on workflow progress) -->
        @php
            $method = strtolower(trim($po->metode_pembayaran ?? ''));
        @endphp
        @if(!in_array($method, ['kredit', 'credit']))
        @php
            $progress = app(\App\Services\ApprovalWorkflowService::class)->getApprovalProgress($po);
            $signatureBoxes = [];

            // Department dokumen (bukan user)
            $docDeptName = optional($po->department)->name ?? '';
            $isBrandMgrDept = in_array($docDeptName, ['Human Greatness', 'Zi&Glo']);

            // 1. Dibuat Oleh (always)
            $creatorName = optional($po->creator)->name ?? '';
            $creatorRole = optional(optional($po->creator)->role)->name ?? '-';

            $displayRole = $creatorRole;
            if ($creatorRole === 'Kepala Toko' && $isBrandMgrDept) {
                $displayRole = 'Brand Manager';
            }

            $signatureBoxes[] = [
                'title' => 'Dibuat Oleh',
                'stamp' => $signatureSrc ?? null,
                'name' => $creatorName,
                'role' => $displayRole,
                'date' => $po->created_at ? \Carbon\Carbon::parse($po->created_at)->format('d-m-Y') : '',
            ];

            // 2+. Based on workflow steps
            $labelMap = [
                'verified' => 'Diverifikasi Oleh',
                'validated' => 'Divalidasi Oleh',
                'approved' => 'Disetujui Oleh',
            ];

            foreach ($progress as $step) {
                $title = $labelMap[$step['step']] ?? ucfirst($step['step']);
                $stepName = $step['completed_by']['name'] ?? '';
                $stepRole = $step['role'] ?? '-';

                // Transform role berdasarkan department dokumen
                $displayRole = $stepRole;
                if ($stepRole === 'Kepala Toko' && $isBrandMgrDept) {
                    $displayRole = 'Brand Manager';
                }

                $signatureBoxes[] = [
                    'title' => $title,
                    'stamp' => ($step['status'] === 'completed' && !empty($approvedSrc)) ? $approvedSrc : null,
                    'name' => $stepName,
                    'role' => $displayRole,
                    'date' => !empty($step['completed_at']) ? \Carbon\Carbon::parse($step['completed_at'])->format('d-m-Y') : '',
                ];
            }
        @endphp

        <div class="signatures-section">
            @foreach ($signatureBoxes as $box)
                <div class="signature-box">
                    <div class="signature-title">{{ $box['title'] }}</div>
                    <div class="signature-stamp">
                        @if (!empty($box['stamp']))
                            <img src="{{ $box['stamp'] }}" alt="Stamp" />
                        @endif
                    </div>
                    <div class="signature-name">{{ $box['name'] }}</div>
                    <div class="signature-role">{{ $box['role'] }}</div>
                    <div class="signature-date">{{ $box['date'] ?  $box['date'] : '' }}</div>
                </div>
            @endforeach
        </div>
        @else
        <!-- Kredit: show only creator signature on the left -->
        <div class="signatures-section kredit">
            <div class="signature-box">
                <div class="signature-title">Dibuat Oleh</div>
                <div class="signature-stamp">
                    @if (!empty($signatureSrc))
                        <img src="{{ $signatureSrc }}" alt="Signature Stamp" />
                    @endif
                </div>
                @php
                    $docDeptName = optional($po->department)->name ?? '';
                    $isBrandMgrDept = in_array($docDeptName, ['Human Greatness', 'Zi&Glo']);

                    $creatorName = optional($po->creator)->name ?? '';
                    $creatorRole = optional(optional($po->creator)->role)->name ?? '-';

                    $displayRole = $creatorRole;
                    if ($creatorRole === 'Kepala Toko' && $isBrandMgrDept) {
                        $displayRole = 'Brand Manager';
                    }
                @endphp
                <div class="signature-name">{{ $creatorName }}</div>
                <div class="signature-role">{{ $displayRole }}</div>
                <div class="signature-date">{{ $po->created_at ? \Carbon\Carbon::parse($po->created_at)->format('d-m-Y') : '' }}</div>
            </div>
        </div>
        @endif
        </div>
        </div>
    </div>
</body>
</html>





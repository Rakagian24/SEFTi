<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Voucher</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            background: white;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            padding: 0;
        }

        /* Header Section */
        .header {
            width: 100%;
            margin-bottom: 30px;
        }

        .header-table {
            border-collapse: collapse;
            width: 100%;
        }

        .header-left {
            width: 35%;
            text-align: left;
            padding-right: 15px;
            vertical-align: middle;
        }

        .header-divider {
            width: 0.1px;
            background-color: #D6DCE5;
            height: 70px;
        }

        .header-right {
            width: 35%;
            text-align: left;
            padding-left: 15px;
            vertical-align: middle;
        }

        .header-logo {
            width: 20%;
            text-align: center;
            vertical-align: middle;
        }

        .header-title {
            font-size: 22px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 3px;
        }

        .header-sub {
            font-size: 10px;
            color: #64748b;
        }

        .meta-label {
            font-size: 10px;
            color: #94a3b8;
        }

        .meta-value {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
        }

        .logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
        }

        /* Info Grid Section */
        .info-grid {
            width: 100%;
            display: table;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin-bottom: 20px;
        }

        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            background: #fafcfe;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px 18px;
            box-sizing: border-box;
        }

        .info-row {
            margin-bottom: 8px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-row .label {
            color: #64748b;
            font-size: 9px;
            display: block;
            margin-bottom: 2px;
        }

        .info-row .value {
            color: #1e293b;
            font-weight: 600;
            font-size: 10px;
            display: block;
        }

        /* Table Section */
        .table-wrapper {
            width: 100%;
            margin-top: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        table.items-table thead {
            background: #fafcfe;
        }

        table.items-table th {
            padding: 10px 8px;
            text-align: left;
            color: #64748b;
            font-weight: bold;
            font-size: 9px;
            border-bottom: 1px solid #e2e8f0;
        }

        table.items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
            font-size: 10px;
        }

        table.items-table tbody tr:last-child td {
            border-bottom: none;
        }

        table.items-table .text-right {
            text-align: right;
        }

        /* Summary Section */
        .summary {
            width: calc(100% - 30px);
            margin: 0 auto;
            background: #fafcfe;
            padding: 12px 15px;
            border-top: 1px solid #e2e8f0;
            box-sizing: border-box;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 3px 0;
            font-size: 10px;
        }

        .summary-table td:first-child {
            width: 50%;
        }

        .summary-table td:nth-child(2) {
            text-align: right;
            color: #64748b;
            width: 25%;
            padding-right: 15px;
        }

        .summary-table td:last-child {
            text-align: right;
            color: #1e293b;
            font-weight: bold;
            width: 25%;
        }

        .summary-table tr.total-row td {
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            padding-bottom: 8px;
            font-weight: bold;
            font-size: 11px;
            color: #0f172a;
        }

        .summary-table tr.spacer-row td {
            padding: 6px 0;
            border: none;
        }

        /* Termin Info Section */
        .termin-info {
            width: 100%;
            margin-top: 20px;
            background: #fafcfe;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px 18px;
            box-sizing: border-box;
        }

        .termin-info-title {
            font-size: 11px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }

        .termin-info-grid {
            width: 100%;
            display: table;
            table-layout: fixed;
        }

        .termin-info-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }

        .termin-info-col:last-child {
            padding-right: 0;
            padding-left: 15px;
        }

        .termin-info-row {
            margin-bottom: 8px;
        }

        .termin-info-row:last-child {
            margin-bottom: 0;
        }

        .termin-info-row .label {
            color: #64748b;
            font-size: 9px;
            display: block;
            margin-bottom: 2px;
        }

        .termin-info-row .value {
            color: #1e293b;
            font-weight: 600;
            font-size: 10px;
            display: block;
        }

        /* Signature Section (match PO layout) */
        .signatures {
            margin-top: 40px;
            width: 100%;
        }

        .signatures-section {
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
    </style>
</head>

<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <table class="header-table" width="100%">
                <tr>
                    <!-- Left: Title -->
                    <td class="header-left">
                        <div class="header-title">Payment Voucher</div>
                        <div class="header-sub">{{ $pv->no_pv ?? 'Draft' }}</div>
                    </td>

                    <!-- Middle: Vertical line -->
                    <td class="header-divider"></td>

                    <!-- Right: Meta Info -->
                    <td class="header-right">
                        <table class="header-meta-table" width="100%">
                            <tr>
                                <td>
                                    <div class="meta-label">
                                        {{ $refLabel ?? ($isMemo ? 'No. Referensi Memo' : 'No. Referensi PO') }}:</div>
                                    <div class="meta-value">{{ $refNo }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="meta-label">No. Bank Keluar:</div>
                                    <div class="meta-value">{{ $pv->no_bk ?? '-' }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="meta-label">Tanggal:</div>
                                    <div class="meta-value">
                                        {{ $pv->tanggal ? \Carbon\Carbon::parse($pv->tanggal)->locale('id')->translatedFormat('d F Y') : '-' }}
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>

                    <!-- Logo -->
                    <td class="header-logo">
                        <img src="{{ $logoSrc }}" class="logo" alt="Logo">
                    </td>
                </tr>
            </table>
        </div>

        <!-- Info Grid Section -->
        <div class="info-grid clearfix">
            <div class="info-box">
                <div class="info-row">
                    <span class="label">Tanggal:</span>
                    <span
                        class="value">{{ $pv->tanggal ? \Carbon\Carbon::parse($pv->tanggal)->format('d-m-Y') : '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Bayar Kepada:</span>
                    @php
                        $isAnggaran = isset($isAnggaran) ? $isAnggaran : strtolower($pv->tipe_pv ?? '') === 'anggaran';
                        $poa = $isAnggaran ? $pv->poAnggaran : null;
                        $bp = $isAnggaran ? $pv->bisnisPartner ?? $poa?->bisnisPartner : null;

                        $supplierName = $isAnggaran
                            ? $bp->nama_bp ?? ($pv->manual_supplier ?? '-')
                            : $pv->supplier?->nama_supplier ??
                                ($pv->purchaseOrder?->supplier?->nama_supplier ??
                                    ($pv->memoPembayaran?->supplier?->nama_supplier ?? ($pv->manual_supplier ?? '-')));

                        $supplierPhone = $isAnggaran
                            ? $bp->no_telepon ?? ($pv->manual_no_telepon ?? '-')
                            : $pv->supplier?->no_telepon ??
                                ($pv->purchaseOrder?->supplier?->no_telepon ??
                                    ($pv->memoPembayaran?->supplier?->no_telepon ?? ($pv->manual_no_telepon ?? '-')));

                        $supplierAddress = $isAnggaran
                            ? $bp->alamat ?? ($pv->manual_alamat ?? '-')
                            : $pv->supplier?->alamat ??
                                ($pv->purchaseOrder?->supplier?->alamat ??
                                    ($pv->memoPembayaran?->supplier?->alamat ?? ($pv->manual_alamat ?? '-')));
                    @endphp
                    <span class="value">{{ $supplierName }}</span>
                </div>
                <div class="info-row">
                    <span class="label">No. Telp:</span>
                    <span class="value">{{ $supplierPhone }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Alamat:</span>
                    <span class="value">{{ $supplierAddress }}</span>
                </div>
            </div>

            <div class="info-box">
                @php
                    $metode =
                        $pv->metode_bayar ??
                        (($isAnggaran ? $pv->poAnggaran?->metode_pembayaran ?? null : null) ??
                            ($pv->purchaseOrder?->metode_pembayaran ?? $pv->memoPembayaran?->metode_pembayaran));

                    $bankName =
                        $pv->bankSupplierAccount?->bank?->nama_bank ??
                        (($isAnggaran
                            ? $bp?->bank?->nama_bank ?? ($pv->poAnggaran?->bank?->nama_bank ?? ($bp->nama_bank ?? null))
                            : null) ??
                            ($pv->purchaseOrder?->bankSupplierAccount?->bank?->nama_bank ??
                                ($pv->memoPembayaran?->bankSupplierAccount?->bank?->nama_bank ??
                                    ($pv->manual_nama_bank ?? '-'))));

                    $noGiro = $pv->no_giro ?? ($pv->purchaseOrder?->no_giro ?? $pv->memoPembayaran?->no_giro);
                    $tglGiro =
                        $pv->tanggal_giro ?? ($pv->purchaseOrder?->tanggal_giro ?? $pv->memoPembayaran?->tanggal_giro);
                    $tglCair =
                        $pv->tanggal_cair ?? ($pv->purchaseOrder?->tanggal_cair ?? $pv->memoPembayaran?->tanggal_cair);
                    $mataUang = $pv->currency ?? 'Rupiah';

                    $namaRekening =
                        $pv->bankSupplierAccount?->nama_rekening ??
                        (($isAnggaran ? $bp->nama_rekening ?? ($pv->poAnggaran?->nama_rekening ?? null) : null) ??
                            ($pv->purchaseOrder?->bankSupplierAccount?->nama_rekening ??
                                ($pv->memoPembayaran?->bankSupplierAccount?->nama_rekening ??
                                    $pv->manual_nama_pemilik_rekening)));

                    $noRekening =
                        $pv->bankSupplierAccount?->no_rekening ??
                        (($isAnggaran ? $bp->no_rekening_va ?? ($pv->poAnggaran?->no_rekening ?? null) : null) ??
                            ($pv->purchaseOrder?->bankSupplierAccount?->no_rekening ??
                                ($pv->memoPembayaran?->bankSupplierAccount?->no_rekening ?? $pv->manual_no_rekening)));

                    $noKartu =
                        $pv->creditCard?->no_kartu_kredit ??
                        ($pv->purchaseOrder?->creditCard?->no_kartu_kredit ?? null);
                @endphp
                <div class="info-row">
                    <span class="label">Bank:</span>
                    <span class="value">{{ $bankName }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Mata Uang:</span>
                    <span class="value">{{ $mataUang }}</span>
                </div>
                @if ($metode === 'Cek/Giro')
                    <div class="info-row">
                        <span class="label">No. Cek/Giro:</span>
                        <span class="value">{{ $noGiro ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Tanggal Giro:</span>
                        <span
                            class="value">{{ $tglGiro ? \Carbon\Carbon::parse($tglGiro)->locale('id')->translatedFormat('d F Y') : '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Tanggal Cair:</span>
                        <span
                            class="value">{{ $tglCair ? \Carbon\Carbon::parse($tglCair)->locale('id')->translatedFormat('d F Y') : '-' }}</span>
                    </div>
                @elseif($metode === 'Kartu Kredit')
                    <div class="info-row">
                        <span class="label">No. Kartu Kredit:</span>
                        <span class="value">{{ $noKartu ?? '-' }}</span>
                    </div>
                @else
                    <div class="info-row">
                        <span class="label">Nama Rekening:</span>
                        <span class="value">{{ $namaRekening ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">No. Rekening/VA:</span>
                        <span class="value">{{ $noRekening ?? '-' }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Items Table -->
        <div class="table-wrapper">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Referensi</th>
                        <th>Tanggal</th>
                        <th>Nama Barang / Keperluan</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">PPN</th>
                        <th class="text-right">Harga / Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $memo = $pv->memoPembayaran;
                        $isMemo = isset($isMemo)
                            ? $isMemo
                            : strtolower($pv->tipe_pv ?? '') === 'lainnya' && !empty($pv->memo_pembayaran_id);
                        $isAnggaran = isset($isAnggaran) ? $isAnggaran : strtolower($pv->tipe_pv ?? '') === 'anggaran';
                        $po = $pv->purchaseOrder ?: ($isMemo ? $memo?->purchaseOrder : null);
                        $poa = $isAnggaran ? $pv->poAnggaran : null;
                    @endphp

                    @if ($isAnggaran && $poa)
                        @if ($poa->items && count($poa->items) > 0)
                            @foreach ($poa->items as $item)
                                <tr>
                                    <td>{{ $poa?->perihal?->nama ?? 'Biaya Anggaran' }}</td>
                                    <td>{{ $poa?->tanggal ? \Carbon\Carbon::parse($poa->tanggal)->format('d-m-Y') : '-' }}
                                    </td>
                                    <td>{{ $item->jenis_pengeluaran_text ?? ($item->keterangan ?? ($poa->detail_keperluan ?? '-')) }}</td>
                                    <td class="text-right">
                                        {{ (float) $item->qty == (int) $item->qty
                                            ? number_format((int) $item->qty, 0, ',', '.')
                                            : rtrim(rtrim(number_format((float) $item->qty, 4, ',', '.'), '0'), ',') }}
                                    </td>

                                    <td class="text-right">-</td>
                                    <td class="text-right">Rp.
                                        {{ number_format(($item->qty ?? 0) * ($item->harga ?? 0), 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td>{{ $poa?->perihal?->nama ?? 'Biaya Anggaran' }}</td>
                                <td>{{ $poa?->tanggal ? \Carbon\Carbon::parse($poa->tanggal)->format('d-m-Y') : '-' }}
                                </td>
                                <td>{{ $poa?->detail_keperluan ?? '-' }}</td>
                                <td class="text-right">-</td>
                                <td class="text-right">-</td>
                                <td class="text-right">Rp.
                                    {{ number_format($poa?->nominal ?? ($pv->nominal ?? 0), 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    @elseif($po && ($po->items && count($po->items) > 0))
                        @foreach ($po->items as $item)
                            <tr>
                                <td>{{ $po?->perihal?->nama ?? 'Pembelian / Biaya' }}</td>
                                <td>{{ $po?->tanggal ? \Carbon\Carbon::parse($po->tanggal)->format('d-m-Y') : '-' }}
                                </td>
                                <td>{{ $item->nama ?? ($item->nama_barang ?? '-') }}</td>
                                <td class="text-right">{{ $item->qty ?? '-' }}</td>
                                <td class="text-right">{{ $po?->ppn ?? false ? '11%' : '-' }}</td>
                                <td class="text-right">Rp.
                                    {{ number_format(($item->qty ?? 0) * ($item->harga ?? 0), 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td>{{ $po?->perihal?->nama ?? 'Pembelian / Biaya' }}</td>
                            <td>{{ $po?->tanggal ? \Carbon\Carbon::parse($po->tanggal)->format('d-m-Y') : '-' }}</td>
                            <td>{{ $po?->keterangan ?? '-' }}</td>
                            <td class="text-right">-</td>
                            <td class="text-right">{{ $po?->ppn ?? false ? '11%' : '-' }}</td>
                            <td class="text-right">Rp. {{ number_format($po?->total ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>

            <!-- Summary Section -->
            @php
                $calcTotal = $total ?? ($po->total ?? ($memo->total ?? 0));
                $calcDiskon = $diskon ?? ($po->diskon ?? 0);
                $calcPpn = $ppn ?? ($po->ppn_nominal ?? 0);
                $calcPph = $pph ?? ($po->pph_nominal ?? 0);
                $calcGrand =
                    $grandTotal ?? ($po->grand_total ?? max($calcTotal - $calcDiskon + $calcPpn + $calcPph, 0));
            @endphp
            <div class="summary">
                <table class="summary-table">
                    <tr>
                        <td></td>
                        <td>Total</td>
                        <td>Rp. {{ number_format($calcTotal + ($calcPpn ?? 0), 0, ',', '.') }}</td>
                    </tr>
                    @if (($calcDiskon ?? 0) > 0)
                        <tr>
                            <td></td>
                            <td>Diskon</td>
                            <td>Rp. {{ number_format($calcDiskon, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if (($calcPph ?? 0) > 0)
                        <tr>
                            <td></td>
                            <td>PPH {{ isset($pphPersen) && $pphPersen ? $pphPersen . '%' : '' }}</td>
                            <td>Rp. {{ number_format($calcPph, 0, ',', '.') }}</td>
                        </tr>
                    @endif

                    @if (!empty($dpSummary) && is_array($dpSummary))
                        {{-- <tr class="">
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr> --}}
                        <tr>
                            <td></td>
                            <td>Total DP</td>
                            <td>Rp. {{ number_format((float) ($dpSummary['dp_nominal'] ?? 0), 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="spacer-row">
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr class="total-row">
                        <td></td>
                        <td>Grand Total</td>
                        <td>Rp. {{ number_format($calcGrand, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Termin Info Section (below items table) -->
        @php $isLainnya = isset($pv) && strtolower($pv->tipe_pv ?? '') === 'lainnya'; @endphp
        @if (($isLainnya || ($isMemo ?? false)) && !empty($terminData))
            <div class="termin-info">
                <div class="termin-info-title">Informasi Termin Pembayaran</div>
                <div class="termin-info-grid">
                    <div class="termin-info-col">
                        <div class="termin-info-row">
                            <span class="label">No. Referensi:</span>
                            <span class="value">{{ $terminData['no_referensi'] ?? '-' }}</span>
                        </div>
                        <div class="termin-info-row">
                            <span class="label">Termin Ke:</span>
                            <span class="value">
                                {{ $terminData['termin_no'] ?? '-' }}
                                @if (!empty($terminData['jumlah_termin']))
                                    dari {{ $terminData['jumlah_termin'] }}
                                @endif
                            </span>
                        </div>
                        <div class="termin-info-row">
                            <span class="label">Nominal Cicilan:</span>
                            <span class="value">Rp.
                                {{ number_format((float) ($terminData['nominal_cicilan'] ?? 0), 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="termin-info-col">
                        @php $jc = $terminData['jumlah_cicilan'] ?? null; @endphp
                        @if ($jc !== null)
                            <div class="termin-info-row">
                                <span class="label">Jumlah Cicilan:</span>
                                <span class="value">Rp. {{ number_format((float) $jc, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        @php $tc = $terminData['total_cicilan'] ?? null; @endphp
                        @if ($tc !== null)
                            <div class="termin-info-row">
                                <span class="label">Total Cicilan:</span>
                                <span class="value">Rp. {{ number_format((float) $tc, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        @php $sisa = $terminData['sisa_pembayaran'] ?? null; @endphp
                        @if ($sisa !== null)
                            <div class="termin-info-row">
                                <span class="label">Sisa Pembayaran:</span>
                                <span class="value">Rp. {{ number_format((float) $sisa, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Signature Section -->
        @php
            $progress = app(\App\Services\ApprovalWorkflowService::class)->getApprovalProgressForPaymentVoucher($pv);
            $signatureBoxes = [];
            $signatureBoxes[] = [
                'title' => 'Dibuat Oleh',
                'stamp' => !empty($pv->created_at) && !empty($signatureSrc) ? $signatureSrc : null,
                'name' => optional($pv->creator)->name ?? '',
                'role' => optional(optional($pv->creator)->role)->name ?? '-',
                'date' => $pv->created_at ? \Carbon\Carbon::parse($pv->created_at)->format('d-m-Y') : '',
            ];
            $labelMapPv = ['verified' => 'Diperiksa Oleh', 'approved' => 'Disetujui Oleh'];
            foreach ($progress as $step) {
                $title = $labelMapPv[$step['step']] ?? ucfirst($step['step']);
                $signatureBoxes[] = [
                    'title' => $title,
                    'stamp' => $step['status'] === 'completed' && !empty($approvedSrc) ? $approvedSrc : null,
                    'name' => $step['completed_by']['name'] ?? '',
                    'role' => $step['role'] ?? '-',
                    'date' => !empty($step['completed_at'])
                        ? \Carbon\Carbon::parse($step['completed_at'])->format('d-m-Y')
                        : '',
                ];
            }
            $signatureBoxes = array_slice($signatureBoxes, 0, 4);
            $colCount = max(count($signatureBoxes), 1);
        @endphp
        <div class="signatures">
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
                        <div class="signature-date">{{ $box['date'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</body>

</html>

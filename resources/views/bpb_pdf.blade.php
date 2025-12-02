<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>BPB</title>
<style>
@page { size: A4 portrait; margin: 40px 50px; }

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    color: #000;
    background: #fff;
    margin: 0;
    padding: 0;
}

/* ===== HEADER ===== */
.header {
    border-bottom: 1px solid #ccc;
    padding-bottom: 10px;
    margin-bottom: 25px;
}
.header-table {
    width: 100%;
    border-collapse: collapse;
}
.header-table td {
    vertical-align: middle;
    text-align: center;
}
.header-logo {
    width: 100px;
    text-align: center;
}
.header-logo img {
    height: 80px;
    width: auto;
}
.header-text {
    text-align: center;
}
.title {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 15px;
}
.company {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 15px;
}
.doc-no {
    font-size: 12px;
    color: #555;
}

/* ===== INFO SECTION ===== */
.info-section {
    margin-top: 50px;
    margin-bottom: 50px;
}
.info-title {
    font-weight: bold;
    margin-bottom: 20px;
}
.info-content {
    line-height: 1.5;
    margin-bottom: 10px;
}

.info-content div {
    line-height: 1.5;
}

.supplier-name {
    margin-bottom: 15px; /* jarak ke alamat */
}

.supplier-address {
    margin-bottom: 5px; /* jarak ke TELP */
}

.supplier-phone {
    margin-bottom: 50px;
}

.info-grid {
    width: 100%;
    border-collapse: collapse;
}
.info-grid td {
    width: 50%;
    vertical-align: top;
    padding-right: 10px;
}
.label {
    font-weight: bold;
    display: inline-block;
    min-width: 80px;
}
.value {
    color: #333;
}

/* ===== TABLE ITEMS ===== */
.table-wrapper {
    margin-top: 70px;
}
.items {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #000;
}
.items th, .items td {
    border: 1px solid #000;
    padding: 6px 5px;
    text-align: center;
    font-size: 11px;
}
.items th {
    background: #f5f5f5;
    font-weight: bold;
}
.text-left { text-align: left; }
.text-right { text-align: right; }

/* ===== SUMMARY ===== */
.summary {
    text-align: right;
    margin-top: 10px;
    font-weight: bold;
    font-size: 12px;
    padding-right: 15px;
}

/* ===== NOTE ===== */
.note {
    margin-top: 50px;
}
.note .label {
    font-weight: bold;
    margin-bottom: 5px;
}

/* ===== SIGNATURE (match PO layout) ===== */
.signatures {
    margin-top: 40px;
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
    <!-- HEADER -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-logo">
                    @if(!empty($logoSrc))
                        <img src="{{ $logoSrc }}" alt="Logo" />
                    @endif
                </td>
                <td class="header-text">
                    <div class="title">Bukti Penerimaan Barang</div>
                    <div class="company">PT. Singa Global Tekstil</div>
                    <div class="doc-no">{{ $bpb->no_bpb ?? 'Draft' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- INFO -->
    <div class="info-section">
        <div class="info-title">Telah Terima Dari :</div>
        <div class="info-content">
            <div class="supplier-name"><strong>{{ $bpb->supplier->nama_supplier ?? '-' }}</strong></div>
            <div class="supplier-address">{{ $bpb->supplier->alamat ?? '-' }}</div>
            <div class="supplier-phone">TELP {{ $bpb->supplier->no_telepon ?? '-' }}</div>
        </div>
        <table class="info-grid">
            <tr>
                <td>
                    <div style="margin-bottom: 15px"><span class="label">Tanggal :</span> <span class="value">{{ $bpb->tanggal ? \Carbon\Carbon::parse($bpb->tanggal)->format('d/m/Y') : '-' }}</span></div>
                    <div><span class="label">No. PO :</span> <span class="value">{{ $bpb->purchaseOrder->no_po ?? '-' }}</span></div>
                </td>
                <td>
                    <div style="margin-bottom: 15px"><span class="label">No. PV :</span> <span class="value">{{ $bpb->paymentVoucher->no_pv ?? '-' }}</span></div>
                    <div><span class="label">No. BPB :</span> <span class="value">{{ $bpb->no_bpb ?? '-' }}</span></div>
                    <div><span class="label">No. SJ :</span> <span class="value">{{ $bpb->surat_jalan_no ?? '-' }}</span></div>
                </td>
            </tr>
        </table>
    </div>

    <!-- TABEL ITEM -->
    <div class="table-wrapper">
        <table class="items">
            <thead>
                <tr>
                    <th style="width:40px;">No</th>
                    <th style="width:45%;">Nama Barang</th>
                    <th style="width:70px;">Qty</th>
                    <th style="width:80px;">Satuan</th>
                    <th style="width:100px;">Harga</th>
                </tr>
            </thead>
            <tbody>
                @if($bpb->items && count($bpb->items) > 0)
                    @foreach($bpb->items as $i => $it)
                    <tr>
                        <td>{{ $i+1 }}</td>
                        <td class="text-left">{{ $it->nama_barang ?? '-' }}</td>
                        <td>{{ number_format((float)($it->qty ?? 0), 0, ',', '.') }}</td>
                        <td>{{ $it->satuan ?? '-' }}</td>
                        <td class="text-right">Rp. {{ number_format((float)(($it->qty ?? 0) * ($it->harga ?? 0)), 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr><td colspan="5">Tidak ada data</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- SUMMARY -->
    <div class="summary">
        Total&nbsp;&nbsp;&nbsp; Rp. {{ number_format($grandTotal ?? 0, 0, ',', '.') }}
    </div>

    <!-- NOTE -->
    <div class="note">
        <div class="label">Catatan :</div>
        <div class="value">{{ $bpb->keterangan ?? '-' }}</div>
    </div>

    <!-- SIGNATURES -->
    @php
        $progress = app(\App\Services\ApprovalWorkflowService::class)->getApprovalProgressForBpb($bpb);
        $signatureBoxes = [];

        // Department dokumen BPB
        $docDeptName = optional($bpb->department)->name ?? '';
        $isBrandMgrDept = in_array($docDeptName, ['Human Greatness', 'Zi&Glo']);

        // Dibuat Oleh
        $creatorRole = optional(optional($bpb->creator)->role)->name ?? '-';
        if ($creatorRole === 'Kepala Toko' && $isBrandMgrDept) {
            $creatorRole = 'Brand Manager';
        }

        $signatureBoxes[] = [
            'title' => 'Dibuat Oleh',
            'stamp' => (!empty($bpb->created_at) && !empty($signatureSrc)) ? $signatureSrc : null,
            'name' => optional($bpb->creator)->name ?? '',
            'role' => $creatorRole,
            'date' => $bpb->created_at ? \Carbon\Carbon::parse($bpb->created_at)->format('d-m-Y') : '',
        ];

        // Diperiksa Oleh (approval)
        foreach ($progress as $step) {
            $displayRole = $step['role'] ?? '-';
            if ($displayRole === 'Kepala Toko' && $isBrandMgrDept) {
                $displayRole = 'Brand Manager';
            }

            $signatureBoxes[] = [
                'title' => 'Diperiksa Oleh',
                'stamp' => ($step['status'] === 'completed' && !empty($approvedSrc)) ? $approvedSrc : null,
                'name' => $step['completed_by']['name'] ?? '',
                'role' => $displayRole,
                'date' => !empty($step['completed_at']) ? \Carbon\Carbon::parse($step['completed_at'])->format('d-m-Y') : '',
            ];
        }

        $signatureBoxes = array_slice($signatureBoxes, 0, 2);
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

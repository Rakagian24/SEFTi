<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Pembayaran Payment Voucher</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm 20mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
            margin: 0;
            padding: 0;
            background: #f3f4f6;
        }

        .page {
            width: 100%;
            box-sizing: border-box;
        }

        .card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 32px;
        }

        .header {
            text-align: center;
            margin-bottom: 32px;
            position: relative;
            padding-top: 10px;
        }

        .logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 80px;
            height: 80px;
        }

        .company-name {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
            margin-top: 0;
        }

        .document-title {
            font-size: 16px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 8px;
        }

        .document-date {
            font-size: 12px;
            color: #6b7280;
        }

        .section-title {
            font-size: 12px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 12px;
            margin-top: 24px;
        }

        .table-wrapper {
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 9px;
        }

        thead {
            background: #f9fafb;
        }

        th,
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: middle;
        }

        th {
            font-weight: 600;
            color: #6b7280;
            font-size: 9px;
        }

        tbody tr {
            background: #ffffff;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .grand-total-row td {
            font-weight: 700;
            border-top: 2px solid #e5e7eb;
            background: #ffffff;
            padding: 12px;
        }

        .no-data {
            text-align: center;
            padding: 24px;
            color: #9ca3af;
        }

        /* Column widths */
        .col-no { width: 3%; }
        .col-supplier { width: 14%; }
        .col-division { width: 7%; }
        .col-date { width: 9%; }
        .col-pv { width: 13%; }
        .col-nominal { width: 11%; }
        .col-desc { width: 20%; }
        .col-release { width: 11%; }
        .col-status { width: 12%; }
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <div class="header">
                <img src="{{ public_path('images/company-logo.png') }}" alt="Logo" class="logo">
                <div class="company-name">PT. Singa Global Tekstil</div>
                <div class="document-title">Daftar Pembayaran Payment Voucher</div>
                <div class="document-date">{{ $period }}</div>
            </div>

            <div class="section-title">E-BANKING</div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th class="col-no text-center">No</th>
                            <th class="col-supplier">Nama Supplier</th>
                            <th class="col-division">Divisi</th>
                            <th class="col-date">Tanggal PV</th>
                            <th class="col-pv">No. PV</th>
                            <th class="col-nominal text-right">Nominal<br>E-Banking</th>
                            <th class="col-desc">Keterangan</th>
                            <th class="col-release">Tanggal Release</th>
                            <th class="col-status">Status Dokumen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php($grand = 0)
                        @forelse($rows as $index => $r)
                            @php($grand += (float)($r['nominal'] ?? 0))
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $r['supplier'] ?? '-' }}</td>
                                <td>{{ $r['department'] ?? '-' }}</td>
                                <td>{{ $r['tanggal'] ?? '-' }}</td>
                                <td>{{ $r['no_pv'] ?? '-' }}</td>
                                <td class="text-right">{{ 'Rp. ' . number_format((float)($r['nominal'] ?? 0), 0, ',', '.') }}</td>
                                <td>{{ $r['keterangan'] ?? 'Lorem ipsum is simply dummy text of the printing' }}</td>
                                <td>{{ $r['tanggal_release'] ?? $r['tanggal'] ?? '-' }}</td>
                                <td>{{ $r['status_dokumen'] ?? 'Lengkap' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="no-data">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="grand-total-row">
                            <td colspan="5"><strong>Grand Total</strong></td>
                            <td class="text-right"><strong>{{ 'Rp. ' . number_format($grand, 0, ',', '.') }}</strong></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

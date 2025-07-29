<?php

namespace App\Http\Controllers;

use App\Models\BankMasuk;
use App\Models\Kwitansi;
use App\Models\AutoMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class BankMatchingController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Ambil data kwitansi dari database gjtrading3
        $kwitansiList = Kwitansi::byDateRange($startDate, $endDate)
            ->unmatched()
            ->orderBy('TANGGAL')
            ->orderBy('KWITANSI_ID')
            ->get();

        // Ambil data bank masuk dari database sefti
        $bankMasukList = BankMasuk::where('status', 'aktif')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal')
            ->orderBy('created_at')
            ->get();

        // Lakukan matching berdasarkan rules
        $matchingResults = $this->performBankMatching($kwitansiList, $bankMasukList);

        return Inertia::render('bank-matching/Index', [
            'matchingResults' => $matchingResults,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    private function performBankMatching($kwitansiList, $bankMasukList)
    {
        $results = [];
        $usedKwitansiIds = [];
        $usedBankMasukIds = [];

        // Group kwitansi dan bank masuk berdasarkan tanggal dan nilai
        $kwitansiByDateValue = [];
        $bankMasukByDateValue = [];

        foreach ($kwitansiList as $kwitansi) {
            $tanggal = $kwitansi->getTanggalValue();
            $tanggalFormatted = $tanggal ? Carbon::parse($tanggal)->format('Y-m-d') : '';
            $key = $tanggalFormatted . '_' . $kwitansi->getNilaiValue();
            if (!isset($kwitansiByDateValue[$key])) {
                $kwitansiByDateValue[$key] = [];
            }
            $kwitansiByDateValue[$key][] = $kwitansi;
        }

        foreach ($bankMasukList as $bankMasuk) {
            $key = $bankMasuk->tanggal . '_' . $bankMasuk->nilai;
            if (!isset($bankMasukByDateValue[$key])) {
                $bankMasukByDateValue[$key] = [];
            }
            $bankMasukByDateValue[$key][] = $bankMasuk;
        }

        // Lakukan matching berdasarkan tanggal dan nilai yang sama
        foreach ($kwitansiByDateValue as $dateValueKey => $kwitansiGroup) {
            if (isset($bankMasukByDateValue[$dateValueKey])) {
                $bankMasukGroup = $bankMasukByDateValue[$dateValueKey];

                // Sort berdasarkan urutan waktu pembuatan
                $sortedKwitansi = collect($kwitansiGroup)->sortBy(function($item) {
                    return $item->getKey();
                });
                $sortedBankMasuk = collect($bankMasukGroup)->sortBy('created_at');

                // Jika jumlah kwitansi lebih banyak dari bank masuk
                if (count($kwitansiGroup) > count($bankMasukGroup)) {
                    // Match berdasarkan urutan waktu pembuatan kwitansi
                    for ($i = 0; $i < count($bankMasukGroup); $i++) {
                        $kwitansi = $sortedKwitansi[$i];
                        $bankMasuk = $sortedBankMasuk[$i];

                        if (!in_array($kwitansi->getKey(), $usedKwitansiIds) &&
                            !in_array($bankMasuk->id, $usedBankMasukIds)) {

                            $results[] = [
                                'no_invoice' => $kwitansi->getKwitansiId(),
                                'tanggal_invoice' => $kwitansi->getTanggalValue(),
                                'nilai_invoice' => $kwitansi->getNilaiValue(),
                                'no_bank_masuk' => $bankMasuk->no_bm,
                                'tanggal_bank_masuk' => $bankMasuk->tanggal,
                                'nilai_bank_masuk' => $bankMasuk->nilai,
                                'is_matched' => true,
                                'kwitansi_id' => $kwitansi->getKey(),
                                'bank_masuk_id' => $bankMasuk->id,
                            ];

                            $usedKwitansiIds[] = $kwitansi->getKey();
                            $usedBankMasukIds[] = $bankMasuk->id;
                        }
                    }
                } else {
                    // Jika jumlah bank masuk lebih banyak atau sama
                    for ($i = 0; $i < count($kwitansiGroup); $i++) {
                        $kwitansi = $sortedKwitansi[$i];
                        $bankMasuk = $sortedBankMasuk[$i];

                        if (!in_array($kwitansi->getKey(), $usedKwitansiIds) &&
                            !in_array($bankMasuk->id, $usedBankMasukIds)) {

                            $results[] = [
                                'no_invoice' => $kwitansi->getKwitansiId(),
                                'tanggal_invoice' => $kwitansi->getTanggalValue(),
                                'nilai_invoice' => $kwitansi->getNilaiValue(),
                                'no_bank_masuk' => $bankMasuk->no_bm,
                                'tanggal_bank_masuk' => $bankMasuk->tanggal,
                                'nilai_bank_masuk' => $bankMasuk->nilai,
                                'is_matched' => true,
                                'kwitansi_id' => $kwitansi->getKey(),
                                'bank_masuk_id' => $bankMasuk->id,
                            ];

                            $usedKwitansiIds[] = $kwitansi->getKey();
                            $usedBankMasukIds[] = $bankMasuk->id;
                        }
                    }
                }
            }
        }

        // Tambahkan kwitansi yang tidak dimatch
        foreach ($kwitansiList as $kwitansi) {
            if (!in_array($kwitansi->getKey(), $usedKwitansiIds)) {
                $results[] = [
                    'no_invoice' => $kwitansi->getKwitansiId(),
                    'tanggal_invoice' => $kwitansi->getTanggalValue(),
                    'nilai_invoice' => $kwitansi->getNilaiValue(),
                    'no_bank_masuk' => '-',
                    'tanggal_bank_masuk' => 'N/A',
                    'nilai_bank_masuk' => 'N/A',
                    'is_matched' => false,
                    'kwitansi_id' => $kwitansi->getKey(),
                    'bank_masuk_id' => null,
                ];
            }
        }

        // Tambahkan bank masuk yang tidak dimatch
        foreach ($bankMasukList as $bankMasuk) {
            if (!in_array($bankMasuk->id, $usedBankMasukIds)) {
                $results[] = [
                    'no_invoice' => '-',
                    'tanggal_invoice' => 'N/A',
                    'nilai_invoice' => 'N/A',
                    'no_bank_masuk' => $bankMasuk->no_bm,
                    'tanggal_bank_masuk' => $bankMasuk->tanggal,
                    'nilai_bank_masuk' => $bankMasuk->nilai,
                    'is_matched' => false,
                    'kwitansi_id' => null,
                    'bank_masuk_id' => $bankMasuk->id,
                ];
            }
        }

        return $results;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'matches' => 'required|array',
            'matches.*.kwitansi_id' => 'required|string',
            'matches.*.bank_masuk_id' => 'required|integer',
            'matches.*.no_invoice' => 'required|string',
            'matches.*.tanggal_invoice' => 'required|date',
            'matches.*.nilai_invoice' => 'required|numeric',
            'matches.*.no_bank_masuk' => 'required|string',
            'matches.*.tanggal_bank_masuk' => 'required|date',
            'matches.*.nilai_bank_masuk' => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['matches'] as $match) {
                AutoMatch::create([
                    'kwitansi_id' => $match['kwitansi_id'],
                    'bank_masuk_id' => $match['bank_masuk_id'],
                    'kwitansi_no' => $match['no_invoice'],
                    'kwitansi_tanggal' => $match['tanggal_invoice'],
                    'kwitansi_nilai' => $match['nilai_invoice'],
                    'bank_masuk_no' => $match['no_bank_masuk'],
                    'bank_masuk_tanggal' => $match['tanggal_bank_masuk'],
                    'bank_masuk_nilai' => $match['nilai_bank_masuk'],
                    'match_date' => now(),
                    'status' => 'confirmed',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            DB::commit();
            return redirect()->route('bank-matching.index')->with('success', 'Data bank matching berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }
}

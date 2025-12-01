<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Bpb;
use Inertia\Inertia;
use App\Models\Supplier;
use App\Models\Department;
use App\Models\PoAnggaran;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\MemoPembayaran;
use App\Models\PaymentVoucher;
use App\Scopes\DepartmentScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PaymentVoucherExport;
use App\Models\PaymentVoucherDocument;
use App\Services\DocumentNumberService;
use Illuminate\Support\Facades\Storage;
use App\Models\PaymentVoucherDpAllocation;
use App\Models\PaymentVoucherBpbAllocation;
use App\Models\PaymentVoucherMemoAllocation;

class PaymentVoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $userRoleName = $user->role->name ?? '';
        $userRole = strtolower($userRoleName);

        // Base query with eager loads (include nested relations used by columns)
        $with = [
            'department', 'perihal', 'supplier', 'creator', 'creditCard',
            'purchaseOrder' => function ($q) {
                $q->with(['department', 'perihal', 'supplier', 'pph', 'termin', 'creditCard.bank', 'bankSupplierAccount.bank']);
            },
            // MemoPembayaran currently has no perihal relation; load only existing relations
            'memoPembayaran' => function ($q) {
                $q->with(['department', 'supplier', 'bankSupplierAccount.bank']);
            },
            // Po Anggaran relations for tipe Anggaran
            'poAnggaran' => function ($q) {
                $q->with(['department','perihal','bank','bisnisPartner']);
            },
        ];
        // DepartmentScope policy:
        // - Admin: bypass DepartmentScope
        // - Staff Toko & Kepala Toko: PVs created by Staff Toko or Kepala Toko in their departments (via DepartmentScope)
        // - Staff Digital Marketing: PVs created by Staff Digital Marketing in their departments (via DepartmentScope)
        // - Other roles (incl. Staff Akunting & Finance, Kabag, Direksi): rely only on DepartmentScope
        if ($userRoleName === 'Admin') {
            $query = PaymentVoucher::withoutGlobalScope(DepartmentScope::class)
                ->with($with);
        } else {
            $query = PaymentVoucher::query()->with($with);

            if (in_array($userRole, ['staff toko','kepala toko'], true)) {
                $query->whereHas('creator.role', function ($q) {
                    $q->whereIn('name', ['Staff Toko', 'Kepala Toko']);
                });
            }

            if ($userRole === 'staff digital marketing') {
                $query->whereHas('creator.role', function ($q) {
                    $q->where('name', 'Staff Digital Marketing');
                });
            }
        }
        // Removed default current-month filter; no implicit date filtering when no date params provided

        // Filters
        if ($request->filled('tanggal_start') || $request->filled('tanggal_end')) {
            $start = $request->filled('tanggal_start') ? $request->tanggal_start : null;
            $end = $request->filled('tanggal_end') ? $request->tanggal_end : null;

            $query->where(function ($q) use ($start, $end) {
                // Non-Draft: filter by 'tanggal'
                $q->where(function ($nonDraft) use ($start, $end) {
                    $nonDraft->where('status', '!=', 'Draft');
                    if ($start && $end) {
                        $nonDraft->whereBetween('tanggal', [$start, $end]);
                    } elseif ($start) {
                        $nonDraft->whereDate('tanggal', '>=', $start);
                    } elseif ($end) {
                        $nonDraft->whereDate('tanggal', '<=', $end);
                    }
                })
                // Draft: filter by created_at OR updated_at (by date)
                ->orWhere(function ($draft) use ($start, $end) {
                    $draft->where('status', 'Draft')
                          ->where(function ($dt) use ($start, $end) {
                              if ($start && $end) {
                                  $dt->whereBetween(DB::raw('DATE(created_at)'), [$start, $end])
                                     ->orWhereBetween(DB::raw('DATE(updated_at)'), [$start, $end]);
                              } elseif ($start) {
                                  $dt->whereDate('created_at', '>=', $start)
                                     ->orWhereDate('updated_at', '>=', $start);
                              } elseif ($end) {
                                  $dt->whereDate('created_at', '<=', $end)
                                     ->orWhereDate('updated_at', '<=', $end);
                              }
                          });
                });
            });
        }

        if ($request->filled('no_pv')) {
            $query->where('no_pv', 'like', '%' . $request->no_pv . '%');
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        // Filter by tipe_pv (Reguler, Anggaran, Lainnya, Pajak, Manual)
        if ($request->filled('tipe_pv')) {
            $query->where('tipe_pv', $request->get('tipe_pv'));
        }
        // Filter by metode pembayaran from PV or related PO/Memo
        // if ($request->filled('metode_bayar')) {
        //     $method = $request->get('metode_bayar');
        //     if ($method === 'Kredit') { $method = 'Kartu Kredit'; }
        //     $query->where(function($q) use ($method) {
        //         $q->where('metode_bayar', $method)
        //           ->orWhereHas('purchaseOrder', function($po) use ($method) {
        //               $po->where('metode_pembayaran', $method);
        //           })
        //           ->orWhereHas('memoPembayaran', function($mp) use ($method) {
        //               $mp->where('metode_pembayaran', $method);
        //           });
        //     });
        // }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        // Filter by kelengkapan dokumen when provided ("1" = Lengkap, "0" = Tidak Lengkap)
        if ($request->filled('kelengkapan_dokumen')) {
            $kd = $request->get('kelengkapan_dokumen');
            // Accept common truthy/falsey representations
            $val = null;
            if ($kd === '1' || $kd === 1 || $kd === true || $kd === 'true') {
                $val = 1;
            } elseif ($kd === '0' || $kd === 0 || $kd === false || $kd === 'false') {
                $val = 0;
            }
            if ($val !== null) {
                $query->where('kelengkapan_dokumen', $val);
            }
        }

        // Search across key fields, with optional search_columns
        if ($request->filled('search')) {
            $search = $request->get('search');
            $searchColumns = $request->filled('search_columns') ? explode(',', (string) $request->get('search_columns')) : [];

            if (!empty($searchColumns)) {
                $query->where(function ($q) use ($search, $searchColumns) {
                    foreach ($searchColumns as $column) {
                        switch ($column) {
                            case 'no_pv':
                                $q->orWhere('no_pv', 'like', "%$search%");
                                break;
                            case 'reference_number':
                                // reference number can be PO.no_po or Memo.no_mb depending on tipe
                                $q->orWhereHas('purchaseOrder', function($po) use ($search) {
                                    $po->where('no_po', 'like', "%$search%");
                                })->orWhereHas('memoPembayaran', function($mp) use ($search) {
                                    $mp->where('no_mb', 'like', "%$search%");
                                });
                                break;
                            case 'no_bk':
                                $q->orWhere('no_bk', 'like', "%$search%");
                                break;
                            case 'tanggal':
                                $q->orWhere('tanggal', 'like', "%$search%");
                                break;
                            case 'status':
                                $q->orWhere('status', 'like', "%$search%");
                                break;
                            case 'supplier':
                                $q->orWhereHas('supplier', function ($sub) use ($search) {
                                    $sub->where('nama_supplier', 'like', "%$search%");
                                })->orWhereHas('purchaseOrder.supplier', function ($sub) use ($search) {
                                    $sub->where('nama_supplier', 'like', "%$search%");
                                })->orWhereHas('memoPembayaran.supplier', function ($sub) use ($search) {
                                    $sub->where('nama_supplier', 'like', "%$search%");
                                });
                                break;
                            case 'department':
                                $q->orWhereHas('department', function ($sub) use ($search) {
                                    $sub->where('name', 'like', "%$search%");
                                })->orWhereHas('purchaseOrder.department', function ($sub) use ($search) {
                                    $sub->where('name', 'like', "%$search%");
                                })->orWhereHas('memoPembayaran.department', function ($sub) use ($search) {
                                    $sub->where('name', 'like', "%$search%");
                                });
                                break;
                            case 'perihal':
                                // perihal exists on PV or falls back to PO perihal
                                $q->orWhereHas('perihal', function($sub) use ($search) {
                                    $sub->where('nama', 'like', "%$search%");
                                })->orWhereHas('purchaseOrder.perihal', function($sub) use ($search) {
                                    $sub->where('nama', 'like', "%$search%");
                                });
                                break;
                            // case 'metode_pembayaran':
                            //     // normalized metode
                            //     $q->orWhere('metode_bayar', 'like', "%$search%")
                            //       ->orWhereHas('purchaseOrder', function($po) use ($search) {
                            //           $po->where('metode_pembayaran', 'like', "%$search%");
                            //       })
                            //       ->orWhereHas('memoPembayaran', function($mp) use ($search) {
                            //           $mp->where('metode_pembayaran', 'like', "%$search%");
                            //       });
                            //     break;
                            case 'grand_total':
                            case 'total':
                                $q->orWhereRaw('CAST(total AS CHAR) LIKE ?', ["%$search%"])
                                  ->orWhereRaw('CAST(grand_total AS CHAR) LIKE ?', ["%$search%"]);
                                break;
                            case 'nama_rekening':
                                $q->orWhere('nama_rekening', 'like', "%$search%");
                                break;
                            case 'no_rekening':
                                $q->orWhere('no_rekening', 'like', "%$search%");
                                break;
                            case 'no_kartu_kredit':
                                $q->orWhere('no_kartu_kredit', 'like', "%$search%");
                                break;
                            case 'keterangan':
                                $q->orWhere('keterangan', 'like', "%$search%");
                                break;
                            case 'created_by':
                                $q->orWhereHas('creator', function ($sub) use ($search) {
                                    $sub->where('name', 'like', "%$search%");
                                });
                                break;
                        }
                    }
                });
            } else {
                // Default broad search when no search_columns specified
                $query->where(function ($q) use ($search) {
                    $q->where('no_pv', 'like', "%$search%")
                      ->orWhere('status', 'like', "%$search%")
                      ->orWhere('no_bk', 'like', "%$search%")
                      ->orWhere('tanggal', 'like', "%$search%")
                    //   ->orWhere('metode_bayar', 'like', "%$search%")
                      ->orWhere('keterangan', 'like', "%$search%")
                      ->orWhereRaw('CAST(grand_total AS CHAR) LIKE ?', ["%$search%"]);
                })
                ->orWhereHas('perihal', function ($qp) use ($search) {
                    $qp->where('nama', 'like', "%$search%");
                })
                ->orWhereHas('purchaseOrder.perihal', function ($qpo) use ($search) {
                    $qpo->where('nama', 'like', "%$search%");
                })
                ->orWhereHas('supplier', function ($qs) use ($search) {
                    $qs->where('nama_supplier', 'like', "%$search%");
                })
                ->orWhereHas('department', function ($qd) use ($search) {
                    $qd->where('name', 'like', "%$search%");
                });
            }
        }

        $perPage = (int) ($request->get('per_page', 10));
        $paymentVouchers = $query
            ->orderBy('id', 'DESC')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($pv) {
                // Normalize metode_pembayaran from PV or related docs
                // $metodePembayaran = $pv->metode_bayar
                //     ?? $pv->purchaseOrder?->metode_pembayaran
                //     ?? $pv->memoPembayaran?->metode_pembayaran;

                // Supplier name from direct relation or related PO/Memo
                $supplierName = $pv->supplier?->nama_supplier
                    ?? $pv->purchaseOrder?->supplier?->nama_supplier
                    ?? $pv->memoPembayaran?->supplier?->nama_supplier
                    ?? $pv->manual_supplier;

                // Department name (fallbacks)
                $departmentName = $pv->department?->name
                    ?? $pv->purchaseOrder?->department?->name
                    ?? $pv->memoPembayaran?->department?->name;

                // Perihal name from PV or related Purchase Order (MemoPembayaran has no perihal relation)
                $perihalName = $pv->perihal?->nama
                    ?? $pv->purchaseOrder?->perihal?->nama;

                // Bank/account info depending on metode
                $namaRekening = $pv->nama_rekening
                    ?? $pv->purchaseOrder?->bankSupplierAccount?->nama_rekening
                    ?? $pv->memoPembayaran?->bankSupplierAccount?->nama_rekening
                    ?? $pv->manual_nama_pemilik_rekening;

                $noRekening = $pv->no_rekening
                    ?? $pv->purchaseOrder?->bankSupplierAccount?->no_rekening
                    ?? $pv->memoPembayaran?->bankSupplierAccount?->no_rekening
                    ?? $pv->manual_no_rekening;

                $noKartuKredit = $pv->no_kartu_kredit
                    ?? $pv->creditCard?->no_kartu_kredit
                    ?? $pv->purchaseOrder?->creditCard?->no_kartu_kredit;

                // Giro fields may exist on PV or PO
                $noGiro = $pv->no_giro ?? $pv->purchaseOrder?->no_giro;
                $tanggalGiro = $pv->tanggal_giro ?? $pv->purchaseOrder?->tanggal_giro;
                $tanggalCair = $pv->tanggal_cair ?? $pv->purchaseOrder?->tanggal_cair;

                // Amounts - use PV fields if available, fallback to PO aggregates
                $total = $pv->total ?? $pv->purchaseOrder?->total;
                $diskon = $pv->diskon ?? $pv->purchaseOrder?->diskon;
                $ppnFlag = $pv->ppn ?? $pv->purchaseOrder?->ppn; // might be boolean
                $ppnNominal = $pv->ppn_nominal ?? $pv->purchaseOrder?->ppn_nominal;
                $pphNominal = $pv->pph_nominal ?? $pv->purchaseOrder?->pph_nominal;
                $grandTotal = $pv->grand_total ?? $pv->purchaseOrder?->grand_total;

                return [
                    'id' => $pv->id,
                    'no_pv' => $pv->no_pv,
                    'no_po' => $pv->purchaseOrder?->no_po,
                    // expose tipe_pv for client conditional rendering
                    'tipe_pv' => $pv->tipe_pv,
                    // expose nominal from PV (used for tipe Pajak/Manual)
                    'nominal' => $pv->nominal,
                    // expose memo cicilan explicitly for tipe Lainnya display in tables
                    'memo_cicilan' => $pv->memoPembayaran?->cicilan,
                    // unified reference number: PO for non-Lainnya, Memo for Lainnya
                    'reference_number' => ($pv->tipe_pv === 'Lainnya')
                        ? ($pv->memoPembayaran?->no_mb)
                        : (($pv->tipe_pv === 'Anggaran')
                            ? ($pv->poAnggaran?->no_po_anggaran)
                            : ($pv->purchaseOrder?->no_po)),
                    'no_bk' => $pv->no_bk,
                    'tanggal' => $pv->tanggal,
                    'status' => $pv->status,

                    // Relational/display fields
                    'supplier_name' => $supplierName,
                    'department_name' => $departmentName,
                    'perihal' => $perihalName,
                    // 'metode_pembayaran' => $metodePembayaran,
                    'nama_rekening' => $namaRekening,
                    'no_rekening' => $noRekening,
                    'no_kartu_kredit' => $noKartuKredit,
                    'no_giro' => $noGiro,
                    'tanggal_giro' => $tanggalGiro,
                    'tanggal_cair' => $tanggalCair,
                    'keterangan' => $pv->keterangan ?? $pv->note,

                    // Amounts
                    'total' => $total,
                    'diskon' => $diskon,
                    'ppn' => $ppnFlag,
                    'ppn_nominal' => $ppnNominal,
                    'pph_nominal' => $pphNominal,
                    'grand_total' => $grandTotal,

                    // expose creator relation minimal for front-end permission checks
                    'creator' => $pv->creator ? [ 'id' => $pv->creator->id, 'name' => $pv->creator->name ] : null,
                    'created_at' => optional($pv->created_at)->toDateString(),
                    // custom flags
                    'kelengkapan_dokumen' => (bool) $pv->kelengkapan_dokumen,
                ];
            });

        return Inertia::render('payment-voucher/Index', [
            'userRole' => $userRole,
            'userPermissions' => $user->role->permissions ?? [],
            'paymentVouchers' => $paymentVouchers,
            // Scope department options to the logged-in user's departments (Admin sees all)
            'departmentOptions' => (function() use ($user) {
                $q = Department::query()->active()->select(['id','name'])->orderBy('name');
                $roleName = $user->role->name ?? '';
                $hasAllDept = ($user->departments ?? collect())->contains('name', 'All');
                if (strtolower($roleName) !== 'admin' && !$hasAllDept) {
                    $q->whereHas('users', function($uq) use ($user) {
                        $uq->where('users.id', $user->id);
                    });
                }
                return $q->get()->map(fn($d)=>['value'=>$d->id,'label'=>$d->name])->values();
            })(),
            'supplierOptions' => (function () {
                static $allDepartmentId = null;
                if ($allDepartmentId === null) {
                    $allDepartmentId = Department::whereRaw('LOWER(name) = ?', ['all'])->value('id');
                }

                return Supplier::query()
                    ->active()
                    ->select(['id','nama_supplier','department_id'])
                    ->orderBy('nama_supplier')
                    ->get()
                    ->map(fn($s)=>[
                        'value'=>$s->id,
                        'label'=>$s->nama_supplier,
                        'department_id'=>$s->department_id,
                        'is_all'=> $allDepartmentId && (int) $s->department_id === (int) $allDepartmentId,
                    ])->values();
            })(),
            'filters' => [
                'tanggal_start' => $request->get('tanggal_start'),
                'tanggal_end' => $request->get('tanggal_end'),
                'no_pv' => $request->get('no_pv'),
                'department_id' => $request->get('department_id'),
                'status' => $request->get('status'),
                'tipe_pv' => $request->get('tipe_pv'),
                'kelengkapan_dokumen' => $request->get('kelengkapan_dokumen'),
                'supplier_id' => $request->get('supplier_id'),
                'search' => $request->get('search'),
                'search_columns' => $request->get('search_columns'),
                'per_page' => $perPage,
            ],
        ]);
    }

    public function exportExcel(Request $request)
    {
        $filters = $request->except(['per_page', 'search', 'search_columns']);
        return Excel::download(new PaymentVoucherExport($filters), 'payment_vouchers.xlsx');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        $deptQuery = Department::query()->active()->select(['id','name','alias'])->orderBy('name');
        $roleName = $user->role->name ?? '';
        $hasAllDept = ($user->departments ?? collect())->contains('name', 'All');
        if (strtolower($roleName) !== 'admin' && !$hasAllDept) {
            $deptQuery->whereHas('users', function($uq) use ($user) {
                $uq->where('users.id', $user->id);
            });
        }
        $departments = $deptQuery->get()
            ->map(fn($d)=>[
                'value'=>$d->id,
                'label'=> $d->name,
                'name'=> $d->name,
                'alias'=>$d->alias,
            ])->values();

        $suppliers = Supplier::active()->with(['bankAccounts.bank'])
            ->select(['id','nama_supplier','no_telepon','alamat','email','department_id'])
            ->orderBy('nama_supplier')->get()
            ->map(function($s) {
                static $allDepartmentId = null;
                if ($allDepartmentId === null) {
                    $allDepartmentId = Department::whereRaw('LOWER(name) = ?', ['all'])->value('id');
                }

                return [
                    'value' => $s->id,
                    'label' => $s->nama_supplier,
                    'email' => $s->email,
                    'phone' => $s->no_telepon,
                    'address' => $s->alamat,
                    'department_id' => $s->department_id,
                    'is_all' => $allDepartmentId && (int) $s->department_id === (int) $allDepartmentId,
                    'bank_accounts' => $s->bankAccounts->map(fn($ba)=>[
                        'id' => $ba->id,
                        'bank' => $ba->bank ? [ 'id' => $ba->bank->id, 'nama_bank' => $ba->bank->nama_bank ] : null,
                        'bank_id' => $ba->bank_id,
                        'bank_name' => $ba->bank?->nama_bank,
                        'account_name' => $ba->nama_rekening,
                        'account_number' => $ba->no_rekening,
                    ])->values(),
                ];
            })->values();

        $perihals = \App\Models\Perihal::query()->active()->select(['id','nama'])->orderBy('nama')->get()
            ->map(fn($p)=>['value'=>$p->id,'label'=>$p->nama])->values();

        // Credit cards for Kartu Kredit metode
        $creditCards = \App\Models\CreditCard::active()->with('bank')
            ->select(['id','bank_id','no_kartu_kredit','nama_pemilik','department_id'])
            ->orderBy('no_kartu_kredit')
            ->get()
            ->map(function($c){
                static $allDepartmentId = null;
                if ($allDepartmentId === null) {
                    $allDepartmentId = Department::whereRaw('LOWER(name) = ?', ['all'])->value('id');
                }

                return [
                    'value' => $c->id,
                    'label' => $c->no_kartu_kredit,
                    'card_number' => $c->no_kartu_kredit,
                    'owner_name' => $c->nama_pemilik,
                    'bank_name' => $c->bank?->nama_bank,
                    'department_id' => $c->department_id,
                    'is_all' => $allDepartmentId && (int) $c->department_id === (int) $allDepartmentId,
                ];
            })->values();

        // Giro options from Purchase Orders with metode_pembayaran 'Cek/Giro' and Approved only
        $giroOptions = \App\Models\PurchaseOrder::with(['supplier'])
            // ->where('metode_pembayaran', 'Cek/Giro')
            ->where('status', 'Approved')
            ->select(['id','no_po','supplier_id','tanggal_giro','tanggal_cair','department_id','no_giro'])
            ->orderBy('no_po')
            ->get()
            ->map(function($po){
                return [
                    'value' => $po->id,
                    'label' => $po->no_po,
                    'name' => $po->no_po,
                    'no_giro' => $po->no_giro,
                    'tanggal_giro' => $po->tanggal_giro,
                    'tanggal_cair' => $po->tanggal_cair,
                    'department_id' => $po->department_id,
                    'supplier_name' => $po->supplier?->nama_supplier,
                ];
            })->values();

        // PPH options for dropdown
        $pphOptions = \App\Models\Pph::query()->active()
            ->select(['id', 'kode_pph', 'nama_pph', 'tarif_pph'])
            ->orderBy('kode_pph')
            ->get()
            ->map(function($pph) {
                return [
                    'value' => $pph->id,
                    'label' => $pph->nama_pph . ' (' . $pph->tarif_pph . '%)',
                    'kode_pph' => $pph->kode_pph,
                    'nama_pph' => $pph->nama_pph,
                    'tarif_pph' => $pph->tarif_pph,
                ];
            })->values();

        return Inertia::render('payment-voucher/Create', [
            'userRole' => $user->role->name ?? '',
            'userPermissions' => $user->role->permissions ?? [],
            'departmentOptions' => $departments,
            'defaultDepartmentId' => (function() use ($user) {
                $userDepts = ($user->departments ?? collect())->reject(function($d){ return strtolower($d->name ?? '') === 'all'; });
                return $userDepts->count() === 1 ? ($userDepts->first()->id ?? null) : null;
            })(),
            'supplierOptions' => $suppliers,
            // Minimal Bisnis Partner options for tipe Anggaran
            'bisnisPartnerOptions' => \App\Models\BisnisPartner::query()
                ->select(['id','nama_bp'])
                ->orderBy('nama_bp')
                ->get()
                ->map(fn($bp)=>['value'=>$bp->id,'label'=>$bp->nama_bp])
                ->values(),
            'perihalOptions' => $perihals,
            'creditCardOptions' => $creditCards,
            'giroOptions' => $giroOptions,
            'pphOptions' => $pphOptions,
            'currencyOptions' => [
                ['value' => 'IDR', 'label' => 'IDR'],
                ['value' => 'USD', 'label' => 'USD'],
            ],
            'banks' => \App\Models\Bank::query()->active()->select(['id','nama_bank','singkatan'])->orderBy('nama_bank')->get(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = Auth::user();

        // Bypass DepartmentScope to allow permitted users (creator/sender/admin) to edit across departments
        $pv = PaymentVoucher::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->with([
                'department', 'perihal', 'supplier', 'creator', 'creditCard',
                'purchaseOrder' => function ($q) {
                    $q->with(['department', 'perihal']);
                },
                'memoPembayaran' => function ($q) {
                    $q->with(['department']);
                },
                'documents'
            ])->findOrFail($id);

        // Authorization aligned with update()
        $isAdmin = ($user?->role?->name ?? '') === 'Admin';
        $isSender = $pv->logs()->where('action', 'sent')->where('user_id', $user?->id)->exists();
        $isCreatorEquivalent = (Auth::id() === $pv->creator_id) || $isSender;
        if (!$isCreatorEquivalent && !$isAdmin) {
            abort(403);
        }

        $deptQuery = Department::query()->active()->select(['id','name','alias'])->orderBy('name');
        $roleName = $user->role->name ?? '';
        $hasAllDept = ($user->departments ?? collect())->contains('name', 'All');
        if (strtolower($roleName) !== 'admin' && !$hasAllDept) {
            $deptQuery->whereHas('users', function($uq) use ($user) {
                $uq->where('users.id', $user->id);
            });
        }
        $departments = $deptQuery->get()
            ->map(fn($d)=>[
                'value'=>$d->id,
                'label'=> $d->name,
                'name'=> $d->name,
                'alias'=>$d->alias,
            ])->values();

        $suppliers = Supplier::active()->with(['bankAccounts.bank'])
            ->select(['id','nama_supplier','no_telepon','alamat','department_id','email'])
            ->orderBy('nama_supplier')->get()
            ->map(function($s){
                return [
                    'value' => $s->id,
                    'label' => $s->nama_supplier,
                    'email' => $s->email,
                    'phone' => $s->no_telepon,
                    'address' => $s->alamat,
                    'department_id' => $s->department_id,
                    'bank_accounts' => $s->bankAccounts->map(fn($ba)=>[
                        'id' => $ba->id,
                        'bank' => $ba->bank ? [ 'id' => $ba->bank->id, 'nama_bank' => $ba->bank->nama_bank ] : null,
                        'bank_id' => $ba->bank_id,
                        'bank_name' => $ba->bank?->nama_bank,
                        'account_name' => $ba->nama_rekening,
                        'account_number' => $ba->no_rekening,
                    ])->values(),
                ];
            })->values();

        $perihals = \App\Models\Perihal::query()->active()->select(['id','nama'])->orderBy('nama')->get()
            ->map(fn($p)=>['value'=>$p->id,'label'=>$p->nama])->values();

        // Credit cards for Kartu Kredit metode
        $creditCards = \App\Models\CreditCard::active()->with('bank')
            ->select(['id','bank_id','no_kartu_kredit','nama_pemilik','department_id'])
            ->orderBy('no_kartu_kredit')
            ->get()
            ->map(function($c){
                return [
                    'value' => $c->id,
                    'label' => $c->no_kartu_kredit,
                    'card_number' => $c->no_kartu_kredit,
                    'owner_name' => $c->nama_pemilik,
                    'bank_name' => $c->bank?->nama_bank,
                    'department_id' => $c->department_id,
                ];
            })->values();

        // Giro options from Purchase Orders with metode_pembayaran 'Cek/Giro' and Approved only
        $giroOptions = \App\Models\PurchaseOrder::with(['supplier'])
            // ->where('metode_pembayaran', 'Cek/Giro')
            ->where('status', 'Approved')
            ->select(['id','no_po','supplier_id','tanggal_giro','tanggal_cair','department_id','no_giro'])
            ->orderBy('no_po')
            ->get()
            ->map(function($po){
                return [
                    'value' => $po->id,
                    'label' => $po->no_po,
                    'name' => $po->no_po,
                    'no_giro' => $po->no_giro,
                    'tanggal_giro' => $po->tanggal_giro,
                    'tanggal_cair' => $po->tanggal_cair,
                    'department_id' => $po->department_id,
                    'supplier_name' => $po->supplier?->nama_supplier,
                ];
            })->values();

        // PPH options for dropdown
        $pphOptions = \App\Models\Pph::query()->active()
            ->select(['id', 'kode_pph', 'nama_pph', 'tarif_pph'])
            ->orderBy('kode_pph')
            ->get()
            ->map(function($pph) {
                return [
                    'value' => $pph->id,
                    'label' => $pph->nama_pph . ' (' . $pph->tarif_pph . '%)',
                    'kode_pph' => $pph->kode_pph,
                    'nama_pph' => $pph->nama_pph,
                    'tarif_pph' => $pph->tarif_pph,
                ];
            })->values();

        // Provide UI alias fields for Manual PVs so the form binds correctly
        $pvPayload = $pv->toArray();
        if (in_array(($pv->tipe_pv ?? null), ['Manual','Pajak'], true)) {
            $pvPayload = array_merge($pvPayload, [
                'supplier_name' => $pv->manual_supplier,
                'supplier_phone' => $pv->manual_no_telepon,
                'supplier_address' => $pv->manual_alamat,
                'supplier_bank_name' => $pv->manual_nama_bank,
                'supplier_account_name' => $pv->manual_nama_pemilik_rekening,
                'supplier_account_number' => $pv->manual_no_rekening,
            ]);
        }
        // Default selected credit card on edit if PV missing but metode is Kredit and PO has one
        // if (($pvPayload['metode_bayar'] ?? null) === 'Kartu Kredit' && empty($pvPayload['credit_card_id'])) {
        //     $pvPayload['credit_card_id'] = $pv->purchaseOrder?->credit_card_id;
        // }

        // Normalize nominal_text for front-end manual nominal input formatting
        if (array_key_exists('nominal', $pvPayload) && $pvPayload['nominal'] !== null) {
            $s = (string) $pvPayload['nominal'];
            if (strpos($s, '.') !== false) {
                $s = rtrim($s, '0');
                $s = rtrim($s, '.');
            }
            if ($s === '') { $s = '0'; }
            $pvPayload['nominal_text'] = $s;
        }

        // Prefill supplier contact UI aliases on edit when supplier relation exists
        if ($pv->supplier) {
            if (empty($pvPayload['supplier_name'])) {
                $pvPayload['supplier_name'] = $pv->supplier->nama_supplier;
            }
            if (empty($pvPayload['supplier_phone'])) {
                $pvPayload['supplier_phone'] = $pv->supplier->no_telepon;
            }
            if (empty($pvPayload['supplier_address'])) {
                $pvPayload['supplier_address'] = $pv->supplier->alamat;
            }
        }

        return Inertia::render('payment-voucher/Edit', [
            'id' => $pv->id,
            'paymentVoucher' => $pvPayload,
            'userRole' => $user->role->name ?? '',
            'userPermissions' => $user->role->permissions ?? [],
            'departmentOptions' => $departments,
            'supplierOptions' => $suppliers,
            'perihalOptions' => $perihals,
            'creditCardOptions' => $creditCards,
            'giroOptions' => $giroOptions,
            'pphOptions' => $pphOptions,
            'currencyOptions' => [
                ['value' => 'IDR', 'label' => 'IDR'],
                ['value' => 'USD', 'label' => 'USD'],
            ],
            'banks' => \App\Models\Bank::query()->active()->select(['id','nama_bank','singkatan'])->orderBy('nama_bank')->get(),
        ]);
    }

    /**
     * Update the specified Payment Voucher.
     */
    public function update(Request $request, string $id)
    {
        // Bypass DepartmentScope to allow permitted users (creator/sender/admin) to update across departments
        $pv = PaymentVoucher::withoutGlobalScope(\App\Scopes\DepartmentScope::class)->findOrFail($id);
        $user = Auth::user();

        $normalized = $request->all();
        if (array_key_exists('po_anggaran_id', $normalized) && $normalized['po_anggaran_id'] === '') {
            $normalized['po_anggaran_id'] = null;
        }
        $request->merge($normalized);

        // Optional: restrict which statuses can be edited
        $isAdmin = ($user?->role?->name ?? '') === 'Admin';
        // Fallback: treat the user who performed 'sent' as creator-equivalent for legacy records
        $isSender = $pv->logs()->where('action', 'sent')->where('user_id', $user?->id)->exists();
        $isCreatorEquivalent = (Auth::id() === $pv->creator_id) || $isSender;
        // Basic authorization: only creator-equivalent or admin may attempt update at all
        if (!$isCreatorEquivalent && !$isAdmin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $canEditStatus = in_array($pv->status, ['Draft', 'In Progress', 'Approved']) || ($pv->status === 'Rejected' && ($isCreatorEquivalent || $isAdmin));
        if (!$canEditStatus) {
            return response()->json(['error' => 'Payment Voucher tidak dapat diubah pada status saat ini'], 422);
        }

        $data = $request->validate([
            'tipe_pv' => 'nullable|string|in:Reguler,Anggaran,Lainnya,Pajak,Manual,DP',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'bank_supplier_account_id' => 'nullable|integer|exists:bank_supplier_accounts,id',
            // derived from supplier relation
            'department_id' => 'nullable|integer|exists:departments,id',
            'perihal_id' => 'nullable|integer|exists:perihals,id',
            'nominal' => 'nullable|numeric|decimal:0,5',
            'currency' => 'nullable|string|in:IDR,USD,EUR',
            // 'metode_bayar' => 'nullable|string|in:Transfer,Cek/Giro,Kartu Kredit',
            'credit_card_id' => 'nullable|integer|exists:credit_cards,id',
            'no_giro' => 'nullable|string',
            'tanggal_giro' => 'nullable|date',
            'tanggal_cair' => 'nullable|date',
            'note' => 'nullable|string',
            'keterangan' => 'nullable|string',
            // derived from supplier bank account / credit card relations
            'purchase_order_id' => 'nullable|integer|exists:purchase_orders,id',
            'memo_pembayaran_id' => 'nullable|integer|exists:memo_pembayarans,id',
            'po_anggaran_id' => 'nullable|integer|exists:po_anggarans,id',
            // BPB linkage (optional)
            'bpb_id' => 'nullable|integer|exists:bpbs,id',
            'bpb_ids' => 'nullable|array|min:1',
            'bpb_ids.*' => 'integer|distinct|exists:bpbs,id',
            // Selected memos (for Reguler PO flow, optional, used to cap nominal when no BPB)
            'memo_ids' => 'nullable|array|min:1',
            'memo_ids.*' => 'integer|distinct|exists:memo_pembayarans,id',
            // New partial allocations (preferred over bpb_ids/memo_ids)
            'bpb_allocations' => 'nullable|array',
            'bpb_allocations.*.bpb_id' => 'required|integer|distinct|exists:bpbs,id',
            'bpb_allocations.*.amount' => 'required|numeric|decimal:0,5|min:0',
            'memo_allocations' => 'nullable|array',
            'memo_allocations.*.memo_id' => 'required|integer|distinct|exists:memo_pembayarans,id',
            'memo_allocations.*.amount' => 'required|numeric|decimal:0,5|min:0',
            // Manual fields (accept either manual_* or UI aliases for backward-compat)
            'manual_supplier' => 'nullable|string',
            'manual_no_telepon' => 'nullable|string',
            'manual_alamat' => 'nullable|string',
            'manual_nama_bank' => 'nullable|string',
            'manual_nama_pemilik_rekening' => 'nullable|string',
            'manual_no_rekening' => 'nullable|string',
            'supplier_name' => 'nullable|string',
            'supplier_phone' => 'nullable|string',
            'supplier_address' => 'nullable|string',
            'supplier_bank_name' => 'nullable|string',
            'supplier_account_name' => 'nullable|string',
            'supplier_account_number' => 'nullable|string',
            // Custom flags
            'kelengkapan_dokumen' => 'nullable|boolean',
        ]);

        // Map UI aliases -> manual_* when tipe_pv = Manual
        if (in_array(($data['tipe_pv'] ?? $pv->tipe_pv), ['Manual','Pajak'], true)) {
            $data['purchase_order_id'] = null;
            $data['memo_pembayaran_id'] = null;
            $data['manual_supplier'] = $data['manual_supplier'] ?? ($data['supplier_name'] ?? null);
            $data['manual_no_telepon'] = $data['manual_no_telepon'] ?? ($data['supplier_phone'] ?? null);
            $data['manual_alamat'] = $data['manual_alamat'] ?? ($data['supplier_address'] ?? null);
            $data['manual_nama_bank'] = $data['manual_nama_bank'] ?? ($data['supplier_bank_name'] ?? null);
            $data['manual_nama_pemilik_rekening'] = $data['manual_nama_pemilik_rekening'] ?? ($data['supplier_account_name'] ?? null);
            $data['manual_no_rekening'] = $data['manual_no_rekening'] ?? ($data['supplier_account_number'] ?? null);
        } elseif (($data['tipe_pv'] ?? $pv->tipe_pv) === 'Lainnya') {
            // Lainnya uses Memo Pembayaran, ensure PO cleared
            $data['purchase_order_id'] = null;
        } elseif (($data['tipe_pv'] ?? $pv->tipe_pv) === 'Anggaran') {
            // Anggaran uses Po Anggaran, ensure PO/Memo cleared
            $data['purchase_order_id'] = null;
            $data['memo_pembayaran_id'] = null;
            $poaId = $data['po_anggaran_id'] ?? $pv->po_anggaran_id;
            if (!empty($poaId)) {
                $poa = PoAnggaran::with(['bisnisPartner','bank'])->find($poaId);
                if ($poa) {
                    // Derive core fields from Po Anggaran
                    $data['department_id'] = $data['department_id'] ?? $poa->department_id;
                    $data['perihal_id'] = $data['perihal_id'] ?? $poa->perihal_id;
                    $data['nominal'] = $poa->nominal;
                    // Map rekening and BP info into manual_* fields for PV printing
                    $data['manual_supplier'] = $data['manual_supplier'] ?? ($poa->bisnisPartner->nama_bp ?? null);
                    $data['manual_no_telepon'] = $data['manual_no_telepon'] ?? ($poa->bisnisPartner->no_telepon ?? null);
                    $data['manual_alamat'] = $data['manual_alamat'] ?? ($poa->bisnisPartner->alamat ?? null);
                    $data['manual_nama_bank'] = $data['manual_nama_bank'] ?? ($poa->bank?->nama_bank ?? null);
                    $data['manual_nama_pemilik_rekening'] = $data['manual_nama_pemilik_rekening'] ?? ($poa->nama_rekening ?? null);
                    $data['manual_no_rekening'] = $data['manual_no_rekening'] ?? ($poa->no_rekening ?? null);
                }
            }
        } elseif (($data['tipe_pv'] ?? $pv->tipe_pv) === 'DP') {
            // DP uses PO with DP setting; ensure Memo/Po Anggaran cleared
            $data['memo_pembayaran_id'] = null;
            $data['po_anggaran_id'] = null;
            $poId = $data['purchase_order_id'] ?? $pv->purchase_order_id;
            if (empty($poId)) {
                return response()->json(['error' => 'Purchase Order wajib diisi untuk PV DP'], 422);
            }
            $po = PurchaseOrder::withoutGlobalScope(DepartmentScope::class)
                ->select(['id','status','dp_active','dp_nominal'])
                ->find($poId);
            if (!$po || !$po->dp_active || ($po->dp_nominal ?? 0) <= 0 || $po->status !== 'Approved') {
                return response()->json(['error' => 'PO tidak valid sebagai PO DP (harus Approved dan memiliki DP aktif)'], 422);
            }
            // Pastikan belum ada BPB atas PO tersebut (BPB non-canceled)
            $hasBpb = Bpb::withoutGlobalScope(DepartmentScope::class)
                ->where('purchase_order_id', $poId)
                ->whereNull('deleted_at')
                ->whereNot('status', 'Canceled')
                ->exists();
            if ($hasBpb) {
                return response()->json(['error' => 'PO DP yang sudah memiliki BPB tidak dapat digunakan untuk PV DP'], 422);
            }

            // Hitung outstanding DP pada PO ini, kecuali PV ini sendiri
            $usedDp = PaymentVoucher::withoutGlobalScope(DepartmentScope::class)
                ->where('tipe_pv', 'DP')
                ->where('purchase_order_id', $poId)
                ->where('status', '!=', 'Canceled')
                ->when($pv->id, function($q) use ($pv) {
                    $q->where('id', '!=', $pv->id);
                })
                ->sum('nominal');
            $maxDp = max(0.0, (float)($po->dp_nominal ?? 0) - (float)$usedDp);
            $requestedNominal = (float)($data['nominal'] ?? $pv->nominal ?? 0);
            if ($requestedNominal <= 0) {
                return response()->json(['error' => 'Nominal PV DP harus lebih besar dari 0'], 422);
            }
            if ($requestedNominal - $maxDp > 0.00001) {
                return response()->json(['error' => 'Nominal PV DP tidak boleh melebihi sisa DP pada PO'], 422);
            }
            // Clamp nominal ke maksimum sisa DP untuk keamanan
            $data['nominal'] = $requestedNominal;
        } else {
            // Non-manual, non-lainnya uses PO; ensure memo cleared
            $data['memo_pembayaran_id'] = null;
        }

        // If tipe Lainnya and memo selected, set bank account and nominal from memo
        if ((($data['tipe_pv'] ?? $pv->tipe_pv) === 'Lainnya')) {
            $memoId = $data['memo_pembayaran_id'] ?? $pv->memo_pembayaran_id;
            if (!empty($memoId)) {
                $memo = \App\Models\MemoPembayaran::select('id','total','bank_supplier_account_id')->find($memoId);
                if ($memo) {
                    $data['bank_supplier_account_id'] = $memo->bank_supplier_account_id;
                    $data['nominal'] = $memo->total ?? 0;
                }
            }
        }

        $pv->fill($data);
        // If client requests to save as draft (e.g., from Edit on Rejected), revert status
        if ($request->boolean('save_as_draft')) {
            $pv->status = 'Draft';
            // Clear rejection fields to avoid confusion on revived draft
            $pv->rejected_by = null;
            $pv->rejected_at = null;
            $pv->rejection_reason = null;
        }
        $pv->save();

        // If BPB/Memo allocations are provided for Reguler PV, persist allocations (preferred)
        // ==========================================================
        // REGULER PV — HANDLE ALLOCATIONS (BPB / MEMO / Legacy Linking)
        // ==========================================================
        if ((($data['tipe_pv'] ?? $pv->tipe_pv) === 'Reguler')) {

            // ------------------------------------------------------
            // 1) BPB ALLOCATIONS (preferred)
            // ------------------------------------------------------
            $allocs = (array)$request->input('bpb_allocations', []);
            if (!empty($allocs)) {

                $bpbIds = array_values(array_unique(array_map(fn($r)=>(int)$r['bpb_id'], $allocs)));

                // Load BPB
                $bpbs = Bpb::withoutGlobalScope(DepartmentScope::class)
                    ->whereIn('id', $bpbIds)
                    ->where('status', 'Approved')
                    ->get(['id','purchase_order_id','grand_total']);

                if ($bpbs->count() !== count($bpbIds)) {
                    return response()->json(['error' => 'Sebagian BPB tidak valid/Approved'], 422);
                }

                // All same PO
                $poIds = $bpbs->pluck('purchase_order_id')->unique()->values();
                if ($poIds->count() !== 1) {
                    return response()->json(['error' => 'Alokasi BPB harus dari PO yang sama'], 422);
                }
                $poId = $poIds->first();

                // Outstanding per BPB (excluding this PV)
                $allocatedRows = DB::table('payment_voucher_bpb_allocations as a')
                    ->join('payment_vouchers as pv2','pv2.id','=','a.payment_voucher_id')
                    ->whereIn('a.bpb_id', $bpbIds)
                    ->whereNot('pv2.status','Canceled')
                    ->where('a.payment_voucher_id', '!=', $pv->id)
                    ->selectRaw('a.bpb_id, SUM(a.amount) as used')
                    ->groupBy('a.bpb_id')
                    ->pluck('used','bpb_id');

                $sum = 0;
                foreach ($allocs as $row) {
                    $bId  = (int)$row['bpb_id'];
                    $amt  = (float)$row['amount'];

                    $bpb  = $bpbs->firstWhere('id', $bId);
                    $used = (float)($allocatedRows[$bId] ?? 0);
                    $out  = max(0, ($bpb->grand_total ?? 0) - $used);

                    if ($amt > $out + 0.00001) {
                        return response()->json(['error' => "Alokasi BPB #$bId melebihi outstanding"], 422);
                    }
                    $sum += $amt;
                }

                // Replace allocations
                PaymentVoucherBpbAllocation::where('payment_voucher_id', $pv->id)->delete();
                foreach ($allocs as $row) {
                    PaymentVoucherBpbAllocation::create([
                        'payment_voucher_id'=> $pv->id,
                        'bpb_id'            => (int)$row['bpb_id'],
                        'amount'            => (float)$row['amount'],
                    ]);
                }

                // Set PV
                $pv->purchase_order_id = $poId;
                $pv->nominal = $sum;
                $pv->save();

                // Clear legacy
                Bpb::withoutGlobalScope(DepartmentScope::class)
                    ->where('payment_voucher_id', $pv->id)
                    ->update(['payment_voucher_id' => null]);

                goto allocation_done;
            }

            // ------------------------------------------------------
            // 2) MEMO ALLOCATIONS (preferred)
            // ------------------------------------------------------
            $memoAllocs = (array)$request->input('memo_allocations', []);
            if (!empty($memoAllocs)) {

                $memoIds = array_values(array_unique(array_map(fn($r)=>(int)$r['memo_id'], $memoAllocs)));

                $memos = MemoPembayaran::whereIn('id', $memoIds)
                    ->where('status','Approved')
                    ->get(['id','purchase_order_id','total']);

                if ($memos->count() !== count($memoIds)) {
                    return response()->json(['error' => 'Sebagian Memo tidak valid/Approved'], 422);
                }

                $poIds = $memos->pluck('purchase_order_id')->unique()->values();
                if ($poIds->count() !== 1) {
                    return response()->json(['error' => 'Alokasi Memo harus dari PO yang sama'], 422);
                }
                $poId = $poIds->first();

                // Outstanding
                $allocatedRows = DB::table('payment_voucher_memo_allocations as a')
                    ->join('payment_vouchers as pv2','pv2.id','=','a.payment_voucher_id')
                    ->whereIn('a.memo_pembayaran_id', $memoIds)
                    ->whereNot('pv2.status','Canceled')
                    ->where('a.payment_voucher_id', '!=', $pv->id)
                    ->selectRaw('a.memo_pembayaran_id as mid, SUM(a.amount) as used')
                    ->groupBy('a.memo_pembayaran_id')
                    ->pluck('used','mid');

                $sum = 0;
                foreach ($memoAllocs as $row) {
                    $mId = (int)$row['memo_id'];
                    $amt = (float)$row['amount'];

                    $mm = $memos->firstWhere('id', $mId);
                    $used = (float)($allocatedRows[$mId] ?? 0);
                    $out  = max(0, ($mm->total ?? 0) - $used);

                    if ($amt > $out + 0.00001) {
                        return response()->json(['error' => "Alokasi Memo #$mId melebihi outstanding"], 422);
                    }
                    $sum += $amt;
                }

                PaymentVoucherMemoAllocation::where('payment_voucher_id', $pv->id)->delete();
                foreach ($memoAllocs as $row) {
                    PaymentVoucherMemoAllocation::create([
                        'payment_voucher_id'=> $pv->id,
                        'memo_pembayaran_id'=> (int)$row['memo_id'],
                        'amount'            => (float)$row['amount'],
                    ]);
                }

                // nominal tidak boleh > sum memo
                $requested = (float)($pv->nominal ?? 0);
                $pv->purchase_order_id = $poId;
                $pv->nominal = min($requested, $sum);
                $pv->save();

                goto allocation_done;
            }

            // ------------------------------------------------------
            // 3) LEGACY: bpb_ids[]
            // ------------------------------------------------------
            $bpbIds = (array)$request->input('bpb_ids', []);
            if (!empty($bpbIds)) {

                $bpbs = Bpb::withoutGlobalScope(DepartmentScope::class)
                    ->with('paymentVoucher')
                    ->whereIn('id', $bpbIds)
                    ->get();

                if ($bpbs->count() !== count(array_unique($bpbIds))) {
                    return response()->json(['error'=>'Sebagian BPB tidak ditemukan'],422);
                }

                foreach ($bpbs as $b) {
                    $blocked = $b->status !== 'Approved'
                        || ($b->payment_voucher_id && $b->payment_voucher_id != $pv->id
                            && optional($b->paymentVoucher)->status !== 'Canceled');

                    if ($blocked) {
                        return response()->json(['error'=>'BPB tidak tersedia untuk dipilih'],422);
                    }
                }

                // same PO
                $poIds = $bpbs->pluck('purchase_order_id')->unique()->values();
                if ($poIds->count() !== 1) {
                    return response()->json(['error'=>'Semua BPB harus dari PO yang sama'],422);
                }

                // Clear old, re-link new
                Bpb::withoutGlobalScope(DepartmentScope::class)
                    ->where('payment_voucher_id',$pv->id)
                    ->whereNotIn('id',$bpbIds)
                    ->update(['payment_voucher_id'=>null]);

                Bpb::withoutGlobalScope(DepartmentScope::class)
                    ->whereIn('id',$bpbIds)
                    ->update(['payment_voucher_id'=>$pv->id]);

                $pv->purchase_order_id = $poIds->first();
                $pv->nominal = $bpbs->sum(fn($b)=>(float)$b->grand_total);
                $pv->save();

                goto allocation_done;
            }

            // ------------------------------------------------------
            // 4) LEGACY SINGLE BPB
            // ------------------------------------------------------
            $singleBpb = $request->input('bpb_id');
            if (!empty($singleBpb)) {

                $bpb = Bpb::withoutGlobalScope(DepartmentScope::class)
                    ->with('paymentVoucher')
                    ->find($singleBpb);

                if (!$bpb) goto legacy_memo_check;

                $blocked = $bpb->status !== 'Approved'
                    || ($bpb->payment_voucher_id && $bpb->payment_voucher_id != $pv->id
                        && optional($bpb->paymentVoucher)->status !== 'Canceled');

                if ($blocked) {
                    return response()->json(['error'=>'BPB tidak tersedia untuk dipilih'],422);
                }

                $pv->purchase_order_id = $bpb->purchase_order_id;
                $pv->nominal = $bpb->grand_total ?? 0;
                $pv->save();

                $bpb->payment_voucher_id = $pv->id;
                $bpb->save();

                goto allocation_done;
            }

            // ------------------------------------------------------
            // 5) LEGACY MEMO_IDS (fallback)
            // ------------------------------------------------------
            legacy_memo_check:
            Bpb::withoutGlobalScope(DepartmentScope::class)
                ->where('payment_voucher_id',$pv->id)
                ->update(['payment_voucher_id'=>null]);

            $memoIds = (array)$request->input('memo_ids', []);
            if (!empty($memoIds) && !empty($pv->purchase_order_id)) {

                $memos = MemoPembayaran::whereIn('id',$memoIds)
                    ->where('status','Approved')
                    ->where('purchase_order_id',$pv->purchase_order_id)
                    ->get(['id','total']);

                if ($memos->count() > 0) {
                    $pv->nominal = $memos->sum(fn($m)=>(float)$m->total);
                    $pv->save();
                }
            }

            allocation_done:
        }

        // DP allocations: gunakan PV DP sebagai pemotong untuk PV Reguler ini (update)
        $dpAllocs = (array) $request->input('dp_allocations', []);
        if (!empty($dpAllocs)) {
            $dpPvIds = array_values(array_unique(array_map(
                fn($r) => (int)($r['dp_payment_voucher_id'] ?? 0),
                $dpAllocs
            )));

            $dpPvs = PaymentVoucher::withoutGlobalScope(DepartmentScope::class)
                ->whereIn('id', $dpPvIds)
                ->where('tipe_pv', 'DP')
                ->where('status', '!=', 'Canceled')
                ->get(['id','purchase_order_id','nominal','status']);

            if ($dpPvs->count() !== count($dpPvIds)) {
                return response()->json(['error' => 'Sebagian PV DP tidak valid/aktif'], 422);
            }

            $poId = (int) ($pv->purchase_order_id ?? 0);
            if ($poId <= 0) {
                return response()->json(['error' => 'PV Reguler belum memiliki Purchase Order yang terkait'], 422);
            }

            $poIdsFromDp = $dpPvs->pluck('purchase_order_id')->unique()->values();
            if ($poIdsFromDp->count() !== 1 || (int)$poIdsFromDp->first() !== $poId) {
                return response()->json(['error' => 'Semua PV DP yang dipilih harus berasal dari PO yang sama'], 422);
            }

            $usedMap = DB::table('payment_voucher_dp_allocations as a')
                ->join('payment_vouchers as pv2', 'pv2.id', '=', 'a.payment_voucher_id')
                ->whereIn('a.dp_payment_voucher_id', $dpPvIds)
                ->whereNot('pv2.status', 'Canceled')
                ->where('a.payment_voucher_id', '!=', $pv->id)
                ->selectRaw('a.dp_payment_voucher_id as dp_id, COALESCE(SUM(a.amount),0) as used')
                ->groupBy('a.dp_payment_voucher_id')
                ->get()->pluck('used', 'dp_id');

            $totalDpUse = 0.0;
            foreach ($dpAllocs as $row) {
                $dpId = (int)($row['dp_payment_voucher_id'] ?? 0);
                $amt = (float)($row['amount'] ?? 0);
                if ($dpId <= 0 || $amt < 0) {
                    return response()->json(['error' => 'Data alokasi PV DP tidak valid'], 422);
                }
                $dpPv = $dpPvs->firstWhere('id', $dpId);
                if (!$dpPv) {
                    return response()->json(['error' => 'Sebagian PV DP tidak ditemukan'], 422);
                }
                $used = (float)($usedMap[$dpId] ?? 0);
                $outstanding = max(0.0, (float)($dpPv->nominal ?? 0) - $used);
                if ($amt - $outstanding > 0.00001) {
                    return response()->json(['error' => "Alokasi PV DP #$dpId melebihi outstanding"], 422);
                }
                $totalDpUse += $amt;
            }

            $pvNominal = (float)($pv->nominal ?? 0);
            if ($totalDpUse - $pvNominal > 0.00001) {
                return response()->json(['error' => 'Total pemakaian PV DP melebihi nilai PV Reguler'], 422);
            }

            PaymentVoucherDpAllocation::where('payment_voucher_id', $pv->id)->delete();
            foreach ($dpAllocs as $row) {
                PaymentVoucherDpAllocation::create([
                    'payment_voucher_id' => $pv->id,
                    'dp_payment_voucher_id' => (int)$row['dp_payment_voucher_id'],
                    'amount' => (float)$row['amount'],
                ]);
            }
        } else {
            PaymentVoucherDpAllocation::where('payment_voucher_id', $pv->id)->delete();
        }

        // Log draft save or update (avoid duplicate logs when nothing changed)
        if ($request->boolean('save_as_draft')) {
            $pv->logs()->create([
                'user_id' => $user->id,
                'action' => 'saved_draft',
                'note' => 'Draft disimpan',
            ]);
        } elseif ($pv->wasChanged()) {
            $pv->logs()->create([
                'user_id' => $user->id,
                'action' => 'updated',
                'note' => 'Payment Voucher diperbarui',
            ]);
        }

        return response()->json(['success' => true, 'id' => $pv->id]);
    }

    /**
     * Store draft Payment Voucher and return JSON id (for Create page flow).
     */
    public function storeDraft(Request $request)
    {
        $user = Auth::user();

        $normalized = $request->all();
        if (array_key_exists('po_anggaran_id', $normalized) && $normalized['po_anggaran_id'] === '') {
            $normalized['po_anggaran_id'] = null;
        }
        $request->merge($normalized);

        $data = $request->validate([
            'tipe_pv' => 'nullable|string|in:Reguler,Anggaran,Lainnya,Pajak,Manual,DP',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'bank_supplier_account_id' => 'nullable|integer|exists:bank_supplier_accounts,id',
            // derived from supplier relation
            'department_id' => 'nullable|integer|exists:departments,id',
            'perihal_id' => 'nullable|integer|exists:perihals,id',
            'nominal' => 'nullable|numeric|decimal:0,5',
            'currency' => 'nullable|string|in:IDR,USD,EUR',
            // 'metode_bayar' => 'nullable|string|in:Transfer,Cek/Giro,Kartu Kredit',
            'credit_card_id' => 'nullable|integer|exists:credit_cards,id',
            'no_giro' => 'nullable|string',
            'tanggal_giro' => 'nullable|date',
            'tanggal_cair' => 'nullable|date',
            'note' => 'nullable|string',
            'keterangan' => 'nullable|string',
            // derived from supplier bank account / credit card relations
            'purchase_order_id' => 'nullable|integer|exists:purchase_orders,id',
            'memo_pembayaran_id' => 'nullable|integer|exists:memo_pembayarans,id',
            'po_anggaran_id' => 'nullable|integer|exists:po_anggarans,id',
            // BPB linkage (optional)
            'bpb_id' => 'nullable|integer|exists:bpbs,id',
            // New partial allocations (preferred over legacy fields)
            'bpb_allocations' => 'nullable|array',
            'bpb_allocations.*.bpb_id' => 'required|integer|distinct|exists:bpbs,id',
            'bpb_allocations.*.amount' => 'required|numeric|decimal:0,5|min:0',
            'memo_allocations' => 'nullable|array',
            'memo_allocations.*.memo_id' => 'required|integer|distinct|exists:memo_pembayarans,id',
            'memo_allocations.*.amount' => 'required|numeric|decimal:0,5|min:0',
            // Manual fields (accept either manual_* or UI aliases for backward-compat)
            'manual_supplier' => 'nullable|string',
            'manual_no_telepon' => 'nullable|string',
            'manual_alamat' => 'nullable|string',
            'manual_nama_bank' => 'nullable|string',
            'manual_nama_pemilik_rekening' => 'nullable|string',
            'manual_no_rekening' => 'nullable|string',
            'supplier_name' => 'nullable|string',
            'supplier_phone' => 'nullable|string',
            'supplier_address' => 'nullable|string',
            'supplier_bank_name' => 'nullable|string',
            'supplier_account_name' => 'nullable|string',
            'supplier_account_number' => 'nullable|string',
            // Custom flags
            'kelengkapan_dokumen' => 'nullable|boolean',
        ]);

        // Default department to current user's first department if not provided
        if (empty($data['department_id'])) {
            $data['department_id'] = $user->departments->first()->id ?? null;
        }

        // Map UI aliases -> manual_* when tipe_pv = Manual
        if (in_array(($data['tipe_pv'] ?? null), ['Manual','Pajak'], true)) {
            $data['purchase_order_id'] = null;
            $data['memo_pembayaran_id'] = null;
            $data['manual_supplier'] = $data['manual_supplier'] ?? ($data['supplier_name'] ?? null);
            $data['manual_no_telepon'] = $data['manual_no_telepon'] ?? ($data['supplier_phone'] ?? null);
            $data['manual_alamat'] = $data['manual_alamat'] ?? ($data['supplier_address'] ?? null);
            $data['manual_nama_bank'] = $data['manual_nama_bank'] ?? ($data['supplier_bank_name'] ?? null);
            $data['manual_nama_pemilik_rekening'] = $data['manual_nama_pemilik_rekening'] ?? ($data['supplier_account_name'] ?? null);
            $data['manual_no_rekening'] = $data['manual_no_rekening'] ?? ($data['supplier_account_number'] ?? null);
        } elseif (($data['tipe_pv'] ?? null) === 'Lainnya') {
            // Lainnya uses Memo Pembayaran, ensure PO cleared
            $data['purchase_order_id'] = null;
        } elseif (($data['tipe_pv'] ?? null) === 'Anggaran') {
            // Anggaran uses Po Anggaran, ensure PO/Memo cleared and map fields
            $data['purchase_order_id'] = null;
            $data['memo_pembayaran_id'] = null;
            if (!empty($data['po_anggaran_id'])) {
                $poa = PoAnggaran::with(['bisnisPartner','bank'])->find($data['po_anggaran_id']);
                if ($poa) {
                    $data['department_id'] = $data['department_id'] ?? $poa->department_id;
                    $data['perihal_id'] = $data['perihal_id'] ?? $poa->perihal_id;
                    $data['nominal'] = $poa->nominal;
                    $data['manual_supplier'] = $data['manual_supplier'] ?? ($poa->bisnisPartner->nama_bp ?? null);
                    $data['manual_no_telepon'] = $data['manual_no_telepon'] ?? ($poa->bisnisPartner->no_telepon ?? null);
                    $data['manual_alamat'] = $data['manual_alamat'] ?? ($poa->bisnisPartner->alamat ?? null);
                    $data['manual_nama_bank'] = $data['manual_nama_bank'] ?? ($poa->bank?->nama_bank ?? null);
                    $data['manual_nama_pemilik_rekening'] = $data['manual_nama_pemilik_rekening'] ?? ($poa->nama_rekening ?? null);
                    $data['manual_no_rekening'] = $data['manual_no_rekening'] ?? ($poa->no_rekening ?? null);
                }
            }
        } elseif (($data['tipe_pv'] ?? null) === 'DP') {
            // DP uses PO with DP setting; ensure Memo/Po Anggaran cleared
            $data['memo_pembayaran_id'] = null;
            $data['po_anggaran_id'] = null;
            $poId = $data['purchase_order_id'] ?? null;
            if (empty($poId)) {
                return response()->json(['error' => 'Purchase Order wajib diisi untuk PV DP'], 422);
            }
            $po = PurchaseOrder::withoutGlobalScope(DepartmentScope::class)
                ->select(['id','status','dp_active','dp_nominal'])
                ->find($poId);
            if (!$po || !$po->dp_active || ($po->dp_nominal ?? 0) <= 0 || $po->status !== 'Approved') {
                return response()->json(['error' => 'PO tidak valid sebagai PO DP (harus Approved dan memiliki DP aktif)'], 422);
            }
            // Pastikan belum ada BPB atas PO tersebut (BPB non-canceled)
            $hasBpb = Bpb::withoutGlobalScope(DepartmentScope::class)
                ->where('purchase_order_id', $poId)
                ->whereNull('deleted_at')
                ->whereNot('status', 'Canceled')
                ->exists();
            if ($hasBpb) {
                return response()->json(['error' => 'PO DP yang sudah memiliki BPB tidak dapat digunakan untuk PV DP'], 422);
            }

            // Hitung outstanding DP pada PO ini
            $usedDp = PaymentVoucher::withoutGlobalScope(DepartmentScope::class)
                ->where('tipe_pv', 'DP')
                ->where('purchase_order_id', $poId)
                ->where('status', '!=', 'Canceled')
                ->sum('nominal');
            $maxDp = max(0.0, (float)($po->dp_nominal ?? 0) - (float)$usedDp);
            $requestedNominal = (float)($data['nominal'] ?? 0);
            if ($requestedNominal <= 0) {
                return response()->json(['error' => 'Nominal PV DP harus lebih besar dari 0'], 422);
            }
            if ($requestedNominal - $maxDp > 0.00001) {
                return response()->json(['error' => 'Nominal PV DP tidak boleh melebihi sisa DP pada PO'], 422);
            }
            $data['nominal'] = $requestedNominal;
        } elseif (!empty($data['tipe_pv'])) {
            // Non-manual, non-lainnya uses PO; ensure memo cleared
            $data['memo_pembayaran_id'] = null;
        }

        // If tipe Lainnya and memo selected, set bank account and nominal from memo (draft creation)
        if ((($data['tipe_pv'] ?? null) === 'Lainnya') && !empty($data['memo_pembayaran_id'])) {
            $memo = \App\Models\MemoPembayaran::select('id','total','bank_supplier_account_id')->find($data['memo_pembayaran_id']);
            if ($memo) {
                $data['bank_supplier_account_id'] = $memo->bank_supplier_account_id;
                // Business rule: PV nominal should reflect Memo's total (grand total), not cicilan
                $data['nominal'] = $memo->total ?? 0;
            }
        }

        // For Reguler PV tied to a PO with active DP, require at least one non-canceled DP PV already exists
        if ((($data['tipe_pv'] ?? null) === 'Reguler') && !empty($data['purchase_order_id'])) {
            $po = PurchaseOrder::withoutGlobalScope(DepartmentScope::class)
                ->select(['id','dp_active','dp_nominal'])
                ->find($data['purchase_order_id']);
            if ($po && $po->dp_active && (float) ($po->dp_nominal ?? 0) > 0) {
                $hasDpPv = PaymentVoucher::withoutGlobalScope(DepartmentScope::class)
                    ->where('purchase_order_id', $po->id)
                    ->where('tipe_pv', 'DP')
                    ->where('status', '!=', 'Canceled')
                    ->exists();
                if (! $hasDpPv) {
                    return response()->json([
                        'error' => 'PO ini memiliki DP aktif. Buat PV dengan tipe DP terlebih dahulu sebelum membuat PV Reguler.'
                    ], 422);
                }
            }
        }

        // Normalize fields according to metode_bayar
        // $effectiveMetode = $data['metode_bayar'] ?? null;
        // if ($effectiveMetode === 'Kartu Kredit') {
        //     $data['supplier_id'] = null;
        // } elseif ($effectiveMetode === 'Transfer') {
        //     $data['credit_card_id'] = null;
        // }

        $pv = new PaymentVoucher();
        $pv->fill($data);
        $pv->status = 'Draft';
        $pv->creator_id = $user->id;
        $pv->save();

        // Reguler: persist allocations if provided, else fallback to legacy links
        if ((($data['tipe_pv'] ?? null) === 'Reguler')) {
            $allocs = (array) $request->input('bpb_allocations', []);
            $memoAllocs = (array) $request->input('memo_allocations', []);
            if (!empty($allocs)) {
                $bpbIds = array_values(array_unique(array_map(fn($r)=> (int)($r['bpb_id'] ?? 0), $allocs)));
                $bpbs = Bpb::withoutGlobalScope(DepartmentScope::class)
                    ->whereIn('id', $bpbIds)
                    ->where('status', 'Approved')
                    ->get(['id','purchase_order_id','grand_total']);
                if ($bpbs->count() !== count($bpbIds)) {
                    return response()->json(['error' => 'Sebagian BPB tidak valid/Approved'], 422);
                }
                $poIds = $bpbs->pluck('purchase_order_id')->unique()->values();
                if ($poIds->count() !== 1) {
                    return response()->json(['error' => 'Alokasi BPB harus dari PO yang sama'], 422);
                }
                $poId = (int)$poIds->first();
                $usedMap = DB::table('payment_voucher_bpb_allocations as a')
                    ->join('payment_vouchers as pv2','pv2.id','=','a.payment_voucher_id')
                    ->whereIn('a.bpb_id', $bpbIds)
                    ->whereNot('pv2.status','Canceled')
                    ->selectRaw('a.bpb_id, COALESCE(SUM(a.amount),0) as used')
                    ->groupBy('a.bpb_id')->get()->pluck('used','bpb_id');
                $sum = 0;
                foreach ($allocs as $row) {
                    $bId = (int)$row['bpb_id'];
                    $amt = (float)$row['amount'];
                    $b = $bpbs->firstWhere('id', $bId);
                    $out = max(0.0, (float)($b->grand_total ?? 0) - (float)($usedMap[$bId] ?? 0));
                    if ($amt - $out > 0.00001) {
                        return response()->json(['error' => "Alokasi BPB #$bId melebihi outstanding"], 422);
                    }
                    $sum += $amt;
                }
                foreach ($allocs as $row) {
                    \App\Models\PaymentVoucherBpbAllocation::create([
                        'payment_voucher_id' => $pv->id,
                        'bpb_id' => (int)$row['bpb_id'],
                        'amount' => (float)$row['amount'],
                    ]);
                }
                $pv->purchase_order_id = $poId;
                $pv->nominal = $sum;
                $pv->save();
            } elseif (!empty($memoAllocs)) {
                $memoIds = array_values(array_unique(array_map(fn($r)=> (int)($r['memo_id'] ?? 0), $memoAllocs)));
                $memos = \App\Models\MemoPembayaran::query()
                    ->whereIn('id', $memoIds)
                    ->where('status','Approved')
                    ->get(['id','purchase_order_id','total']);
                if ($memos->count() !== count($memoIds)) {
                    return response()->json(['error' => 'Sebagian Memo tidak valid/Approved'], 422);
                }
                $poIds = $memos->pluck('purchase_order_id')->unique()->values();
                if ($poIds->count() !== 1) {
                    return response()->json(['error' => 'Alokasi Memo harus dari PO yang sama'], 422);
                }
                $poId = (int)$poIds->first();
                $usedMap = DB::table('payment_voucher_memo_allocations as a')
                    ->join('payment_vouchers as pv2','pv2.id','=','a.payment_voucher_id')
                    ->whereIn('a.memo_pembayaran_id', $memoIds)
                    ->whereNot('pv2.status','Canceled')
                    ->selectRaw('a.memo_pembayaran_id as mid, COALESCE(SUM(a.amount),0) as used')
                    ->groupBy('a.memo_pembayaran_id')->get()->pluck('used','mid');
                $sum = 0;
                foreach ($memoAllocs as $row) {
                    $mId = (int)$row['memo_id'];
                    $amt = (float)$row['amount'];
                    $mm = $memos->firstWhere('id', $mId);
                    $out = max(0.0, (float)($mm->total ?? 0) - (float)($usedMap[$mId] ?? 0));
                    if ($amt - $out > 0.00001) {
                        return response()->json(['error' => "Alokasi Memo #$mId melebihi outstanding"], 422);
                    }
                    $sum += $amt;
                }
                foreach ($memoAllocs as $row) {
                    \App\Models\PaymentVoucherMemoAllocation::create([
                        'payment_voucher_id' => $pv->id,
                        'memo_pembayaran_id' => (int)$row['memo_id'],
                        'amount' => (float)$row['amount'],
                    ]);
                }
                $pv->purchase_order_id = $poId;
                $pv->nominal = max(0.0, (float)($data['nominal'] ?? 0));
                if ($pv->nominal - $sum > 0.00001) $pv->nominal = $sum;
                $pv->save();
            } else {
                // Legacy link fallback
                $bpbIds = (array) $request->input('bpb_ids', []);
                if (!empty($bpbIds)) {
                    $bpbs = Bpb::withoutGlobalScope(DepartmentScope::class)
                        ->with(['paymentVoucher'])
                        ->whereIn('id', $bpbIds)
                        ->get();
                    if ($bpbs->count() !== count(array_unique($bpbIds))) {
                        return response()->json(['error' => 'Sebagian BPB tidak ditemukan'], 422);
                    }
                    foreach ($bpbs as $b) {
                        $blocked = $b->status !== 'Approved'
                            || ($b->payment_voucher_id && optional($b->paymentVoucher)->status !== 'Canceled');
                        if ($blocked) {
                            return response()->json(['error' => 'BPB tidak tersedia untuk dipilih'], 422);
                        }
                    }
                    $poIds = $bpbs->pluck('purchase_order_id')->unique()->values();
                    if ($poIds->count() !== 1) {
                        return response()->json(['error' => 'Semua BPB yang dipilih harus berasal dari PO yang sama'], 422);
                    }
                    Bpb::withoutGlobalScope(DepartmentScope::class)
                        ->whereIn('id', $bpbIds)
                        ->update(['payment_voucher_id' => $pv->id]);
                    $pv->purchase_order_id = $poIds->first();
                    $pv->nominal = (float) $bpbs->sum(function($b){ return (float) ($b->grand_total ?? 0); });
                    $pv->save();
                } elseif (!empty($request->input('bpb_id'))) {
                    $bpb = Bpb::withoutGlobalScope(DepartmentScope::class)
                        ->with(['paymentVoucher'])
                        ->find($request->input('bpb_id'));
                    if ($bpb) {
                        $blocked = $bpb->status !== 'Approved'
                            || ($bpb->payment_voucher_id && optional($bpb->paymentVoucher)->status !== 'Canceled');
                        if ($blocked) {
                            return response()->json(['error' => 'BPB tidak tersedia untuk dipilih'], 422);
                        }
                        $bpb->payment_voucher_id = $pv->id;
                        $bpb->save();
                        $pv->purchase_order_id = $bpb->purchase_order_id;
                        $pv->nominal = $bpb->grand_total ?? 0;
                        $pv->save();
                    }
                }
            }

            // DP allocations: gunakan PV DP sebagai pemotong untuk PV Reguler ini (draft)
        $dpAllocs = (array) $request->input('dp_allocations', []);
        if (!empty($dpAllocs)) {
            $dpPvIds = array_values(array_unique(array_map(
                fn($r) => (int)($r['dp_payment_voucher_id'] ?? 0),
                $dpAllocs
            )));

            $dpPvs = PaymentVoucher::withoutGlobalScope(DepartmentScope::class)
                ->whereIn('id', $dpPvIds)
                ->where('tipe_pv', 'DP')
                ->where('status', '!=', 'Canceled')
                ->get(['id','purchase_order_id','nominal','status']);

            if ($dpPvs->count() !== count($dpPvIds)) {
                return response()->json(['error' => 'Sebagian PV DP tidak valid/aktif'], 422);
            }

            $poId = (int) ($pv->purchase_order_id ?? 0);
            if ($poId <= 0) {
                return response()->json(['error' => 'PV Reguler belum memiliki Purchase Order yang terkait'], 422);
            }

            $poIdsFromDp = $dpPvs->pluck('purchase_order_id')->unique()->values();
            if ($poIdsFromDp->count() !== 1 || (int)$poIdsFromDp->first() !== $poId) {
                return response()->json(['error' => 'Semua PV DP yang dipilih harus berasal dari PO yang sama'], 422);
            }

            $usedMap = DB::table('payment_voucher_dp_allocations as a')
                ->join('payment_vouchers as pv2', 'pv2.id', '=', 'a.payment_voucher_id')
                ->whereIn('a.dp_payment_voucher_id', $dpPvIds)
                ->whereNot('pv2.status', 'Canceled')
                ->selectRaw('a.dp_payment_voucher_id as dp_id, COALESCE(SUM(a.amount),0) as used')
                ->groupBy('a.dp_payment_voucher_id')
                ->get()->pluck('used', 'dp_id');

            $totalDpUse = 0.0;
            foreach ($dpAllocs as $row) {
                $dpId = (int)($row['dp_payment_voucher_id'] ?? 0);
                $amt = (float)($row['amount'] ?? 0);
                if ($dpId <= 0 || $amt < 0) {
                    return response()->json(['error' => 'Data alokasi PV DP tidak valid'], 422);
                }
                $dpPv = $dpPvs->firstWhere('id', $dpId);
                if (!$dpPv) {
                    return response()->json(['error' => 'Sebagian PV DP tidak ditemukan'], 422);
                }
                $used = (float)($usedMap[$dpId] ?? 0);
                $outstanding = max(0.0, (float)($dpPv->nominal ?? 0) - $used);
                if ($amt - $outstanding > 0.00001) {
                    return response()->json(['error' => "Alokasi PV DP #$dpId melebihi outstanding"], 422);
                }
                $totalDpUse += $amt;
            }

            $pvNominal = (float)($pv->nominal ?? 0);
            if ($totalDpUse - $pvNominal > 0.00001) {
                return response()->json(['error' => 'Total pemakaian PV DP melebihi nilai PV Reguler'], 422);
            }

            PaymentVoucherDpAllocation::where('payment_voucher_id', $pv->id)->delete();
            foreach ($dpAllocs as $row) {
                PaymentVoucherDpAllocation::create([
                    'payment_voucher_id' => $pv->id,
                    'dp_payment_voucher_id' => (int)$row['dp_payment_voucher_id'],
                    'amount' => (float)$row['amount'],
                ]);
            }
        } else {
            PaymentVoucherDpAllocation::where('payment_voucher_id', $pv->id)->delete();
        }
                }
        // Log draft creation
        $pv->logs()->create([
            'user_id' => $user->id,
            'action' => 'saved_draft',
            'note' => 'Draft dibuat',
        ]);

        return response()->json(['id' => $pv->id]);
    }

    /**
     * Send selected drafts -> assign no_pv and tanggal, set status to In Progress
     */
    public function send(Request $request)
    {
        $user = Auth::user();
        $now = Carbon::now();

        // Support two flows:
        // 1) Existing drafts provided via ids[]
        // 2) No ids, but a payload is provided to create+send in one step
        $pvs = collect();
        $createdPvId = null;
        if ($request->has('ids')) {
            $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer',
            ]);
            $pvs = PaymentVoucher::whereIn('id', $request->ids)
                ->whereIn('status', ['Draft', 'Rejected', 'Approved'])
                ->orderBy('created_at', 'asc')
                ->get();
            // Optional: persist active document checklist passed from client prior to validation
            try {
                $actives = (array) ($request->input('documents_active') ?? []);
                if (!empty($actives)) {
                    $standardTypes = ['bukti_transfer_bca','bukti_input_bca','invoice','surat_jalan','efaktur'];
                    $toActivate = array_values(array_intersect($standardTypes, $actives));
                    foreach ($pvs as $pvRow) {
                        foreach ($toActivate as $t) {
                            $doc = PaymentVoucherDocument::firstOrNew([
                                'payment_voucher_id' => $pvRow->id,
                                'type' => $t,
                            ]);
                            $doc->active = true;
                            $doc->save();
                        }
                    }
                }
            } catch (\Throwable $e) {}
        } elseif ($request->has('payload')) {
            // Validate like storeDraft and map fields
            $data = validator($request->input('payload') ?? [], [
                'tipe_pv' => 'nullable|string|in:Reguler,Anggaran,Lainnya,Pajak,Manual,DP',
                'supplier_id' => 'nullable|integer|exists:suppliers,id',
                'bank_supplier_account_id' => 'nullable|integer|exists:bank_supplier_accounts,id',
                'department_id' => 'nullable|integer|exists:departments,id',
                'perihal_id' => 'nullable|integer|exists:perihals,id',
                'nominal' => 'nullable|numeric|decimal:0,5',
                'currency' => 'nullable|string|in:IDR,USD,EUR',
                // 'metode_bayar' => 'nullable|string|in:Transfer,Cek/Giro,Kartu Kredit',
                'credit_card_id' => 'nullable|integer|exists:credit_cards,id',
                'no_giro' => 'nullable|string',
                'tanggal_giro' => 'nullable|date',
                'tanggal_cair' => 'nullable|date',
                'note' => 'nullable|string',
                'keterangan' => 'nullable|string',
                'purchase_order_id' => 'nullable|integer|exists:purchase_orders,id',
                'memo_pembayaran_id' => 'nullable|integer|exists:memo_pembayarans,id',
                'po_anggaran_id' => 'nullable|integer|exists:po_anggarans,id',
                'manual_supplier' => 'nullable|string',
                'manual_no_telepon' => 'nullable|string',
                'manual_alamat' => 'nullable|string',
                'manual_nama_bank' => 'nullable|string',
                'manual_nama_pemilik_rekening' => 'nullable|string',
                'manual_no_rekening' => 'nullable|string',
                'supplier_name' => 'nullable|string',
                'supplier_phone' => 'nullable|string',
                'supplier_address' => 'nullable|string',
                'supplier_bank_name' => 'nullable|string',
                'supplier_account_name' => 'nullable|string',
                'supplier_account_number' => 'nullable|string',
                'kelengkapan_dokumen' => 'nullable|boolean',
            ])->validate();

            // Default department if missing
            if (empty($data['department_id'])) {
                $data['department_id'] = $user->departments->first()->id ?? null;
            }

            // Map alias fields per tipe_pv like storeDraft
            if (in_array(($data['tipe_pv'] ?? null), ['Manual','Pajak'], true)) {
                $data['purchase_order_id'] = null;
                $data['memo_pembayaran_id'] = null;
                $data['manual_supplier'] = $data['manual_supplier'] ?? ($data['supplier_name'] ?? null);
                $data['manual_no_telepon'] = $data['manual_no_telepon'] ?? ($data['supplier_phone'] ?? null);
                $data['manual_alamat'] = $data['manual_alamat'] ?? ($data['supplier_address'] ?? null);
                $data['manual_nama_bank'] = $data['manual_nama_bank'] ?? ($data['supplier_bank_name'] ?? null);
                $data['manual_nama_pemilik_rekening'] = $data['manual_nama_pemilik_rekening'] ?? ($data['supplier_account_name'] ?? null);
                $data['manual_no_rekening'] = $data['manual_no_rekening'] ?? ($data['supplier_account_number'] ?? null);
            } elseif (($data['tipe_pv'] ?? null) === 'Lainnya') {
                // Lainnya uses Memo Pembayaran, ensure PO cleared
                $data['purchase_order_id'] = null;
            } elseif (($data['tipe_pv'] ?? null) === 'Anggaran') {
                // Anggaran uses Po Anggaran, ensure PO/Memo cleared and map fields
                $data['purchase_order_id'] = null;
                $data['memo_pembayaran_id'] = null;
                if (!empty($data['po_anggaran_id'])) {
                    $poa = PoAnggaran::with(['bisnisPartner','bank'])->find($data['po_anggaran_id']);
                    if ($poa) {
                        $data['department_id'] = $data['department_id'] ?? $poa->department_id;
                        $data['perihal_id'] = $data['perihal_id'] ?? $poa->perihal_id;
                        $data['nominal'] = $poa->nominal;
                        $data['manual_supplier'] = $data['manual_supplier'] ?? ($poa->bisnisPartner->nama_bp ?? null);
                        $data['manual_no_telepon'] = $data['manual_no_telepon'] ?? ($poa->bisnisPartner->no_telepon ?? null);
                        $data['manual_alamat'] = $data['manual_alamat'] ?? ($poa->bisnisPartner->alamat ?? null);
                        $data['manual_nama_bank'] = $data['manual_nama_bank'] ?? ($poa->bank?->nama_bank ?? null);
                        $data['manual_nama_pemilik_rekening'] = $data['manual_nama_pemilik_rekening'] ?? ($poa->nama_rekening ?? null);
                        $data['manual_no_rekening'] = $data['manual_no_rekening'] ?? ($poa->no_rekening ?? null);
                    }
                }
            } elseif (($data['tipe_pv'] ?? null) === 'DP') {
                // DP uses PO with DP setting; ensure Memo/Po Anggaran cleared
                $data['memo_pembayaran_id'] = null;
                $data['po_anggaran_id'] = null;
                $poId = $data['purchase_order_id'] ?? null;
                if (empty($poId)) {
                    return response()->json(['error' => 'Purchase Order wajib diisi untuk PV DP'], 422);
                }
                $po = PurchaseOrder::withoutGlobalScope(DepartmentScope::class)
                    ->select(['id','status','dp_active','dp_nominal'])
                    ->find($poId);
                if (!$po || !$po->dp_active || ($po->dp_nominal ?? 0) <= 0 || $po->status !== 'Approved') {
                    return response()->json(['error' => 'PO tidak valid sebagai PO DP (harus Approved dan memiliki DP aktif)'], 422);
                }
                // Pastikan belum ada BPB atas PO tersebut (BPB non-canceled)
                $hasBpb = Bpb::withoutGlobalScope(DepartmentScope::class)
                    ->where('purchase_order_id', $poId)
                    ->whereNull('deleted_at')
                    ->whereNot('status', 'Canceled')
                    ->exists();
                if ($hasBpb) {
                    return response()->json(['error' => 'PO DP yang sudah memiliki BPB tidak dapat digunakan untuk PV DP'], 422);
                }

                // Hitung outstanding DP pada PO ini
                $usedDp = PaymentVoucher::withoutGlobalScope(DepartmentScope::class)
                    ->where('tipe_pv', 'DP')
                    ->where('purchase_order_id', $poId)
                    ->where('status', '!=', 'Canceled')
                    ->sum('nominal');
                $maxDp = max(0.0, (float)($po->dp_nominal ?? 0) - (float)$usedDp);
                $requestedNominal = (float)($data['nominal'] ?? 0);
                if ($requestedNominal <= 0) {
                    return response()->json(['error' => 'Nominal PV DP harus lebih besar dari 0'], 422);
                }
                if ($requestedNominal - $maxDp > 0.00001) {
                    return response()->json(['error' => 'Nominal PV DP tidak boleh melebihi sisa DP pada PO'], 422);
                }
                $data['nominal'] = $requestedNominal;
            } elseif (!empty($data['tipe_pv'])) {
                // Non-manual, non-lainnya uses PO; ensure memo cleared
                $data['memo_pembayaran_id'] = null;
            }

            // If tipe Lainnya and memo selected in payload flow, set bank account and nominal from memo
            if ((($data['tipe_pv'] ?? null) === 'Lainnya') && !empty($data['memo_pembayaran_id'])) {
                $memo = \App\Models\MemoPembayaran::select('id','total','bank_supplier_account_id')->find($data['memo_pembayaran_id']);
                if ($memo) {
                    $data['bank_supplier_account_id'] = $memo->bank_supplier_account_id;
                    $data['nominal'] = $memo->total ?? 0;
                }
            }

            // For Reguler PV tied to a PO with active DP, require at least one non-canceled DP PV already exists
            if ((($data['tipe_pv'] ?? null) === 'Reguler') && !empty($data['purchase_order_id'])) {
                $po = PurchaseOrder::withoutGlobalScope(DepartmentScope::class)
                    ->select(['id','dp_active','dp_nominal'])
                    ->find($data['purchase_order_id']);
                if ($po && $po->dp_active && (float) ($po->dp_nominal ?? 0) > 0) {
                    $hasDpPv = PaymentVoucher::withoutGlobalScope(DepartmentScope::class)
                        ->where('purchase_order_id', $po->id)
                        ->where('tipe_pv', 'DP')
                        ->where('status', '!=', 'Canceled')
                        ->exists();
                    if (! $hasDpPv) {
                        return response()->json([
                            'error' => 'PO ini memiliki DP aktif. Buat PV dengan tipe DP terlebih dahulu sebelum membuat PV Reguler.'
                        ], 422);
                    }
                }
            }

            // Create PV and push to collection
            $pv = new PaymentVoucher();
            $pv->fill($data);
            $pv->status = 'Draft';
            $pv->creator_id = $user->id;
            $pv->save();
            // Persist active document checklist (excluding 'lainnya') so validation can enforce uploads
            try {
                $actives = (array) ($request->input('payload.documents_active') ?? []);
                if (!empty($actives)) {
                    $standardTypes = ['bukti_transfer_bca','bukti_input_bca','invoice','surat_jalan','efaktur'];
                    $toActivate = array_values(array_intersect($standardTypes, $actives));
                    foreach ($toActivate as $t) {
                        $doc = PaymentVoucherDocument::firstOrNew([
                            'payment_voucher_id' => $pv->id,
                            'type' => $t,
                        ]);
                        $doc->active = true;
                        $doc->save();
                    }
                }
            } catch (\Throwable $e) {
                // ignore; validation still runs on whatever rows exist
            }
            // Handle allocations in payload (preferred for Reguler)
            if ((($data['tipe_pv'] ?? null) === 'Reguler')) {
                $allocs = (array) ($request->input('payload.bpb_allocations') ?? []);
                $memoAllocs = (array) ($request->input('payload.memo_allocations') ?? []);
                if (!empty($allocs)) {
                    $bpbIds = array_values(array_unique(array_map(fn($r)=> (int)($r['bpb_id'] ?? 0), $allocs)));
                    $bpbs = Bpb::withoutGlobalScope(DepartmentScope::class)
                        ->whereIn('id', $bpbIds)
                        ->where('status', 'Approved')
                        ->get(['id','purchase_order_id','grand_total']);
                    if ($bpbs->count() !== count($bpbIds)) {
                        return response()->json(['error' => 'Sebagian BPB tidak valid/Approved'], 422);
                    }
                    $poIds = $bpbs->pluck('purchase_order_id')->unique()->values();
                    if ($poIds->count() !== 1) {
                        return response()->json(['error' => 'Alokasi BPB harus dari PO yang sama'], 422);
                    }
                    $poId = (int)$poIds->first();
                    $usedMap = DB::table('payment_voucher_bpb_allocations as a')
                        ->join('payment_vouchers as pv2','pv2.id','=','a.payment_voucher_id')
                        ->whereIn('a.bpb_id', $bpbIds)
                        ->whereNot('pv2.status','Canceled')
                        ->selectRaw('a.bpb_id, COALESCE(SUM(a.amount),0) as used')
                        ->groupBy('a.bpb_id')->get()->pluck('used','bpb_id');
                    $sum = 0;
                    foreach ($allocs as $row) {
                        $bId = (int)$row['bpb_id'];
                        $amt = (float)$row['amount'];
                        $b = $bpbs->firstWhere('id', $bId);
                        $out = max(0.0, (float)($b->grand_total ?? 0) - (float)($usedMap[$bId] ?? 0));
                        if ($amt - $out > 0.00001) {
                            return response()->json(['error' => "Alokasi BPB #$bId melebihi outstanding"], 422);
                        }
                        $sum += $amt;
                    }
                    foreach ($allocs as $row) {
                        \App\Models\PaymentVoucherBpbAllocation::create([
                            'payment_voucher_id' => $pv->id,
                            'bpb_id' => (int)$row['bpb_id'],
                            'amount' => (float)$row['amount'],
                        ]);
                    }
                    $pv->purchase_order_id = $poId;
                    $pv->nominal = $sum;
                    $pv->save();
                } elseif (!empty($memoAllocs)) {
                    $memoIds = array_values(array_unique(array_map(fn($r)=> (int)($r['memo_id'] ?? 0), $memoAllocs)));
                    $memos = \App\Models\MemoPembayaran::query()
                        ->whereIn('id', $memoIds)
                        ->where('status','Approved')
                        ->get(['id','purchase_order_id','total']);
                    if ($memos->count() !== count($memoIds)) {
                        return response()->json(['error' => 'Sebagian Memo tidak valid/Approved'], 422);
                    }
                    $poIds = $memos->pluck('purchase_order_id')->unique()->values();
                    if ($poIds->count() !== 1) {
                        return response()->json(['error' => 'Alokasi Memo harus dari PO yang sama'], 422);
                    }
                    $poId = (int)$poIds->first();
                    $usedMap = DB::table('payment_voucher_memo_allocations as a')
                        ->join('payment_vouchers as pv2','pv2.id','=','a.payment_voucher_id')
                        ->whereIn('a.memo_pembayaran_id', $memoIds)
                        ->whereNot('pv2.status','Canceled')
                        ->selectRaw('a.memo_pembayaran_id as mid, COALESCE(SUM(a.amount),0) as used')
                        ->groupBy('a.memo_pembayaran_id')->get()->pluck('used','mid');
                    $sum = 0;
                    foreach ($memoAllocs as $row) {
                        $mId = (int)$row['memo_id'];
                        $amt = (float)$row['amount'];
                        $mm = $memos->firstWhere('id', $mId);
                        $out = max(0.0, (float)($mm->total ?? 0) - (float)($usedMap[$mId] ?? 0));
                        if ($amt - $out > 0.00001) {
                            return response()->json(['error' => "Alokasi Memo #$mId melebihi outstanding"], 422);
                        }
                        $sum += $amt;
                    }
                    foreach ($memoAllocs as $row) {
                        \App\Models\PaymentVoucherMemoAllocation::create([
                            'payment_voucher_id' => $pv->id,
                            'memo_pembayaran_id' => (int)$row['memo_id'],
                            'amount' => (float)$row['amount'],
                        ]);
                    }
                    $pv->purchase_order_id = $poId;
                    $pv->nominal = max(0.0, (float)($data['nominal'] ?? 0));
                    if ($pv->nominal - $sum > 0.00001) $pv->nominal = $sum;
                    $pv->save();
                }

                // DP allocations: gunakan PV DP sebagai pemotong untuk PV Reguler ini (payload create+send)
                $dpAllocs = (array) ($request->input('payload.dp_allocations') ?? []);
                if (!empty($dpAllocs)) {
                    $dpPvIds = array_values(array_unique(array_map(
                        fn($r) => (int)($r['dp_payment_voucher_id'] ?? 0),
                        $dpAllocs
                    )));

                    $dpPvs = PaymentVoucher::withoutGlobalScope(DepartmentScope::class)
                        ->whereIn('id', $dpPvIds)
                        ->where('tipe_pv', 'DP')
                        ->where('status', '!=', 'Canceled')
                        ->get(['id','purchase_order_id','nominal','status']);

                    if ($dpPvs->count() !== count($dpPvIds)) {
                        return response()->json(['error' => 'Sebagian PV DP tidak valid/aktif'], 422);
                    }

                    $poId = (int) ($pv->purchase_order_id ?? 0);
                    if ($poId <= 0) {
                        return response()->json(['error' => 'PV Reguler belum memiliki Purchase Order yang terkait'], 422);
                    }

                    $poIdsFromDp = $dpPvs->pluck('purchase_order_id')->unique()->values();
                    if ($poIdsFromDp->count() !== 1 || (int)$poIdsFromDp->first() !== $poId) {
                        return response()->json(['error' => 'Semua PV DP yang dipilih harus berasal dari PO yang sama'], 422);
                    }

                    $usedMap = DB::table('payment_voucher_dp_allocations as a')
                        ->join('payment_vouchers as pv2', 'pv2.id', '=', 'a.payment_voucher_id')
                        ->whereIn('a.dp_payment_voucher_id', $dpPvIds)
                        ->whereNot('pv2.status', 'Canceled')
                        ->selectRaw('a.dp_payment_voucher_id as dp_id, COALESCE(SUM(a.amount),0) as used')
                        ->groupBy('a.dp_payment_voucher_id')
                        ->get()->pluck('used', 'dp_id');

                    $totalDpUse = 0.0;
                    foreach ($dpAllocs as $row) {
                        $dpId = (int)($row['dp_payment_voucher_id'] ?? 0);
                        $amt = (float)($row['amount'] ?? 0);
                        if ($dpId <= 0 || $amt < 0) {
                            return response()->json(['error' => 'Data alokasi PV DP tidak valid'], 422);
                        }
                        $dpPv = $dpPvs->firstWhere('id', $dpId);
                        if (!$dpPv) {
                            return response()->json(['error' => 'Sebagian PV DP tidak ditemukan'], 422);
                        }
                        $used = (float)($usedMap[$dpId] ?? 0);
                        $outstanding = max(0.0, (float)($dpPv->nominal ?? 0) - $used);
                        if ($amt - $outstanding > 0.00001) {
                            return response()->json(['error' => "Alokasi PV DP #$dpId melebihi outstanding"], 422);
                        }
                        $totalDpUse += $amt;
                    }

                    $pvNominal = (float)($pv->nominal ?? 0);
                    if ($totalDpUse - $pvNominal > 0.00001) {
                        return response()->json(['error' => 'Total pemakaian PV DP melebihi nilai PV Reguler'], 422);
                    }

                    PaymentVoucherDpAllocation::where('payment_voucher_id', $pv->id)->delete();
                    foreach ($dpAllocs as $row) {
                        PaymentVoucherDpAllocation::create([
                            'payment_voucher_id' => $pv->id,
                            'dp_payment_voucher_id' => (int)$row['dp_payment_voucher_id'],
                            'amount' => (float)$row['amount'],
                        ]);
                    }
                } else {
                    PaymentVoucherDpAllocation::where('payment_voucher_id', $pv->id)->delete();
                }
            }

            $createdPvId = $pv->id;
            $pvs = collect([$pv]);
        } else {
            return response()->json(['success' => false, 'message' => 'No IDs or payload provided'], 422);
        }

        // Validate like Create page rules before sending
        $invalid = [];
        $toProcess = collect();

        foreach ($pvs as $pv) {
            if ($pv->status === 'Approved') {
                // For already Approved PVs: if documents complete, close it
                if ($this->documentsAreComplete($pv)) {
                    $pv->status = 'Closed';
                    $pv->save();
                    $pv->logs()->create([
                        'user_id' => $user->id,
                        'action' => 'closed',
                        'note' => 'Payment Voucher ditutup setelah dokumen lengkap',
                    ]);
                }
                continue;
            }

            $errors = $this->validateReadyToSend($pv);
            if (!empty($errors)) {
                $invalid[(string)$pv->id] = $errors;
            } else {
                $toProcess->push($pv);
            }
        }

        if (!empty($invalid)) {
            // Build contextual, user-friendly message and structured details
            $count = count($invalid);
            $firstId = array_key_first($invalid);
            $firstPv = $pvs->firstWhere('id', (int)$firstId) ?? $pvs->first();
            $missingDocs = [];
            foreach (array_keys($invalid) as $pid) {
                $pvObj = $pvs->firstWhere('id', (int)$pid);
                if ($pvObj) {
                    $missing = $this->getMissingRequiredDocTypes($pvObj);
                    if (!empty($missing)) {
                        $missingDocs[] = [
                            'pv_id' => (int)$pid,
                            'missing_types' => $missing,
                            'missing_labels' => $this->mapDocKeysToLabels($missing),
                        ];
                    }
                }
            }

            // Human message
            if ($count === 1) {
                $msg = 'Payment Voucher belum dapat dikirim.';
                if (!empty($missingDocs)) {
                    $types = implode(', ', $missingDocs[0]['missing_labels'] ?? $this->mapDocKeysToLabels($missingDocs[0]['missing_types'] ?? []));
                    $msg = "Dokumen belum lengkap: $types. Mohon upload dokumen yang masih kurang.";
                } else {
                    $msg = 'Form belum lengkap. Mohon lengkapi field wajib sebelum mengirim.';
                }
            } else {
                $msg = 'Beberapa Payment Voucher gagal dikirim. Mohon lengkapi dokumen/field wajib pada PV terkait.';
            }

            return response()->json([
                'success' => false,
                'message' => $msg,
                // For UI parsers to render details if desired
                'missing_documents' => $missingDocs,
                'invalid' => $invalid,
            ], 422);
        }

        foreach ($toProcess as $pv) {
            $department = Department::find($pv->department_id);
            $alias = $department?->alias ?? 'DEPT';
            $pv->tanggal = $now->toDateString();
            // Only generate no_pv if not already assigned (resend keeps existing number)
            if (empty($pv->no_pv)) {
                $candidate = DocumentNumberService::generateNumberForDate('Payment Voucher', $pv->tipe_pv, $pv->department_id, $alias, $now);
                if (!DocumentNumberService::isNumberUnique($candidate)) {
                    $candidate = DocumentNumberService::generateNumberForDate('Payment Voucher', $pv->tipe_pv, $pv->department_id, $alias, $now);
                }
                $pv->no_pv = $candidate;
            }
            // Set status based on CREATOR role and tipe_pv (not sender)
            $creatorRole = strtolower(optional($pv->creator?->role)->name ?? '');
            $tipePv = $pv->tipe_pv;

            // Always log 'sent'
            $pv->logs()->create([
                'user_id' => $user->id,
                'action' => 'sent',
                'note' => 'Payment Voucher dikirim',
            ]);

            if ($tipePv === 'Manual') {
                // Manual rules
                if ($creatorRole === 'kabag') {
                    // Kabag creator -> langsung Approved pada saat dibuat/dikirim
                    $pv->status = 'Approved';
                    $pv->approved_by = $pv->creator_id;
                    $pv->approved_at = $now;
                    $pv->save();

                    $pv->logs()->create([
                        'user_id' => $pv->creator_id,
                        'action' => 'approved',
                        'note' => 'Payment Voucher auto-approved (creator Kabag)',
                    ]);
                } else {
                    // Staff Akunting & Finance creator -> Kabag approve (single-step)
                    $pv->status = 'In Progress';
                    $pv->save();
                }
            } elseif ($tipePv === 'Pajak') {
                // Pajak rules: Kabag creator -> auto-Verified; then Kadiv validate -> Direksi approve
                if ($creatorRole === 'kabag') {
                    $pv->status = 'Verified';
                    $pv->verified_by = $pv->creator_id;
                    $pv->verified_at = $now;
                    $pv->save();

                    $pv->logs()->create([
                        'user_id' => $pv->creator_id,
                        'action' => 'verified',
                        'note' => 'Payment Voucher auto-verified (creator Kabag)',
                    ]);
                } else {
                    $pv->status = 'In Progress';
                    $pv->save();
                }
            } else {
                // Default rules: Kabag creator -> auto-Verified; then Direksi approve
                if ($creatorRole === 'kabag') {
                    $pv->status = 'Verified';
                    $pv->verified_by = $pv->creator_id;
                    $pv->verified_at = $now;
                    $pv->save();

                    $pv->logs()->create([
                        'user_id' => $pv->creator_id,
                        'action' => 'verified',
                        'note' => 'Payment Voucher auto-verified (creator Kabag)',
                    ]);
                } else {
                    $pv->status = 'In Progress';
                    $pv->save();
                }
            }
        }

        return response()->json(['success' => true, 'sent' => $toProcess->pluck('id')->all(), 'created_id' => $createdPvId]);
    }

    /**
     * Display the specified resource (detail/read-only for approved states).
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $pv = PaymentVoucher::with([
            'department', 'perihal', 'supplier', 'creator',
            'verifier', 'approver', 'rejecter',
            // ensure PV-level relations present
            'bankSupplierAccount.bank',
            'creditCard.bank',
            'purchaseOrder' => function ($q) {
                $q->with([
                    'department', 'perihal', 'supplier', 'pph', 'termin',
                    'creditCard.bank', 'bankSupplierAccount.bank'
                ]);
            },
            'memoPembayaran' => function ($q) {
                $q->with(['department', 'supplier', 'bankSupplierAccount.bank']);
            },
            'poAnggaran' => function ($q) {
                $q->with(['department', 'perihal', 'bisnisPartner.bank', 'items']);
            },
            'documents'
        ])->findOrFail($id);

        return Inertia::render('payment-voucher/Detail', [
            'paymentVoucher' => $pv,
            'userRole' => $user->role->name ?? '',
            'userPermissions' => $user->role->permissions ?? []
        ]);
    }

    /**
     * Display activity log for a PV.
     */
    public function log(string $id)
    {
        $user = Auth::user();
        $logs = \App\Models\PaymentVoucherLog::with(['user.role'])
            ->where('payment_voucher_id', $id)
            ->latest()
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'at' => $log->created_at?->toDateTimeString(),
                    'user' => $log->user?->name,
                    'role' => $log->user?->role?->name,
                    'action' => $log->action,
                    'note' => $log->note,
                ];
            });

        return Inertia::render('payment-voucher/Log', [
            'id' => $id,
            'logs' => $logs,
            'userRole' => $user->role->name ?? '',
            'userPermissions' => $user->role->permissions ?? []
        ]);
    }

    /**
     * Upload a supporting document for PV
     */
    public function uploadDocument(Request $request, string $id)
    {
        $pv = PaymentVoucher::findOrFail($id);
        $request->validate([
            'type' => 'required|in:bukti_transfer_bca,bukti_input_bca,invoice,surat_jalan,efaktur,lainnya',
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $path = $request->file('file')->store('pv-documents');

        // Replace existing document of the same type if present
        $doc = PaymentVoucherDocument::where('payment_voucher_id', $pv->id)
            ->where('type', $request->type)
            ->first();

        if ($doc) {
            // delete previous file if exists
            if (!empty($doc->path)) {
                try { Storage::delete($doc->path); } catch (\Throwable $e) {}
            }
        } else {
            $doc = new PaymentVoucherDocument();
            $doc->payment_voucher_id = $pv->id;
            $doc->type = $request->type;
        }

        $doc->active = true;
        $doc->path = $path;
        $doc->original_name = $request->file('file')->getClientOriginalName();
        $doc->save();

        return back()->with('success', 'Dokumen berhasil diunggah');
    }

    /** Toggle active state of a document */
    public function toggleDocument(string $id, PaymentVoucherDocument $document)
    {
        // Optional: authorize that doc belongs to pv id
        if ((string)$document->payment_voucher_id !== (string)$id) abort(404);
        $document->active = !$document->active;
        $document->save();
        return back();
    }

    /** Explicitly set active state for a document type on a PV (create placeholder if missing) */
    public function setDocumentActive(Request $request, string $id)
    {
        $pv = PaymentVoucher::findOrFail($id);
        $data = $request->validate([
            'type' => 'required|in:bukti_transfer_bca,bukti_input_bca,invoice,surat_jalan,efaktur,lainnya',
            'active' => 'required|boolean',
        ]);

        $doc = PaymentVoucherDocument::where('payment_voucher_id', $pv->id)
            ->where('type', $data['type'])
            ->first();

        if (!$doc) {
            $doc = new PaymentVoucherDocument();
            $doc->payment_voucher_id = $pv->id;
            $doc->type = $data['type'];
        }

        $doc->active = (bool)$data['active'];
        $doc->save();

        return back(303);
    }

    private function documentsAreComplete(PaymentVoucher $pv): bool
    {
        // Only enforce completeness for document rows explicitly marked active.
        // Missing rows are treated as non-active (thus not required).
        $standardTypes = ['bukti_transfer_bca','bukti_input_bca','invoice','surat_jalan','efaktur'];
        $docRows = $pv->documents()->whereIn('type', $standardTypes)->get(['type','active','path']);

        // Required types are those that have an active row
        $requiredTypes = $docRows->filter(function ($d) {
            return (bool) $d->active;
        })->pluck('type')->all();

        if (empty($requiredTypes)) {
            return true; // no active requirements
        }

        // Uploaded types are active rows with a stored file path
        $uploadedTypes = $docRows->filter(function ($d) {
            return (bool) $d->active && !empty($d->path);
        })->pluck('type')->all();

        $missingTypes = array_values(array_diff($requiredTypes, $uploadedTypes));
        if (!empty($missingTypes)) return false;
        return true;
    }

    /** Return list of required doc types (active) that are still missing uploads */
    private function getMissingRequiredDocTypes(PaymentVoucher $pv): array
    {
        $standardTypes = ['bukti_transfer_bca','bukti_input_bca','invoice','surat_jalan','efaktur'];
        $docRows = $pv->documents()->whereIn('type', $standardTypes)->get(['type','active','path']);
        $required = $docRows->filter(fn($d)=> (bool)$d->active)->pluck('type')->all();
        if (empty($required)) return [];
        $uploaded = $docRows->filter(fn($d)=> (bool)$d->active && !empty($d->path))->pluck('type')->all();
        return array_values(array_diff($required, $uploaded));
    }

    /** Map internal document keys to user-friendly labels */
    private function mapDocKeysToLabels(array $keys): array
    {
        $labels = [
            'bukti_transfer_bca' => 'Bukti Transfer BCA',
            'bukti_input_bca' => 'Bukti Input BCA',
            'invoice' => 'Invoice/Nota/Faktur',
            'surat_jalan' => 'Surat Jalan',
            'efaktur' => 'E-Faktur Pajak',
            'lainnya' => 'Lainnya',
        ];
        return array_values(array_map(function($k) use ($labels) {
            return $labels[$k] ?? (string)$k;
        }, $keys));
    }

    /**
     * Validate PV completeness before sending (align with Create page rules).
     * Returns an array of error messages; empty array means valid.
     */
    private function validateReadyToSend(PaymentVoucher $pv): array
    {
        $errors = [];

        // Department and metode are fundamental
        if (empty($pv->department_id)) {
            $errors[] = 'Departemen wajib diisi';
        }
        // if (empty($pv->metode_bayar)) {
        //     $errors[] = 'Metode bayar wajib diisi';
        // }

        $tipe = (string) ($pv->tipe_pv ?? '');
        // $metode = (string) ($pv->metode_bayar ?? '');

        // Tipe-specific requirements
        if ($tipe === 'Manual' || $tipe === 'Pajak') {
            if (empty($pv->perihal_id)) {
                $errors[] = 'Perihal wajib diisi untuk tipe Manual/Pajak';
            }
            if ($pv->nominal === null || (float)$pv->nominal <= 0) {
                $errors[] = 'Nominal wajib diisi dan lebih dari 0 untuk tipe Manual/Pajak';
            }
            if (empty($pv->currency)) {
                $errors[] = 'Currency wajib diisi untuk tipe Manual/Pajak';
            }
        } else {
            // Non-manual-like: require reference doc
            if ($tipe === 'Lainnya') {
                if (empty($pv->memo_pembayaran_id)) {
                    $errors[] = 'Memo Pembayaran wajib dipilih untuk tipe Lainnya';
                }
            } elseif ($tipe === 'Anggaran') {
                if (empty($pv->po_anggaran_id)) {
                    $errors[] = 'PO Anggaran wajib dipilih untuk tipe Anggaran';
                }
            } else {
                if (empty($pv->purchase_order_id)) {
                    $errors[] = 'Purchase Order wajib dipilih';
                }
            }
        }

        // Metode-specific requirements
        // if ($metode === 'Transfer') {
        //     if (empty($pv->supplier_id)) {
        //         $errors[] = 'Supplier wajib dipilih untuk metode Transfer';
        //     }
        //     if (empty($pv->bank_supplier_account_id)) {
        //         // Fallback: use related PO/Memo bank account if available
        //         $relatedBankAccountId = $pv->purchaseOrder?->bank_supplier_account_id
        //             ?? $pv->memoPembayaran?->bank_supplier_account_id
        //             ?? null;
        //         if ($relatedBankAccountId) {
        //             $pv->bank_supplier_account_id = $relatedBankAccountId;
        //             $pv->save();
        //         } else {
        //             $errors[] = 'Rekening Supplier wajib dipilih untuk metode Transfer';
        //         }
        //     }
        // } elseif ($metode === 'Kartu Kredit') {
        //     if (empty($pv->credit_card_id)) {
        //         $errors[] = 'Kartu Kredit wajib dipilih untuk metode Kartu Kredit';
        //     }
        // }

        // Document completeness: any checked (active) doc types must have file (excluding 'lainnya')
        if (!$this->documentsAreComplete($pv)) {
            $errors[] = 'Dokumen belum lengkap sesuai checklist aktif';
        }

        // Outstanding validations per tipe
        try {
            // Ensure nominal positive when relevant
            $nominal = (float) ($pv->nominal ?? 0);
            if (in_array($tipe, ['Reguler','Lainnya','Anggaran'], true)) {
                if ($nominal <= 0) {
                    $errors[] = 'Nominal wajib lebih dari 0';
                }
            }

            if ($tipe === 'Reguler') {
                // Allocation-aware checks
                $bpbAllocs = \App\Models\PaymentVoucherBpbAllocation::where('payment_voucher_id', $pv->id)->get(['bpb_id','amount']);
                $memoAllocs = \App\Models\PaymentVoucherMemoAllocation::where('payment_voucher_id', $pv->id)->get(['memo_pembayaran_id','amount']);

                if ($bpbAllocs->count() > 0) {
                    // Validate BPB allocations: all Approved, same PO, each within outstanding, and nominal equals sum
                    $bpbIds = $bpbAllocs->pluck('bpb_id')->unique()->values()->all();
                    $bpbs = Bpb::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
                        ->whereIn('id', $bpbIds)
                        ->get(['id','purchase_order_id','status','grand_total']);
                    if ($bpbs->count() !== count($bpbIds)) {
                        $errors[] = 'Sebagian BPB pada alokasi tidak ditemukan';
                    } else {
                        // Same PO and Approved
                        $poIds = $bpbs->pluck('purchase_order_id')->unique()->values();
                        if ($poIds->count() !== 1) {
                            $errors[] = 'Alokasi BPB harus dari PO yang sama';
                        }
                        foreach ($bpbs as $b) {
                            if ($b->status !== 'Approved') {
                                $errors[] = "BPB #{$b->id} belum Approved";
                                break;
                            }
                        }
                        // Outstanding per BPB
                        $usedMap = DB::table('payment_voucher_bpb_allocations as a')
                            ->join('payment_vouchers as pv2','pv2.id','=','a.payment_voucher_id')
                            ->whereIn('a.bpb_id', $bpbIds)
                            ->whereNot('pv2.status','Canceled')
                            ->when($pv->id, function($q) use ($pv){ $q->where('a.payment_voucher_id','!=',$pv->id); })
                            ->selectRaw('a.bpb_id, COALESCE(SUM(a.amount),0) as used')
                            ->groupBy('a.bpb_id')->get()->pluck('used','bpb_id');
                        $sum = 0.0;
                        foreach ($bpbAllocs as $row) {
                            $bId = (int)$row->bpb_id;
                            $amt = (float)$row->amount;
                            $b = $bpbs->firstWhere('id', $bId);
                            $out = max(0.0, (float)($b->grand_total ?? 0) - (float)($usedMap[$bId] ?? 0));
                            if ($amt - $out > 0.00001) {
                                $errors[] = "Alokasi BPB #$bId melebihi outstanding";
                                break;
                            }
                            $sum += $amt;
                        }
                        if (abs($nominal - $sum) > 0.00001) {
                            $errors[] = 'Nominal harus sama dengan total alokasi BPB';
                        }
                    }
                } elseif ($memoAllocs->count() > 0) {
                    // Validate Memo allocations: Approved, same PO, each within outstanding; nominal <= sum
                    $memoIds = $memoAllocs->pluck('memo_pembayaran_id')->unique()->values()->all();
                    $memos = \App\Models\MemoPembayaran::query()
                        ->whereIn('id', $memoIds)
                        ->get(['id','purchase_order_id','status','total']);
                    if ($memos->count() !== count($memoIds)) {
                        $errors[] = 'Sebagian Memo pada alokasi tidak ditemukan';
                    } else {
                        $poIds = $memos->pluck('purchase_order_id')->unique()->values();
                        if ($poIds->count() !== 1) {
                            $errors[] = 'Alokasi Memo harus dari PO yang sama';
                        }
                        foreach ($memos as $m) {
                            if ($m->status !== 'Approved') {
                                $errors[] = "Memo #{$m->id} belum Approved";
                                break;
                            }
                        }
                        $usedMap = DB::table('payment_voucher_memo_allocations as a')
                            ->join('payment_vouchers as pv2','pv2.id','=','a.payment_voucher_id')
                            ->whereIn('a.memo_pembayaran_id', $memoIds)
                            ->whereNot('pv2.status','Canceled')
                            ->when($pv->id, function($q) use ($pv){ $q->where('a.payment_voucher_id','!=',$pv->id); })
                            ->selectRaw('a.memo_pembayaran_id as mid, COALESCE(SUM(a.amount),0) as used')
                            ->groupBy('a.memo_pembayaran_id')->get()->pluck('used','mid');
                        $sum = 0.0;
                        foreach ($memoAllocs as $row) {
                            $mId = (int)$row->memo_pembayaran_id;
                            $amt = (float)$row->amount;
                            $mm = $memos->firstWhere('id', $mId);
                            $out = max(0.0, (float)($mm->total ?? 0) - (float)($usedMap[$mId] ?? 0));
                            if ($amt - $out > 0.00001) {
                                $errors[] = "Alokasi Memo #$mId melebihi outstanding";
                                break;
                            }
                            $sum += $amt;
                        }
                        if ($nominal - $sum > 0.00001) {
                            $errors[] = 'Nominal melebihi total alokasi Memo';
                        }
                    }
                }

                // Also enforce PO-level outstanding cap when purchase_order_id present
                if (!empty($pv->purchase_order_id)) {
                    $po = \App\Models\PurchaseOrder::find($pv->purchase_order_id);
                    if ($po) {
                        $grand = (float) ($po->grand_total ?? $po->total ?? 0);
                        $used = (float) DB::table('payment_vouchers')
                            ->where('purchase_order_id', $po->id)
                            ->whereNotIn('status', ['Draft','Canceled','Rejected'])
                            ->where('id', '!=', $pv->id)
                            ->sum('nominal');
                        $out = max(0, $grand - $used);
                        if ($nominal - $out > 0.00001) {
                            $errors[] = 'Nominal melebihi sisa outstanding PO (maksimal ' . number_format($out, 0, ',', '.') . ')';
                        }
                    }
                }
            } elseif ($tipe === 'Lainnya' && !empty($pv->memo_pembayaran_id)) {
                $memo = \App\Models\MemoPembayaran::with('purchaseOrder')->find($pv->memo_pembayaran_id);
                $srcPo = $memo?->purchaseOrder;
                if ($srcPo) {
                    $grand = (float) ($srcPo->grand_total ?? $srcPo->total ?? 0);
                    $used = (float) DB::table('payment_vouchers')
                        ->where('purchase_order_id', $srcPo->id)
                        ->whereNotIn('status', ['Draft','Canceled','Rejected'])
                        ->where('id', '!=', $pv->id)
                        ->sum('nominal');
                    $out = max(0, $grand - $used);
                    if ($nominal - $out > 0.00001) {
                        $errors[] = 'Nominal melebihi sisa outstanding PO (maksimal ' . number_format($out, 0, ',', '.') . ')';
                    }
                }
            } elseif ($tipe === 'Anggaran' && !empty($pv->po_anggaran_id)) {
                $poa = \App\Models\PoAnggaran::find($pv->po_anggaran_id);
                if ($poa) {
                    $grand = (float) ($poa->nominal ?? 0);
                    $used = (float) DB::table('payment_vouchers')
                        ->where('po_anggaran_id', $poa->id)
                        ->whereNotIn('status', ['Draft','Canceled','Rejected'])
                        ->where('id', '!=', $pv->id)
                        ->sum('nominal');
                    $out = max(0, $grand - $used);
                    if ($nominal - $out > 0.00001) {
                        $errors[] = 'Nominal melebihi sisa outstanding PO Anggaran (maksimal ' . number_format($out, 0, ',', '.') . ')';
                    }
                }
            }
        } catch (\Throwable $e) {
            // fail-safe: do not block send on exception, but log if needed
        }

        return $errors;
    }

    /** Download a document */
    public function downloadDocument(PaymentVoucherDocument $document)
    {
        return Storage::download($document->path, $document->original_name ?: 'document.pdf');
    }

    /** View a document inline in the browser */
    public function viewDocument(PaymentVoucherDocument $document)
    {
        if (empty($document->path) || !Storage::exists($document->path)) {
            abort(404);
        }

        $mime = Storage::mimeType($document->path) ?: 'application/pdf';
        $filename = $document->original_name ?: 'document.pdf';

        $stream = Storage::readStream($document->path);
        if ($stream === false) {
            abort(404);
        }

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . str_replace('"', '\"', $filename) . '"',
        ]);
    }

    /** Delete a document */
    public function deleteDocument(PaymentVoucherDocument $document)
    {
        if ($document->path) Storage::delete($document->path);
        $document->delete();
        return back();
    }

    /** List Memo Pembayaran for a specific Purchase Order (for PV PO selection modal) */
    public function getMemosForPurchaseOrder(PurchaseOrder $purchase_order)
    {
        try {
            $memos = \App\Models\MemoPembayaran::query()
                ->with(['department'])
                ->where('purchase_order_id', $purchase_order->id)
                ->where('status', 'Approved')
                ->orderBy('created_at', 'asc')
                ->get(['id','no_mb','tanggal','department_id','total','status','keterangan']);

            // Allocation-based used amounts per memo (tolerate missing table pre-migration)
            try {
                $usedMap = DB::table('payment_voucher_memo_allocations as a')
                    ->join('payment_vouchers as pv','pv.id','=','a.payment_voucher_id')
                    ->whereIn('a.memo_pembayaran_id', $memos->pluck('id')->all())
                    ->where('pv.status','!=','Canceled')
                    ->selectRaw('a.memo_pembayaran_id as mid, COALESCE(SUM(a.amount),0) as used')
                    ->groupBy('a.memo_pembayaran_id')
                    ->pluck('used','mid');
            } catch (\Throwable $e) {
                $usedMap = collect();
            }

            $data = $memos->map(function($m) use ($usedMap) {
                $total = (float) ($m->total ?? 0);
                $used = (float) ($usedMap[$m->id] ?? 0);
                $out = max($total - $used, 0);
                return [
                    'id' => $m->id,
                    'no_mb' => $m->no_mb,
                    'tanggal' => $m->tanggal,
                    'department' => $m->department ? [ 'id' => $m->department->id, 'name' => $m->department->name ] : null,
                    'total' => $total,
                    'outstanding' => $out,
                    'status' => $m->status,
                    'keterangan' => $m->keterangan,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Failed to load memos',
            ], 500);
        }
    }

    /** Preview Payment Voucher (HTML) */
    public function preview(string $id)
    {
        try {
            $pv = PaymentVoucher::with([
                'department','perihal','supplier','bankSupplierAccount.bank','creditCard.bank',
                'purchaseOrder.perihal','purchaseOrder.supplier','purchaseOrder.bankSupplierAccount.bank','purchaseOrder.items','purchaseOrder.termin','purchaseOrder.creditCard.bank',
                'memoPembayaran.supplier','memoPembayaran.bankSupplierAccount.bank','memoPembayaran.purchaseOrder.termin','memoPembayaran.purchaseOrder.items'
            ])->findOrFail($id);

            // Determine reference context & source PO
            $isMemo = (strtolower($pv->tipe_pv ?? '') === 'lainnya') && !empty($pv->memo_pembayaran_id);
            $po = $pv->purchaseOrder ?: ($isMemo ? ($pv->memoPembayaran?->purchaseOrder) : null);
            // Calculate summary (align with PurchaseOrder logic)
            $total = 0;
            if ($po && $po->items && count($po->items) > 0) {
                $total = $po->items->sum(function($item){
                    return ($item->qty ?? 1) * ($item->harga ?? 0);
                });
            } elseif ($po) {
                $total = ($po->tipe_po === 'Lainnya') ? ((float) ($po->nominal ?? 0)) : ((float) ($po->harga ?? 0));
            } else {
                // Fallback to PV amount fields if any in future
                $total = (float) ($pv->total ?? 0);
            }

            $diskon = $po?->diskon ?? 0;
            $dpp = max($total - $diskon, 0);
            $ppn = ($po?->ppn ? $dpp * 0.11 : 0);

            // DPP khusus PPh (hanya item bertipe 'Jasa')
            $dppPph = 0;
            if ($po && $po->items && count($po->items) > 0) {
                $dppPph = $po->items->filter(function($item){
                    return isset($item->tipe) && strtolower($item->tipe) === 'jasa';
                })->sum(function($item){
                    return ($item->qty ?? 1) * ($item->harga ?? 0);
                });
            } elseif ($po && $po->tipe_po === 'Lainnya') {
                $dppPph = (float) ($po->nominal ?? 0);
            }

            $pphPersen = 0;
            $pph = 0;
            if ($po && $po->pph_id) {
                $pphModel = \App\Models\Pph::find($po->pph_id);
                if ($pphModel) {
                    $pphPersen = $pphModel->tarif_pph ?? 0;
                    $pph = $dppPph * ($pphPersen / 100);
                }
            }

            $grandTotal = $dpp + $ppn + $pph;

            $tanggal = $pv->tanggal
                ? Carbon::parse($pv->tanggal)->locale('id')->translatedFormat('d F Y')
                : Carbon::now()->locale('id')->translatedFormat('d F Y');

            $logoSrc = $this->getBase64Image('images/company-logo.png');
            $signatureSrc = $this->getBase64Image('images/signature.png');
            $approvedSrc = $this->getBase64Image('images/approved.png');

            $refNo = $isMemo
                ? ($pv->memoPembayaran?->no_mb ?? '-')
                : ($pv->purchaseOrder?->no_po ?? '-');

            // Build termin data for tipe Lainnya (Memo) if available
            $terminData = null;
            if ($isMemo) {
                try {
                    $memo = $pv->memoPembayaran;
                    $srcPo = $pv->purchaseOrder ?: ($memo?->purchaseOrder);
                    $termin = $memo?->termin ?: ($srcPo?->termin);
                    if ($termin) {
                        $jumlahTotal = (int) ($termin->jumlah_termin ?? 0);

                        // Hitung termin ke- dan kumulatif cicilan berbasis memo sebelumnya (exclude Canceled/Rejected)
                        $poId = $srcPo?->id;
                        $terminId = $termin->id ?? ($memo?->termin_id ?? null);
                        $baseQ = \App\Models\MemoPembayaran::query()
                            ->whereNotIn('status', ['Canceled','Rejected']);

                        if ($poId) {
                            $baseQ->where('purchase_order_id', $poId);
                        } elseif ($terminId) {
                            $baseQ->where('termin_id', $terminId);
                        }

                        if (!empty($memo?->created_at)) {
                            $createdAt = $memo->created_at;
                            $currentId = $memo->id;
                            $baseQ->where(function($q) use ($createdAt, $currentId) {
                                $q->where('created_at', '<', $createdAt)
                                  ->orWhere(function($qq) use ($createdAt, $currentId) {
                                      $qq->where('created_at', '=', $createdAt)
                                         ->where('id', '<', $currentId);
                                  });
                            });
                        } else {
                            $baseQ->where('id', '<', $memo->id);
                        }

                        $priorCount = (int) $baseQ->count();
                        $terminKe = $priorCount + 1;

                        // Kumulatif cicilan hingga memo ini
                        $priorSum = (float) $baseQ->clone()->sum('cicilan');
                        $nominalCicilan = (float) ($memo?->cicilan ?? 0);
                        $jumlahCicilan = $priorSum + $nominalCicilan;

                        // Total tagihan (fallback berjenjang)
                        $totalTagihan = (float) ($termin->grand_total ?? ($srcPo?->total ?? ($memo?->grand_total ?? $memo?->total ?? 0)));
                        $sisa = max($totalTagihan - $jumlahCicilan, 0);

                        if ($jumlahTotal > 0) { $terminKe = min($terminKe, $jumlahTotal); }

                        $terminData = [
                            'termin_no' => $terminKe,
                            'nominal_cicilan' => $nominalCicilan,
                            'jumlah_cicilan' => $jumlahCicilan,
                            'total_cicilan' => $jumlahCicilan, // tampilkan kumulatif seperti di Memo
                            'no_referensi' => $termin->no_referensi ?? null,
                            'jumlah_termin' => $jumlahTotal ?: null,
                            'sisa_pembayaran' => $sisa,
                        ];
                    } else {
                        // Fallback when no Termin relation: compute by PO and Memo
                        $poId = $srcPo?->id;
                        if ($poId && $memo) {
                            $baseQ = \App\Models\MemoPembayaran::query()
                                ->whereNotIn('status', ['Canceled','Rejected'])
                                ->where('purchase_order_id', $poId);

                            if (!empty($memo?->created_at)) {
                                $createdAt = $memo->created_at;
                                $currentId = $memo->id;
                                $baseQ->where(function($q) use ($createdAt, $currentId) {
                                    $q->where('created_at', '<', $createdAt)
                                      ->orWhere(function($qq) use ($createdAt, $currentId) {
                                          $qq->where('created_at', '=', $createdAt)
                                             ->where('id', '<', $currentId);
                                      });
                                });
                            } else {
                                $baseQ->where('id', '<', $memo->id);
                            }

                            $priorCount = (int) $baseQ->count();
                            $terminKe = $priorCount + 1;
                            $priorSum = (float) $baseQ->clone()->sum('cicilan');
                            $nominalCicilan = (float) ($memo?->cicilan ?? 0);
                            $jumlahCicilan = $priorSum + $nominalCicilan;
                            $totalTagihan = (float) ($srcPo?->total ?? ($memo?->grand_total ?? $memo?->total ?? 0));
                            $sisa = max($totalTagihan - $jumlahCicilan, 0);

                            $terminData = [
                                'termin_no' => $terminKe,
                                'nominal_cicilan' => $nominalCicilan,
                                'jumlah_cicilan' => $jumlahCicilan,
                                'total_cicilan' => $jumlahCicilan,
                                'no_referensi' => null,
                                'jumlah_termin' => null,
                                'sisa_pembayaran' => $sisa,
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    // swallow termin calc errors for PDF
                }
            }

            return view('payment_voucher_pdf_preview', [
                'pv' => $pv,
                'tanggal' => $tanggal,
                'total' => $total,
                'diskon' => $diskon,
                'ppn' => $ppn,
                'pph' => $pph,
                'grandTotal' => $grandTotal,
                'pphPersen' => $pphPersen,
                'isMemo' => $isMemo,
                'refNo' => $refNo,
                'terminData' => $terminData,
                'logoSrc' => $logoSrc,
                'signatureSrc' => $signatureSrc,
                'approvedSrc' => $approvedSrc,
            ]);
        } catch (\Exception $e) {
            Log::error('PaymentVoucher Preview error', ['pv_id'=>$id,'error'=>$e->getMessage()]);
            return response()->json(['error' => 'Failed to render preview: '.$e->getMessage()], 500);
        }
    }

    /** Download Payment Voucher PDF */
    public function download(string $id)
    {
        try {
            $pv = PaymentVoucher::with([
                'department','perihal','supplier','bankSupplierAccount.bank','creditCard.bank',
                'purchaseOrder.perihal','purchaseOrder.supplier','purchaseOrder.bankSupplierAccount.bank','purchaseOrder.items','purchaseOrder.termin','purchaseOrder.creditCard.bank',
                'memoPembayaran.supplier','memoPembayaran.bankSupplierAccount.bank','memoPembayaran.purchaseOrder.termin','memoPembayaran.purchaseOrder.items'
            ])->findOrFail($id);

            // Determine reference context & source PO
            $isMemo = (strtolower($pv->tipe_pv ?? '') === 'lainnya') && !empty($pv->memo_pembayaran_id);
            $po = $pv->purchaseOrder ?: ($isMemo ? ($pv->memoPembayaran?->purchaseOrder) : null);
            // Calculate summary (align with PurchaseOrder logic)
            $total = 0;
            if ($po && $po->items && count($po->items) > 0) {
                $total = $po->items->sum(function($item){
                    return ($item->qty ?? 1) * ($item->harga ?? 0);
                });
            } elseif ($po) {
                $total = ($po->tipe_po === 'Lainnya') ? ((float) ($po->nominal ?? 0)) : ((float) ($po->harga ?? 0));
            } else {
                // Fallback to PV amount fields if any in future
                $total = (float) ($pv->total ?? 0);
            }

            $diskon = $po?->diskon ?? 0;
            $dpp = max($total - $diskon, 0);
            $ppn = ($po?->ppn ? $dpp * 0.11 : 0);

            // DPP khusus PPh (hanya item bertipe 'Jasa')
            $dppPph = 0;
            if ($po && $po->items && count($po->items) > 0) {
                $dppPph = $po->items->filter(function($item){
                    return isset($item->tipe) && strtolower($item->tipe) === 'jasa';
                })->sum(function($item){
                    return ($item->qty ?? 1) * ($item->harga ?? 0);
                });
            } elseif ($po && $po->tipe_po === 'Lainnya') {
                $dppPph = (float) ($po->nominal ?? 0);
            }

            $pphPersen = 0;
            $pph = 0;
            if ($po && $po->pph_id) {
                $pphModel = \App\Models\Pph::find($po->pph_id);
                if ($pphModel) {
                    $pphPersen = $pphModel->tarif_pph ?? 0;
                    $pph = $dppPph * ($pphPersen / 100);
                }
            }

            $grandTotal = $dpp + $ppn + $pph;

            $tanggal = $pv->tanggal
                ? Carbon::parse($pv->tanggal)->locale('id')->translatedFormat('d F Y')
                : Carbon::now()->locale('id')->translatedFormat('d F Y');

            $logoSrc = $this->getBase64Image('images/company-logo.png');
            $signatureSrc = $this->getBase64Image('images/signature.png');
            $approvedSrc = $this->getBase64Image('images/approved.png');

            $refNo = $isMemo
                ? ($pv->memoPembayaran?->no_mb ?? '-')
                : ($pv->purchaseOrder?->no_po ?? '-');

            // Build termin data for tipe Lainnya (Memo) if available
            $terminData = null;
            if ($isMemo) {
                try {
                    $memo = $pv->memoPembayaran;
                    $srcPo = $pv->purchaseOrder ?: ($memo?->purchaseOrder);
                    $termin = $memo?->termin ?: ($srcPo?->termin);
                    if ($termin) {
                        $jumlahTotal = (int) ($termin->jumlah_termin ?? 0);

                        // Hitung termin ke- dan kumulatif cicilan berbasis memo sebelumnya (exclude Canceled/Rejected)
                        $poId = $srcPo?->id;
                        $terminId = $termin->id ?? ($memo?->termin_id ?? null);
                        $baseQ = \App\Models\MemoPembayaran::query()
                            ->whereNotIn('status', ['Canceled','Rejected']);

                        if ($poId) {
                            $baseQ->where('purchase_order_id', $poId);
                        } elseif ($terminId) {
                            $baseQ->where('termin_id', $terminId);
                        }

                        if (!empty($memo?->created_at)) {
                            $createdAt = $memo->created_at;
                            $currentId = $memo->id;
                            $baseQ->where(function($q) use ($createdAt, $currentId) {
                                $q->where('created_at', '<', $createdAt)
                                  ->orWhere(function($qq) use ($createdAt, $currentId) {
                                      $qq->where('created_at', '=', $createdAt)
                                         ->where('id', '<', $currentId);
                                  });
                            });
                        } else {
                            $baseQ->where('id', '<', $memo->id);
                        }

                        $priorCount = (int) $baseQ->count();
                        $terminKe = $priorCount + 1;

                        // Kumulatif cicilan hingga memo ini
                        $priorSum = (float) $baseQ->clone()->sum('cicilan');
                        $nominalCicilan = (float) ($memo?->cicilan ?? 0);
                        $jumlahCicilan = $priorSum + $nominalCicilan;

                        // Total tagihan (fallback berjenjang)
                        $totalTagihan = (float) ($termin->grand_total ?? ($srcPo?->total ?? ($memo?->grand_total ?? $memo?->total ?? 0)));
                        $sisa = max($totalTagihan - $jumlahCicilan, 0);

                        if ($jumlahTotal > 0) { $terminKe = min($terminKe, $jumlahTotal); }

                        $terminData = [
                            'termin_no' => $terminKe,
                            'nominal_cicilan' => $nominalCicilan,
                            'jumlah_cicilan' => $jumlahCicilan,
                            'total_cicilan' => $jumlahCicilan, // tampilkan kumulatif seperti di Memo
                            'no_referensi' => $termin->no_referensi ?? null,
                            'jumlah_termin' => $jumlahTotal ?: null,
                            'sisa_pembayaran' => $sisa,
                        ];
                    } else {
                        // Fallback when no Termin relation: compute by PO and Memo
                        $poId = $srcPo?->id;
                        if ($poId && $memo) {
                            $baseQ = \App\Models\MemoPembayaran::query()
                                ->whereNotIn('status', ['Canceled','Rejected'])
                                ->where('purchase_order_id', $poId);

                            if (!empty($memo?->created_at)) {
                                $createdAt = $memo->created_at;
                                $currentId = $memo->id;
                                $baseQ->where(function($q) use ($createdAt, $currentId) {
                                    $q->where('created_at', '<', $createdAt)
                                      ->orWhere(function($qq) use ($createdAt, $currentId) {
                                          $qq->where('created_at', '=', $createdAt)
                                             ->where('id', '<', $currentId);
                                      });
                                });
                            } else {
                                $baseQ->where('id', '<', $memo->id);
                            }

                            $priorCount = (int) $baseQ->count();
                            $terminKe = $priorCount + 1;
                            $priorSum = (float) $baseQ->clone()->sum('cicilan');
                            $nominalCicilan = (float) ($memo?->cicilan ?? 0);
                            $jumlahCicilan = $priorSum + $nominalCicilan;
                            $totalTagihan = (float) ($srcPo?->total ?? ($memo?->grand_total ?? $memo?->total ?? 0));
                            $sisa = max($totalTagihan - $jumlahCicilan, 0);

                            $terminData = [
                                'termin_no' => $terminKe,
                                'nominal_cicilan' => $nominalCicilan,
                                'jumlah_cicilan' => $jumlahCicilan,
                                'total_cicilan' => $jumlahCicilan,
                                'no_referensi' => null,
                                'jumlah_termin' => null,
                                'sisa_pembayaran' => $sisa,
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    // swallow termin calc errors for PDF
                }
            }

            $pdf = Pdf::loadView('payment_voucher_pdf', [
                'pv' => $pv,
                'tanggal' => $tanggal,
                'total' => $total,
                'diskon' => $diskon,
                'ppn' => $ppn,
                'pph' => $pph,
                'grandTotal' => $grandTotal,
                'pphPersen' => $pphPersen,
                'isMemo' => $isMemo,
                'refNo' => $refNo,
                'terminData' => $terminData,
                'logoSrc' => $logoSrc,
                'signatureSrc' => $signatureSrc,
                'approvedSrc' => $approvedSrc,
            ])->setOptions(config('dompdf.options'))
              ->setPaper('a4','portrait');

            $filename = 'PaymentVoucher_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $pv->no_pv ?? 'Draft') . '.pdf';
            return $pdf->stream($filename);
        } catch (\Exception $e) {
            Log::error('PaymentVoucher Download error', ['pv_id'=>$id,'error'=>$e->getMessage()]);
            return response()->json(['error' => 'Failed to generate PDF: '.$e->getMessage()], 500);
        }
    }

    private function getBase64Image($imagePath)
    {
        $fullPath = public_path($imagePath);
        if (file_exists($fullPath)) {
            $imageData = file_get_contents($fullPath);
            $imageInfo = getimagesizefromstring($imageData);
            $mimeType = $imageInfo['mime'] ?? 'image/png';
            return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        }
        return '';
    }

    /**
     * Cancel a draft Payment Voucher.
     */
    public function cancel(string $id)
    {
        $user = Auth::user();
        $pv = PaymentVoucher::where('id', $id)->where('status', 'Draft')->firstOrFail();
        $pv->status = 'Canceled';
        $pv->save();

        $pv->logs()->create([
            'user_id' => $user->id,
            'action' => 'canceled',
            'note' => 'Payment Voucher dibatalkan',
        ]);

        return back()->with('success', 'Payment Voucher berhasil dibatalkan');
    }

    /**
     * Search Approved Purchase Orders for Payment Voucher selection
     */
    public function searchPurchaseOrders(Request $request)
    {
        $search = $request->input('search');
        // $metode = $request->input('metode_bayar') ?: $request->input('metode_pembayaran');
        $supplierId = $request->input('supplier_id');
        $departmentId = $request->input('department_id');
        $giroId = $request->input('giro_id');
        $creditCardId = $request->input('credit_card_id');
        $tipePv = $request->input('tipe_pv');
        $perPage = (int) $request->input('per_page', 20);
        $currentPvId = $request->input('current_pv_id');

        $query = \App\Models\PurchaseOrder::query()
            ->with(['perihal', 'supplier', 'department', 'bankSupplierAccount.bank', 'bank', 'creditCard.bank'])
            ->where('status', 'Approved');

        // Filter by tipe_pv -> map to purchase_orders.tipe_po
        if (in_array($tipePv, ['Reguler','Anggaran','Lainnya'], true)) {
            $query->where('tipe_po', $tipePv);
        }

        // For Reguler PV selection rules:
        // - If a PO has related Memo Pembayaran, they must be Approved; otherwise, hide the PO from list
        // - Similarly, if a PO has BPB, only show the PO when all BPBs are Approved (pending BPB hides the PO)
        // - If PO has active DP, it is only selectable when at least one non-canceled DP PV already exists
        if ($tipePv === 'Reguler') {
            // Exclude POs that have any non-Approved memo_pembayarans
            $query->whereNotExists(function($q){
                $q->select(DB::raw(1))
                  ->from('memo_pembayarans as m')
                  ->whereColumn('m.purchase_order_id', 'purchase_orders.id')
                  ->where('m.status', '!=', 'Approved');
            });

            // Exclude POs that have any BPB not Approved
            $query->whereNotExists(function($q){
                $q->select(DB::raw(1))
                  ->from('bpbs as b')
                  ->whereColumn('b.purchase_order_id', 'purchase_orders.id')
                  ->where('b.status', '!=', 'Approved');
            });

            // DP rule: allow POs without active DP OR with active DP that already have at least one non-canceled DP PV
            $query->where(function($q){
                $q->where(function($sub){
                    $sub->whereNull('dp_nominal')
                        ->orWhere('dp_nominal', '<=', 0)
                        ->orWhere('dp_active', '!=', 1);
                })
                ->orWhereExists(function($qq){
                    $qq->select(DB::raw(1))
                       ->from('payment_vouchers as pvdp')
                       ->whereColumn('pvdp.purchase_order_id', 'purchase_orders.id')
                       ->where('pvdp.tipe_pv', 'DP')
                       ->where('pvdp.status', '!=', 'Canceled');
                });
            });
        } elseif ($tipePv === 'DP') {
            // PV DP hanya boleh memilih PO yang memiliki DP aktif & nominal > 0
            $query->where('dp_active', 1)
                  ->where(function($q){
                      $q->whereNotNull('dp_nominal')
                        ->where('dp_nominal', '>', 0);
                  });

            // PO DP yang sudah memiliki BPB non-canceled tidak boleh dipakai untuk PV DP
            $query->whereNotExists(function($q){
                $q->select(DB::raw(1))
                  ->from('bpbs as b')
                  ->whereColumn('b.purchase_order_id', 'purchase_orders.id')
                  ->whereNull('b.deleted_at')
                  ->where('b.status', '!=', 'Canceled');
            });
        }

        // Always filter by department if provided
        if (!empty($departmentId)) {
            $query->where('department_id', $departmentId);
        }

        // Always filter by supplier if provided
        if (!empty($supplierId)) {
            $query->where('supplier_id', $supplierId);
        }

        // Metode-based filters
        // if ($metode === 'Cek/Giro' && $giroId) {
        //     // In PV, giro selection points to a PO id
        //     $query->where('id', $giroId);
        // } elseif ($metode === 'Kartu Kredit' && $creditCardId) {
        //     // Only POs tied to a specific credit card
        //     $query->where('credit_card_id', $creditCardId);
        // }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('no_po', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function($qs) use ($search){
                      $qs->where('nama_supplier', 'like', "%{$search}%");
                  })
                  ->orWhereHas('department', function($qd) use ($search){
                      $qd->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('perihal', function($qp) use ($search){
                      $qp->where('nama', 'like', "%{$search}%");
                  });
            });
        }

        $purchaseOrders = $query->orderByDesc('created_at')->paginate($perPage);

        $data = collect($purchaseOrders->items())
            // Untuk tipe PV DP, hanya tampilkan PO yang masih punya sisa DP > 0
            ->filter(function($po) use ($tipePv) {
                if ($tipePv !== 'DP') {
                    return true;
                }
                $dpNominal = (float) ($po->dp_nominal ?? 0);
                if ($dpNominal <= 0 || !$po->dp_active) {
                    return false;
                }
                try {
                    $usedDp = (float) DB::table('payment_vouchers')
                        ->where('tipe_pv', 'DP')
                        ->where('purchase_order_id', $po->id)
                        ->where('status', '!=', 'Canceled')
                        ->sum('nominal');
                } catch (\Throwable $e) { $usedDp = 0.0; }
                $remaining = max(0.0, $dpNominal - $usedDp);
                return $remaining > 0.00001;
            })
            ->map(function($po) use ($tipePv) {
            // Compute outstanding based on existing Payment Vouchers on this PO (exclude Draft/Canceled/Rejected)
            $grandTotal = (float) ($po->grand_total ?? $po->total ?? 0);
            try {
                $usedTotal = (float) DB::table('payment_vouchers')
                    ->where('purchase_order_id', $po->id)
                    ->whereNotIn('status', ['Draft','Canceled','Rejected'])
                    ->sum('nominal');
            } catch (\Throwable $e) { $usedTotal = 0.0; }
            $outstanding = max(0, $grandTotal - $usedTotal);

            // DP info
            $dpActive = (bool) ($po->dp_active ?? false);
            $dpNominal = (float) ($po->dp_nominal ?? 0);
            $dpUsed = 0.0;
            $dpRemaining = null;
            if ($dpActive && $dpNominal > 0) {
                try {
                    $dpUsed = (float) DB::table('payment_vouchers')
                        ->where('tipe_pv', 'DP')
                        ->where('purchase_order_id', $po->id)
                        ->where('status', '!=', 'Canceled')
                        ->sum('nominal');
                } catch (\Throwable $e) { $dpUsed = 0.0; }
                $dpRemaining = max(0.0, $dpNominal - $dpUsed);
            }

            return [
                'id' => $po->id,
                'no_po' => $po->no_po,
                'tanggal' => $po->tanggal,
                'no_invoice' => $po->no_invoice,
                'supplier_id' => $po->supplier_id,
                'supplier' => [
                    'id' => $po->supplier?->id,
                    'nama_supplier' => $po->supplier?->nama_supplier,
                    'alamat' => $po->supplier?->alamat,
                    'no_telepon' => $po->supplier?->no_telepon,
                    'email' => $po->supplier?->email,
                ],
                'department' => [ 'id' => $po->department?->id, 'name' => $po->department?->name ],
                'perihal' => [ 'id' => $po->perihal?->id, 'nama' => $po->perihal?->nama ],
                'total' => $po->total ?? 0,
                'grand_total' => $grandTotal,
                'outstanding' => $outstanding,
                // DP fields for PV DP flows
                'dp_active' => $dpActive,
                'dp_nominal' => $dpNominal,
                'dp_used' => $dpUsed,
                'dp_remaining' => $dpRemaining,
                'keterangan' => $po->keterangan,
                'status' => $po->status,
                // 'metode_pembayaran' => $po->metode_pembayaran,
                'nama_rekening' => $po->nama_rekening,
                'no_rekening' => $po->no_rekening,
                'bankSupplierAccount' => $po->bankSupplierAccount ? [
                    'id' => $po->bankSupplierAccount->id,
                    'nama_rekening' => $po->bankSupplierAccount->nama_rekening,
                    'no_rekening' => $po->bankSupplierAccount->no_rekening,
                    'bank' => $po->bankSupplierAccount->bank ? [
                        'id' => $po->bankSupplierAccount->bank->id,
                        'nama_bank' => $po->bankSupplierAccount->bank->nama_bank,
                    ] : null,
                ] : null,
                'bank' => $po->bank ? [
                    'id' => $po->bank->id,
                    'nama_bank' => $po->bank->nama_bank,
                ] : null,
                'credit_card' => $po->creditCard ? [
                    'id' => $po->creditCard->id,
                    'no_kartu_kredit' => $po->creditCard->no_kartu_kredit,
                    'nama_pemilik' => $po->creditCard->nama_pemilik,
                    'bank_name' => $po->creditCard->bank?->nama_bank,
                ] : null,
                // Helpers for client filtering
                // 'giro_id' => $po->metode_pembayaran === 'Cek/Giro' ? $po->id : null,
                'no_giro' => $po->no_giro,
                'credit_card_id' => $po->credit_card_id,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'current_page' => $purchaseOrders->currentPage(),
            'last_page' => $purchaseOrders->lastPage(),
            'total_count' => $purchaseOrders->total(),
            'filter_info' => [
                // 'metode_bayar' => $metode,
                'supplier_id' => $supplierId,
                'giro_id' => $giroId,
                'credit_card_id' => $creditCardId,
            ],
        ]);
    }

    /**
     * List available PV DP (tipe_pv = 'DP') for use as deductions in regular PVs.
     * Filters by purchase_order_id and supplier_id, and returns only DP PVs
     * with outstanding amount > 0 (based on payment_voucher_dp_allocations).
     */
    public function searchDpPaymentVouchers(Request $request)
    {
        $search = $request->input('search');
        $purchaseOrderId = (int) $request->input('purchase_order_id');
        $supplierId = (int) $request->input('supplier_id');
        $perPage = (int) $request->input('per_page', 20);

        $query = PaymentVoucher::withoutGlobalScope(DepartmentScope::class)
            ->with(['purchaseOrder:id,no_po,supplier_id', 'supplier:id,nama_supplier'])
            ->where('tipe_pv', 'DP')
            ->where('status', '!=', 'Canceled');

        if ($purchaseOrderId > 0) {
            $query->where('purchase_order_id', $purchaseOrderId);
        }
        if ($supplierId > 0) {
            $query->where('supplier_id', $supplierId);
        }
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('no_pv', 'like', "%{$search}%")
                  ->orWhereHas('purchaseOrder', function ($po) use ($search) {
                      $po->where('no_po', 'like', "%{$search}%");
                  })
                  ->orWhereHas('supplier', function ($s) use ($search) {
                      $s->where('nama_supplier', 'like', "%{$search}%");
                  });
            });
        }

        $paginator = $query->orderByDesc('id')->paginate($perPage);
        $items = collect($paginator->items());

        // Compute used amount per DP PV from allocations (exclude Canceled PVs)
        $dpPvIds = $items->pluck('id')->all();
        if (!empty($dpPvIds)) {
            try {
                $usedMap = DB::table('payment_voucher_dp_allocations as a')
                    ->join('payment_vouchers as pv', 'pv.id', '=', 'a.payment_voucher_id')
                    ->whereIn('a.dp_payment_voucher_id', $dpPvIds)
                    ->where('pv.status', '!=', 'Canceled')
                    ->selectRaw('a.dp_payment_voucher_id as dp_id, COALESCE(SUM(a.amount),0) as used')
                    ->groupBy('a.dp_payment_voucher_id')
                    ->pluck('used', 'dp_id');
            } catch (\Throwable $e) {
                $usedMap = collect();
            }
        } else {
            $usedMap = collect();
        }

        $data = $items
            // Only return DP PVs with outstanding > 0
            ->filter(function (PaymentVoucher $pv) use ($usedMap) {
                $nominal = (float) ($pv->nominal ?? 0);
                if ($nominal <= 0) {
                    return false;
                }
                $used = (float) ($usedMap[$pv->id] ?? 0);
                $outstanding = max(0.0, $nominal - $used);
                return $outstanding > 0.00001;
            })
            ->map(function (PaymentVoucher $pv) use ($usedMap) {
                $nominal = (float) ($pv->nominal ?? 0);
                $used = (float) ($usedMap[$pv->id] ?? 0);
                $outstanding = max(0.0, $nominal - $used);

                return [
                    'id' => $pv->id,
                    'no_pv' => $pv->no_pv,
                    'tanggal' => $pv->tanggal,
                    'status' => $pv->status,
                    'purchase_order_id' => $pv->purchase_order_id,
                    'no_po' => $pv->purchaseOrder?->no_po,
                    'supplier_id' => $pv->supplier_id,
                    'supplier_name' => $pv->supplier?->nama_supplier,
                    'nominal' => $nominal,
                    'used_amount' => $used,
                    'outstanding' => $outstanding,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total_count' => $paginator->total(),
            'filter_info' => [
                'purchase_order_id' => $purchaseOrderId,
                'supplier_id' => $supplierId,
            ],
        ]);
    }

    /**
     * List available BPBs (Approved) for a given Purchase Order, excluding those already used by non-Canceled PVs.
     */
    public function getBpbsForPurchaseOrder(Request $request, \App\Models\PurchaseOrder $purchase_order)
    {
        $currentPvId = $request->input('current_pv_id');

        $bpbs = Bpb::withoutGlobalScope(DepartmentScope::class)
            ->with(['paymentVoucher'])
            ->where('purchase_order_id', $purchase_order->id)
            ->where('status', 'Approved')
            ->orderBy('created_at', 'asc')
            ->get(['id','no_bpb','tanggal','grand_total','keterangan','purchase_order_id','payment_voucher_id']);

        // Compute used amount per BPB from allocations (exclude Canceled PVs); tolerate missing table
        try {
            $usedMap = DB::table('payment_voucher_bpb_allocations as a')
                ->join('payment_vouchers as pv','pv.id','=','a.payment_voucher_id')
                ->whereIn('a.bpb_id', $bpbs->pluck('id')->all())
                ->where('pv.status','!=','Canceled')
                ->selectRaw('a.bpb_id, COALESCE(SUM(a.amount),0) as used')
                ->groupBy('a.bpb_id')
                ->pluck('used','bpb_id');
        } catch (\Throwable $e) {
            $usedMap = collect();
        }

        $data = $bpbs->map(function($b) use ($usedMap) {
            $grand = (float) ($b->grand_total ?? 0);
            $used = (float) ($usedMap[$b->id] ?? 0);
            $out = max($grand - $used, 0);
            return [
                'id' => $b->id,
                'no_bpb' => $b->no_bpb,
                'tanggal' => $b->tanggal,
                'grand_total' => $grand,
                'outstanding' => $out,
                'keterangan' => $b->keterangan,
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Search Approved Memo Pembayaran for Payment Voucher (tipe Lainnya)
     */
    public function searchMemos(Request $request)
    {
        $search = $request->input('search');
        // $metode = $request->input('metode_bayar') ?: $request->input('metode_pembayaran');
        $supplierId = $request->input('supplier_id');
        $departmentId = $request->input('department_id');
        $currentPvId = $request->input('current_pv_id');
        $perPage = (int) $request->input('per_page', 20);

        $query = \App\Models\MemoPembayaran::query()
            ->with(['department', 'supplier', 'purchaseOrder.perihal', 'termin'])
            ->where('status', 'Approved')
            // Only memos that are tied to a PO with tipe_po = 'Lainnya'
            ->whereHas('purchaseOrder', function($po){
                $po->where('tipe_po', 'Lainnya');
            });

        if (!empty($departmentId)) {
            $query->where('department_id', $departmentId);
        }
        if (!empty($supplierId)) {
            $query->where(function($q) use ($supplierId){
                $q->where('supplier_id', $supplierId)
                  ->orWhereHas('purchaseOrder', function($qp) use ($supplierId){
                      $qp->where('supplier_id', $supplierId);
                  });
            });
        }
        // if (!empty($metode)) {
        //     $query->where('metode_pembayaran', $metode);
        // }
        if (!empty($search)) {
            $query->where(function($q) use ($search){
                $q->where('no_mb', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%")
                  ->orWhereHas('purchaseOrder', function($qp) use ($search){
                      $qp->where('no_po', 'like', "%{$search}%");
                  })
                  ->orWhereHas('supplier', function($qs) use ($search){
                      $qs->where('nama_supplier', 'like', "%{$search}%");
                  })
                  ->orWhereHas('department', function($qd) use ($search){
                      $qd->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Exclude memos that already have a Payment Voucher with status other than 'Canceled'.
        // Allow the memo if it is already linked to the current PV being edited.
        $query->whereDoesntHave('paymentVouchers', function($pv) use ($currentPvId) {
            $pv->where('status', '!=', 'Canceled');
            if (!empty($currentPvId)) {
                $pv->where('id', '!=', $currentPvId);
            }
        });

        $memos = $query->orderByDesc('created_at')->paginate($perPage);

        $data = collect($memos->items())->map(function($m){
            $po = $m->purchaseOrder;
            // Compute which installment number this memo represents within the same termin
            $terminKe = null;
            if ($m->termin_id) {
                try {
                    $terminKe = \App\Models\MemoPembayaran::where('termin_id', $m->termin_id)
                        ->where('created_at', '<=', $m->created_at)
                        ->orderBy('created_at')
                        ->count();
                } catch (\Throwable $e) {}
            }
            return [
                'id' => $m->id,
                'no_memo' => $m->no_mb,
                'tanggal' => $m->tanggal,
                'status' => $m->status,
                'total' => $m->total,
                'cicilan' => $m->cicilan,
                'nominal' => $m->total,
                'keterangan' => $m->keterangan,
                // 'metode_pembayaran' => $m->metode_pembayaran,
                'department' => $m->department ? [ 'id' => $m->department->id, 'name' => $m->department->name ] : null,
                'supplier' => $m->supplier ? [
                    'id' => $m->supplier->id,
                    'nama_supplier' => $m->supplier->nama_supplier,
                    'alamat' => $m->supplier->alamat,
                    'no_telepon' => $m->supplier->no_telepon,
                ] : null,
                'perihal' => $po && $po->perihal ? [ 'id' => $po->perihal->id, 'nama' => $po->perihal->nama ] : null,
                'purchase_order' => $po ? [ 'id' => $po->id, 'no_po' => $po->no_po ] : null,
                'termin' => $m->termin ? [
                    'jumlah_termin' => $m->termin->jumlah_termin,
                    'jumlah_termin_dibuat' => $m->termin->jumlah_termin_dibuat,
                    'total_cicilan' => $m->termin->total_cicilan,
                    'sisa_pembayaran' => $m->termin->sisa_pembayaran,
                    'no_referensi' => $m->termin->no_referensi ?? null,
                    'termin_ke' => $terminKe,
                ] : ($terminKe ? ['termin_ke' => $terminKe] : null),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'current_page' => $memos->currentPage(),
            'last_page' => $memos->lastPage(),
            'total_count' => $memos->total(),
        ]);
    }

    /**
     * Search Approved Po Anggaran for Payment Voucher (tipe Anggaran)
     */
    public function searchPoAnggaran(Request $request)
    {
        $search = $request->input('search');
        $bisnisPartnerId = $request->input('bisnis_partner_id');
        $departmentId = $request->input('department_id');
        $perPage = (int) $request->input('per_page', 20);
        $currentPvId = $request->input('current_pv_id');

        $query = PoAnggaran::query()
            ->with(['department','perihal','bisnisPartner','bank'])
            ->where('status', 'Approved');

        if (!empty($departmentId)) {
            $query->where('department_id', $departmentId);
        }
        if (!empty($bisnisPartnerId)) {
            $query->where('bisnis_partner_id', $bisnisPartnerId);
        }
        if (!empty($search)) {
            $query->where(function($q) use ($search){
                $q->where('no_po_anggaran', 'like', "%{$search}%")
                  ->orWhere('detail_keperluan', 'like', "%{$search}%")
                  ->orWhereHas('bisnisPartner', function($bp) use ($search){
                      $bp->where('nama_bp', 'like', "%{$search}%");
                  })
                  ->orWhereHas('perihal', function($p) use ($search){
                      $p->where('nama', 'like', "%{$search}%");
                  })
                  ->orWhereHas('department', function($d) use ($search){
                      $d->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Exclude Po Anggaran already used by existing PVs (allow current PV when editing)
        $query->whereNotExists(function($q) use ($currentPvId) {
            $q->select(DB::raw(1))
              ->from('payment_vouchers as pv')
              ->whereColumn('pv.po_anggaran_id', 'po_anggarans.id')
              ->when($currentPvId, function($qq) use ($currentPvId) {
                  $qq->where('pv.id', '!=', $currentPvId);
              })
              ->where('pv.status', '!=', 'Canceled');
        });

        $poas = $query->orderByDesc('created_at')->paginate($perPage);

        $data = collect($poas->items())->map(function($poa){
            // Compute outstanding for Po Anggaran based on existing PVs (exclude Draft/Canceled/Rejected)
            $grand = (float) ($poa->nominal ?? 0);
            try {
                $used = (float) DB::table('payment_vouchers')
                    ->where('po_anggaran_id', $poa->id)
                    ->whereNotIn('status', ['Draft','Canceled','Rejected'])
                    ->sum('nominal');
            } catch (\Throwable $e) { $used = 0.0; }
            $out = max(0, $grand - $used);
            return [
                'id' => $poa->id,
                'no_po_anggaran' => $poa->no_po_anggaran,
                'tanggal' => $poa->tanggal,
                'department' => $poa->department ? ['id'=>$poa->department->id,'name'=>$poa->department->name] : null,
                'perihal' => $poa->perihal ? ['id'=>$poa->perihal->id,'nama'=>$poa->perihal->nama] : null,
                'bisnis_partner' => $poa->bisnisPartner ? [
                    'id' => $poa->bisnisPartner->id,
                    'nama_bp' => $poa->bisnisPartner->nama_bp,
                    'no_telepon' => $poa->bisnisPartner->no_telepon,
                    'alamat' => $poa->bisnisPartner->alamat,
                ] : null,
                'bank' => $poa->bank ? ['id'=>$poa->bank->id,'nama_bank'=>$poa->bank->nama_bank] : null,
                'nama_rekening' => $poa->nama_rekening,
                'no_rekening' => $poa->no_rekening,
                'nominal' => $poa->nominal,
                'outstanding' => $out,
                'status' => $poa->status,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'current_page' => $poas->currentPage(),
            'last_page' => $poas->lastPage(),
            'total_count' => $poas->total(),
            'filter_info' => [
                'department_id' => $departmentId,
                'bisnis_partner_id' => $bisnisPartnerId,
            ],
        ]);
    }
}


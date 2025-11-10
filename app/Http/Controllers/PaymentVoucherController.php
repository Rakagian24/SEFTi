<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\PaymentVoucher;
use App\Scopes\DepartmentScope;
use App\Services\DocumentNumberService;
use App\Models\Department;
use App\Models\Supplier;
use App\Models\PaymentVoucherDocument;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PaymentVoucherExport;

class PaymentVoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $userRole = $user->role->name ?? '';

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
        ];
        // DepartmentScope policy:
        // - Admin: bypass DepartmentScope
        // - Staff Toko & Staff Digital Marketing: own-created only (bypass DepartmentScope)
        // - Other roles (incl. Staff Akunting & Finance, Kepala Toko, Kabag, Direksi): rely on DepartmentScope
        $roleLower = strtolower($userRole);
        if ($userRole === 'Admin') {
            $query = PaymentVoucher::withoutGlobalScope(DepartmentScope::class)
                ->with($with);
        } elseif (in_array($roleLower, ['staff toko','staff digital marketing'], true)) {
            $query = PaymentVoucher::withoutGlobalScope(DepartmentScope::class)
                ->with($with)
                ->where('creator_id', $user->id);
        } else {
            $query = PaymentVoucher::query()->with($with);
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
                    // unified reference number: PO for non-Lainnya, Memo for Lainnya
                    'reference_number' => ($pv->tipe_pv === 'Lainnya')
                        ? ($pv->memoPembayaran?->no_mb)
                        : ($pv->purchaseOrder?->no_po),
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
            'supplierOptions' => Supplier::query()->active()->select(['id','nama_supplier','department_id'])->orderBy('nama_supplier')->get()->map(fn($s)=>['value'=>$s->id,'label'=>$s->nama_supplier,'department_id'=>$s->department_id])->values(),
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

        return Inertia::render('payment-voucher/Create', [
            'userRole' => $user->role->name ?? '',
            'userPermissions' => $user->role->permissions ?? [],
            'departmentOptions' => $departments,
            'defaultDepartmentId' => (function() use ($user) {
                $userDepts = ($user->departments ?? collect())->reject(function($d){ return strtolower($d->name ?? '') === 'all'; });
                return $userDepts->count() === 1 ? ($userDepts->first()->id ?? null) : null;
            })(),
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
            'tipe_pv' => 'nullable|string|in:Reguler,Anggaran,Lainnya,Pajak,Manual',
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
        } else {
            // Non-manual, non-lainnya uses PO; ensure memo cleared
            $data['memo_pembayaran_id'] = null;
        }

        // If tipe Lainnya and memo selected, set bank account from memo
        if ((($data['tipe_pv'] ?? $pv->tipe_pv) === 'Lainnya')) {
            $memoId = $data['memo_pembayaran_id'] ?? $pv->memo_pembayaran_id;
            if (!empty($memoId)) {
                $memo = \App\Models\MemoPembayaran::select('bank_supplier_account_id')->find($memoId);
                if ($memo) {
                    $data['bank_supplier_account_id'] = $memo->bank_supplier_account_id;
                }
            }
        }

        // Normalize fields according to metode_bayar
        // $effectiveMetode = $data['metode_bayar'] ?? $pv->metode_bayar;
        // if ($effectiveMetode === 'Kartu Kredit') {
        //     // Kredit mode: supplier not required on PV (comes from PO), keep credit_card_id
        //     $data['supplier_id'] = null;
        // } elseif ($effectiveMetode === 'Transfer') {
        //     // Transfer mode: ensure credit_card_id cleared
        //     $data['credit_card_id'] = null;
        // }

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

        // No pivot: single purchase_order_id now used

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

        $data = $request->validate([
            'tipe_pv' => 'nullable|string|in:Reguler,Anggaran,Lainnya,Pajak,Manual',
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
        } elseif (!empty($data['tipe_pv'])) {
            // Non-manual, non-lainnya uses PO; ensure memo cleared
            $data['memo_pembayaran_id'] = null;
        }

        // If tipe Lainnya and memo selected, set bank account from memo (draft creation)
        if ((($data['tipe_pv'] ?? null) === 'Lainnya') && !empty($data['memo_pembayaran_id'])) {
            $memo = \App\Models\MemoPembayaran::select('bank_supplier_account_id')->find($data['memo_pembayaran_id']);
            if ($memo) {
                $data['bank_supplier_account_id'] = $memo->bank_supplier_account_id;
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

        // No pivot: single purchase_order_id now used

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
                'tipe_pv' => 'nullable|string|in:Reguler,Anggaran,Lainnya,Pajak,Manual',
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
                $data['purchase_order_id'] = null;
            } elseif (!empty($data['tipe_pv'])) {
                $data['memo_pembayaran_id'] = null;
            }
            // Normalize by metode_bayar
            // $effectiveMetode = $data['metode_bayar'] ?? null;
            // if ($effectiveMetode === 'Kartu Kredit') {
            //     $data['supplier_id'] = null;
            // } elseif ($effectiveMetode === 'Transfer') {
            //     $data['credit_card_id'] = null;
            // }

            // If tipe Lainnya and memo selected in payload flow, set bank account from memo
            if ((($data['tipe_pv'] ?? null) === 'Lainnya') && !empty($data['memo_pembayaran_id'])) {
                $memo = \App\Models\MemoPembayaran::select('bank_supplier_account_id')->find($data['memo_pembayaran_id']);
                if ($memo) {
                    $data['bank_supplier_account_id'] = $memo->bank_supplier_account_id;
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

        return $errors;
    }

    /** Download a document */
    public function downloadDocument(PaymentVoucherDocument $document)
    {
        return Storage::download($document->path, $document->original_name ?: 'document.pdf');
    }

    /** Delete a document */
    public function deleteDocument(PaymentVoucherDocument $document)
    {
        if ($document->path) Storage::delete($document->path);
        $document->delete();
        return back();
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
                'logoSrc' => $logoSrc,
                'signatureSrc' => $signatureSrc,
                'approvedSrc' => $approvedSrc,
            ])->setOptions(config('dompdf.options'))
              ->setPaper('a4','portrait');

            $filename = 'PaymentVoucher_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $pv->no_pv ?? 'Draft') . '.pdf';
            return $pdf->download($filename);
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

        // Exclude POs already used by existing Payment Vouchers (allow current PV when editing)
        $query->whereNotExists(function($q) use ($currentPvId) {
            $q->select(DB::raw(1))
              ->from('payment_vouchers as pv')
              ->whereColumn('pv.purchase_order_id', 'purchase_orders.id')
              // Allow currently edited PV to keep its PO in the list
              ->when($currentPvId, function($qq) use ($currentPvId) {
                  $qq->where('pv.id', '!=', $currentPvId);
              })
              // Exclude POs used by ANY PV that is not canceled
              // Only when PV is 'Canceled' the PO becomes available again
              ->where('pv.status', '!=', 'Canceled');
        });

        // Filter by tipe_pv -> map to purchase_orders.tipe_po
        if (in_array($tipePv, ['Reguler','Anggaran','Lainnya'], true)) {
            $query->where('tipe_po', $tipePv);
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

        $data = collect($purchaseOrders->items())->map(function($po){
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
            ->where('status', 'Approved');

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
}


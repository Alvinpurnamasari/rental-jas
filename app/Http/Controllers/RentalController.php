<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ActivityLog;
use App\Services\RentalService;
use App\Http\Requests\StoreRentalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class RentalController extends Controller
{
    public function __construct(protected RentalService $rentalService) {}

    private function qrisData(): array
    {
        $qrisPath = \App\Services\SettingsService::get('qris_image');
        if (!$qrisPath || !Storage::disk('public')->exists($qrisPath)) {
            return ['available' => false, 'url' => null];
        }

        $fullPath = storage_path('app/public/' . $qrisPath);
        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        $url = 'data:image/' . $extension . ';base64,' . base64_encode(file_get_contents($fullPath));

        return ['available' => true, 'url' => $url];
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Rental::with(["customer", "items", "createdBy", "branch"]);

        if ($user->role === "sales") {
            $query->where("created_by", $user->id);
        } elseif (!$user->isSuperAdmin()) {
            $query->where("branch_id", $user->branch_id);
        }

        if ($request->has("status") && $request->status) {
            $query->where("rental_status", $request->status);
        }

        if ($request->has("search") && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where("invoice_number", "like", "%$search%")
                  ->orWhereHas("customer", function($q2) use ($search) {
                      $q2->where("name", "like", "%$search%")
                         ->orWhere("phone", "like", "%$search%");
                  });
            });
        }

        $sort = $request->get("sort", "created_at");
        $order = $request->get("order", "desc");
        $allowedSorts = ["created_at", "rental_date", "total_amount", "invoice_number"];
        if (!in_array($sort, $allowedSorts, true)) $sort = "created_at";
        $order = $order === "asc" ? "asc" : "desc";

        $rentals = $query->orderBy($sort, $order)->paginate(15)->withQueryString();
        return view("rentals.index", compact("rentals"));
    }

    public function create()
    {
        $user = Auth::user();
        $customers = Customer::when($user->role === "sales", function($q) use ($user) {
            $q->where("user_id", $user->id);
        })->when(!$user->isSuperAdmin() && $user->role !== "sales", function($q) use ($user) {
            $q->where("branch_id", $user->branch_id);
        })->get();

        $products = Product::when(!$user->isSuperAdmin(), function($q) use ($user) {
            $q->whereHas('branches', fn($bq) => $bq->where('branches.id', $user->branch_id));
        })->where("status", "available")->where("stock_available", ">", 0)->get();

        $qris = $this->qrisData();

        return view("rentals.create", compact("customers", "products", "qris"));
    }

    public function store(StoreRentalRequest $request)
    {
        $rental = $this->rentalService->createRental($request->validated());
        return redirect()->route("rentals.show", $rental)->with("success", "Penyewaan berhasil dibuat!");
    }

    public function show(Rental $rental)
    {
        $rental->load(["customer", "items.product.category", "createdBy", "payments", "guarantees", "activityLogs.user", "branch"]);
        $rentalData = $this->rentalService->buildDetailPayload($rental);
        $qris = $this->qrisData();
        return view("rentals.show", compact("rental", "rentalData", "qris"));
    }

    public function edit(Rental $rental)
    {
        $user = Auth::user();
        if (!$user || !$user->canManage()) abort(403);

        $rental->load(["customer", "items.product", "guarantees"]);
        $customers = Customer::when(!$user->isSuperAdmin(), function($q) use ($user) {
            $q->where("branch_id", $user->branch_id);
        })->get();

        $products = Product::whereHas("category", function($q) {
            $q->where("name", "like", "Jas%");
        })->when(!$user->isSuperAdmin(), function($q) use ($user) {
            $q->where("branch_id", $user->branch_id);
        })->get();

        $qris = $this->qrisData();

        return view("rentals.edit", compact("rental", "customers", "products", "qris"));
    }

    public function update(Request $request, Rental $rental)
    {
        $user = Auth::user();
        if (!$user || !$user->canManage()) abort(403);

        $isLocked = $rental->payment_status === Rental::PAYMENT_PAID
            || $rental->rental_status === Rental::STATUS_ACTIVE;

        if ($isLocked) {
            $lockedFieldErrors = [];

            if ((string) $request->input('customer_id') !== (string) $rental->customer_id) {
                $lockedFieldErrors[] = 'Pelanggan';
            }
            if ($request->input('rental_date') !== $rental->rental_date?->format('Y-m-d')) {
                $lockedFieldErrors[] = 'Tanggal Sewa';
            }
            if ($request->input('return_due_date') !== $rental->return_due_date?->format('Y-m-d')) {
                $lockedFieldErrors[] = 'Tanggal Pengembalian';
            }

            $originalItemMap = $rental->items()
                ->get(['product_id', 'quantity'])
                ->mapWithKeys(fn($i) => [(string) $i->product_id => (string) $i->quantity])
                ->all();

            $submittedItemMap = collect($request->input('items', []))
                ->mapWithKeys(fn($i) => [(string) $i['product_id'] => (string) $i['quantity']])
                ->all();

            if (count($originalItemMap) !== count($submittedItemMap) || $originalItemMap !== $submittedItemMap) {
                $lockedFieldErrors[] = 'Item Disewa (produk/jumlah)';
            }

            if ($lockedFieldErrors) {
                abort(422, 'Field berikut sudah dikunci karena penyewaan sudah dibayar/berjalan dan tidak dapat diubah: ' . implode(', ', $lockedFieldErrors));
            }
        }

        $data = $request->validate([
            "customer_id" => "required|exists:customers,id",
            "rental_date" => "required|date",
            "duration_days" => "required|integer|min:1",
            "discount" => "nullable|numeric|min:0",
            "deposit" => "nullable|numeric|min:0",
            "rental_status" => "nullable|in:waiting,active,overdue,returned,cancelled",
            "payment_status" => "nullable|in:unpaid,partial,paid",
            "return_due_date" => "nullable|date",
            "notes" => "nullable|string",
            "payment_method" => "nullable|string|in:cash,qris,transfer",
            "guarantee_type" => "nullable|string|in:ktp,sim,deposit,custom",
            "guarantee_id_number" => "nullable|string|max:50",
            "guarantee_notes" => "nullable|string|max:500",
            "remove_guarantee_photo" => "nullable|in:0,1",
            "guarantee_id_photo" => "nullable|image|mimes:jpeg,png,jpg|max:2048",
            "items" => "required|array|min:1",
            "items.*.product_id" => "required|exists:products,id",
            "items.*.product_size" => "required|in:XS,S,M,L,XL,XXL,3XL,4XL",
            "items.*.quantity" => "required|integer|min:1",
        ]);

        DB::transaction(function () use ($rental, $data, $request) {
            $rental->update([
                "customer_id" => $data["customer_id"],
                "rental_date" => $data["rental_date"],
                "duration_days" => (int) $data["duration_days"],
                "discount" => (float) ($data["discount"] ?? 0),
                "deposit" => (float) ($data["deposit"] ?? 0),
                "rental_status" => $data["rental_status"] ?? $rental->rental_status,
                "notes" => $data["notes"] ?? null,
            ]);

            if (!in_array($rental->rental_status, ["cancelled","returned"], true)) {
                $rental->update([
                    "return_due_date" => Carbon::parse($data["rental_date"])->addDays((int) $data["duration_days"]),
                ]);
            }

            $durationDays = (int) $data["duration_days"];
            $submitted = collect($data["items"])->map(function ($row) {
                return [
                    "product_id" => (int) $row["product_id"],
                    "product_size" => (string) $row["product_size"],
                    "quantity" => (int) $row["quantity"],
                ];
            });

            $deduped = $submitted->groupBy(fn($r) => $r["product_id"]."|".$r["product_size"]);
            if ($deduped->count() !== $submitted->count()) {
                abort(422, "Duplicate rental items are not allowed.");
            }

            // Lock and restore old stock
            $oldItems = $rental->items()->get(["id","product_id","quantity"]);
            foreach ($oldItems as $oldItem) {
                $product = Product::whereKey($oldItem->product_id)->lockForUpdate()->firstOrFail();
                $product->stock_available = (int)$product->stock_available + (int)$oldItem->quantity;
                $product->save();
            }

            $subtotal = 0;
            $existingByKey = $rental->items->keyBy(fn($it) => $it->product_id."|".$it->product_size);
            $seenKeys = [];

            // Lock and deduct new stock
            foreach ($deduped as $key => $rows) {
                $row = $rows->first();
                $seenKeys[] = $key;
                $product = Product::whereKey($row["product_id"])->lockForUpdate()->firstOrFail();
                if (!str_starts_with($product->category?->name ?? "", "Jas")) abort(422, "Invalid category for product.");

                $requestedQty = (int)$row["quantity"];
                if ($requestedQty <= 0) abort(422, "Quantity must be > 0.");
                if ((int)$product->stock_available < $requestedQty) abort(422, "Requested stock is not available.");

                $product->stock_available = (int)$product->stock_available - $requestedQty;
                $product->status = ((int)$product->stock_available <= 0) ? "rented" : $product->status;
                $product->save();

                $itemSubtotal = (float)$product->rental_price * $requestedQty * $durationDays;
                $subtotal += $itemSubtotal;

                if ($existingByKey->has($key)) {
                    $item = $existingByKey->get($key);
                    $item->update([
                        "product_id" => $product->id,
                        "product_name" => $product->name,
                        "product_size" => $row["product_size"],
                        "product_color" => $product->color,
                        "quantity" => $requestedQty,
                        "price_per_day" => $product->rental_price,
                        "duration_days" => $durationDays,
                        "subtotal" => $itemSubtotal,
                    ]);
                } else {
                    $rental->items()->create([
                        "product_id" => $product->id,
                        "product_name" => $product->name,
                        "product_size" => $row["product_size"],
                        "product_color" => $product->color,
                        "quantity" => $requestedQty,
                        "price_per_day" => $product->rental_price,
                        "duration_days" => $durationDays,
                        "subtotal" => $itemSubtotal,
                    ]);
                }
            }

            foreach ($rental->items()->get() as $item) {
                $key = $item->product_id."|".$item->product_size;
                if (!in_array($key, $seenKeys, true)) $item->delete();
            }

            $discount = (float)($data["discount"] ?? 0);
            $totalAmount = (float)$subtotal - $discount;
            $rental->update([
                "subtotal" => (float)$subtotal,
                "discount" => $discount,
                "deposit" => (float)($data["deposit"] ?? 0),
                "total_amount" => (float)$totalAmount,
            ]);

            if (!empty($data["payment_method"])) {
                $rental->update(["payment_method" => $data["payment_method"]]);
            }

            $guaranteeType = $data["guarantee_type"] ?? null;
            $existingGuarantee = $rental->guarantees()->first();

            if ($guaranteeType) {
                $guaranteeData = [
                    "type" => $guaranteeType,
                    "id_number" => $data["guarantee_id_number"] ?? null,
                    "description" => $data["guarantee_notes"] ?? null,
                ];

                if ($request->hasFile("guarantee_id_photo")) {
                    if ($existingGuarantee && $existingGuarantee->id_photo) {
                        Storage::disk("public")->delete($existingGuarantee->id_photo);
                    }
                    $guaranteeData["id_photo"] = $request->file("guarantee_id_photo")->store("guarantees/id_photos", "public");
                } elseif ($data["remove_guarantee_photo"] ?? false) {
                    if ($existingGuarantee && $existingGuarantee->id_photo) {
                        Storage::disk("public")->delete($existingGuarantee->id_photo);
                    }
                    $guaranteeData["id_photo"] = null;
                }

                if ($existingGuarantee) {
                    $existingGuarantee->update($guaranteeData);
                } else {
                    $guaranteeData["rental_id"] = $rental->id;
                    $guaranteeData["deposit_amount"] = $guaranteeType === 'deposit' ? (float) ($data["guarantee_deposit"] ?? 0) : 0;
                    $guaranteeData["status"] = "held";
                    \App\Models\Guarantee::create($guaranteeData);
                }
            }

            $rental->refresh(["payments"]);
            $this->rentalService->recalculatePaymentStatus($rental);

            if (!empty($data["return_due_date"])) {
                $rental->update(["return_due_date" => Carbon::parse($data["return_due_date"])->toDateString()]);
            }
        });

        return redirect()->route("rentals.show", $rental)->with("success", "Penyewaan berhasil diperbarui!");
    }

    public function destroy(Rental $rental)
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) abort(403);

        DB::transaction(function () use ($rental) {
            foreach ($rental->items as $item) {
                $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                $newStock = $product->stock_available + $item->quantity;
                $product->update([
                    "stock_available" => $newStock,
                    "status" => ($product->status === "rented" && $newStock > 0) ? "available" : $product->status,
                ]);
            }
            $rental->delete();
        });

        return redirect()->route("rentals.index")->with("success", "Penyewaan berhasil dihapus!");
    }

    public function scanPage()
    {
        return view("rentals.scan");
    }

    public function scanQr(string $invoice)
    {
        $raw = trim($invoice);
        $code = strtoupper($raw);

        if (preg_match("/^(INV|RCPT-INV)\d{12}$/", $code)) {
            $rental = Rental::with(["customer", "branch", "items.product.category", "guarantees", "activityLogs.user", "returnedBy", "createdBy"])->where("invoice_number", $code)->first();
            if (!$rental) {
                return view("rentals.scan-result", ["rental" => null, "error" => "Penyewaan dengan nomor invoice '{$code}' tidak ditemukan dalam sistem."]);
            }
            return view("rentals.scan-result", compact("rental"));
        }

        if (preg_match("/^\d+$/", $raw)) {
            $rentalId = (int) $raw;
            $rental = Rental::with(["customer", "branch", "items.product.category", "guarantees", "activityLogs.user", "returnedBy", "createdBy"])->find($rentalId);
            if (!$rental) {
                return view("rentals.scan-result", ["rental" => null, "error" => "Penyewaan dengan ID '{$rentalId}' tidak ditemukan dalam sistem."]);
            }
            return view("rentals.scan-result", compact("rental"));
        }

        return view("rentals.scan-result", ["rental" => null, "error" => "Format QR Code tidak valid. Format yang diterima: INV2026070310014, RCPT-INV2026070310014, atau ID rental numerik (contoh: 93)"]);
    }

    public function processPayment(Request $request, Rental $rental)
    {
        $this->rentalService->processPayment($rental, $request->validate([
            "amount" => "required|numeric|min:0",
            "method" => "required|string",
            "payment_type" => ["nullable", "in:rental,late_fee,damage_fee,deposit"],
            "reference_number" => "nullable|string",
            "notes" => "nullable|string",
        ]));
        $rental->refresh();
        $this->rentalService->recalculatePaymentStatus($rental);
        return back()->with("success", "Pembayaran berhasil diproses!");
    }

    public function processReturn(Request $request, Rental $rental)
    {
        $this->rentalService->processReturn($rental, $request->all());
        return back()->with("success", "Pengembalian berhasil diproses!");
    }

    public function cancel(Rental $rental)
    {
        DB::transaction(function () use ($rental) {
            $rental->update(["rental_status" => Rental::STATUS_CANCELLED]);
            foreach ($rental->items as $item) {
                if (!$item->is_returned) {
                    $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                    $newStock = $product->stock_available + $item->quantity;
                    $product->update([
                        "stock_available" => $newStock,
                        "status" => ($product->status === "rented" && $newStock > 0) ? "available" : $product->status,
                    ]);
                }
            }
        });
        return back()->with("success", "Penyewaan berhasil dibatalkan!");
    }

    public function cancelReturn(Rental $rental)
    {
        DB::transaction(function () use ($rental) {
            $newStatus = $rental->return_due_date->isPast() ? Rental::STATUS_OVERDUE : Rental::STATUS_ACTIVE;
            $rental->update([
                "rental_status" => $newStatus,
                "returned_at" => null,
                "actual_return_date" => null,
                "overdue_days" => null,
            ]);
            foreach ($rental->items as $item) {
                $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                $newStock = $product->stock_available - $item->quantity;
                $product->update([
                    "stock_available" => $newStock,
                    "status" => $newStock <= 0 ? "rented" : $product->status,
                ]);
                $item->update(["is_returned" => false]);
            }
        });
        return back()->with("success", "Pengembalian berhasil dibatalkan!");
    }

    public function exportPdf(Rental $rental)
    {
        $rental->loadMissing(['customer', 'branch', 'items.product.category', 'createdBy']);
        $pdf = Pdf::loadView("rentals.pdf", compact("rental"));
        return $pdf->download($rental->invoice_number . ".pdf");
    }

    public function whatsapp(Rental $rental)
    {
        $message = urlencode("Halo " . optional($rental->customer)->name . ",\n\nTerima kasih telah menggunakan layanan SewaJas.\n\nBerikut adalah Invoice Anda.\n\nNomor Invoice: {$rental->invoice_number}\n\nSilakan unduh PDF melalui tautan berikut:\n" . route("rentals.pdf", $rental) . "\n\nApabila ada pertanyaan, silakan hubungi kami.\nTerima kasih.");
        $waUrl = "https://wa.me/" . optional($rental->customer)->phone . "?text={$message}";
        return redirect()->away($waUrl);
    }

    public function sendReminder(Rental $rental)
    {
        $message = urlencode("Halo " . optional($rental->customer)->name . ",\n\nReminder untuk pengembalian penyewaan:\nInvoice: {$rental->invoice_number}\nJatuh Tempo: " . optional($rental->return_due_date)->format('d/m/Y') . "");
        $waUrl = "https://wa.me/" . optional($rental->customer)->phone . "?text={$message}";
        return redirect()->away($waUrl);
    }

    public function confirmReturnAjax(Request $request, Rental $rental)
    {
        $validated = $request->validate([
            "payment_status" => ["required", "in:unpaid,partial,paid"],
            "items" => ["required", "array"],
            "items.*.id" => ["required", "integer"],
            "items.*.condition" => ["required", "string"],
            "items.*.notes" => ["nullable", "string"],
            "items.*.damage_fee" => ["nullable", "numeric", "min:0"],
        ]);

        $itemsPayload = $validated["items"] ?? [];
        foreach ($itemsPayload as $row) {
            if (empty($row["condition"])) {
                return response()->json(["message" => "Masih ada barang yang belum diperiksa."], 422);
            }
            if (in_array($row["condition"], ["rusak_ringan", "rusak_berat", "lost"]) && empty($row["damage_fee"])) {
                return response()->json(["message" => "Denda harus diisi untuk barang yang rusak atau hilang."], 422);
            }
        }

        $processPayload = [
            "items" => array_map(function ($row) {
                $conditionMap = ["good" => "baik", "rusak_ringan" => "rusak_ringan", "rusak_berat" => "rusak_berat", "lost" => "hilang"];
                return [
                    "rental_item_id" => (int) $row["id"],
                    "condition" => $conditionMap[$row["condition"]] ?? $row["condition"],
                    "notes" => $row["notes"] ?? null,
                    "damage_fee" => $row["damage_fee"] ?? 0,
                ];
            }, $itemsPayload)
        ];

        $this->rentalService->processReturn($rental, $processPayload);
        $rental->refresh();
        $rental->load(["customer", "branch", "createdBy", "items.product.category", "activityLogs.user", "returnedBy", "payments"]);

        return response()->json(["success" => true, "rental" => [
            "id" => $rental->id,
            "invoice_number" => $rental->invoice_number,
            "rental_status" => $rental->rental_status,
            "payment_status" => $rental->payment_status,
            "fine_status" => $rental->fine_status,
            "fine_amount" => (float) $rental->fine_amount,
            "fine_paid_amount" => (float) $rental->fine_paid_amount,
            "returned_at" => $rental->returned_at?->format("d M Y H:i"),
            "returned_by" => $rental->returnedBy?->name ?? optional($rental->createdBy)->name,
            "total_amount" => (float) $rental->total_amount,
            "paid_amount" => (float) $rental->paid_amount,
            "remaining_amount" => (float) $rental->remaining_amount,
            "late_fee" => (float) $rental->late_fee,
            "overdue_days" => (int) $rental->overdue_days,
            "subtotal" => $rental->subtotal ?? null,
            "discount" => $rental->discount ?? null,
            "deposit" => (float) ($rental->guarantees->where('type', 'deposit')->sum('deposit_amount') ?? 0),
            "return_due_date" => $rental->return_due_date?->format("d/m/Y"),
            "customer" => ["name" => optional($rental->customer)->name, "phone" => optional($rental->customer)->phone],
            "items" => $rental->items->map(function ($item) {
                return [
                    "id" => $item->id,
                    "product_name" => optional($item->product)->name,
                    "size" => $item->size,
                    "quantity" => $item->quantity,
                    "price" => (float) $item->price,
                    "subtotal" => (float) $item->subtotal,
                    "photo" => $item->product && $item->product->photo ? asset("storage/".$item->product->photo) : null,
                    "return_condition" => $item->return_condition,
                    "return_notes" => $item->return_notes,
                    "is_returned" => (bool) $item->is_returned,
                    "damage_fee" => (float) ($item->damage_fee ?? 0),
                ];
            }),
            "activity_logs" => $rental->activityLogs->take(10)->map(function ($log) {
                return [
                    "id" => $log->id,
                    "description" => $log->description,
                    "user" => optional($log->user)->name,
                    "created_at" => optional($log->created_at)->format("d M Y H:i"),
                ];
            }),
        ]]);
    }

    public function paymentUpdate(Request $request, Rental $rental, \App\Models\Payment $payment)
    {
        $this->rentalService->updatePayment($rental, $payment, $request->validate([
            "amount" => "required|numeric|min:0",
            "method" => "required|string",
            "reference_number" => "nullable|string",
            "notes" => "nullable|string",
        ]));
        return back()->with("success", "Pembayaran berhasil diperbarui!");
    }

    public function paymentDestroy(Rental $rental, \App\Models\Payment $payment)
    {
        $this->rentalService->deletePayment($rental, $payment);
        return back()->with("success", "Pembayaran berhasil dihapus!");
    }

    public function paymentRefund(Rental $rental, \App\Models\Payment $payment)
    {
        $this->rentalService->refundPayment($rental, $payment);
        return back()->with("success", "Refund berhasil diproses!");
    }

    public function paymentVoid(Rental $rental, \App\Models\Payment $payment)
    {
        $this->rentalService->voidPayment($rental, $payment);
        return back()->with("success", "Void pembayaran berhasil diproses!");
    }

    public function markRefundGiven(Request $request, Rental $rental)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        $this->rentalService->recordRefund($rental, $validated['amount'], $validated['notes'] ?? null);
        $rental->refresh();
        $rental->load(['customer', 'branch', 'guarantees', 'payments', 'items.product', 'activityLogs.user']);

        return response()->json([
            'success' => true,
            'message' => 'Kembalian berhasil dicatat!',
            'rental' => [
                'id' => $rental->id,
                'invoice_number' => $rental->invoice_number,
                'change_amount' => (float) $rental->change_amount,
                'overpayment' => (float) $rental->change_amount,
                'refund_given' => (float) abs($rental->payments()->where('type', 'refund')->sum('amount')),
                'payment_status' => $rental->payment_status,
                'fine_status' => $rental->fine_status,
                'customer' => ['name' => optional($rental->customer)->name, 'phone' => optional($rental->customer)->phone],
                'branch' => ['name' => optional($rental->branch)->name],
                'guarantees' => $rental->guarantees->map(fn($g) => ['id' => $g->id, 'type' => $g->type, 'status' => $g->status]),
                'payments' => $rental->payments->map(fn($p) => ['id' => $p->id, 'payment_number' => $p->payment_number, 'amount' => (float) $p->amount, 'method' => $p->method, 'paid_at' => $p->paid_at?->format('d/m/Y H:i')]),
                'activity_logs' => $rental->activityLogs->take(10)->map(fn($log) => ['id' => $log->id, 'description' => $log->description, 'user' => optional($log->user)->name, 'created_at' => $log->created_at?->format('d M Y H:i')]),
                'items' => $rental->items->map(fn($item) => ['id' => $item->id, 'product_name' => optional($item->product)->name, 'size' => $item->size, 'quantity' => $item->quantity, 'price' => (float) optional($item)->price]),
            ],
        ]);
    }

    public function handoverRental(Request $request, Rental $rental)
    {
        if ($rental->rental_status !== Rental::STATUS_WAITING) {
            return response()->json([
                'success' => false,
                'message' => 'Rental ini sudah tidak dalam status Booking. Serah terima hanya dapat dilakukan untuk rental dengan status Booking.',
            ], 422);
        }

        $oldValues = $rental->getAttributes();
        $updateData = [
            'rental_status' => Rental::STATUS_ACTIVE,
        ];

        DB::transaction(function () use ($rental, $oldValues, $updateData) {
            $rental->update($updateData);

            ActivityLog::create([
                'user_id'     => auth()->id(),
                'branch_id'   => auth()->user()->branch_id,
                'action'      => 'handover_rental',
                'model_type'  => Rental::class,
                'model_id'    => $rental->id,
                'description' => auth()->user()->name . ' melakukan serah-terima jas kepada customer',
                'old_values'  => $oldValues,
                'new_values'  => $updateData,
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ]);
        });

        $rental->refresh();
        $rental->load(['customer', 'branch', 'createdBy', 'items.product', 'activityLogs.user']);

        return response()->json([
            'success' => true,
            'message' => 'Serah terima berhasil! Rental sekarang aktif.',
            'rental'  => [
                'id'                     => $rental->id,
                'invoice_number'         => $rental->invoice_number,
                'rental_status'          => $rental->rental_status,
                'payment_status'         => $rental->payment_status,
                'returned_at'            => $rental->returned_at?->format('d M Y H:i'),
                'returned_by'            => optional($rental->returnedBy)->name ?? optional($rental->createdBy)->name,
                'total_amount'           => (float) $rental->total_amount,
                'paid_amount'            => (float) $rental->paid_amount,
                'remaining_amount'       => (float) $rental->remaining_amount,
                'late_fee'               => (float) $rental->late_fee,
                'overdue_days'           => (int) $rental->overdue_days,
                'customer'               => ['name' => optional($rental->customer)->name, 'phone' => optional($rental->customer)->phone],
                'branch'                 => ['name' => optional($rental->branch)->name],
                'items'                  => $rental->items->map(fn($item) => [
                    'id'           => $item->id,
                    'product_name' => optional($item->product)->name,
                    'size'         => $item->size,
                    'quantity'     => $item->quantity,
                    'price'        => (float) optional($item)->price,
                ]),
                'rental_date'            => optional($rental->rental_date)->format('d/m/Y'),
                'return_due_date'        => optional($rental->return_due_date)->format('d/m/Y'),
                'activity_logs'          => $rental->activityLogs->take(10)->map(fn($log) => [
                    'id'          => $log->id,
                    'description' => $log->description,
                    'user'        => optional($log->user)->name,
                    'created_at'  => $log->created_at?->format('d M Y H:i'),
                ]),
            ],
        ]);
    }

    public function updateStatus(Request $request, Rental $rental)
    {
        $validated = $request->validate([
            "rental_status" => ["nullable", "in:active,returned,overdue"],
            "payment_status" => ["nullable", "in:unpaid,partial,paid"],
        ]);

        DB::transaction(function () use ($rental, $validated) {
            $oldValues = $rental->getAttributes();
            $updateData = [];
            $now = now();

            if (isset($validated["rental_status"])) {
                $updateData["rental_status"] = $validated["rental_status"];

                if ($validated["rental_status"] === "returned") {
                    $updateData["returned_at"] = $now;
                    $updateData["actual_return_date"] = $now->toDateString();
                    $updateData["returned_by"] = auth()->id();
                    $overdueDays = max(0, $now->diffInDays($rental->return_due_date, false) * -1);
                    $updateData["overdue_days"] = $overdueDays;
                    $lateFeePerDay = config("app.late_fee_per_day", 0);
                    $updateData["late_fee"] = $overdueDays * $lateFeePerDay;

                    foreach ($rental->items as $item) {
                        $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                        $newStock = $product->stock_available + $item->quantity;
                        $product->update(["stock_available" => $newStock, "status" => $newStock > 0 ? "available" : $product->status]);
                        $item->update(["is_returned" => true]);
                    }
                } else {
                    $updateData["returned_at"] = null;
                    $updateData["actual_return_date"] = null;
                    $updateData["returned_by"] = null;
                    $updateData["overdue_days"] = null;
                    $updateData["late_fee"] = 0;

                    if ($rental->rental_status === 'returned') {
                        foreach ($rental->items as $item) {
                            $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                            $newStock = $product->stock_available - $item->quantity;
                            $product->update(["stock_available" => $newStock, "status" => $newStock <= 0 ? "rented" : $product->status]);
                            $item->update(["is_returned" => false]);
                        }
                    }
                }
            }

            if (!empty($updateData)) {
                $rental->update($updateData);
                ActivityLog::create([
                    "user_id" => auth()->id(),
                    "branch_id" => auth()->user()->branch_id,
                    "action" => "update_status",
                    "model_type" => Rental::class,
                    "model_id" => $rental->id,
                    "description" => auth()->user()->name . " mengubah status penyewaan",
                    "old_values" => $oldValues,
                    "new_values" => $updateData,
                    "ip_address" => request()->ip(),
                    "user_agent" => request()->userAgent(),
                ]);
            }
        });

        $rental->refresh();
        $rental->load(["customer", "branch", "createdBy", "items.product", "activityLogs.user"]);

        $this->rentalService->addSalesPoints($rental, 'kembali', 5);

        return response()->json(["success" => true, "rental" => [
            "id" => $rental->id,
            "invoice_number" => $rental->invoice_number,
            "rental_status" => $rental->rental_status,
            "payment_status" => $rental->payment_status,
            "returned_at" => $rental->returned_at?->format("d M Y H:i"),
            "returned_by" => $rental->returnedBy?->name ?? optional($rental->createdBy)->name,
            "total_amount" => (float) $rental->total_amount,
            "paid_amount" => (float) $rental->paid_amount,
            "remaining_amount" => (float) $rental->remaining_amount,
            "late_fee" => (float) $rental->late_fee,
            "overdue_days" => (int) $rental->overdue_days,
            "customer" => ["name" => optional($rental->customer)->name, "phone" => optional($rental->customer)->phone],
            "branch" => ["name" => optional($rental->branch)->name],
            "items" => $rental->items->map(fn($item) => [
                "id" => $item->id,
                "product_name" => optional($item->product)->name,
                "size" => $item->size,
                "quantity" => $item->quantity,
                "price" => (float) optional($item)->price,
            ]),
            "rental_date" => optional($rental->rental_date)->format("d/m/Y"),
            "return_due_date" => optional($rental->return_due_date)->format("d/m/Y"),
            "activity_logs" => $rental->activityLogs->take(10)->map(fn($log) => [
                "id" => $log->id,
                "description" => $log->description,
                "user" => optional($log->user)->name,
                "created_at" => optional($log->created_at)->format("d M Y H:i"),
            ]),
        ]]);
    }
}


<?php

namespace App\Services;

use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\Product;
use App\Models\Payment;
use App\Models\Guarantee;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;

class RentalService
{
    /** Tarif denda keterlambatan tetap per hari. */
    private const LATE_FEE_PER_DAY = 25000;

    public function updatePayment(Rental $rental, \App\Models\Payment $payment, array $data): void
    {
        app(\App\Services\PaymentService::class)->update($rental, $payment, $data);
        $rental->refresh();
        $this->recalculatePaymentStatus($rental);
    }

    public function deletePayment(Rental $rental, \App\Models\Payment $payment): void
    {
        app(\App\Services\PaymentService::class)->delete($rental, $payment);
        $rental->refresh();
        $this->recalculatePaymentStatus($rental);
    }

    public function refundPayment(Rental $rental, \App\Models\Payment $payment): void
    {
        app(\App\Services\PaymentService::class)->refundPayment($rental, $payment);
        $rental->refresh();
        $this->recalculatePaymentStatus($rental);
    }

    public function voidPayment(Rental $rental, \App\Models\Payment $payment): void
    {
        app(\App\Services\PaymentService::class)->voidPayment($rental, $payment);
        $rental->refresh();
        $this->recalculatePaymentStatus($rental);
    }

    public function recordRefund(Rental $rental, float $amount, ?string $notes = null): void
    {
        $rental->refresh();
        $this->recalculatePaymentStatus($rental);
        $rental->refresh();

        $currentOverpayment = (float) ($rental->change_amount ?? 0);

        if ($amount > $currentOverpayment) {
            throw new \InvalidArgumentException(
                "Refund amount (Rp" . number_format($amount, 0, ',', '.') . ") exceeds available overpayment (Rp" . number_format($currentOverpayment, 0, ',', '.') . ")"
            );
        }

        $oldValues = $this->rentalSnapshot($rental);

        DB::transaction(function () use ($rental, $amount, $notes, $oldValues) {
            Payment::create([
                'rental_id'        => $rental->id,
                'received_by'      => Auth::id(),
                'payment_number'   => 'REFUND-' . now()->format('YmdHis'),
                'amount'           => 0 - $amount,
                'method'           => 'other',
                'type'             => 'refund',
                'notes'            => $notes ?? 'Kembalian overpayment',
                'paid_at'          => now(),
            ]);

            $this->recalculatePaymentStatus($rental);
            $newValues = $this->rentalSnapshot($rental);

            $this->logActivity('record_refund', $rental, $oldValues, $newValues);
        });
    }

    public function recalculatePaymentStatus(Rental $rental): array
    {
        $oldValues = $this->rentalSnapshot($rental);

        $rentalFee = (float) $rental->total_amount;
        $fineAmount = (float) $rental->fine_amount;

        // Tagihan sewa dan denda selalu dihitung terpisah berdasarkan tipe pembayaran.
        // Pembayaran denda tidak boleh melunasi tagihan sewa, dan sebaliknya.
        $totalPaidForRental = (float) $rental->payments()
            ->whereIn('type', ['rental', 'deposit'])
            ->sum('amount');

        $totalPaidForFine = (float) $rental->payments()
            ->whereIn('type', ['late_fee', 'damage_fee'])
            ->sum('amount');

        $remainingRental = max(0, $rentalFee - $totalPaidForRental);
        $remainingFine = max(0, $fineAmount - $totalPaidForFine);

        if ($totalPaidForRental <= 0) {
            $paymentStatus = Rental::PAYMENT_UNPAID;
        } elseif ($totalPaidForRental < $rentalFee) {
            $paymentStatus = Rental::PAYMENT_PARTIAL;
        } else {
            $paymentStatus = Rental::PAYMENT_PAID;
        }

        // Kembalian hanya berasal dari kelebihan pembayaran dalam kelompoknya sendiri.
        // Kelebihan pembayaran sewa tidak otomatis dialihkan untuk membayar denda.
        $refundGiven = (float) abs($rental->payments()
            ->where('type', 'refund')
            ->sum('amount'));

        $rentalOverpayment = max(0, $totalPaidForRental - $rentalFee);
        $fineOverpayment = max(0, $totalPaidForFine - $fineAmount);
        $changeAmount = max(0, $rentalOverpayment + $fineOverpayment - $refundGiven);

        // Denda dianggap terbayar hanya dari pembayaran bertipe late_fee/damage_fee.
        $finePaidAmount = min($fineAmount, max(0, $totalPaidForFine));

        if ($fineAmount <= 0) {
            $fineStatus = Rental::FINE_NONE;
        } elseif ($finePaidAmount >= $fineAmount) {
            $fineStatus = Rental::FINE_PAID;
        } elseif ($finePaidAmount > 0) {
            $fineStatus = Rental::FINE_PARTIAL;
        } else {
            $fineStatus = Rental::FINE_UNPAID;
        }

        $rental->update([
            'paid_amount' => $totalPaidForRental,
            'payment_status' => $paymentStatus,
            'remaining_amount' => $remainingRental,
            'fine_paid_amount' => $finePaidAmount,
            'fine_status' => $fineStatus,
            'change_amount' => $changeAmount,
        ]);

        $newValues = $this->rentalSnapshot($rental);

        $this->addSalesPoints($rental, 'serah', 3);

        return $newValues;
    }

    public function rentalSnapshotWhitelisted(Rental $rental): array
    {
        $whitelist = ActivityLogFormatter::whitelistFor('Rental');
        return array_intersect_key($this->rentalSnapshot($rental), array_flip($whitelist));
    }

    public function addSalesPoints(Rental $rental, string $type, int $points): void
    {
        $guardColumn = match ($type) {
            'serah' => 'points_awarded_serah',
            'kembali' => 'points_awarded_kembali',
            default => throw new \InvalidArgumentException('Invalid point type: ' . $type),
        };

        if ($rental->{$guardColumn}) {
            return;
        }

        $sales = match ($type) {
            'serah' => $rental->createdBy,
            'kembali' => $rental->returnedBy,
        };

        if (!$sales || !$sales->isSales()) {
            return;
        }

        $sales->increment('total_points', $points);
        $rental->update([$guardColumn => true]);
    }

    public function buildDetailPayload(Rental $rental): array
    {
        return [
            'id' => $rental->id,
            'invoice_number' => $rental->invoice_number,
            'rental_status' => $rental->rental_status,
            'payment_status' => $rental->payment_status,
            'rental_date' => $rental->rental_date?->format('d/m/Y') ?? '-',
            'return_due_date' => $rental->return_due_date?->format('d/m/Y') ?? '-',
            'created_by' => optional($rental->createdBy)->name,
            'returned_by' => optional($rental->returnedBy)->name,
            'subtotal' => (float) ($rental->subtotal ?? 0),
            'discount' => (float) ($rental->discount ?? 0),
            'deposit' => (float) ($rental->guarantees->where('type', 'deposit')->sum('deposit_amount') ?? 0),
            'total_amount' => (float) ($rental->total_amount ?? 0),
            'paid_amount' => (float) ($rental->paid_amount ?? 0),
            'remaining_amount' => (float) ($rental->remaining_amount ?? 0),
            'payment_balances' => [
                'rental' => max(
                    0,
                    (float) ($rental->total_amount ?? 0)
                    - (float) ($rental->paid_amount ?? 0)
                ),
                'late_fee' => max(
                    0,
                    (float) ($rental->late_fee ?? 0)
                    - (float) $rental->payments()->where('type', 'late_fee')->sum('amount')
                ),
                'damage_fee' => max(
                    0,
                    (float) $rental->items->sum('damage_fee')
                    - (float) $rental->payments()->where('type', 'damage_fee')->sum('amount')
                ),
                'deposit' => max(
                    0,
                    (float) $rental->guarantees
                        ->where('type', 'deposit')
                        ->sum('deposit_amount')
                ),
            ],
            'late_fee' => (float) ($rental->late_fee ?? 0),
            'fine_amount' => (float) ($rental->fine_amount ?? 0),
            'fine_paid_amount' => (float) ($rental->fine_paid_amount ?? 0),
            'fine_status' => $rental->fine_status ?? Rental::FINE_NONE,
            'payment_method' => $rental->payment_method ?? null,
            'notes' => $rental->notes ?? null,
            'change_amount' => (float) ($rental->change_amount ?? 0),
            'total_owed' => max(0, (float) ($rental->total_amount ?? 0) + (float) ($rental->fine_amount ?? 0)),
            'overpayment' => (float) ($rental->change_amount ?? 0),
            'refund_given' => (float) abs($rental->payments()->where('type', 'refund')->sum('amount')),
            'customer' => [
                'name' => $rental->customer->name ?? '-',
                'phone' => $rental->customer->phone ?? '-',
            ],
            'branch' => [
                'name' => $rental->branch->name ?? '-',
            ],
            'items' => $rental->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'size' => $item->product_size,
                    'category_name' => optional($item->product?->category)->name ?? '-',
                    'quantity' => (int) $item->quantity,
                    'price' => (float) ($item->price_per_day ?? 0),
                    'subtotal' => (float) ($item->subtotal ?? (($item->price_per_day ?? 0) * ($item->quantity ?? 0))),
                    'photo' => $item->product && $item->product->photo ? asset('storage/' . $item->product->photo) : null,
                    'return_condition' => $item->return_condition,
                    'damage_fee' => (float) ($item->damage_fee ?? 0),
                    'return_notes' => $item->return_notes,
                    'is_returned' => (bool) $item->is_returned,
                ];
            })->values(),
            'guarantees' => $rental->guarantees->map(function ($guarantee) {
                return [
                    'id' => $guarantee->id,
                    'type' => $guarantee->type,
                    'type_label' => $guarantee->type_label ?? $guarantee->type,
                    'id_number' => $guarantee->id_number,
                    'deposit_amount' => (float) ($guarantee->deposit_amount ?? 0),
                    'status' => $guarantee->status,
                    'notes' => $guarantee->description ?? null,
                    'id_photo_url' => $guarantee->id_photo_url ?? null,
                ];
            })->values(),
            'payments' => $rental->payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'amount' => (float) ($payment->amount ?? 0),
                    'method' => $payment->method,
                    'method_label' => $payment->method_label,
                    'paid_at' => $payment->paid_at?->format('d/m/Y H:i'),
                    'notes' => $payment->notes,
                ];
            })->values(),
            'activity_logs' => $rental->activityLogs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'description' => $log->description,
                    'user' => optional($log->user)->name,
                    'created_at' => $log->created_at?->format('d/m/Y H:i'),
                ];
            })->values(),
        ];
    }

    /**
     * Generate a globally unique, branch-aware, chronological invoice number.
     *
     * Format: INV{YYYYMMDD}{BB}{SSSS}
     * Example: INV20260727010001
     *
      * Race-condition safe:
      * - Uses lockForUpdate() on the last matching invoice
      * - Retry loop with up to 100 attempts per branch+date
      * - Final EXISTS check before returning
     *
     * Must be called from within a DB::transaction() with the same connection.
     *
     * @param int $branchId
     * @return string
     * @throws \RuntimeException after 100 failed attempts
     */
    public function generateInvoiceNumber(int $branchId): string
    {
        $prefix = 'INV';
        $now = now();
        $date = $now->format('Ymd');
        $branchCode = str_pad($branchId, 2, '0', STR_PAD_LEFT);
        $pattern = "{$prefix}{$date}{$branchCode}%";
        $maxAttempts = 100;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            // 1. Lock the LAST matching invoice row for today + branch
            $last = Rental::query()
                ->where('invoice_number', 'like', $pattern)
                ->whereDate('created_at', $now->toDateString())
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            // 2. Extract or start sequence
            $nextSequence = $last
                ? (int) substr($last->invoice_number, -4) + 1
                : 1;

            // 3. Generate candidate number
            $candidate = $prefix . $date . $branchCode . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

            // 4. Verify uniqueness with EXISTS check
            //    (pessimistic lock above prevents concurrent inserts,
            //     but this double-check ensures correctness)
            $exists = Rental::query()
                ->where('invoice_number', $candidate)
                ->lockForUpdate()
                ->exists();

            if (!$exists) {
                return $candidate;
            }

            // 5. Collision detected — retry with next sequence
            //    This can happen if a concurrent transaction inserted
            //    the same number between our lock and check.
            //    We simply loop and try the next sequence number.
        }

        // 6. Max retries exhausted — fail hard instead of creating a duplicate
        throw new \RuntimeException(
            "Gagal membuat nomor invoice setelah {$maxAttempts} percobaan " .
            "untuk cabang {$branchId} pada tanggal {$date}. " .
            "Silakan coba lagi."
        );
    }

    public function generatePaymentNumber(): string
    {
        $prefix = 'PAY';
        $now = now();
        $date = $now->format('Ymd');
        $last = Payment::where('payment_number', 'like', "{$prefix}{$date}%")
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->first();
        $sequence = $last ? (int) substr($last->payment_number, -4) + 1 : 1;
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function createRental(array $data): Rental
    {
        return DB::transaction(function () use ($data) {
            $user = Auth::user();
            $durationDays = (int) $data['duration_days'];

           
            // ─── STEP 1: Resolve rental branch ───
            $user = Auth::user();

            // Ambil ID produk yang dipilih untuk penyewaan.
            $productIds = collect($data['items'])
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($productIds->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Silakan pilih minimal satu produk.',
                ]);
            }

            // Kunci data produk agar pemeriksaan stok tetap aman.
            $products = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Ada produk yang tidak ditemukan.',
                ]);
            }

            // ─── STEP 2: Determine the branch ───
            // Untuk admin cabang, gunakan cabang yang sedang diakses.
            // Untuk Super Admin, gunakan cabang produk jika semua produk
            // memiliki cabang utama yang sama.
            $branchId = null;

            if (!$user->isSuperAdmin()) {
                $accessibleBranchIds = collect($user->accessible_branch_ids ?? [])
                    ->map(fn ($id) => (int) $id);

                $candidateBranchIds = $products->first()
                    ->branches()
                    ->pluck('branches.id')
                    ->map(fn ($id) => (int) $id)
                    ->intersect($accessibleBranchIds)
                    ->values();

                if ($candidateBranchIds->count() === 1) {
                    $branchId = $candidateBranchIds->first();
                } elseif ($candidateBranchIds->count() > 1) {
                    // Jika pengguna mengakses beberapa cabang, pilih cabang
                    // yang sama-sama menyediakan seluruh produk.
                    $commonBranchIds = null;

                    foreach ($products as $product) {
                        $availableBranchIds = $product->branches()
                            ->pluck('branches.id')
                            ->map(fn ($id) => (int) $id);

                        $commonBranchIds = $commonBranchIds === null
                            ? $availableBranchIds
                            : $commonBranchIds->intersect($availableBranchIds);
                    }

                    $commonBranchIds = $commonBranchIds
                        ->intersect($accessibleBranchIds)
                        ->values();

                    if ($commonBranchIds->count() === 1) {
                        $branchId = $commonBranchIds->first();
                    }
                }
            } else {
                $mainBranchIds = $products->pluck('branch_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($mainBranchIds->count() === 1) {
                    $branchId = $mainBranchIds->first();
                }
            }

            if ($branchId === null) {
                throw ValidationException::withMessages([
                    'items' => 'Cabang penyewaan tidak dapat ditentukan. Pastikan semua produk tersedia di cabang yang sama.',
                ]);
            }

            // ─── STEP 3: Verify every product is available at this branch ───
            foreach ($products as $product) {
                $availableAtBranch = $product->branches()
                    ->where('branches.id', $branchId)
                    ->exists();

                if (!$availableAtBranch) {
                    throw ValidationException::withMessages([
                        'items' => "Produk {$product->name} tidak tersedia di cabang penyewaan yang dipilih.",
                    ]);
                }
            }

            // ─── STEP 4: Verify user access ───
            if (
                !$user->isSuperAdmin()
                && !$user->canAccessBranch((int) $branchId)
            ) {
                throw ValidationException::withMessages([
                    'items' => 'Anda tidak memiliki akses ke cabang penyewaan ini.',
                ]);
            }


            // Calculate subtotal (using locked products from step 1)
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $product = Product::whereKey($item['product_id'])->lockForUpdate()->firstOrFail();
                $subtotal += $product->rental_price * $item['quantity'] * $durationDays;
            }

            $discount = $data['discount'] ?? 0;
            $totalAmount = $subtotal - $discount;

            // ─── Generate invoice number inside the same transaction ───
            $invoiceNumber = $this->generateInvoiceNumber($branchId);

            // Create rental with resolved branchId
            $rental = Rental::create([
                'invoice_number'  => $invoiceNumber,
                'branch_id'       => $branchId,
                'customer_id'     => $data['customer_id'],
                'created_by'      => $user->id,
                'rental_date'     => $data['rental_date'],
                'return_due_date' => Carbon::parse($data['rental_date'])->addDays($durationDays),
                'duration_days'   => $durationDays,
                'subtotal'        => $subtotal,
                'discount'        => $discount,
                'total_amount'    => $totalAmount,
                'paid_amount'     => 0,
                'payment_status'  => Rental::PAYMENT_UNPAID,
                'payment_method'  => $data['payment_method'] ?? null,
                'rental_status'   => Rental::STATUS_WAITING,
                'notes'           => $data['notes'] ?? null,
            ]);

            // Create rental items & reduce stock
            foreach ($data['items'] as $item) {
                $product = Product::whereKey($item['product_id'])->lockForUpdate()->firstOrFail();
                $itemSubtotal = $product->rental_price * $item['quantity'] * $durationDays;

                RentalItem::create([
                    'rental_id'     => $rental->id,
                    'product_id'    => $product->id,
                    'product_name'  => $product->name,
                    'product_size'  => $item['product_size'],
                    'product_color' => $product->color,
                    'quantity'      => $item['quantity'],
                    'price_per_day' => $product->rental_price,
                    'duration_days' => $durationDays,
                    'subtotal'      => $itemSubtotal,
                ]);

                // Reduce stock per-size
                $remaining = $product->decrementSizeStock($item['product_size'], $item['quantity']);
                if ($remaining < 0) {
                    throw ValidationException::withMessages([
                        'items' => "Stok ukuran {$item['product_size']} produk {$product->name} tidak mencukupi (tersedia: {$product->stock_available}, diminta: {$item['quantity']}).",
                    ]);
                }
            }

            // Create guarantee - handle flat form fields
            $guaranteeType = $data['guarantee_type'] ?? ($data['guarantee']['type'] ?? null);
            if (!empty($guaranteeType)) {
                $idPhotoPath = null;

                if ($guaranteeType === 'ktp' || $guaranteeType === 'sim') {
                    if (!empty($data['guarantee_id_photo']) && is_a($data['guarantee_id_photo'], \Illuminate\Http\UploadedFile::class)) {
                        $idPhotoPath = $data['guarantee_id_photo']->store('guarantees/id_photos', 'public');
                    }
                }

                if ($guaranteeType === 'deposit') {
                    $depositAmount = (float) ($data['guarantee_deposit'] ?? 0);
                    if ($depositAmount <= 0) {
                        abort(422, 'Jumlah deposit wajib diisi untuk jenis jaminan Deposit Uang.');
                    }
                }

                if ($guaranteeType === 'custom') {
                    $description = trim((string) ($data['guarantee_notes'] ?? ''));
                    if ($description === '') {
                        abort(422, 'Deskripsi jaminan custom wajib diisi.');
                    }
                }

                Guarantee::create([
                    'rental_id'      => $rental->id,
                    'type'           => $guaranteeType,
                    'id_number'      => $data['guarantee_id_number'] ?? ($data['guarantee']['id_number'] ?? null),
                    'id_name'        => $data['guarantee_id_name'] ?? ($data['guarantee']['id_name'] ?? null),
                    'deposit_amount' => $guaranteeType === 'deposit' ? (float) ($data['guarantee_deposit'] ?? 0) : 0,
                    'description'    => $data['guarantee_notes'] ?? ($data['guarantee']['description'] ?? null),
                    'id_photo'       => $idPhotoPath,
                    'status'         => 'held',
                ]);
            }

            // Generate QR code
            $this->generateQrCode($rental);

            // Log activity
            $this->logActivity('create_rental', $rental, [], $this->rentalSnapshot($rental));
            $this->notifyBranchAndSuperAdmins(
                $rental,
                'rental_new',
                'Penyewaan baru',
                'Penyewaan ' . $rental->invoice_number . ' berhasil dibuat.'
            );

            return $rental->fresh(['items', 'customer', 'guarantees']);
        });
    }

     public function processPayment(Rental $rental, array $data): Payment
    {
        $oldValues = $this->rentalSnapshot($rental);

        return DB::transaction(function () use ($rental, $data, $oldValues) {
            $amount = $data['amount'] ?? 0;
            if (is_string($amount)) {
                $amount = preg_replace('/[^0-9]/', '', $amount);
            }
            $amount = (float) $amount;
            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Jumlah pembayaran harus lebih besar dari Rp0.',
                ]);
            }

            $now = now();
            $paymentType = $data['payment_type'] ?? 'rental';
            if (!in_array($paymentType, ['rental', 'deposit', 'late_fee', 'damage_fee'], true)) {
                throw ValidationException::withMessages([
                    'payment_type' => 'Tipe pembayaran tidak valid.',
                ]);
            }

            $recentPayment = Payment::where('rental_id', $rental->id)
                ->where('amount', $amount)
                ->where('method', $data['method'])
                ->where('type', $paymentType)
                ->where('created_at', '>=', $now->copy()->subSeconds(5))
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            if ($recentPayment) {
                throw new \RuntimeException('Pembayaran yang sama sudah diproses 2 detik yang lalu. Jika ini tidak benar, coba lagi dalam beberapa saat.');
            }

            $payment = Payment::create([
                'rental_id'        => $rental->id,
                'received_by'      => Auth::id(),
                'payment_number'   => $this->generatePaymentNumber(),
                'amount'           => $amount,
                'method'           => $data['method'],
                'reference_number' => $data['reference_number'] ?? null,
                'type'             => $paymentType,
                'notes'            => $data['notes'] ?? null,
                'paid_at'          => $now,
            ]);

            // Jangan menambah paid_amount/fine_paid_amount secara manual di sini.
            // Satu sumber kebenaran adalah riwayat pembayaran, yang dihitung ulang di bawah.
            $this->recalculatePaymentStatus($rental);
            $newValues = $this->rentalSnapshot($rental);

            $this->logActivity('process_payment', $rental, $oldValues, $newValues);
            $this->notifyBranchAndSuperAdmins(
                $rental,
                'payment',
                'Pembayaran diterima',
                'Pembayaran sebesar Rp' . number_format($amount, 0, ',', '.') . ' untuk invoice ' . $rental->invoice_number . ' berhasil dicatat.'
            );

            $this->addSalesPoints($rental, 'serah', 3);

            return $payment;
        });
    }

    public function processReturn(Rental $rental, array $data = []): Rental
    {
        $oldValues = $this->rentalSnapshot($rental);

        return DB::transaction(function () use ($rental, $data, $oldValues) {
            $now = now();
            $lateFee = 0;
            $totalDamageFees = 0;

            if ($rental->return_due_date) {
                // Hitung berdasarkan hari kalender agar keterlambatan 7 hari = 7 x Rp25.000.
                $dueDate = Carbon::parse($rental->return_due_date)->startOfDay();
                $returnDate = $now->copy()->startOfDay();
                $overdueDays = max(0, (int) $dueDate->diffInDays($returnDate, false));
                $lateFee = (float) ($overdueDays * self::LATE_FEE_PER_DAY);
            }

            foreach ($rental->items as $item) {
                $returnData = collect($data['items'] ?? [])
                    ->firstWhere('rental_item_id', $item->id);

                $condition = $returnData['condition'] ?? 'baik';
                $damageFee = $returnData['damage_fee'] ?? 0;
                $totalDamageFees += $damageFee;

                $item->update([
                    'is_returned'        => true,
                    'returned_at'        => $now,
                    'return_condition'   => $condition,
                
                    'maintenance_status' => in_array(
                        $condition,
                        ['rusak_ringan', 'rusak_berat']
                    ) ? 'pending' : null,
                
                    'damage_fee'         => $damageFee,
                    'return_notes'       => $returnData['notes'] ?? null,
                ]);

                $product = Product::whereKey($item->product_id)->lockForUpdate()->first();

                if ($product) {
                    // Hanya barang yang kembali dalam kondisi baik
                    // yang masuk kembali ke stok tersedia.
                    if ($condition === 'baik') {
                        $product->incrementSizeStock(
                            $item->product_size,
                            $item->quantity
                        );
                    }
                } else {
                    ActivityLog::create([
                        'user_id'   => Auth::id(),
                        'branch_id' => Auth::user()?->branch_id,
                        'action'    => 'product_not_found_return',
                        'model_type' => Rental::class,
                        'model_id'   => $rental->id,
                        'description' => "Produk ID {$item->product_id} tidak ditemukan di database saat memproses pengembalian item ID {$item->id} (rental {$rental->invoice_number}). Stok tidak diperbarui untuk item ini.",
                    ]);
                }
            }

            $rental->guarantees()->update(['status' => 'returned', 'returned_at' => $now]);

            $fineAmount = (float) round($lateFee + $totalDamageFees);
            $rental->update([
                'rental_status'      => Rental::STATUS_RETURNED,
                'actual_return_date' => $now->toDateString(),
                'returned_at'        => $now,
                'returned_by'        => Auth::id(),
                'late_fee'           => $lateFee,
                'total_amount'       => $rental->total_amount,
                'fine_amount'        => $fineAmount,
                'fine_status'        => $fineAmount > 0 ? Rental::FINE_UNPAID : Rental::FINE_NONE,
            ]);

            $this->recalculatePaymentStatus($rental);
            $newValues = $this->rentalSnapshot($rental);

            $this->logActivity('return_rental', $rental, $oldValues, $newValues);
            $this->notifyBranchAndSuperAdmins(
                $rental,
                'rental_return',
                'Pengembalian barang',
                'Pengembalian untuk invoice ' . $rental->invoice_number . ' telah diproses.'
            );

            $this->addSalesPoints($rental, 'kembali', 5);

            return $rental->fresh();
        });
    }

    public function generateQrCode(Rental $rental): void
    {
        $qrData = route('rentals.scan', $rental->invoice_number);
        $path = 'qrcodes/rentals/' . $rental->invoice_number . '.svg';
        $fullPath = storage_path('app/public/' . $path);

        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $svg = QrCode::format('svg')->size(200)->generate($qrData);
        file_put_contents($fullPath, $svg);

        $rental->update(['qr_code' => $path]);
    }

    public function getQrUrl(Rental $rental): string
    {
        return route('rentals.scan', $rental->invoice_number);
    }

    public function updateOverdueRentals(): int
    {
        // Ambil penyewaan aktif yang sudah lewat tenggat DAN penyewaan yang
        // statusnya sudah overdue, agar data lama juga mendapat notifikasi.
        $rentals = Rental::query()
            ->whereIn('rental_status', [Rental::STATUS_ACTIVE, Rental::STATUS_OVERDUE])
            ->whereDate('return_due_date', '<', now()->toDateString())
            ->get();

        foreach ($rentals as $rental) {
            if ($rental->rental_status !== Rental::STATUS_OVERDUE) {
                $rental->update(['rental_status' => Rental::STATUS_OVERDUE]);
            }

            // Idempotent: setiap pengguna hanya mendapat satu notifikasi
            // keterlambatan untuk invoice yang sama.
            $this->notifyBranchAndSuperAdmins(
                $rental,
                'rental_late',
                'Penyewaan terlambat',
                'Penyewaan dengan invoice ' . $rental->invoice_number . ' telah melewati tanggal pengembalian.',
                true
            );
        }

        return $rentals->count();
    }

    /**
     * Kirim notifikasi transaksi kepada Admin Toko/Sales cabang terkait
     * dan seluruh Super Admin.
     */
    protected function notifyBranchAndSuperAdmins(
        Rental $rental,
        string $type,
        string $title,
        string $message,
        bool $preventDuplicate = false
    ): void {
        $branchId = (int) $rental->branch_id;

        $recipients = User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($branchId) {
                $query->where('role', User::ROLE_SUPER_ADMIN)
                    ->orWhere(function ($branchQuery) use ($branchId) {
                        $branchQuery
                            ->whereIn('role', [User::ROLE_ADMIN_TOKO, User::ROLE_SALES])
                            ->where('branch_id', $branchId);
                    });
            })
            ->get(['id']);

        foreach ($recipients as $recipient) {
            if ($preventDuplicate && Notification::query()
                ->where('notifiable_type', 'user')
                ->where('notifiable_id', $recipient->id)
                ->where('type', $type)
                ->where('meta->rental_id', $rental->id)
                ->exists()) {
                continue;
            }

            Notification::create([
                'notifiable_type' => 'user',
                'notifiable_id'   => $recipient->id,
                'branch_id'       => $branchId,
                'type'            => $type,
                'title'           => $title,
                'message'         => $message,
                'action_url'      => '/rentals/' . $rental->id,
                'meta'            => [
                    'rental_id'     => $rental->id,
                    'invoice_number'=> $rental->invoice_number,
                    'branch_id'     => $branchId,
                ],
                'is_read'         => false,
            ]);
        }
    }

    protected function rentalSnapshot(Rental $rental): array
    {
        return [
            'id'                     => $rental->id,
            'invoice_number'         => $rental->invoice_number,
            'rental_status'          => $rental->rental_status,
            'payment_status'         => $rental->payment_status,
            'fine_status'            => $rental->fine_status,
            'paid_amount'            => (float) ($rental->paid_amount ?? 0),
            'remaining_amount'       => (float) ($rental->remaining_amount ?? 0),
            'fine_paid_amount'       => (float) ($rental->fine_paid_amount ?? 0),
            'fine_amount'            => (float) ($rental->fine_amount ?? 0),
            'late_fee'               => (float) ($rental->late_fee ?? 0),
            'total_amount'           => (float) ($rental->total_amount ?? 0),
            'change_amount'          => (float) ($rental->change_amount ?? 0),
            'overdue_days'           => (int) ($rental->overdue_days ?? 0),
            'subtotal'               => (float) ($rental->subtotal ?? 0),
            'discount'               => (float) ($rental->discount ?? 0),
            'notes'                  => $rental->notes ?? null,
            'payment_method'         => $rental->payment_method ?? null,
            'returned_by'            => $rental->returned_by,
            'actual_return_date'     => $rental->actual_return_date?->format('Y-m-d'),
            'return_due_date'        => $rental->return_due_date?->format('Y-m-d'),
            'rental_date'            => $rental->rental_date?->format('Y-m-d'),
            'customer'               => optional($rental->customer)->name ?? '-',
        ];
    }

    protected function logActivity(string $action, Rental $rental, ?array $oldValues = null, ?array $newValues = null): void
    {
        $description = \App\Services\ActivityLogFormatter::format(
            $oldValues,
            $newValues,
            Rental::class,
            $rental->id,
            $action
        );

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()?->branch_id,
            'action'      => $action,
            'model_type'  => Rental::class,
            'model_id'    => $rental->id,
            'description' => $description,
            'old_values'  => $oldValues ?: null,
            'new_values'  => $newValues ?: null,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }
}

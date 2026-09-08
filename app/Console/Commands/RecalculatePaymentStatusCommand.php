<?php

namespace App\Console\Commands;

use App\Models\Rental;
use App\Services\RentalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculatePaymentStatusCommand extends Command
{
    protected $signature = 'rentals:recalculate-payment-status
                            {--dry-run : Preview changes without writing to database}
                            {--force : Actually apply changes to database}';
    protected $description = 'Recalculate paid_amount, fine_paid_amount, payment_status, fine_status, and change_amount from actual payments';

    public function handle(RentalService $rentalService): int
    {
        $isDryRun = $this->option('dry-run');
        $isForce = $this->option('force');

        if (!$isDryRun && !$isForce) {
            $this->warn('Please specify --dry-run to preview or --force to apply changes.');
            return 1;
        }

        $rentals = Rental::with('payments')->get()->filter(function ($rental) {
            $refundGiven = (float) abs($rental->payments()->where('type', 'refund')->sum('amount'));
            return $refundGiven == 0;
        })->values();

        if ($rentals->isEmpty()) {
            $this->info('No rentals found.');
            return 0;
        }

        $rows = [];
        $updateCount = 0;

        foreach ($rentals as $rental) {
            $rentalFee = (float) $rental->total_amount;
            $fineAmount = (float) $rental->fine_amount;

            $totalPaidForRental = (float) $rental->payments()
                ->whereIn('type', ['rental', 'deposit'])
                ->where('type', '!=', 'refund')
                ->sum('amount');

            $totalPaidForFine = (float) $rental->payments()
                ->whereIn('type', ['late_fee', 'damage_fee'])
                ->where('type', '!=', 'refund')
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

            $totalPaidRaw = (float) $rental->payments()
                ->where('type', '!=', 'refund')
                ->sum('amount');
            $totalOwed = max(0, $rentalFee + $fineAmount);
            $grossOverpayment = max(0, $totalPaidRaw - $totalOwed);

            $refundGiven = (float) abs($rental->payments()
                ->where('type', 'refund')
                ->sum('amount'));
            $changeAmount = max(0, $grossOverpayment - $refundGiven);

            if ($fineAmount <= 0) {
                $fineStatus = Rental::FINE_NONE;
            } elseif ($totalPaidRaw >= $totalOwed) {
                $fineStatus = Rental::FINE_PAID;
            } elseif ($totalPaidRaw > $rentalFee) {
                $fineStatus = Rental::FINE_PARTIAL;
            } else {
                $fineStatus = Rental::FINE_UNPAID;
            }

            $needsUpdate = false;
            $changes = [];

            if ((float) $rental->paid_amount !== $totalPaidForRental) {
                $needsUpdate = true;
                $changes[] = "paid_amount: {$rental->paid_amount} → {$totalPaidForRental}";
            }
            if ((float) $rental->fine_paid_amount !== $totalPaidForFine) {
                $needsUpdate = true;
                $changes[] = "fine_paid_amount: {$rental->fine_paid_amount} → {$totalPaidForFine}";
            }
            if ($rental->payment_status !== $paymentStatus) {
                $needsUpdate = true;
                $changes[] = "payment_status: {$rental->payment_status} → {$paymentStatus}";
            }
            if ($rental->fine_status !== $fineStatus) {
                $needsUpdate = true;
                $changes[] = "fine_status: {$rental->fine_status} → {$fineStatus}";
            }
            if ((float) $rental->change_amount !== $changeAmount) {
                $needsUpdate = true;
                $changes[] = "change_amount: {$rental->change_amount} → {$changeAmount}";
            }

            if (!$needsUpdate) {
                continue;
            }

            $rows[] = [
                'ID' => $rental->id,
                'Invoice' => $rental->invoice_number,
                'Paid Amount' => $rental->paid_amount . ' → ' . $totalPaidForRental,
                'Fine Paid' => $rental->fine_paid_amount . ' → ' . $totalPaidForFine,
                'Payment Status' => $rental->payment_status . ' → ' . $paymentStatus,
                'Fine Status' => $rental->fine_status . ' → ' . $fineStatus,
                'Change Amount' => $rental->change_amount . ' → ' . $changeAmount,
            ];

            if ($isForce) {
                DB::transaction(function () use ($rental, $totalPaidForRental, $totalPaidForFine, $paymentStatus, $fineStatus, $changeAmount, $remainingRental) {
                    $rental->update([
                        'paid_amount' => $totalPaidForRental,
                        'fine_paid_amount' => $totalPaidForFine,
                        'payment_status' => $paymentStatus,
                        'fine_status' => $fineStatus,
                        'change_amount' => $changeAmount,
                        'remaining_amount' => $remainingRental,
                    ]);
                });
                $updateCount++;
            }
        }

        if ($rows === []) {
            $this->info('No rentals need updating. All payment statuses are already correct.');
            return 0;
        }

        $this->table(
            ['ID', 'Invoice', 'Paid Amount', 'Fine Paid', 'Payment Status', 'Fine Status', 'Change Amount'],
            $rows
        );

        if ($isForce) {
            $this->info("Backfill completed. {$updateCount} rental(s) updated.");
        } else {
            $this->info('This was a dry run. No changes were made to the database.');
            $this->info('Run with --force to apply changes.');
        }

        return 0;
    }
}

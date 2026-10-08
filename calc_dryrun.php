<?php

declare(strict_types=1);

$bootstrapError = null;

try {
    require __DIR__ . '/vendor/autoload.php';
    $app = require __DIR__ . '/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
} catch (\Throwable $exception) {
    $bootstrapError = get_class($exception);
}

$results = [];

foreach ([13, 14, 15] as $rentalId) {
    $rental = null;

    try {
        if ($bootstrapError !== null) {
            throw new \RuntimeException('Laravel bootstrap failed: ' . $bootstrapError);
        }

        $rental = \Illuminate\Support\Facades\DB::table('rentals')
            ->select(['id', 'total_amount', 'fine_amount', 'fine_status', 'fine_paid_amount', 'change_amount'])
            ->where('id', $rentalId)
            ->first();

        if ($rental === null) {
            throw new \RuntimeException('Rental tidak ditemukan');
        }

        $payments = \Illuminate\Support\Facades\DB::table('payments')
            ->select(['amount', 'type'])
            ->where('rental_id', $rentalId)
            ->get();

        $totalPaidRaw = 0.0;
        $refundGiven = 0.0;

        foreach ($payments as $payment) {
            if ($payment->type === 'refund') {
                $refundGiven += abs((float) $payment->amount);
            } else {
                $totalPaidRaw += (float) $payment->amount;
            }
        }

        $rentalFee = (float) $rental->total_amount;
        $fineAmount = (float) $rental->fine_amount;
        $totalReceived = $totalPaidRaw - $refundGiven;
        $availableForFine = max(0, $totalReceived - $rentalFee);
        $finePaidAmount = min($fineAmount, $availableForFine);
        $totalOwed = $rentalFee + $fineAmount;
        $changeAmount = max(0, $totalPaidRaw - $totalOwed) - $refundGiven;

        if ($fineAmount <= 0) {
            $fineStatus = 'none';
        } elseif ($finePaidAmount >= $fineAmount) {
            $fineStatus = 'paid';
        } elseif ($finePaidAmount > 0) {
            $fineStatus = 'partial';
        } else {
            $fineStatus = 'unpaid';
        }

        $results[$rentalId] = [
            'fine_status' => [$rental->fine_status, $fineStatus],
            'fine_paid_amount' => [$rental->fine_paid_amount, $finePaidAmount],
            'change_amount' => [$rental->change_amount, $changeAmount],
            'error' => null,
        ];
    } catch (\Throwable $exception) {
        $results[$rentalId] = [
            'fine_status' => [$rental->fine_status ?? '-', 'ERROR'],
            'fine_paid_amount' => [$rental->fine_paid_amount ?? '-', 'ERROR'],
            'change_amount' => [$rental->change_amount ?? '-', 'ERROR'],
            'error' => get_class($exception),
        ];
    }
}

$formatValue = static function ($value): string {
    if (!is_numeric($value)) {
        return (string) $value;
    }

    return number_format((float) $value, 2, '.', '');
};

echo "| Rental | Field | Nilai lama (DB) | Nilai baru (dry-run) |\n";
echo "|---:|---|---:|---:|\n";

foreach ($results as $rentalId => $result) {
    foreach (['fine_status', 'fine_paid_amount', 'change_amount'] as $field) {
        [$oldValue, $newValue] = $result[$field];
        echo '| ' . $rentalId
            . ' | ' . $field
            . ' | ' . $formatValue($oldValue)
            . ' | ' . $formatValue($newValue)
            . " |\n";
    }

    if ($result['error'] !== null) {
        echo '| ' . $rentalId . ' | error | ' . $result['error'] . " | ERROR |\n";
    }
}

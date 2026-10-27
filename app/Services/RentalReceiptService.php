<?php

namespace App\Services;

use App\Models\Rental;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class RentalReceiptService
{
    public function getReceiptPayload(Rental $rental): array
    {
        $rental->load([
            'customer',
            'createdBy',
            'branch',
            'items.product.category',
            'payments.receivedBy',
            'guarantees',
            'returnRecord',
        ]);

        $deposit = (float) ($rental->guarantees->where('type', 'deposit')->sum('deposit_amount') ?? 0);
        $lateFee = (float) ($rental->late_fee ?? 0);
        $damageFee = (float) ($rental->items->sum('damage_fee') ?? 0);
        $denda = $lateFee + $damageFee;

        return [
            'receipt_number' => $this->generateReceiptNumber($rental),
            'deposit' => $deposit,
            'late_fee' => $lateFee,
            'damage_fee' => $damageFee,
            'denda' => $denda,
            'paid_amount' => (float) ($rental->paid_amount ?? 0),
            'total_amount' => (float) ($rental->total_amount ?? 0),
            'remaining_amount' => max(0, (float) ($rental->total_amount ?? 0) - (float) ($rental->paid_amount ?? 0)),
            'generated_at' => now('Asia/Jakarta')->toIso8601String(),
        ];
    }

    protected function generateReceiptNumber(Rental $rental): string
    {
        return 'RCPT-' . $rental->invoice_number;
    }

    public function getQrPayload(Rental $rental): array
    {
        $payload = [
            'rental_id' => $rental->id,
            'invoice_number' => $rental->invoice_number,
        ];

        return [
            'url' => route('rentals.show', $rental),
            'payload' => $payload,
        ];
    }

    public function downloadPdf(Rental $rental)
    {
        $receipt = $this->getReceiptPayload($rental);
        $pdf = Pdf::loadView('rentals.receipt_pdf', compact('rental', 'receipt'));

        return $pdf->download(($receipt['receipt_number'] ?? 'RECEIPT') . '.pdf');
    }
    
public function downloadInvoicePdf(Rental $rental)
{
    $rental->load([
        'customer',
        'createdBy',
        'branch',
        'items.product.category',
        'payments.receivedBy',
        'guarantees',
        'returnRecord',
    ]);

    $receipt = $this->getReceiptPayload($rental);

    $pdf = Pdf::loadView('rentals.invoice_pdf', compact('rental', 'receipt'))
        ->setPaper('a4', 'portrait');

    return $pdf->download('Invoice-' . $rental->invoice_number . '.pdf');
}


    public function sendWhatsapp(Rental $rental)
    {
        $receipt = $this->getReceiptPayload($rental);

        $message = urlencode(
            "Halo {$rental->customer->name},\n\n".
            "Terima kasih telah menggunakan layanan SewaJas.\n\n".
            "Berikut adalah Receipt Anda.\n\n".
            "Nomor Receipt: {$receipt['receipt_number']}\n\n".
            "Silakan unduh PDF melalui tautan berikut:\n" . route('rentals.receipt.pdf', $rental) . "\n\n".
            "Apabila ada pertanyaan, silakan hubungi kami.\n".
            "Terima kasih."
        );

        $waUrl = "https://wa.me/{$rental->customer->phone}?text={$message}";
       '' return redirect()->away($waUrl);
    }

    public function ensureQrStored(Rental $rental): ?string
    {
        return null;
    }
}


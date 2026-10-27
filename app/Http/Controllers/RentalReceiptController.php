<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Services\RentalReceiptService;
use Illuminate\Http\Request;

class RentalReceiptController extends Controller
{
    public function __construct(protected RentalReceiptService $service) {}

    public function show(Rental $rental)
    {
        $receipt = $this->service->getReceiptPayload($rental);
        return view('rentals.receipt', compact('rental', 'receipt'));
    }

    public function pdf(Rental $rental)
    {
        return $this->service->downloadPdf($rental);
    }

    public function qr(Rental $rental)
    {
        $qr = $this->service->getQrPayload($rental);
        return response()->json($qr);
    }

    public function whatsapp(Rental $rental)
    {
        return $this->service->sendWhatsapp($rental);
    }
    public function invoice(Rental $rental)
{
    return $this->service->downloadInvoicePdf($rental);
}
}


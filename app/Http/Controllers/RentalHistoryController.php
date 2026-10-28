<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class RentalHistoryController extends Controller
{
    /**
     * Daftar riwayat penyewaan yang sudah dikembalikan,
     * pembayaran sewa lunas, dan denda sudah lunas/tidak ada.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Rental::with([
            'branch',
            'customer',
            'createdBy',
            'returnedBy',
        ])
        ->where('rental_status', Rental::STATUS_RETURNED)
        ->where('payment_status', Rental::PAYMENT_PAID)
        ->whereIn('fine_status', [
            Rental::FINE_NONE,
            Rental::FINE_PAID,
        ]);

        // Batasi data sesuai cabang yang boleh diakses.
        if (!$user->isSuperAdmin()) {
            $query->whereIn(
                'branch_id',
                $user->accessible_branch_ids
            );
        }

        // Pencarian invoice atau nama pelanggan.
        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter cabang hanya untuk super admin.
        if ($user->isSuperAdmin() && $request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate(
                'actual_return_date',
                '>=',
                $request->input('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'actual_return_date',
                '<=',
                $request->input('date_to')
            );
        }

        $histories = $query
            ->orderByDesc('actual_return_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $branches = $user->isSuperAdmin()
            ? Branch::orderBy('name')->get()
            : collect();

        return view('rental_histories.index', compact(
            'histories',
            'branches'
        ));
    }

    /**
     * Detail riwayat.
     */
    public function show(Rental $rentalHistory)
    {
        $user = Auth::user();

        abort_unless(
            $user->isSuperAdmin()
                || $user->canAccessBranch((int) $rentalHistory->branch_id),
            404
        );

        abort_unless(
            $rentalHistory->rental_status === Rental::STATUS_RETURNED
                && $rentalHistory->payment_status === Rental::PAYMENT_PAID
                && in_array($rentalHistory->fine_status, [
                    Rental::FINE_NONE,
                    Rental::FINE_PAID,
                ], true),
            404
        );

        $rentalHistory->load([
            'branch',
            'customer',
            'createdBy',
            'returnedBy',
            'items.product.category',
            'payments',
            'guarantees',
            'returnRecord',
        ]);

        $history = $rentalHistory;

        return view('rental_histories.show', compact('history'));
    }

    /**
     * Unduh invoice PDF.
     */
    public function downloadInvoice(Rental $rentalHistory)
    {
        $user = Auth::user();

        abort_unless(
            $user->isSuperAdmin()
                || $user->canAccessBranch((int) $rentalHistory->branch_id),
            404
        );

        abort_unless(
            $rentalHistory->rental_status === Rental::STATUS_RETURNED
                && $rentalHistory->payment_status === Rental::PAYMENT_PAID
                && in_array($rentalHistory->fine_status, [
                    Rental::FINE_NONE,
                    Rental::FINE_PAID,
                ], true),
            404
        );

        $rentalHistory->load([
            'branch',
            'customer',
            'createdBy',
            'returnedBy',
            'items.product.category',
            'payments',
            'guarantees',
            'returnRecord',
        ]);

        $history = $rentalHistory;

        // Variabel ini dibutuhkan oleh template invoice Nota.
        $items = $history->items;
        $payments = $history->payments;
        $guarantees = $history->guarantees;
        $appName = config('app.name', 'Rental Jas');
        $qrBase64 = null;

        $statusPill = 'LUNAS';
        $statusPillClass = 'pill';

        $money = function ($amount) {
            return 'Rp ' . number_format((float) $amount, 0, ',', '.');
        };

        $pdf = Pdf::loadView('rental_histories.invoice', compact(
            'history',
            'items',
            'payments',
            'guarantees',
            'appName',
            'qrBase64',
            'statusPill',
            'statusPillClass',
            'money'
        ))->setPaper('a4', 'portrait');

        return $pdf->download(
            $history->invoice_number . '-invoice.pdf'
        );
    }
}

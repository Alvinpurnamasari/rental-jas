
@php
    $rental = $rental ?? null;
    $receipt = $receipt ?? [];

    $receiptNumber = $receipt['receipt_number'] ?? ('RCPT-' . ($rental->invoice_number ?? '-'));
    $receiptDate = now('Asia/Jakarta')->format('d M Y H:i');

    $customerName = optional($rental->customer)->name ?? '-';
    $invoiceNumber = $rental->invoice_number ?? '-';
    $rentalDate = optional($rental->rental_date)->format('d M Y') ?? '-';
    $returnDueDate = optional($rental->return_due_date)->format('d M Y') ?? '-';
    $actualReturnDate = optional($rental->returned_at)?->format('d M Y H:i');

    $rentalStatus = $rental->rental_status ?? 'waiting';

    $statusBadge = match ($rentalStatus) {
        'returned'  => 'SELESAI',
        'active'    => 'SEDANG DISEWA',
        'overdue'   => 'TERLAMBAT',
        'cancelled' => 'DIBATALKAN',
        'waiting'   => 'BOOKING',
        default     => strtoupper($rentalStatus),
    };

    $statusBadgeClass = match ($rentalStatus) {
        'returned', 'active' => 'status-paid',
        'overdue'             => 'status-overdue',
        'cancelled'           => 'status-unpaid',
        default               => 'status-unpaid',
    };

    // Total tetap mengambil nilai dari sistem rental yang sudah ada.
    $totalAmount = (float) ($rental->total_amount ?? 0);

    $createdByName = optional($rental->createdBy)->name
        ?? \App\Services\SettingsService::get('app_name', 'SewaJas');

    $processedByName = optional($rental->returnedBy)->name ?? null;

    $appName = \App\Services\SettingsService::get('app_name', 'SewaJas');
    $appLogo = \App\Services\SettingsService::get('app_logo');
    $appLogoUrl = '';

    if ($appLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($appLogo)) {
    $fullPath = storage_path('app/public/' . $appLogo);
    $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

    $mimeTypes = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
    ];

    if (isset($mimeTypes[$extension])) {
        $mimeType = $mimeTypes[$extension];

        $appLogoUrl = 'data:' . $mimeType . ';base64,'
            . base64_encode(file_get_contents($fullPath));
    }
}


    // PDF fallback logo: embed local public logo as Base64 for DomPDF.
    if (empty($appLogoUrl)) {
        $fallbackLogoPath = public_path('images/sewajas-logo.png');

        if (is_file($fallbackLogoPath)) {
            $fallbackMime = function_exists('mime_content_type')
                ? mime_content_type($fallbackLogoPath)
                : 'image/png';

            if (in_array($fallbackMime, ['image/png', 'image/jpeg', 'image/gif'], true)) {
                $appLogoUrl = 'data:' . $fallbackMime . ';base64,'
                    . base64_encode(file_get_contents($fallbackLogoPath));
            }
        }
    }

    $companyAddress = \App\Services\SettingsService::get('company_address');
    $companyPhone = \App\Services\SettingsService::get('company_phone');
    $companyWebsite = \App\Services\SettingsService::get('company_website');

    $qrBase64 = null;

    try {
        $qrRoute = route('rentals.show', $rental);

        $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size(160)
            ->generate($qrRoute);

        $qrBase64 = 'data:image/svg+xml;base64,'
            . base64_encode((string) $qrSvg);
    } catch (\Throwable $e) {
        $qrBase64 = null;
    }
@endphp

@if(($outputMode ?? 'browser') === 'pdf')
<div class="receipt-compact-wrapper receipt-pdf-wrapper">
    <div class="receipt-compact-card receipt-pdf-card">
        {{-- HEADER --}}
        <div class="receipt-header-sewajas receipt-pdf-header">
            <table class="pdf-brand-table">
                <tr>
                    <td class="pdf-logo-cell">
                        <div class="receipt-logo-box">
                            @if($appLogoUrl)
                                <img src="{{ $appLogoUrl }}" alt="Logo {{ $appName }}" class="receipt-logo-image">
                            @else
                                <span class="pdf-logo-fallback">SJ</span>
                            @endif
                        </div>
                    </td>
                    <td class="pdf-brand-cell">
                        <div class="receipt-brand-title">{{ $appName }}</div>
                        <div class="receipt-brand-subtitle">Sewajas</div>
                    </td>
                    <td class="pdf-receipt-cell">RECEIPT</td>
                </tr>
            </table>
            <div class="pdf-header-meta">
                <span>{{ $receiptNumber }}</span>
                <span class="pdf-meta-divider">|</span>
                <span>{{ $receiptDate }}</span>
            </div>
        </div>

        {{-- CUSTOMER + BOOKING --}}
        <div class="receipt-section pdf-info-section">
            <table class="pdf-info-table">
                <tr><td class="pdf-label">Customer</td><td class="pdf-value">{{ $customerName }}</td></tr>
                <tr><td class="pdf-label">Invoice</td><td class="pdf-value">{{ $invoiceNumber }}</td></tr>
                <tr><td class="pdf-label">Sewa</td><td class="pdf-value">{{ $rentalDate }}</td></tr>
                <tr><td class="pdf-label">Kembali</td><td class="pdf-value">{{ $returnDueDate }}</td></tr>
                @if($actualReturnDate)
                    <tr><td class="pdf-label">Dikembalikan</td><td class="pdf-value">{{ $actualReturnDate }}</td></tr>
                @endif
                <tr class="pdf-status-row">
                    <td class="pdf-label">Status</td>
                    <td class="pdf-value"><span class="status-badge {{ $statusBadgeClass }}">{{ $statusBadge }}</span></td>
                </tr>
            </table>
        </div>

        {{-- ITEMS --}}
        <div class="receipt-section pdf-items-section">
            <div class="receipt-section-title">PRODUK YANG DISEWA</div>
            <div class="receipt-items">
                @forelse($rental->items as $item)
                    @php
                        $product = $item->product;
                        $quantity = max(1, (int) ($item->quantity ?? 1));
                        $unitPrice = $item->unit_price
                            ?? $item->price
                            ?? optional($product)->rental_price
                            ?? optional($product)->price
                            ?? 0;
                        $unitPrice = (float) $unitPrice;
                    @endphp
                    <table class="pdf-product-table">
                        <tr>
                            <td class="pdf-product-details">
                                <div class="receipt-item-name">{{ $product->name ?? ('Produk #' . $item->product_id) }}</div>
                                <div class="receipt-item-price">
                                    Rp {{ number_format($unitPrice, 0, ',', '.') }}
                                    <span class="receipt-item-price-label">/ item</span>
                                </div>
                            </td>
                            <td class="pdf-quantity-cell">
                                <span class="receipt-item-qty">x{{ $quantity }}</span>
                            </td>
                        </tr>
                    </table>
                @empty
                    <div class="receipt-item-empty">Belum ada produk pada transaksi ini.</div>
                @endforelse
            </div>
        </div>

        {{-- QR CODE --}}
        <div class="receipt-section receipt-qr-section pdf-qr-section">
            <div class="receipt-qr-wrapper">
                @if(!empty($qrBase64))
                    <img src="{{ $qrBase64 }}" alt="QR Code detail rental" class="receipt-qr-image">
                @endif
            </div>
            <div class="receipt-qr-label">Scan untuk detail rental</div>
        </div>

        {{-- PETUGAS --}}
        <div class="receipt-section receipt-petugas-section">
            <table class="pdf-info-table">
                <tr><td class="pdf-label">Dibuat oleh</td><td class="pdf-value">{{ $createdByName }}</td></tr>
                @if($processedByName)
                    <tr><td class="pdf-label">Diproses oleh</td><td class="pdf-value">{{ $processedByName }}</td></tr>
                @endif
            </table>
        </div>

        {{-- TOTAL --}}
        <div class="receipt-total-section">
            <table class="pdf-total-table">
                <tr>
                    <td class="pdf-total-label">TOTAL</td>
                    <td class="pdf-total-value">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        {{-- FOOTER --}}
        <div class="receipt-footer">
            @if($companyAddress || $companyPhone || $companyWebsite)
                <div class="receipt-company-info">
                    @if($companyAddress)<div class="receipt-company-line">{{ $companyAddress }}</div>@endif
                    @if($companyPhone)<div class="receipt-company-line">{{ $companyPhone }}</div>@endif
                    @if($companyWebsite)<div class="receipt-company-line">{{ $companyWebsite }}</div>@endif
                </div>
            @endif
        </div>
    </div>
</div>
@else
<div class="receipt-compact-wrapper">
    <div class="receipt-compact-card">

 {{-- HEADER --}}
<div class="receipt-header receipt-header-sewajas">

    <table class="receipt-brand-table">
        <tr>
            <td class="receipt-brand-logo-cell">
                <div class="receipt-logo-box">
                    @if($appLogoUrl)
                        <img
                            src="{{ $appLogoUrl }}"
                            alt="Logo {{ $appName }}"
                            class="receipt-logo-image"
                        >
                    @else
                        <span class="receipt-logo-fallback">
                            <img
                            src="{{ asset('images/sewajas-logo.png') }}"
                            alt="Logo SewaJas"
                            class="receipt-logo-image"
                        >
                        </span>
                    @endif
                </div>
            </td>

            <td class="receipt-brand-text-cell">
                <div class="receipt-brand-title">
                    {{ $appName }}
                </div>

                <div class="receipt-brand-subtitle">
                    Sewajas
                </div>
            </td>

            <td class="receipt-header-label-cell">
                <span class="receipt-header-label">RECEIPT</span>
            </td>
        </tr>
    </table>

    <div class="receipt-header-meta">
        <span>{{ $receiptNumber }}</span>
        <span class="receipt-meta-sep">|</span>
        <span>{{ $receiptDate }}</span>
    </div>

</div>


        {{-- CUSTOMER + BOOKING --}}
        <div class="receipt-section">
            <div class="receipt-row">
                <span class="receipt-label">Customer</span>
                <span class="receipt-value">{{ $customerName }}</span>
            </div>

            <div class="receipt-row">
                <span class="receipt-label">Invoice</span>
                <span class="receipt-value">{{ $invoiceNumber }}</span>
            </div>

            <div class="receipt-row">
                <span class="receipt-label">Sewa</span>
                <span class="receipt-value">{{ $rentalDate }}</span>
            </div>

            <div class="receipt-row">
                <span class="receipt-label">Kembali</span>
                <span class="receipt-value">{{ $returnDueDate }}</span>
            </div>

            @if($actualReturnDate)
                <div class="receipt-row">
                    <span class="receipt-label">Dikembalikan</span>
                    <span class="receipt-value">{{ $actualReturnDate }}</span>
                </div>
            @endif

            <div class="receipt-row receipt-row-status">
                <span class="receipt-label">Status</span>
                <span class="receipt-value">
                    <span class="status-badge {{ $statusBadgeClass }}">
                        {{ $statusBadge }}
                    </span>
                </span>
            </div>
        </div>

        {{-- ITEMS + HARGA --}}
        <div class="receipt-section">
            <div class="receipt-section-title">Produk yang Disewa</div>

            <div class="receipt-items">
                @forelse($rental->items as $item)
                    @php
                        $product = $item->product;
                        $quantity = max(1, (int) ($item->quantity ?? 1));

                        /*
                         * Prioritas harga:
                         * 1. Harga satuan pada item rental.
                         * 2. Harga rental pada produk.
                         * 3. Harga produk.
                         *
                         * Pastikan nama kolom ini sesuai dengan model Anda.
                         */
                        $unitPrice = $item->unit_price
                            ?? $item->price
                            ?? optional($product)->rental_price
                            ?? optional($product)->price
                            ?? 0;

                        $unitPrice = (float) $unitPrice;
                    @endphp

                    <div class="receipt-item receipt-item-priced">
                        <div class="receipt-item-details">
                            <div class="receipt-item-name">
                                {{ $product->name ?? ('Produk #' . $item->product_id) }}
                            </div>

                            <div class="receipt-item-price">
                                Rp {{ number_format($unitPrice, 0, ',', '.') }}
                                <span class="receipt-item-price-label">/ item</span>
                            </div>
                        </div>

                        <div class="receipt-item-qty">
                            x{{ $quantity }}
                        </div>
                    </div>
                @empty
                    <div class="receipt-item-empty">
                        Belum ada produk pada transaksi ini.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- QR CODE --}}
        <div class="receipt-section receipt-qr-section">
            <div class="receipt-qr-wrapper">
                @if(!empty($qrBase64))
                    <img
                        src="{{ $qrBase64 }}"
                        alt="QR Code detail rental"
                        class="receipt-qr-image"
                    >
                @endif
            </div>

            <div class="receipt-qr-label">
                Scan untuk detail rental
            </div>
        </div>

        {{-- PETUGAS --}}
        <div class="receipt-section receipt-petugas-section">
            <div class="receipt-row">
                <span class="receipt-label">Dibuat oleh</span>
                <span class="receipt-value">{{ $createdByName }}</span>
            </div>

            @if($processedByName)
                <div class="receipt-row">
                    <span class="receipt-label">Diproses oleh</span>
                    <span class="receipt-value">{{ $processedByName }}</span>
                </div>
            @endif
        </div>

        {{-- TOTAL --}}
        <div class="receipt-total-section">
            <div class="receipt-total-row">
                <span class="receipt-total-label">TOTAL</span>

                <span class="receipt-total-value">
                    Rp {{ number_format($totalAmount, 0, ',', '.') }}
                </span>
            </div>
        </div>

        {{-- FOOTER --}}
        <div class="receipt-footer">
            @if($companyAddress || $companyPhone || $companyWebsite)
                <div class="receipt-company-info">
                    @if($companyAddress)
                        <div class="receipt-company-line">
                            {{ $companyAddress }}
                        </div>
                    @endif

                    @if($companyPhone)
                        <div class="receipt-company-line">
                            {{ $companyPhone }}
                        </div>
                    @endif

                    @if($companyWebsite)
                        <div class="receipt-company-line">
                            {{ $companyWebsite }}
                        </div>
                    @endif
                </div>
            @endif
        </div>

    </div>
</div>


@endif

<style>
    .receipt-compact-card .receipt-item-priced {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        width: 100%;
        margin: 0 0 8px;
        padding: 12px 14px;
        background: #f8fafc;
        border: 1px solid #e6ebf2;
        border-radius: 12px;
        box-sizing: border-box;
        text-align: left;
    }

    .receipt-compact-card .receipt-item-details {
        flex: 1 1 auto;
        min-width: 0;
        text-align: left;
    }

    .receipt-compact-card .receipt-item-priced .receipt-item-name {
        margin: 0;
        color: #202b3c;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.45;
        text-align: left;
        overflow-wrap: anywhere;
    }

    .receipt-compact-card .receipt-item-price {
        margin-top: 5px;
        color: #2454bd;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.4;
        text-align: left;
    }

    .receipt-compact-card .receipt-item-price-label {
        color: #8792a3;
        font-size: 11px;
        font-weight: 400;
    }

    .receipt-compact-card .receipt-item-priced .receipt-item-qty {
        display: inline-flex;
        flex: 0 0 52px;
        align-items: center;
        justify-content: center;
        min-width: 52px;
        min-height: 32px;
        margin: 0;
        padding: 6px 8px;
        border-radius: 20px;
        background: #e8ebf0;
        color: #526176;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        box-sizing: border-box;
    }

    .receipt-compact-card .receipt-item-empty {
        padding: 12px;
        color: #6b7280;
        text-align: center;
    }

    .receipt-compact-card .receipt-header-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 5px 8px;
        margin-top: 10px;
        overflow-wrap: anywhere;
    }

    .receipt-compact-card .receipt-qr-image {
        display: block;
        width: 120px;
        max-width: 100%;
        height: auto;
        margin: 0 auto;
    }

    .receipt-compact-card {
        overflow: hidden;
    }

    @media print {
        .receipt-compact-card .receipt-item-priced {
            break-inside: avoid;
        }
    }
    
    .receipt-compact-card .receipt-header-sewajas {
    position: relative;
    overflow: visible;
    height: auto;
    min-height: 0;
    padding: 16px 18px 18px;
    color: #ffffff;
    background-color: #245bdc;
    background-image:
        radial-gradient(
            circle,
            rgba(255, 255, 255, 0.22) 1px,
            transparent 1.5px
        ),
        linear-gradient(
            145deg,
            transparent 48%,
            rgba(255, 255, 255, 0.07) 48.2%,
            transparent 70%
        );
    background-size: 18px 18px, 100% 100%;
    border-radius: 16px 16px 0 0;
}

.receipt-compact-card .receipt-brand-row {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.receipt-compact-card .receipt-logo-box {
    display: flex;
    flex: 0 0 48px;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border: 1px solid rgba(255, 255, 255, 0.35);
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.13);
    box-sizing: border-box;
}

.receipt-compact-card .receipt-logo-image {
    display: block;
    width: 100%;
    height: 100%;
    padding: 5px;
    object-fit: contain;
    box-sizing: border-box;
}

.receipt-compact-card .receipt-logo-fallback {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
}

.receipt-compact-card .receipt-brand-text {
    flex: 1 1 auto;
    min-width: 0;
}

.receipt-compact-card .receipt-brand-title {
    color: #ffffff;
    font-size: 25px;
    font-weight: 800;
    line-height: 1.15;
    letter-spacing: -0.5px;
}

.receipt-compact-card .receipt-brand-subtitle {
    margin-top: 3px;
    color: rgba(255, 255, 255, 0.95);
    font-size: 13px;
    font-weight: 600;
    line-height: 1.3;
}

.receipt-compact-card .receipt-header-label {
    flex: 0 0 auto;
    color: #ffffff;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2px;
    text-align: right;
}

.receipt-compact-card .receipt-header-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 5px 8px;
    width: 100%;
    height: auto;
    min-height: 24px;
    margin-top: 16px;
    padding: 0 2px;
    color: rgba(255, 255, 255, 0.95);
    font-size: 11px;
    line-height: 1.6;
    text-align: center;
    overflow: visible;
    overflow-wrap: anywhere;
    box-sizing: border-box;
}

.receipt-compact-card .receipt-meta-sep {
    opacity: 0.7;
}

@media (max-width: 420px) {
    .receipt-compact-card .receipt-header-sewajas {
        padding: 14px 12px 16px;
    }

    .receipt-compact-card .receipt-brand-row {
        gap: 9px;
    }

    .receipt-compact-card .receipt-logo-box {
        flex-basis: 42px;
        width: 42px;
        height: 42px;
    }

    .receipt-compact-card .receipt-brand-title {
        font-size: 21px;
    }

    .receipt-compact-card .receipt-brand-subtitle {
        font-size: 12px;
    }

    .receipt-compact-card .receipt-header-label {
        font-size: 9px;
        letter-spacing: 1px;
    }
}

/* =====================================
   RECEIPT - PERATAAN STATUS DAN QTY
===================================== */

/* Baris status */
.receipt-compact-card .receipt-row-status {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    min-height: 48px;
    width: 100%;
}

.receipt-compact-card .receipt-row-status .receipt-label {
    flex: 1 1 auto;
    min-width: 0;
    margin: 0;
}

.receipt-compact-card .receipt-row-status .receipt-value {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    justify-content: flex-end;
    margin: 0;
}

/* Badge status */
.receipt-compact-card .receipt-row-status .status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 112px;
    height: 36px;
    padding: 0 14px;
    border-radius: 999px;
    box-sizing: border-box;
    line-height: 1;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

/* Kartu produk */
.receipt-compact-card .receipt-item-priced {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    width: 100%;
    min-height: 82px;
    margin: 0 0 8px;
    padding: 14px 16px;
    box-sizing: border-box;
}

/* Informasi produk */
.receipt-compact-card .receipt-item-details {
    flex: 1 1 auto;
    min-width: 0;
    text-align: left;
}

/* Badge jumlah */
.receipt-compact-card .receipt-item-priced .receipt-item-qty {
    display: inline-flex;
    flex: 0 0 56px;
    align-items: center;
    justify-content: center;
    width: 56px;
    min-width: 56px;
    height: 36px;
    min-height: 36px;
    margin: 0;
    padding: 0;
    border: 0;
    border-radius: 999px;
    background: #e8ebf0;
    color: #526176;
    box-sizing: border-box;
    font-size: 13px;
    font-weight: 700;
    line-height: 1;
    text-align: center;
}

</style>


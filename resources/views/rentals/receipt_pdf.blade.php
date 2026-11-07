
@php
    $outputMode = 'pdf';

    $receiptPrintedBy = auth()->user()?->name
        ?? ($rental->createdBy?->name
        ?? \App\Services\SettingsService::get('app_name', 'SewaJas'));

    $receiptPrintedAt = now()->format('d M Y');
@endphp

@include('rentals.partials.premium-doc-head', [
    'docTitle' => 'Receipt ' . ($receipt['receipt_number'] ?? ''),
    'outputMode' => $outputMode,
])

@include('rentals.partials.doc-parts-receipt', [
    'rental' => $rental,
    'receipt' => $receipt
])



<style>
    @page { size: A4; margin: 10mm; }

    html, body {
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        color: #111827 !important;
        font-family: DejaVu Sans, Arial, sans-serif !important;
        font-size: 9px !important;
    }

    .receipt-pdf-wrapper {
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        text-align: center !important;
    }

    .receipt-pdf-card {
        display: block !important;
        width: 100mm !important;
        max-width: 100mm !important;
        margin: 0 auto !important;
        padding: 0 !important;
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 9px !important;
        text-align: left !important;
        overflow: hidden !important;
        font-family: DejaVu Sans, Arial, sans-serif !important;
    }

    .receipt-pdf-card table {
        border-collapse: collapse !important;
        border-spacing: 0 !important;
    }

    /* Header */
    .receipt-pdf-header {
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 12px 10px 11px !important;
        color: #ffffff !important;
        background: #245bdc !important;
    }

    .pdf-brand-table {
        display: table !important;
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
    }

    .pdf-brand-table tr { display: table-row !important; }
    .pdf-brand-table td {
        display: table-cell !important;
        padding: 0 !important;
        border: 0 !important;
        vertical-align: middle !important;
        color: #ffffff !important;
    }

    .pdf-logo-cell {
    width: 48px !important;
    text-align: left !important;
    padding-left: 15px !important;
    }
    .pdf-brand-cell {
        width: auto !important;
        text-align: center !important;
        padding: 0 4px !important;
    }
    .pdf-receipt-cell {
    width: 72px !important;
    min-width: 72px !important;
    text-align: center !important;
    white-space: nowrap !important;
    font-size: 6px !important;
    font-weight: bold !important;
    letter-spacing: .15px !important;
    padding-right: 8px !important;
    padding-left: 0 !important;
}
.
    .receipt-pdf-header .receipt-logo-box {
        display: block !important;
        width: 38px !important;
        height: 38px !important;
        margin: 0 !important;
        padding: 1px !important;
        border: 1px solid rgba(255,255,255,.4) !important;
        border-radius: 8px !important;
        background: rgba(255,255,255,.12) !important;
        text-align: center !important;
    }

    .receipt-pdf-header .receipt-logo-image {
        display: block !important;
        width: 34px !important;
        height: 34px !important;
        margin: 1px auto !important;
        padding: 0 !important;
    }

    .receipt-pdf-header .pdf-logo-fallback {
        display: block !important;
        width: 34px !important;
        height: 34px !important;
        margin: 1px auto !important;
        color: #ffffff !important;
        font-size: 13px !important;
        font-weight: bold !important;
        line-height: 34px !important;
        text-align: center !important;
    }

    .receipt-pdf-header .receipt-brand-title {
        margin: 0 !important;
        color: #ffffff !important;
        font-size: 18px !important;
        font-weight: bold !important;
        line-height: 1.2 !important;
        text-align: center !important;
    }

    .receipt-pdf-header .receipt-brand-subtitle {
        margin-top: 2px !important;
        color: #ffffff !important;
        font-size: 7px !important;
        line-height: 1.2 !important;
        text-align: center !important;
    }

    .pdf-header-meta {
        display: block !important;
        width: 100% !important;
        margin-top: 9px !important;
        color: #ffffff !important;
        font-size: 6.5px !important;
        line-height: 1.5 !important;
        text-align: center !important;
    }

    .pdf-meta-divider { padding: 0 5px !important; }

    /* Real HTML tables for all two-column rows */
    .pdf-info-section {
        padding: 9px 11px !important;
        background: #ffffff !important;
        border-bottom: 1px dashed #e5e7eb !important;
    }

    .pdf-info-table {
        display: table !important;
        width: 100% !important;
        table-layout: fixed !important;
    }

    .pdf-info-table tr { display: table-row !important; }
    .pdf-info-table td {
        display: table-cell !important;
        padding: 3px 0 !important;
        border: 0 !important;
        vertical-align: middle !important;
        line-height: 1.4 !important;
        font-size: 8px !important;
    }

    .pdf-info-table .pdf-label {
        width: 34% !important;
        color: #64748b !important;
        text-align: left !important;
    }

    .pdf-info-table .pdf-value {
        width: 66% !important;
        color: #111827 !important;
        font-weight: bold !important;
        text-align: right !important;
        overflow-wrap: break-word !important;
        word-wrap: break-word !important;
    }

    .pdf-status-row td {
        padding-top: 7px !important;
        border-top: 1px solid #f1f5f9 !important;
    }

    .pdf-status-row .pdf-value { text-align: right !important; }

    .status-badge {
        display: inline-block !important;
        min-width: 72px !important;
        padding: 6px 8px !important;
        border-radius: 20px !important;
        font-size: 7px !important;
        font-weight: bold !important;
        line-height: 1.2 !important;
        text-align: center !important;
    }

    .status-paid { background: #dcfce7 !important; color: #15803d !important; }
    .status-unpaid, .status-overdue { background: #fee2e2 !important; color: #b91c1c !important; }

    /* Product rows use actual table markup */
    .pdf-items-section {
        padding: 10px 11px !important;
        background: #ffffff !important;
        border-bottom: 1px dashed #e5e7eb !important;
    }

    .receipt-pdf-card .receipt-section-title {
        display: block !important;
        margin: 0 0 8px !important;
        color: #64748b !important;
        font-size: 7px !important;
        font-weight: bold !important;
        letter-spacing: .8px !important;
        text-align: center !important;
    }

    .pdf-product-table {
        display: table !important;
        width: 100% !important;
        table-layout: fixed !important;
        margin: 0 0 7px !important;
        background: #f8fafc !important;
        border: 1px solid #e6ebf2 !important;
        page-break-inside: avoid !important;
    }

    .pdf-product-table tr { display: table-row !important; }
    .pdf-product-table td {
        display: table-cell !important;
        padding: 9px 8px !important;
        border: 0 !important;
        vertical-align: middle !important;
    }

    .pdf-product-details { width: 76% !important; }
    .pdf-quantity-cell { width: 24% !important; text-align: right !important; }

    .receipt-pdf-card .receipt-item-name {
        display: block !important;
        margin: 0 !important;
        color: #202b3c !important;
        font-size: 8.5px !important;
        font-weight: bold !important;
        line-height: 1.4 !important;
        text-align: left !important;
    }

    .receipt-pdf-card .receipt-item-price {
        display: block !important;
        margin-top: 4px !important;
        color: #2454bd !important;
        font-size: 8px !important;
        font-weight: bold !important;
        text-align: left !important;
    }

    .receipt-pdf-card .receipt-item-price-label {
        color: #8792a3 !important;
        font-size: 7px !important;
        font-weight: normal !important;
    }

    .receipt-pdf-card .receipt-item-qty {
        display: inline-block !important;
        min-width: 38px !important;
        padding: 7px 5px !important;
        border-radius: 18px !important;
        background: #e8ebf0 !important;
        color: #526176 !important;
        font-size: 8px !important;
        font-weight: bold !important;
        text-align: center !important;
    }

    .receipt-item-empty {
        padding: 10px !important;
        color: #6b7280 !important;
        text-align: center !important;
    }

    /* QR */
    .pdf-qr-section {
        padding: 12px !important;
        background: #ffffff !important;
        text-align: center !important;
        border-bottom: 1px dashed #e5e7eb !important;
    }

    .receipt-pdf-card .receipt-qr-wrapper {
        display: block !important;
        width: 88px !important;
        height: 88px !important;
        margin: 0 auto 5px !important;
        padding: 1px !important;
        text-align: center !important;
    }

    .receipt-pdf-card .receipt-qr-image {
        display: block !important;
        width: 86px !important;
        height: 86px !important;
        margin: 0 auto !important;
    }

    .receipt-pdf-card .receipt-qr-label {
        color: #64748b !important;
        font-size: 7px !important;
        text-align: center !important;
    }

    /* Staff and total */
    .receipt-petugas-section {
        padding: 8px 11px !important;
        background: #f8fafc !important;
        border-bottom: 1px dashed #e5e7eb !important;
    }

    .receipt-total-section {
        padding: 10px 11px !important;
        background: #f0f9ff !important;
        border-bottom: 1px solid #e5e7eb !important;
    }

    .pdf-total-table {
        display: table !important;
        width: 100% !important;
        table-layout: fixed !important;
    }

    .pdf-total-table tr { display: table-row !important; }
    .pdf-total-table td {
        display: table-cell !important;
        padding: 0 !important;
        border: 0 !important;
        vertical-align: middle !important;
    }

    .pdf-total-label {
        width: 35% !important;
        color: #1e40af !important;
        font-size: 9px !important;
        font-weight: bold !important;
        text-align: left !important;
    }

    .pdf-total-value {
        width: 65% !important;
        color: #1e40af !important;
        font-size: 15px !important;
        font-weight: bold !important;
        text-align: right !important;
        white-space: nowrap !important;
    }

    /* Footer */
    .receipt-pdf-card .receipt-footer {
        padding: 9px 11px 10px !important;
        background: #ffffff !important;
        text-align: center !important;
    }

    .receipt-pdf-card .receipt-company-line {
        display: block !important;
        color: #64748b !important;
        font-size: 6.5px !important;
        line-height: 1.6 !important;
        text-align: center !important;
        word-wrap: break-word !important;
    }

    .no-print, .doc-toolbar, .doc-toolbar-wrapper { display: none !important; }

    
</style>



@include('rentals.partials.doc-close-html')

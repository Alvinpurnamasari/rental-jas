@php
    $docTitle = $docTitle ?? \App\Services\SettingsService::get('app_name', 'SewaJas') . ' Document';
    $outputMode = $outputMode ?? 'web';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $docTitle }}</title>
    <style>
        @page {
            size: A4;
            margin: 8mm;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            height: auto;
            font-family: Segoe UI, Tahoma, Arial, Helvetica, sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #111827;
            background: #ffffff;
            overflow-x: hidden;
            max-width: 100%;
        }

        /* Colors */
        .text-primary { color: #1E40AF; }
        .text-muted { color: #64748B; }
        .text-success { color: #15803D; }
        .text-warning { color: #A16207; }
        .text-danger { color: #B91C1C; }

        .bg-white { background-color: #ffffff; }
        .bg-muted { background-color: #F8FAFC; }
        .bg-light { background-color: #F1F5F9; }

        /* Utils */
        .w-full { width: 100%; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: 700; }
        .font-extrabold { font-weight: 800; }
        .uppercase { text-transform: uppercase; }
        .leading-tight { line-height: 1.25; }

        /* Page wrapper */
        .page {
            width: 100%;
            max-width: 100vw;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        /* Table base */
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            padding: 6px 8px;
            vertical-align: top;
        }

        th {
            background: #F8FAFC;
            font-size: 10px;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 1px solid #E5E7EB;
        }

        td {
            font-size: 11px;
            color: #111827;
            border-bottom: 1px solid #F1F5F9;
        }

        tr:last-child td {
            border-bottom: none;
        }

        /* Header */
        .header-section {
            border-bottom: 2px solid #E5E7EB;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }

        .header-table td {
            padding: 0;
            vertical-align: top;
        }

        .brand-logo {
            width: 54px;
            height: 54px;
            background: #1E40AF;
            color: #ffffff;
            font-weight: 800;
            font-size: 20px;
            text-align: center;
            line-height: 54px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
        }

        .brand-name {
            font-size: 17px;
            font-weight: 700;
            color: #111827;
            margin-top: 10px;
            letter-spacing: -0.01em;
        }

        .brand-detail {
            font-size: 10px;
            color: #64748B;
            margin-top: 3px;
            line-height: 1.6;
        }

        .doc-meta-label {
            font-size: 10px;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .doc-meta-value {
            font-size: 11px;
            font-weight: 700;
            color: #111827;
            margin-top: 2px;
        }

        .status-badge {
            display: inline-block;
            vertical-align: middle;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            line-height: 1.4;
        }

        .status-paid { background: #DCFCE7; color: #15803D; }
        .status-unpaid { background: #FEE2E2; color: #B91C1C; }
        .status-partial { background: #FEF3C7; color: #A16207; }
        .status-overdue { background: #FEE2E2; color: #B91C1C; }

        /* QR Card */
        .qr-card {
            border: 1px solid #E5E7EB;
            background: #ffffff;
            padding: 10px;
            text-align: center;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            display: inline-block;
        }

        .qr-image {
            width: 100px;
            height: 100px;
            display: block;
            margin: 0 auto;
        }

        .qr-caption {
            font-size: 9px;
            color: #64748B;
            margin-top: 6px;
        }


        /* Info Card */
        .info-card {
            background: #ffffff;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            padding: 6px 8px;
            margin-bottom: 6px;
        }

        .info-card-title {
            font-size: 10px;
            font-weight: 700;
            color: #1E40AF;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 4px;
            padding-bottom: 3px;
            border-bottom: 1px solid #E5E7EB;
        }

        .section-title {
            font-size: 9px;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 3px;
        }

        .info-row {
            padding: 1px 0;
        }

        .info-label {
            font-size: 9px;
            color: #64748B;
            display: inline-block;
            width: 80px;
            vertical-align: middle;  
        }

        .info-value {
            font-size: 11px;
            font-weight: 600;
            color: #111827;
            vertical-align: middle;   
        }
        /* Payment Card */
        .payment-card {
            background: #ffffff;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            padding: 6px 8px;
            margin-bottom: 6px;
        }

        .payment-card-title {
            font-size: 10px;
            font-weight: 700;
            color: #1E40AF;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 4px;
            padding-bottom: 3px;
            border-bottom: 1px solid #E5E7EB;
        }

        .payment-row {
            padding: 2px 0;
        }

        .payment-label {
            font-size: 9px;
            color: #64748B;
            display: inline-block;
            width: 90px;
        }

        .payment-value {
            font-size: 9px;
            font-weight: 600;
            color: #111827;
        }

        /* Transaction Card */
        .transaction-card {
            background: #ffffff;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 14px;
        }

        .transaction-card-title {
            font-size: 12px;
            font-weight: 700;
            color: #1E40AF;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #E5E7EB;
        }

        .transaction-row {
            padding: 4px 0;
        }

        .transaction-label {
            font-size: 10px;
            color: #64748B;
            display: inline-block;
            width: 100px;
        }

        .transaction-value {
            font-size: 11px;
            font-weight: 600;
            color: #111827;
        }

        /* Audit Trail Card */
        .audit-card {
            background: #ffffff;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 14px;
        }

        .audit-card-title {
            font-size: 12px;
            font-weight: 700;
            color: #1E40AF;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #E5E7EB;
        }

        .audit-row {
            padding: 4px 0;
        }

        .audit-label {
            font-size: 10px;
            color: #64748B;
            display: inline-block;
            width: 110px;
        }

        .audit-value {
            font-size: 11px;
            font-weight: 600;
            color: #111827;
        }

        /* Items Table */
        .items-table th {
            background: #F8FAFC;
            font-size: 10px;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 12px 12px;
            border-bottom: 1px solid #E5E7EB;
        }

        .items-table td {
            padding: 12px 12px;
            font-size: 11px;
            border-bottom: 1px solid #F1F5F9;
        }

        .items-table .text-right { text-align: right; }
        .items-table .text-center { text-align: center; }

        /* Summary */
        .summary-card {
            background: #ffffff;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            padding: 6px 8px;
            margin-bottom: 6px;
        }

        .summary-card-title {
            font-size: 10px;
            font-weight: 700;
            color: #1E40AF;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 4px;
            padding-bottom: 3px;
            border-bottom: 1px solid #E5E7EB;
        }

        .summary-table td {
            padding: 3px 0;
            font-size: 9px;
        }

        .summary-table .summary-label {
            color: #64748B;
            font-weight: 600;
            text-align: left;
        }

        .summary-table .summary-value {
            font-weight: 700;
            text-align: right;
        }

        .summary-total td {
            padding: 10px 0 6px;
            font-size: 22px;
            font-weight: 800;
            color: #1E40AF;
            border-top: 2px solid #E5E7EB;
        }

        /* Notes */
        .notes-box {
            background: #F8FAFC;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            padding: 8px 10px;
            margin-top: 10px;
            font-size: 9px;
            color: #64748B;
            line-height: 1.4;
        }

        /* Footer */
        .doc-footer {
            border-top: 1px solid #E5E7EB;
            padding-top: 10px;
            margin-top: 12px;
            font-size: 9px;
            color: #64748B;
            text-align: center;
            line-height: 1.6;
        }

        /* Buttons */
        .doc-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.15s ease;
            line-height: 1;
        }

        .doc-btn-primary {
            background: #2563EB;
            color: #FFFFFF;
            border: none;
            font-weight: 600;
            box-shadow: 0 1px 2px rgba(0,0,0,0.08);
        }

        .doc-btn-primary:hover {
            background: #1D4ED8;
        }

        .doc-btn-whatsapp {
            background: #25D366;
            color: #FFFFFF;
            border: none;
            font-weight: 600;
            box-shadow: 0 1px 2px rgba(0,0,0,0.08);
        }

        .doc-btn-whatsapp:hover {
            background: #1EBE5A;
        }

        .doc-btn-secondary {
            background: #FFFFFF;
            color: #374151;
            border: 1px solid #D1D5DB;
            font-weight: 500;
        }

        .doc-btn-secondary:hover {
            background: #F9FAFB;
            border-color: #9CA3AF;
        }

        .doc-btn-ghost {
            background: transparent;
            color: #6B7280;
            border: 1px solid transparent;
            font-weight: 500;
        }

        .doc-btn-ghost:hover {
            background: #F3F4F6;
            color: #374151;
        }

        /* Toolbar */
        .doc-toolbar-wrapper {
            position: relative;
            width: 100%;
            max-width: 100%;
            overflow: hidden;
        }

        .doc-toolbar-wrapper::after {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 24px;
            background: linear-gradient(to right, transparent, rgba(255,255,255,0.9));
            pointer-events: none;
        }

        .doc-toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 24px;
            background: #FFFFFF;
            border-bottom: 1px solid #E5E7EB;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            position: sticky;
            top: 0;
            z-index: 10;
            width: 100%;
            max-width: 100%;
        }

        /* Toolbar responsive */
        @media (max-width: 640px) {
            .doc-toolbar-wrapper::after {
                content: '';
                position: absolute;
                right: 0;
                top: 0;
                bottom: 0;
                width: 24px;
                background: linear-gradient(to right, transparent, rgba(255,255,255,0.9));
                pointer-events: none;
            }

            .doc-toolbar {
                overflow-x: auto;
                overflow-y: hidden;
                width: 100%;
                max-width: 100%;
                flex-wrap: nowrap;
                padding: 12px 16px;
                gap: 8px;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
            }

            .doc-toolbar::-webkit-scrollbar {
                display: none;
            }

            .doc-btn-primary,
            .doc-btn-whatsapp,
            .doc-btn-secondary,
            .doc-btn-ghost {
                flex-shrink: 0;
                white-space: nowrap;
                padding: 8px 14px;
                font-size: 13px;
            }
        }

        @media (max-width: 375px) {
            .doc-btn-primary .btn-text,
            .doc-btn-secondary .btn-text,
            .doc-btn-ghost .btn-text {
                display: none;
            }

            .doc-btn-whatsapp .btn-text {
                display: inline;
            }

            .doc-btn-primary,
            .doc-btn-secondary,
            .doc-btn-ghost,
            .doc-btn-whatsapp {
                padding: 8px 10px;
            }
        }

        /* Print & PDF */
        @media print {
            @page { size: A4; margin: 8mm; }
            body { background: #ffffff !important; }
            .no-print { display: none !important; }
            .page { padding: 0 !important; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

            /* Compact receipt: show all items without scroll */
            .receipt-items {
                max-height: none !important;
                overflow-y: visible !important;
            }
            .receipt-compact-wrapper {
                padding: 0 !important;
                background: #ffffff !important;
                min-height: auto !important;
            }
            .receipt-compact-card {
                max-width: 80mm !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }
            .doc-toolbar {
                position: static !important;
                box-shadow: none !important;
            }
            .doc-toolbar-wrapper::after {
                display: none !important;
            }
        }

        .mode-pdf .page {
            padding: 0;
        }

        /* =========================================================
           COMPACT RECEIPT (Boarding Pass / Thermal Style)
           ========================================================= */
        .receipt-compact-wrapper {
            text-align: center;
            padding: 16px 12px;
            background: #F3F4F6;
        }

        .receipt-compact-card {
            width: 100%;
            max-width: 380px;
            margin: 0 auto;
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08), 0 1px 3px rgba(0,0,0,0.04);
            overflow: hidden;
            font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
        }

        .receipt-header {
            background: #2563EB;
            color: #ffffff;
            padding: 18px 20px 14px;
        }

        .receipt-header-top {
            overflow: hidden;
            margin-bottom: 6px;
        }

        .receipt-header-top .receipt-app-name {
            float: left;
        }

        .receipt-header-top .receipt-header-label {
            float: right;
        }

        .receipt-header-meta {
            font-size: 11px;
            opacity: 0.9;
            overflow: hidden;
            margin-top: 4px;
        }

        .receipt-meta-sep {
            opacity: 0.6;
            margin: 0 6px;
        }

        .receipt-app-name {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .receipt-header-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.18em;
            color: #FFFFFF;
            opacity: 1;
            text-shadow: 0 1px 2px rgba(0,0,0,0.25);
        }

        .receipt-brand {
            font-size: 9px;
            font-weight: 600;
            opacity: 0.7;
            letter-spacing: 0.04em;
        }

        .receipt-section {
            padding: 14px 20px;
            border-bottom: 1px dashed #E5E7EB;
        }

        .receipt-section:last-of-type {
            border-bottom: none;
        }

        .receipt-section-title {
            font-size: 9px;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 8px;
        }

        .receipt-row {
            display: table;
            width: 100%;
            padding: 3px 0;
        }

        .receipt-label {
            font-size: 11px;
            color: #64748B;
            font-weight: 500;
            display: table-cell;
            width: 35%;
            text-align: left;
            vertical-align: middle;
        }

        .receipt-value {
            font-size: 11px;
            color: #111827;
            font-weight: 600;
            text-align: right;
            display: table-cell;
            width: 65%;
            max-width: 65%;
            word-break: break-word;
            vertical-align: middle;
        }

        .receipt-row-status {
            margin-top: 4px;
            padding-top: 6px;
            border-top: 1px solid #F1F5F9;
        }

        .receipt-items {
            display: block;
            overflow: hidden;
            margin-top: 6px;
        }

        .receipt-item {
            display: table;
            width: 100%;
            padding: 6px 10px;
            background: #F8FAFC;
            border-radius: 8px;
            border: 1px solid #F1F5F9;
            margin-bottom: 6px;
        }

        .receipt-item-name {
            font-size: 11px;
            font-weight: 600;
            color: #111827;
            display: table-cell;
            width: 70%;
            vertical-align: middle;
        }

        .receipt-item-qty {
            font-size: 10px;
            font-weight: 700;
            color: #64748B;
            background: #E5E7EB;
            padding: 2px 8px;
            border-radius: 999px;
            display: table-cell;
            width: 20%;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
        }

        .receipt-qr-section {
            text-align: center;
            padding: 18px 20px;
            background: #ffffff;
            border-top: 1px dashed #E5E7EB;
            border-bottom: 1px dashed #E5E7EB;
        }

        .receipt-qr-wrapper {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #E5E7EB;
            background: #ffffff;
            margin: 0 auto 8px;
        }

        .receipt-qr-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .receipt-qr-label {
            font-size: 10px;
            color: #64748B;
            font-weight: 500;
        }

        .receipt-petugas-section {
            background: #F8FAFC;
        }

        .receipt-total-section {
            padding: 14px 20px;
            background: #F0F9FF;
            border-top: 2px solid #E5E7EB;
        }

        .receipt-total-row {
            display: table;
            width: 100%;
        }

        .receipt-total-label {
            font-size: 12px;
            font-weight: 700;
            color: #1E40AF;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            display: table-cell;
            width: 35%;
            vertical-align: middle;
        }

        .receipt-total-value {
            font-size: 22px;
            font-weight: 800;
            color: #1E40AF;
            display: table-cell;
            width: 65%;
            text-align: right;
            vertical-align: middle;
        }

        .receipt-footer {
            padding: 10px 20px 14px;
            text-align: center;
            border-top: 1px solid #E5E7EB;
        }

        .receipt-footer-text {
            font-size: 9px;
            color: #64748B;
            margin-bottom: 2px;
        }

        .receipt-footer-note {
            font-size: 8px;
            color: #94A3B8;
        }

        .receipt-company-info {
            text-align: center;
            padding: 8px 0 4px 0;
            border-top: 1px dashed #E5E7EB;
            margin-top: 8px;
        }

        .receipt-company-line {
            font-size: 10px;
            color: #6B7280;
            line-height: 1.6;
        }

        
/* =========================================================
   RECEIPT TOOLBAR - RESPONSIVE MOBILE
   Menu tersusun 2 kolom, tanpa geser horizontal
   ========================================================= */

@media (max-width: 640px) {
    .doc-toolbar-wrapper {
        width: 100% !important;
        overflow: visible !important;
    }

    .doc-toolbar-wrapper::after {
        display: none !important;
        content: none !important;
    }

    .doc-toolbar {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: wrap !important;
        justify-content: flex-start !important;
        align-items: stretch !important;
        gap: 8px !important;
        width: 100% !important;
        padding: 12px !important;
        overflow: visible !important;
    }

    .doc-toolbar .doc-btn {
        display: inline-flex !important;
        flex: 0 0 calc(50% - 4px) !important;
        width: calc(50% - 4px) !important;
        min-width: 0 !important;
        max-width: calc(50% - 4px) !important;
        min-height: 42px !important;
        justify-content: center !important;
        align-items: center !important;
        gap: 6px !important;
        padding: 9px 6px !important;
        font-size: 12px !important;
        white-space: normal !important;
        text-align: center !important;
        box-sizing: border-box !important;
    }

    .doc-toolbar .doc-btn .btn-text {
        display: inline !important;
        white-space: normal !important;
        line-height: 1.25 !important;
    }

    .doc-toolbar .doc-btn i {
        flex-shrink: 0 !important;
    }
}
/* Tombol Kembali - tampilan lebih tegas */
.doc-toolbar .doc-btn-ghost {
    color: #245bdc !important;
    background: #ffffff !important;
    border: 1px solid #245bdc !important;
    font-weight: 600 !important;
    opacity: 1 !important;
    text-decoration: none !important;
}

.doc-toolbar .doc-btn-ghost:hover {
    color: #ffffff !important;
    background: #245bdc !important;
    border-color: #245bdc !important;
}

/* Pastikan panah dan tulisan ikut berwarna */
.doc-toolbar .doc-btn-ghost span {
    color: inherit !important;
}


    </style>
</head>
<body>
    <div class="page">

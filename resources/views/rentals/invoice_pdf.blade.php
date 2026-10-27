
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $rental->invoice_number }}</title>
    <style>
        @page {
            margin: 28px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1e293b;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header table,
        .info table,
        .items,
        .totals {
            width: 100%;
            border-collapse: collapse;
        }

        .brand {
            color: #2563eb;
            font-size: 23px;
            font-weight: bold;
        }

        .invoice-title {
            text-align: right;
            font-size: 22px;
            font-weight: bold;
        }

        .muted {
            color: #64748b;
        }

        .info td {
            width: 50%;
            vertical-align: top;
            padding: 8px 0;
        }

        .items {
            margin-top: 20px;
        }

        .items th {
            background: #eff6ff;
            color: #1d4ed8;
            text-align: left;
        }

        .items th,
        .items td {
            padding: 10px 8px;
            border: 1px solid #dbe3ef;
        }

        .right {
            text-align: right;
        }

        .totals {
            margin-top: 18px;
        }

        .totals td {
            padding: 6px;
        }

        .grand-total {
            font-size: 16px;
            font-weight: bold;
            color: #1d4ed8;
            border-top: 2px solid #2563eb;
        }

        .footer {
            margin-top: 35px;
            padding-top: 10px;
            border-top: 1px solid #dbe3ef;
            text-align: center;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="brand">SEWAJAS</div>
                    <div class="muted">
                        {{ $rental->branch->name ?? 'Layanan Penyewaan Jas' }}
                    </div>
                </td>
                <td class="invoice-title">
                    INVOICE
                    <div style="font-size: 11px; margin-top: 8px;">
                        {{ $rental->invoice_number }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="info">
        <table>
            <tr>
                <td>
                    <strong>INVOICE UNTUK</strong><br>
                    {{ $rental->customer->name ?? '-' }}<br>
                    {{ $rental->customer->phone ?? '-' }}
                </td>
                <td>
                    <strong>INFORMASI PENYEWAAN</strong><br>
                    Tanggal sewa:
                    {{ $rental->rental_date ?? $rental->created_at?->format('d/m/Y') ?? '-' }}<br>
                    Jatuh tempo:
                    {{ $rental->due_date ?? '-' }}<br>
                    Status:
                    {{ ucfirst($rental->status ?? '-') }}
                </td>
            </tr>
        </table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>No.</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Ukuran</th>
                <th class="right">Harga</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rental->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product->name ?? '-' }}</td>
                    <td>{{ $item->product->category->name ?? '-' }}</td>
                    <td>{{ $item->size ?? '-' }}</td>
                    <td class="right">
                        Rp {{ number_format((float) ($item->price ?? $item->rental_price ?? 0), 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center">
                        Tidak ada barang penyewaan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @php
        $subtotal = (float) ($rental->subtotal ?? $rental->total_amount ?? 0);
        $discount = (float) ($rental->discount ?? 0);
        $deposit = (float) ($receipt['deposit'] ?? 0);
        $paid = (float) ($rental->paid_amount ?? 0);
        $total = (float) ($rental->total_amount ?? 0);
        $remaining = max(0, $total - $paid);
    @endphp

    <table class="totals">
        <tr>
            <td class="right">Subtotal</td>
            <td class="right" style="width: 160px">
                Rp {{ number_format($subtotal, 0, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td class="right">Diskon</td>
            <td class="right">
                Rp {{ number_format($discount, 0, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td class="right">Deposit / Jaminan</td>
            <td class="right">
                Rp {{ number_format($deposit, 0, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td class="right">Total pembayaran sewa</td>
            <td class="right">
                Rp {{ number_format($total, 0, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td class="right">Sudah dibayar</td>
            <td class="right">
                Rp {{ number_format($paid, 0, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td class="right grand-total">SISA TAGIHAN</td>
            <td class="right grand-total">
                Rp {{ number_format($remaining, 0, ',', '.') }}
            </td>
        </tr>
    </table>

    <div class="footer">
        Invoice ini dibuat secara otomatis oleh sistem SewaJas.<br>
        Terima kasih telah menggunakan layanan kami.
    </div>
</body>
</html>

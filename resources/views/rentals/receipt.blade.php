@php
    $outputMode = 'web';
@endphp

@include('rentals.partials.premium-doc-head', [
    'docTitle' => 'Receipt',
    'outputMode' => $outputMode,
])

<div class="no-print doc-toolbar-wrapper">
    <div class="no-print doc-toolbar">
        <a class="doc-btn doc-btn-ghost" href="{{ route('rentals.show', $rental) }}">
            <i data-lucide="chevron-left" class="w-4 h-4"></i> <span class="btn-text">Kembali</span>
        </a>
        <a class="doc-btn doc-btn-primary" href="{{ route('rentals.receipt.pdf', $rental) }}">
            <i data-lucide="download" class="w-4 h-4"></i> <span class="btn-text">Download PDF</span>
        </a>
        <button class="doc-btn doc-btn-secondary" type="button" onclick="window.print()">
            <i data-lucide="printer" class="w-4 h-4"></i> <span class="btn-text">Print</span>
        </button>
        
        <a class="doc-btn doc-btn-whatsapp"
        href="javascript:void(0)"
        onclick="shareReceiptToWa(
            '{{ route('rentals.receipt.pdf', $rental) }}',
            '{{ addslashes($rental->customer->name ?? 'Customer') }}',
            '{{ $rental->invoice_number }}',
            '{{ preg_replace('/[^0-9]/', '', str_starts_with($rental->customer->phone ?? '', '0') ? '62'.substr($rental->customer->phone, 1) : ($rental->customer->phone ?? '')) }}'
        )">
        <i data-lucide="message-circle" class="w-4 h-4"></i>
        <span class="btn-text">WhatsApp</span>
        </a>
                <button class="doc-btn doc-btn-secondary" type="button" onclick="navigator.clipboard.writeText('{{ route('rentals.receipt.pdf', $rental) }}'); alert('Link PDF Receipt berhasil disalin!')">
            <i data-lucide="link" class="w-4 h-4"></i> <span class="btn-text">Copy Link Receipt</span>
        </button>
    </div>
</div>

@include('rentals.partials.doc-parts-receipt', ['rental'=>$rental, 'receipt'=>$receipt])

<script>

function shareReceiptToWa(pdfUrl, customerName, invoiceNumber, customerPhone) {
    if (!customerPhone) {
        alert('Nomor WhatsApp customer tidak tersedia.');
        return;
    }

    const message = encodeURIComponent(
        "Halo " + customerName + ",\n\n" +
        "Berikut receipt penyewaan Anda.\n" +
        "Nomor invoice: " + invoiceNumber + "\n\n" +
        "Silakan buka tautan berikut untuk melihat receipt PDF:\n" +
        pdfUrl + "\n\n" +
        "Terima kasih telah menggunakan layanan SewaJas."
    );

    window.open(
        "https://wa.me/" + customerPhone + "?text=" + message,
        "_blank"
    );
}

</script>

@include('rentals.partials.doc-close-html')



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
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span class="btn-text">Kembali</span>
        </a>

        <a class="doc-btn doc-btn-primary" href="{{ route('rentals.receipt.pdf', $rental) }}">
            <i data-lucide="download" class="w-4 h-4"></i>
            <span class="btn-text">Download PDF</span>
        </a>

        <button class="doc-btn doc-btn-secondary" type="button" onclick="window.print()">
            <i data-lucide="printer" class="w-4 h-4"></i>
            <span class="btn-text">Print</span>
        </button>

        <button
            class="doc-btn doc-btn-whatsapp"
            type="button"
            onclick="shareReceiptToWa(
                @js($rental->customer->name ?? 'Customer'),
                @js($rental->invoice_number),
                @js(route('rentals.receipt.pdf', $rental))
            )"
        >
            <i data-lucide="message-circle" class="w-4 h-4"></i>
            <span class="btn-text">WhatsApp</span>
        </button>

        <button
            class="doc-btn doc-btn-secondary"
            type="button"
            onclick="copyReceiptLink(@js(route('rentals.receipt.pdf', $rental)))"
        >
            <i data-lucide="link" class="w-4 h-4"></i>
            <span class="btn-text">Copy Link Receipt</span>
        </button>
    </div>
</div>

@include('rentals.partials.doc-parts-receipt', [
    'rental' => $rental,
    'receipt' => $receipt
])

{{-- Library untuk mengubah tampilan nota menjadi gambar --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
async function shareReceiptToWa(customerName, invoiceNumber, pdfUrl) {
    const receiptCard = document.querySelector('.receipt-compact-card');

    if (!receiptCard) {
        alert('Tampilan nota tidak ditemukan.');
        return;
    }

    if (typeof html2canvas === 'undefined') {
        alert('Library gambar gagal dimuat. Periksa koneksi internet dan muat ulang halaman.');
        return;
    }

    try {
        const canvas = await html2canvas(receiptCard, {
            backgroundColor: '#ffffff',
            scale: 2,
            useCORS: true,
            logging: false
        });

        const blob = await new Promise((resolve, reject) => {
            canvas.toBlob(result => {
                if (result) {
                    resolve(result);
                } else {
                    reject(new Error('Gagal membuat gambar nota.'));
                }
            }, 'image/png');
        });

        const safeInvoice = String(invoiceNumber || 'nota')
            .replace(/[^a-zA-Z0-9-_]/g, '-');

        const imageFile = new File(
            [blob],
            `nota-${safeInvoice}.png`,
            { type: 'image/png' }
        );

        const message =
            `Halo ${customerName},\n\n` +
            `Berikut nota penyewaan Anda.\n` +
            `Nomor invoice: ${invoiceNumber}\n\n` +
            `Terima kasih telah menggunakan layanan SewaJas.`;

        // Bagikan file gambar melalui menu berbagi perangkat.
        if (
            navigator.share &&
            navigator.canShare &&
            navigator.canShare({ files: [imageFile] })
        ) {
            await navigator.share({
                title: `Nota ${invoiceNumber}`,
                text: message,
                files: [imageFile]
            });

            return;
        }

        // Cadangan jika browser tidak mendukung berbagi file.
        const imageUrl = URL.createObjectURL(blob);
        const link = document.createElement('a');

        link.href = imageUrl;
        link.download = imageFile.name;
        document.body.appendChild(link);
        link.click();
        link.remove();

        // Beri waktu browser memulai proses download.
        setTimeout(() => URL.revokeObjectURL(imageUrl), 60000);

        const whatsappUrl =
            'https://wa.me/?text=' +
            encodeURIComponent(
                message + '\n\nLink PDF nota: ' + pdfUrl
            );

        window.open(whatsappUrl, '_blank');

        alert(
            'Browser belum mendukung berbagi gambar langsung. ' +
            'Gambar nota disiapkan untuk diunduh. Jika WhatsApp terbuka, ' +
            'lampirkan gambar tersebut secara manual.'
        );
    } catch (error) {
        if (error.name === 'AbortError') {
            return;
        }

        console.error('Gagal membagikan nota:', error);
        alert('Gagal membuat atau membagikan gambar nota. Silakan coba lagi.');
    }
}

async function copyReceiptLink(url) {
    try {
        await navigator.clipboard.writeText(url);
        alert('Link PDF Receipt berhasil disalin!');
    } catch (error) {
        window.prompt('Salin link PDF Receipt berikut:', url);
    }
}
</script>

@include('rentals.partials.doc-close-html')

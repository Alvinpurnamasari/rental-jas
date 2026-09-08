@php
    $rental = $rental ?? null;
    $paymentMethod = $rental->payment_method ?? null;
    $paymentStatus = $rental->payment_status ?? 'unpaid';
    $changeAmount = $rental->change_amount ?? 0;

    $methodLabel = match($paymentMethod) {
        'cash' => 'Tunai',
        'transfer' => 'Transfer',
        'qris' => 'QRIS',
        default => $paymentMethod ?? '-',
    };
    $statusLabel = match($paymentStatus) {
        'paid' => 'Lunas',
        'partial' => 'Sebagian',
        'overdue' => 'Terlambat',
        default => 'Belum Bayar',
    };
    $statusBadgeClass = match($paymentStatus) {
        'paid' => 'status-paid',
        'partial' => 'status-partial',
        'overdue' => 'status-overdue',
        default => 'status-unpaid',
    };
@endphp

<div class="payment-card">
    <div class="payment-card-title">Payment Information</div>
    <div class="payment-row">
        <span class="payment-label">Metode</span>
        <span class="payment-value">{{ $methodLabel }}</span>
    </div>
    <div class="payment-row">
        <span class="payment-label">Status Sewa</span>
        <span class="status-badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
    </div>
    @if($rental->fine_status === 'unpaid' || $rental->fine_status === 'partial')
    <div class="payment-row">
        <span class="payment-label">Status Denda</span>
        <span class="status-badge status-partial">{{ match($rental->fine_status) { 'unpaid' => 'BELUM DIBAYAR', 'partial' => 'SEBAGIAN', default => strtoupper($rental->fine_status) } }}</span>
        @if($rental->fine_amount > 0)
        <span class="payment-value" style="color:#B91C1C; font-weight:700;">Rp {{ number_format($rental->fine_amount, 0, ',', '.') }}</span>
        @endif
    </div>
    @if($changeAmount === 0 && $rental->fine_amount > 0 && ($rental->fine_status === 'unpaid' || $rental->fine_status === 'partial'))
    <div class="payment-row">
        <span class="payment-label">Sisa Denda Belum Tertutup</span>
        <span class="payment-value" style="color:#B91C1C; font-weight:700;">Rp {{ number_format(max(0, (float) ($rental->fine_amount ?? 0) - (float) ($rental->fine_paid_amount ?? 0)), 0, ',', '.') }}</span>
    </div>
    @endif
    @endif
    @if(!empty($rental->payments) && $rental->payments->count() > 0)
        @php
            $latestPayment = $rental->payments->sortByDesc('paid_at')->first();
        @endphp
        <div class="payment-row">
            <span class="payment-label">Paid Date</span>
            <span class="payment-value">{{ optional($latestPayment->paid_at)->format('d M Y H:i') ?? '-' }}</span>
        </div>
        @if(!empty($latestPayment->reference_number))
        <div class="payment-row">
            <span class="payment-label">Reference</span>
            <span class="payment-value">{{ $latestPayment->reference_number }}</span>
        </div>
        @endif
    @endif
    @if($paymentMethod === 'cash' && $changeAmount > 0)
    <div class="payment-row">
            <span class="payment-label">Cash Change</span>
        <span class="payment-value" style="color:#15803D; font-weight:700;">Rp {{ number_format($changeAmount, 0, ',', '.') }}</span>
    </div>
    @endif
</div>

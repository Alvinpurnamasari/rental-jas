{{-- resources/views/reports/payments.blade.php --}}
@extends('Layouts.app')
@section('title', 'Laporan Pembayaran')
@section('page-title', 'Laporan Pembayaran')
@section('subtitle', 'Riwayat pembayaran transaksi penyewaan')

@section('content')
<div class="space-y-6">

    {{-- ── SUMMARY CARDS ───────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        @foreach([
            ['label'=>'Total Pembayaran', 'value'=>$summary['total'], 'color'=>'var(--primary)', 'bg'=>'var(--secondary)', 'icon'=>'receipt'],
            ['label'=>'Total Nominal', 'value'=>'Rp '.number_format($summary['total_amount'], 0, ',', '.'), 'color'=>'#15803D', 'bg'=>'#F0FDF4', 'icon'=>'coins'],
            ['label'=>'Tunai', 'value'=>$summary['cash'], 'color'=>'#10B981', 'bg'=>'#10B98115', 'icon'=>'banknote'],
            ['label'=>'Transfer', 'value'=>$summary['transfer'], 'color'=>'#3B82F6', 'bg'=>'#3B82F615', 'icon'=>'arrow-left-right'],
            ['label'=>'QRIS', 'value'=>$summary['qris'], 'color'=>'#F59E0B', 'bg'=>'#F59E0B15', 'icon'=>'qr-code'],
        ] as $card)
        <div class="card p-4 text-center">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center mx-auto mb-2"
                 style="background:{{ $card['bg'] }}">
                <i data-lucide="{{ $card['icon'] }}" class="w-4 h-4" style="color:{{ $card['color'] }}"></i>
            </div>
            <p class="text-xl font-bold font-playfair" style="color:var(--text-dark)">{{ $card['value'] }}</p>
            <p class="text-xs" style="color:var(--text-soft)">{{ $card['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- ── FILTER ─────────────────────────────────────────────── --}}
    <div class="ds-card p-5">
        <form method="GET" action="{{ route('reports.payments') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium mb-1" style="color:var(--text-soft)">Periode</label>
                <select name="period" class="form-input" onchange="togglePaymentPeriod(this)">
                    <option value="">Pilih Tanggal</option>
                    <option value="today" {{ ($period ?? '') === 'today' ? 'selected' : '' }}>Hari Ini</option>
                    <option value="week" {{ ($period ?? '') === 'week' ? 'selected' : '' }}>Minggu Ini</option>
                    <option value="month" {{ ($period ?? '') === 'month' ? 'selected' : '' }}>Bulan Ini</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1" style="color:var(--text-soft)">Dari Tanggal</label>
                <input type="date" name="date_from" id="payment_date_from" value="{{ $dateFrom ?? '' }}" class="form-input">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1" style="color:var(--text-soft)">Sampai Tanggal</label>
                <input type="date" name="date_to" id="payment_date_to" value="{{ $dateTo ?? '' }}" class="form-input">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1" style="color:var(--text-soft)">Cari</label>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Invoice / nama / HP" class="form-input">
            </div>
            <button type="submit" class="btn-primary">
                <i data-lucide="search" class="w-4 h-4"></i> Tampilkan
            </button>
            <a href="{{ route('reports.payments') }}" class="btn-secondary">Reset</a>
        </form>
    </div>

    {{-- ── EXPORT BUTTONS ───────────────────────────────────────── --}}
    <div class="flex items-center justify-end gap-2 mb-6">
        <a href="{{ route('reports.export.payments.excel', request()->query()) }}" class="btn-secondary text-sm py-1.5 px-3">
            <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i> Export Excel
        </a>
        <a href="{{ route('reports.export.payments.pdf', request()->query()) }}" class="btn-secondary text-sm py-1.5 px-3">
            <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Export PDF
        </a>
    </div>

    {{-- ── TABEL PEMBAYARAN ───────────────────────────────────────── --}}
    <div class="ds-card overflow-hidden">
        <div class="px-5 py-4 border-b flex items-center justify-between" style="border-color:var(--border)">
            <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Daftar Pembayaran</h2>
            <span class="text-xs" style="color:var(--text-soft)">{{ $payments->total() }} pembayaran</span>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <colgroup>
                <col style="width: 12%">
                <col style="width: 12%">
                <col style="width: 12%">
                <col style="width: 16%">
                <col style="width: 14%">
                <col style="width: 10%">
                <col style="width: 10%">
                <col style="width: 14%">
            </colgroup>
            <thead>
                <tr>
                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 bg-slate-50 text-left">Tanggal</th>
                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 bg-slate-50 text-left">Invoice</th>
                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 bg-slate-50 text-left">Pelanggan</th>
                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 bg-slate-50 text-right">Jumlah</th>
                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 bg-slate-50 text-center">Metode</th>
                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 bg-slate-50 text-center">Tipe</th>
                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 bg-slate-50 text-left">Catatan</th>
                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 bg-slate-50 text-left">Diterima Oleh</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-cream-sand/30">
                @forelse($payments as $p)
                <tr class="transition-colors">
                    <td class="px-5 py-3.5 text-sm text-slate-700 align-middle text-sm" style="color:var(--text-soft)">
                        {{ $p->paid_at?->format('d M Y H:i') ?? '-' }}
                    </td>
                    <td class="px-5 py-3.5 text-sm text-slate-700 align-middle font-mono text-xs font-semibold" style="color:var(--primary)">
                        {{ $p->rental?->invoice_number ?? '-' }}
                    </td>
                    <td class="px-5 py-3.5 text-sm text-slate-700 align-middle">
                        <p class="font-medium text-sm" style="color:var(--text-dark)">{{ $p->rental?->customer?->name ?? '-' }}</p>
                    </td>
                    <td class="px-5 py-3.5 text-sm text-slate-700 align-middle text-right font-semibold text-sm" style="color:#15803D">
                        Rp {{ number_format((float) $p->amount, 0, ',', '.') }}
                    </td>
                    <td class="px-5 py-3.5 text-sm text-slate-700 align-middle text-center">
                        <span class="badge badge-gray">{{ $p->method_label ?? $p->method }}</span>
                    </td>
                    <td class="px-5 py-3.5 text-sm text-slate-700 align-middle text-center">
                        @php
                            $typeLabel = match($p->type) {
                                'rental' => 'Sewa',
                                'deposit' => 'Deposit',
                                'late_fee' => 'Denda',
                                'damage_fee' => 'Kerusakan',
                                'refund' => 'Refund',
                                default => ucfirst($p->type)
                            };
                            $typeClass = match($p->type) {
                                'rental', 'deposit' => 'badge-green',
                                'late_fee', 'damage_fee' => 'badge-gold',
                                'refund' => 'badge-red',
                                default => 'badge-gray'
                            };
                        @endphp
                        <span class="badge {{ $typeClass }}">{{ $typeLabel }}</span>
                    </td>
                    <td class="px-5 py-3.5 text-sm text-slate-700 align-middle text-sm" style="color:var(--text-soft)">
                        {{ $p->notes ?? '-' }}
                    </td>
                    <td class="px-5 py-3.5 text-sm text-slate-700 align-middle text-sm" style="color:var(--text-soft)">
                        {{ $p->receivedBy?->name ?? '-' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-12 text-center">
                        <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2" style="color:var(--border)"></i>
                        <p class="text-sm" style="color:var(--text-soft)">Tidak ada pembayaran ditemukan</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($payments->hasPages())
        <div class="px-6 py-4 border-t" style="border-color:var(--border)">
            {{ $payments->appends(request()->query())->links('components.pagination') }}
        </div>
        @endif
    </div>
</div>
@push('scripts')
<script>
lucide.createIcons();

function togglePaymentPeriod(select) {
    const dateFrom = document.getElementById('payment_date_from');
    const dateTo = document.getElementById('payment_date_to');
    if (!dateFrom || !dateTo) return;

    if (select.value === 'today' || select.value === 'week' || select.value === 'month') {
        dateFrom.disabled = true;
        dateTo.disabled = true;
    } else {
        dateFrom.disabled = false;
        dateTo.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const periodSelect = document.querySelector('select[name="period"]');
    if (periodSelect) {
        togglePaymentPeriod(periodSelect);
    }
});
</script>
@endpush
@endsection

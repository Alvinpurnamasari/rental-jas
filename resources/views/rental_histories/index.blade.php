@extends('Layouts.app')

@section('title', 'Riwayat Penyewaan')
@section('page-title', 'Riwayat Penyewaan')

@section('content')
<div class="w-full space-y-5 px-4 py-5 sm:px-6 lg:px-7">
    {{-- Header --}}
    <div>
        <h1 class="text-xl font-bold text-slate-900">
            Riwayat Penyewaan
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            Daftar penyewaan yang sudah selesai, dikembalikan, dan memenuhi ketentuan pembayaran.
        </p>
    </div>

    {{-- Filter --}}
    <div class="w-full rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" class="space-y-3">

            {{-- Baris pertama: pencarian dan cabang --}}
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        
                <div class="min-w-0">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Cari
                    </label>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari invoice atau nama pelanggan..."
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >
                </div>
        
                @if(auth()->user()->isSuperAdmin())
                    <div class="min-w-0">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Cabang
                        </label>
                        <select
                            name="branch_id"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">Semua Cabang</option>
                            @foreach($branches as $branch)
                                <option
                                    value="{{ $branch->id }}"
                                    {{ request('branch_id') == $branch->id ? 'selected' : '' }}
                                >
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
        
            </div>
        
            {{-- Baris kedua: tanggal dan filter sejajar --}}
            <div class="grid grid-cols-1 items-end gap-3 sm:grid-cols-3">
        
                <div class="min-w-0">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Dari Tanggal
                    </label>
                    <input
                        type="date"
                        name="date_from"
                        value="{{ request('date_from') }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >
                </div>
        
                <div class="min-w-0">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Sampai Tanggal
                    </label>
                    <input
                        type="date"
                        name="date_to"
                        value="{{ request('date_to') }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >
                </div>
        
                <div class="min-w-0">
                    <button
                        type="submit"
                        class="btn-primary flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm"
                    >
                        <i data-lucide="search" class="h-4 w-4"></i>
                        <span>Filter</span>
                    </button>
                </div>
        
            </div>
        </form>
    </div>

    {{-- Tabel riwayat --}}
    <div class="w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        @if($histories->isNotEmpty())

            <div class="w-full overflow-x-auto">
                <table class="w-full min-w-[1050px] border-collapse text-left">
                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Invoice
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Pelanggan
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Cabang
                            </th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Tgl Sewa
                            </th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Tgl Kembali
                            </th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Total
                            </th>
                            <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Status Bayar
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Dibuat Oleh
                            </th>
                            <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach($histories as $history)
                            <tr class="transition-colors hover:bg-slate-50">

                                {{-- Invoice --}}
                                <td class="whitespace-nowrap px-4 py-3">
                                    <a href="{{ route('rental-histories.show', $history) }}"
                                        class="font-mono text-sm font-semibold text-slate-900 hover:text-slate-700 hover:underline">
                                            {{ $history->invoice_number }}
                                    </a>
                                </td>

                                {{-- Pelanggan --}}
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-slate-800">
                                        {{ $history->customer->name ?? '-' }}
                                    </div>
                                    <div class="mt-0.5 text-xs text-slate-500">
                                        {{ $history->customer->phone ?? '-' }}
                                    </div>
                                </td>

                                {{-- Cabang --}}
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ $history->branch->name ?? '-' }}
                                </td>

                                {{-- Tanggal sewa --}}
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                    {{ $history->rental_date?->format('d/m/Y') ?? '-' }}
                                </td>

                                {{-- Tanggal kembali --}}
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                    {{ $history->actual_return_date?->format('d/m/Y') ?? '-' }}
                                </td>

                                {{-- Total --}}
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-slate-800">
                                    Rp {{ number_format($history->total_amount, 0, ',', '.') }}
                                </td>

                                {{-- Status pembayaran --}}
                                <td class="whitespace-nowrap px-4 py-3">
                                    @php
                                        $paymentBadge = match ($history->payment_status) {
                                            'paid' => 'bg-emerald-100 text-emerald-700',
                                            'partial' => 'bg-amber-100 text-amber-700',
                                            default => 'bg-red-100 text-red-700',
                                        };
                                    @endphp

                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $paymentBadge }}">
                                        {{ $history->payment_status_label }}
                                    </span>
                                </td>

                                {{-- Pembuat --}}
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ $history->createdBy->name ?? '-' }}
                                </td>

                                {{-- Aksi --}}
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <a href="{{ route('rental-histories.show', $history) }}"
                                       class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">
                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                        Detail
                                    </a>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="flex flex-col gap-3 border-t border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">
                    Menampilkan riwayat penyewaan
                </p>
                <div>
                    {{ $histories->links() }}
                </div>
            </div>

        @else

            {{-- Kondisi data kosong --}}
            <div class="flex min-h-64 flex-col items-center justify-center px-4 py-12 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100">
                    <i data-lucide="archive" class="h-7 w-7 text-slate-400"></i>
                </div>
                <h3 class="mt-4 text-base font-semibold text-slate-800">
                    Belum ada riwayat penyewaan
                </h3>
                <p class="mt-1 max-w-md text-sm text-slate-500">
                    Penyewaan yang memenuhi ketentuan riwayat akan muncul di sini.
                </p>
            </div>

        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
});
</script>
@endpush
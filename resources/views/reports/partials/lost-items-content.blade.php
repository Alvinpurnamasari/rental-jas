
<div id="lost-items-content">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-medium text-slate-500">
                Total Barang Hilang
            </div>
            <div class="mt-2 text-2xl font-bold text-slate-800">
                {{ number_format($totalItemsLost ?? 0) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                Total kuantitas barang
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-medium text-slate-500">
                Total Denda
            </div>
            <div class="mt-2 text-2xl font-bold text-rose-600">
                Rp {{ number_format($totalFine ?? 0, 0, ',', '.') }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                Denda barang hilang
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-medium text-slate-500">
                Jumlah Catatan
            </div>
            <div class="mt-2 text-2xl font-bold text-blue-600">
                {{ number_format($totalRecords ?? 0) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                Catatan barang hilang
            </div>
        </div>
    </div>

<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm mb-4">
    <form method="GET"
          action="{{ route('reports.lost-items') }}"
          class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end"
          id="lost-items-filter">

        <div>
            <label for="date_from" class="block text-sm font-medium text-slate-600 mb-1">
                Dari Tanggal
            </label>
            <input type="date"
                   name="date_from"
                   id="date_from"
                   value="{{ $dateFrom ?? '' }}"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
        </div>

        <div>
            <label for="date_to" class="block text-sm font-medium text-slate-600 mb-1">
                Sampai Tanggal
            </label>
            <input type="date"
                   name="date_to"
                   id="date_to"
                   value="{{ $dateTo ?? '' }}"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
        </div>

        <div>
            <label for="search" class="block text-sm font-medium text-slate-600 mb-1">
                Cari Data
            </label>
            <input type="text"
                   name="search"
                   id="search"
                   value="{{ $search ?? '' }}"
                   placeholder="Nomor invoice atau nama pelanggan"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
        </div>

        <div class="flex gap-2">
            <button type="submit"
                    class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                Cari
            </button>

            <a href="{{ route('reports.lost-items') }}"
               class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                Reset
            </a>
        </div>
    </form>
</div>


    <div class="card shadow-sm border-0">
        <div class="w-full min-w-0 p-4">
            <div class="w-full overflow-x-auto">
                <table class="w-full min-w-[1100px] table-auto text-left">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Tanggal Pengembalian</th>
                            <th>Invoice</th>
                            <th>Pelanggan</th>
                            <th>Cabang</th>
                            <th>Barang</th>
                            <th>Jumlah Hilang</th>
                            <th>Denda</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($lostItems as $item)
                            <tr>
                                <td>
                                    {{ $lostItems->firstItem() + $loop->index }}
                                </td>
                                <td>
                                    {{ $item->returned_at
                                        ? \Carbon\Carbon::parse($item->returned_at)->format('d/m/Y')
                                        : '-' }}
                                </td>
                                <td>
                                    {{ $item->rental->invoice_number ?? '-' }}
                                </td>
                                <td>
                                    {{ $item->rental?->customer?->name ?? 'Pelanggan tidak ditemukan' }}
                                </td>
                                <td>
                                    {{ $item->rental->branch->name ?? '-' }}
                                </td>
                                <td>
                                    {{ $item->product->name ?? '-' }}
                                </td>
                                <td>{{ number_format($item->quantity ?? 0) }}</td>
                                <td>
                                    Rp {{ number_format($item->damage_fee ?? 0, 0, ',', '.') }}
                                </td>
                                <td>{{ $item->return_notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    Belum ada data barang hilang.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($lostItems->hasPages())
                <div class="mt-3">
                    {{ $lostItems->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

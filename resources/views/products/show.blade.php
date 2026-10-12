@extends('Layouts.app')

@section('title', 'Detail Produk — ' . $product->name)

@section('content')
    <div class="space-y-6">

        {{-- ── HEADER ──────────────────────────────────────────── --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('products.index') }}" class="btn-secondary p-2">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div>
                    <h1 class="font-playfair text-2xl font-bold" style="color: var(--text-dark)">{{ $product->name }}</h1>
                    <p class="text-sm mt-0.5" style="color: var(--text-soft)">{{ $product->code }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @if (!auth()->user()->isSales())
                <a href="{{ route('products.edit', $product) }}" class="btn-primary">
                    <i data-lucide="pencil" class="w-4 h-4"></i> Edit Produk
                </a>
                <form method="POST" action="{{ route('products.destroy', $product) }}" id="deleteProductShow_{{ $product->id }}" class="hidden">
                    @csrf @method('DELETE')
                </form>
                <button type="button" onclick="confirmDelete('deleteProductShow_{{ $product->id }}', 'produk {{ $product->name }}')" class="btn-secondary text-red-500 hover:bg-red-50">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Hapus</span>
                </button>
                @endif
            </div>
        </div>

        {{-- ── MAIN CONTENT ──────────────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ── KOLOM KIRI ───────────────────────────────────── --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Hero Card --}}
                <div class="card overflow-hidden">
                    <div class="grid grid-cols-1 sm:grid-cols-2">
                        {{-- Foto --}}
                        <div class="relative bg-gray-50 flex items-center justify-center min-h-56"
                            style="background: var(--secondary)">
                            @if ($product->photo)
                                <img src="{{ $product->photo_url }}" alt="{{ $product->name }}"
                                    class="w-full h-full object-cover">
                            @else
                                <div class="flex flex-col items-center gap-3 p-8 text-center">
                                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center"
                                        style="background: rgba(var(--primary-rgb), 0.1)">
                                        <i data-lucide="shirt" class="w-8 h-8" style="color:var(--primary)"></i>
                                    </div>
                                    <p class="text-xs" style="color:var(--text-soft)">Belum ada foto</p>
                                </div>
                            @endif

                            {{-- Status overlay badge --}}
                            <div class="absolute top-3 left-3">
                                @if ($product->status === 'available')
                                    <span class="badge badge-green">Tersedia</span>
                                @elseif($product->status === 'rented')
                                    <span class="badge badge-blue">Disewa</span>
                                @elseif($product->status === 'maintenance')
                                    <span class="badge badge-red">Maintenance</span>
                                @else
                                    <span class="badge badge-gray">{{ $product->status }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Quick info --}}
                        <div class="p-6 flex flex-col justify-between">
                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide mb-0.5"
                                        style="color:var(--text-soft)">Harga Sewa</p>
                                    <p class="text-3xl font-bold font-playfair" style="color:var(--text-dark)">
                                        Rp {{ number_format($product->rental_price, 0, ',', '.') }}
                                    </p>
                                    <p class="text-xs" style="color:var(--text-soft)">per hari</p>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                     <div class="rounded-xl p-3" style="background:var(--secondary)">
                                            <p class="text-xs" style="color:var(--text-soft)">Ukuran</p>
                                            <div class="flex flex-wrap gap-0.5 mt-0.5">
                                                @forelse($product->sizes as $size)
                                                    <span class="badge badge-gray text-xs">{{ $size->size }}</span>
                                                @empty
                                                    <span class="text-sm" style="color:var(--text-dark)">—</span>
                                                @endforelse
                                            </div>
                                        </div>
                                    <div class="rounded-xl p-3" style="background:var(--secondary)">
                                        <p class="text-xs" style="color:var(--text-soft)">Warna</p>
                                        <p class="font-semibold text-sm mt-0.5" style="color:var(--text-dark)">
                                            {{ $product->color ?? '—' }}</p>
                                    </div>
                                     <div class="rounded-xl p-3" style="background:var(--secondary)">
                                        <p class="text-xs" style="color:var(--text-soft)">Stok</p>
                                        <p class="font-semibold text-sm mt-0.5" style="color:var(--text-dark)">
                                            {{ $product->available_stock }}/{{ $product->total_stock }}
                                        </p>
                                    </div>
                                    <div class="rounded-xl p-3" style="background:var(--secondary)">
                                        <p class="text-xs" style="color:var(--text-soft)">Kondisi</p>
                                        <div class="mt-0.5">
                                            @if ($product->condition === 'excellent')
                                                <span class="badge badge-gold text-xs">Excellent</span>
                                            @elseif($product->condition === 'good')
                                                <span class="badge badge-green text-xs">Good</span>
                                            @elseif($product->condition === 'fair')
                                                <span class="badge badge-yellow text-xs">Fair</span>
                                            @else
                                                <span class="badge badge-red text-xs">Poor</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Stok progress --}}
                            <div class="mt-4">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="text-xs" style="color:var(--text-soft)">Ketersediaan stok</p>
                                    <p class="text-xs font-medium" style="color:var(--text-dark)">
                                        @php $pct = $product->total_stock > 0 ? round(($product->available_stock / $product->total_stock) * 100) : 0; @endphp
                                        {{ $pct }}%
                                    </p>
                                </div>
                                <div class="w-full h-1.5 rounded-full overflow-hidden" style="background:var(--border)">
                                    @php $barColor = $pct > 50 ? '#22c55e' : ($pct > 20 ? '#f59e0b' : '#ef4444'); @endphp
                                    <div class="h-full rounded-full transition-all"
                                         style="width:{{ $pct }}%; background:{{ $barColor }}"></div>
                                </div>
                            </div>

                            {{-- Size Stock Table --}}
                            <div class="mt-4">
                                <p class="text-xs font-medium mb-2" style="color:var(--text-dark)">Detail Ukuran & Stok</p>
                                @if($product->sizes->isNotEmpty())
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr style="border-color:var(--border)">
                                            <th class="text-left py-1.5" style="color:var(--text-soft)">Ukuran</th>
                                            <th class="text-center py-1.5" style="color:var(--text-soft)">Stok Total</th>
                                            <th class="text-center py-1.5" style="color:var(--text-soft)">Stok Tersedia</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($product->sizes as $size)
                                        <tr class="border-t" style="border-color:var(--border)">
                                            <td class="py-1.5" style="color:var(--text-dark)">{{ $size->size }}</td>
                                            <td class="text-center py-1.5" style="color:var(--text-dark)">{{ $size->stock_total }}</td>
                                            <td class="text-center py-1.5" style="color:var(--text-dark)">{{ $size->stock_available }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @else
                                <p class="text-xs" style="color:var(--text-soft)">Belum ada data ukuran.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Deskripsi --}}
                @if ($product->description)
                    <div class="card p-6">
                        <div class="flex items-center gap-2 pb-3 mb-4 border-b" style="border-color:var(--border)">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center"
                                style="background:var(--secondary)">
                                <i data-lucide="align-left" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                            </div>
                            <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Deskripsi</h2>
                        </div>
                        <p class="text-sm leading-relaxed" style="color:var(--text-soft)">{{ $product->description }}</p>
                    </div>
                @endif

                {{-- Riwayat Rental --}}
                <div class="card overflow-hidden">
                    <div class="p-6 pb-4 flex items-center justify-between border-b" style="border-color:var(--border)">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center"
                                style="background:var(--secondary)">
                                <i data-lucide="history" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                            </div>
                            <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Riwayat Rental</h2>
                        </div>
                        @php $rentalItemsList = $product->rentalItems ?? collect(); @endphp
                        @if ($rentalItemsList->isNotEmpty())
                            <span class="badge badge-gray">{{ $rentalItemsList->count() }} transaksi</span>
                        @endif
                    </div>

                    @if ($rentalItemsList->isNotEmpty())
                        {{-- Tampilan desktop: tabel tetap seperti semula --}}
                        <div class="hidden md:block overflow-x-auto">
                            <table class="elegant-table w-full">
                                <thead>
                                    <tr>
                                        <th class="text-left">Pelanggan</th>
                                        <th class="text-center">Tanggal Sewa</th>
                                        <th class="text-center">Tanggal Kembali</th>
                                        <th class="text-right">Total</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rentalItemsList as $ri)
                                        @php $r = $ri->rental; @endphp
                                        @if($r)
                                        <tr>
                                            <td>
                                                <p class="text-sm font-medium" style="color:var(--text-dark)">
                                                    {{ optional($r->customer)->name ?? '-' }}</p>
                                                <p class="text-xs" style="color:var(--text-soft)">
                                                    {{ optional($r->customer)->phone ?? '' }}</p>
                                            </td>
                                            <td class="text-center text-sm" style="color:var(--text-soft)">
                                                {{ optional($r->rental_date)->format('d M Y') ?? '-' }}
                                            </td>
                                            <td class="text-center text-sm" style="color:var(--text-soft)">
                                                {{ optional($r->return_due_date)->format('d M Y') ?? '-' }}
                                            </td>
                                            <td class="text-right text-sm font-semibold" style="color:var(--text-dark)">
                                                Rp {{ number_format($r->total_amount ?? 0, 0, ',', '.') }}
                                            </td>
                                            <td class="text-center">
                                                @if ($r->rental_status === 'active')
                                                    <span class="badge badge-blue">Aktif</span>
                                                @elseif($r->rental_status === 'returned')
                                                    <span class="badge badge-green">Dikembalikan</span>
                                                @elseif($r->rental_status === 'overdue')
                                                    <span class="badge badge-red">Terlambat</span>
                                                @else
                                                    <span class="badge badge-gray">{{ $r->rental_status }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Tampilan mobile: kartu agar kolom tidak bertumpuk atau terpotong --}}
                        <div class="md:hidden divide-y" style="border-color:var(--border)">
                            @foreach ($rentalItemsList as $ri)
                                @php $r = $ri->rental; @endphp
                                @if($r)
                                    <div class="p-4 space-y-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold break-words" style="color:var(--text-dark)">
                                                {{ optional($r->customer)->name ?? '-' }}
                                            </p>
                                            @if(optional($r->customer)->phone)
                                                <p class="text-xs mt-0.5 break-words" style="color:var(--text-soft)">
                                                    {{ $r->customer->phone }}
                                                </p>
                                            @endif
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="min-w-0 rounded-lg p-3" style="background:var(--secondary)">
                                                <p class="text-xs mb-1" style="color:var(--text-soft)">Tanggal Sewa</p>
                                                <p class="text-sm font-medium break-words" style="color:var(--text-dark)">
                                                    {{ optional($r->rental_date)->format('d M Y') ?? '-' }}
                                                </p>
                                            </div>
                                            <div class="min-w-0 rounded-lg p-3" style="background:var(--secondary)">
                                                <p class="text-xs mb-1" style="color:var(--text-soft)">Tanggal Kembali</p>
                                                <p class="text-sm font-medium break-words" style="color:var(--text-dark)">
                                                    {{ optional($r->return_due_date)->format('d M Y') ?? '-' }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <p class="text-sm font-semibold" style="color:var(--text-dark)">
                                                Rp {{ number_format($r->total_amount ?? 0, 0, ',', '.') }}
                                            </p>
                                            <div>
                                                @if ($r->rental_status === 'active')
                                                    <span class="badge badge-blue">Aktif</span>
                                                @elseif($r->rental_status === 'returned')
                                                    <span class="badge badge-green">Dikembalikan</span>
                                                @elseif($r->rental_status === 'overdue')
                                                    <span class="badge badge-red">Terlambat</span>
                                                @else
                                                    <span class="badge badge-gray">{{ $r->rental_status }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-3"
                                style="background:var(--secondary)">
                                <i data-lucide="calendar-x" class="w-5 h-5" style="color:var(--text-soft)"></i>
                            </div>
                            <p class="text-sm font-medium" style="color:var(--text-dark)">Belum ada riwayat rental</p>
                            <p class="text-xs mt-1" style="color:var(--text-soft)">Produk ini belum pernah disewa</p>
                        </div>
                    @endif
                </div>

            </div>

            {{-- ── KOLOM KANAN ──────────────────────────────────── --}}
            <div class="space-y-6">

                {{-- Detail Info --}}
                <div class="card p-6 space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b" style="border-color:var(--border)">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center"
                            style="background:var(--secondary)">
                            <i data-lucide="list" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                        </div>
                        <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Detail Produk</h2>
                    </div>

                    @php
                        $allSizes = $product->sizes->pluck('size')->implode(', ') ?: '—';
                    @endphp

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="tag" class="w-3.5 h-3.5 flex-shrink-0"
                                    style="color:var(--text-soft)"></i>
                                <span class="text-sm" style="color:var(--text-soft)">Kode Produk</span>
                            </div>
                            <span class="text-sm font-medium"
                                style="color:var(--text-dark)">{{ $product->code }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="folder" class="w-3.5 h-3.5 flex-shrink-0"
                                    style="color:var(--text-soft)"></i>
                                <span class="text-sm" style="color:var(--text-soft)">Kategori</span>
                            </div>
                            <span class="text-sm font-medium"
                                style="color:var(--text-dark)">{{ $product->category?->name ?? '—' }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="ruler" class="w-3.5 h-3.5 flex-shrink-0"
                                    style="color:var(--text-soft)"></i>
                                <span class="text-sm" style="color:var(--text-soft)">Ukuran</span>
                            </div>
                            <span class="text-sm font-medium"
                                style="color:var(--text-dark)">{!! $allSizes !!}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="layers" class="w-3.5 h-3.5 flex-shrink-0"
                                    style="color:var(--text-soft)"></i>
                                <span class="text-sm" style="color:var(--text-soft)">Stok Total</span>
                            </div>
                            <span class="text-sm font-medium"
                                style="color:var(--text-dark)">{{ $product->total_stock }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5 flex-shrink-0"
                                    style="color:var(--text-soft)"></i>
                                <span class="text-sm" style="color:var(--text-soft)">Stok Tersedia</span>
                            </div>
                            <span class="text-sm font-medium"
                                style="color:var(--text-dark)">{{ $product->available_stock }}</span>
                        </div>
                    </div>
                </div>

                {{-- Catatan --}}
                @if ($product->notes)
                    <div class="card p-6">
                        <div class="flex items-center gap-2 pb-3 mb-3 border-b" style="border-color:var(--border)">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center"
                                style="background:var(--secondary)">
                                <i data-lucide="file-text" class="w-3.5 h-3.5" style="color:var(--primary)"></i>
                            </div>
                            <h2 class="font-semibold text-sm" style="color:var(--text-dark)">Catatan Internal</h2>
                        </div>
                        <p class="text-sm leading-relaxed" style="color:var(--text-soft)">{{ $product->notes }}</p>
                    </div>
                @endif

                {{-- Timestamps --}}
                <div class="card p-5 space-y-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="clock" class="w-3.5 h-3.5" style="color:var(--text-soft)"></i>
                        <div>
                            <p class="text-xs" style="color:var(--text-soft)">Diperbarui</p>
                            <p class="text-sm font-medium" style="color:var(--text-dark)">
                                {{ $product->updated_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="calendar-plus" class="w-3.5 h-3.5" style="color:var(--text-soft)"></i>
                        <div>
                            <p class="text-xs" style="color:var(--text-soft)">Ditambahkan</p>
                            <p class="text-sm font-medium" style="color:var(--text-dark)">
                                {{ $product->created_at->format('d M Y, H:i') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="card p-5 space-y-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wide" style="color:var(--text-soft)">Aksi Cepat
                    </h3>
                    @if (!auth()->user()->isSales())
                    <a href="{{ route('products.edit', $product) }}" class="btn-primary w-full justify-center">
                        <i data-lucide="pencil" class="w-4 h-4"></i> Edit Produk
                    </a>
                    @endif
                    {{-- Tombol ubah status langsung --}}
                    @if ($product->status !== 'maintenance')
                        @if (!auth()->user()->isSales())
                        <form method="POST" action="{{ route('products.update', $product) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="status" value="maintenance">
                            <input type="hidden" name="name" value="{{ $product->name }}">
                            <input type="hidden" name="code" value="{{ $product->code }}">
                            <input type="hidden" name="category_id" value="{{ $product->category_id }}">
                            <input type="hidden" name="condition" value="{{ $product->condition }}">
                            <input type="hidden" name="rental_price" value="{{ $product->rental_price }}">
                            <input type="hidden" name="stock_total" value="{{ $product->stock_total }}">
                            <input type="hidden" name="stock_available" value="{{ $product->stock_available }}">
                            <button type="submit"
                                class="btn-secondary w-full justify-center text-orange-500 hover:bg-orange-50">
                                <i data-lucide="wrench" class="w-4 h-4"></i> Tandai Maintenance
                            </button>
                        </form>
                        @endif
                    @endif
                    @if ($product->status !== 'available')
                        @if (!auth()->user()->isSales())
                        <form method="POST" action="{{ route('products.update', $product) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="status" value="available">
                            <input type="hidden" name="name" value="{{ $product->name }}">
                            <input type="hidden" name="code" value="{{ $product->code }}">
                            <input type="hidden" name="category_id" value="{{ $product->category_id }}">
                            <input type="hidden" name="condition" value="{{ $product->condition }}">
                            <input type="hidden" name="rental_price" value="{{ $product->rental_price }}">
                            <input type="hidden" name="stock_total" value="{{ $product->stock_total }}">
                            <input type="hidden" name="stock_available" value="{{ $product->stock_available }}">
                            <button type="submit"
                                class="btn-secondary w-full justify-center text-green-600 hover:bg-green-50">
                                <i data-lucide="check-circle" class="w-4 h-4"></i> Tandai Tersedia
                            </button>
                        </form>
                        @endif
                    @endif
                    @if (!auth()->user()->isSales())
                    <form method="POST" action="{{ route('products.destroy', $product) }}" id="deleteProductQuick_{{ $product->id }}" class="hidden">
                        @csrf @method('DELETE')
                    </form>
                    <button type="button" onclick="confirmDelete('deleteProductQuick_{{ $product->id }}', 'produk {{ $product->name }}')" class="btn-secondary w-full justify-center text-red-500 hover:bg-red-50">
                        <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus Produk
                    </button>
                    @endif

                    @if (auth()->user()->isSales())
                    @if ($product->sizes->isNotEmpty())
                    <button type="button" onclick="openStockModal()" class="btn-primary w-full justify-center">
                        <i data-lucide="package" class="w-4 h-4"></i> Update Stok
                    </button>
                    @else
                    <p class="text-xs text-center" style="color: var(--text-soft)">Update stok tidak tersedia karena data ukuran produk belum ada.</p>
                    @endif
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- Modal Update Stok (untuk Sales) --}}
    @if (auth()->user()->isSales())
    @php
        $stockSizeOptions = $product->sizes;
        $selectedStockSize = $stockSizeOptions->firstWhere('id', old('size_id')) ?? $stockSizeOptions->first();
        $oldStockMatchesSelectedSize = $selectedStockSize && (string) old('size_id') === (string) $selectedStockSize->id;
        $stockInputValue = $oldStockMatchesSelectedSize
            ? old('stock_available', $selectedStockSize->stock_available)
            : ($selectedStockSize->stock_available ?? 0);
    @endphp
    <div id="stock-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold" style="color: var(--text-dark)">Update Stok Produk</h3>
                <button type="button" onclick="closeStockModal()" class="rounded-lg p-1 hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5" style="color: var(--text-soft)"></i>
                </button>
            </div>

            <div class="mb-4 p-4 rounded-xl bg-slate-50">
                <p class="text-sm font-semibold" style="color: var(--text-dark)">{{ $product->name }}</p>
                @if ($selectedStockSize)
                <p id="stock-size-context" class="text-sm font-semibold mt-2" style="color: var(--text-dark)" aria-live="polite">
                    Ukuran {{ $selectedStockSize->size }}
                </p>
                <p id="stock-size-counts" class="text-xs mt-1" style="color: var(--text-soft)" aria-live="polite">
                    Stok tersedia: {{ $selectedStockSize->stock_available }} dari {{ $selectedStockSize->stock_total }}
                </p>
                @else
                <p class="text-xs mt-1" style="color: var(--text-soft)">Data ukuran produk belum tersedia.</p>
                @endif
            </div>

            <form method="POST" action="{{ route('products.update-stock', $product) }}" id="stock-update-form">
                @csrf
                @method('PATCH')

                <div class="space-y-4">
                    @if ($selectedStockSize)
                    <div>
                        <label for="stock_size_id" class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">
                            Ukuran <span class="text-red-500">*</span>
                        </label>
                        <select name="size_id" id="stock_size_id" required
                                class="form-input @error('size_id') border-red-400 @enderror">
                            @foreach ($stockSizeOptions as $sizeOption)
                            <option value="{{ $sizeOption->id }}"
                                    data-size="{{ $sizeOption->size }}"
                                    data-current="{{ $sizeOption->stock_available }}"
                                    data-total="{{ $sizeOption->stock_total }}"
                                    @selected((string) $selectedStockSize->id === (string) $sizeOption->id)>
                                Ukuran {{ $sizeOption->size }} (tersedia {{ $sizeOption->stock_available }} / {{ $sizeOption->stock_total }})
                            </option>
                            @endforeach
                        </select>
                        @error('size_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="stock_available_input" class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">
                            Stok Tersedia Baru <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="stock_available" id="stock_available_input"
                               value="{{ $stockInputValue }}"
                               min="0" max="{{ $selectedStockSize->stock_total }}"
                               class="form-input @error('stock_available') border-red-400 @enderror"
                               required>
                        <p id="stock-size-limit" class="text-xs mt-1" style="color: var(--text-soft)">Maksimal: {{ $selectedStockSize->stock_total }}</p>
                        @error('stock_available')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">Catatan Perubahan (opsional)</label>
                        <textarea name="stock_note" rows="2" class="form-input resize-none text-sm" placeholder="Contoh: Stok bertambah karena retur...">{{ old('stock_note') }}</textarea>
                        @error('stock_note')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    @else
                    <p class="text-sm" style="color: var(--text-soft)">Stok tidak dapat diperbarui sebelum ukuran produk tersedia.</p>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-3 mt-6">
                    <button type="button" onclick="closeStockModal()" class="btn-secondary">Batal</button>
                    <button type="submit" class="btn-primary" @disabled(!$selectedStockSize)>Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openStockModal() {
            const modal = document.getElementById('stock-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeStockModal() {
            const modal = document.getElementById('stock-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        const stockSizeSelect = document.getElementById('stock_size_id');
        const stockInput = document.getElementById('stock_available_input');
        const updateStockSizeContext = (resetInput = false) => {
            const option = stockSizeSelect?.selectedOptions[0];
            if (!option || !stockInput) return;

            const size = option.dataset.size;
            const current = option.dataset.current;
            const total = option.dataset.total;
            document.getElementById('stock-size-context').textContent = `Ukuran ${size}`;
            document.getElementById('stock-size-counts').textContent = `Stok tersedia: ${current} dari ${total}`;
            document.getElementById('stock-size-limit').textContent = `Maksimal: ${total}`;
            stockInput.max = total;
            if (resetInput) stockInput.value = current;
        };

        stockSizeSelect?.addEventListener('change', () => updateStockSizeContext(true));

        document.getElementById('stock-update-form')?.addEventListener('submit', function(e) {
            if (!stockInput) return;

            const max = parseInt(stockInput.getAttribute('max'), 10);
            if (parseInt(stockInput.value, 10) > max) {
                e.preventDefault();
                const size = stockSizeSelect?.selectedOptions[0]?.dataset.size;
                alert('Stok tersedia ukuran ' + size + ' tidak boleh melebihi stok total (' + max + ')');
                stockInput.focus();
            }
        });

        @if ($errors->has('size_id') || $errors->has('stock_available') || $errors->has('stock_note'))
        openStockModal();
        @endif
    </script>
    @endif

    @push('scripts')
        <script>
            lucide.createIcons();
        </script>
    @endpush
@endsection

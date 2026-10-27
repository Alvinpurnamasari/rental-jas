@extends('Layouts.app')

@section('title', 'Buat Penyewaan')
@section('page-title', 'Buat Penyewaan')

@section('content')
<div class="rental-create-page w-full max-w-4xl mx-auto space-y-5 min-w-0">
    <style>
        .rental-create-page, .rental-create-page * { box-sizing: border-box; }
        .rental-create-page .card { min-width: 0; }
        .rental-create-page .form-input { width: 100%; max-width: 100%; min-width: 0; }
        .rental-create-page input, .rental-create-page select, .rental-create-page textarea { min-width: 0; }
        .rental-create-page .input-group-phone { width: 100%; min-width: 0; }
        .rental-create-page #products-container > * { min-width: 0; }
        .rental-create-page #products-container select[name$="[product_id]"] { flex: 1 1 100%; width: 100%; }
        .rental-create-page #products-container select[name$="[product_size]"] { width: 100%; min-width: 0; }
        .rental-create-page #products-container input[name$="[quantity]"] { width: 5rem; flex: 0 0 5rem; }
        @media (max-width: 639px) {
            .rental-create-page { width: 100%; max-width: 100%; }
            .rental-create-page .card { padding: 1rem; border-radius: 1.1rem; }
            .rental-create-page .grid { min-width: 0; }
            .rental-create-page .grid > div { min-width: 0; }
            .rental-create-page .flex.items-center.justify-between { gap: .65rem; }
            /* Tombol aksi berada di dalam card terakhir, bukan anak langsung form. */
            .rental-create-page form .flex.items-center.justify-between {
                flex-direction: column-reverse;
                align-items: stretch;
                width: 100%;
                min-width: 0;
            }
            .rental-create-page form .flex.items-center.justify-between > a,
            .rental-create-page form .flex.items-center.justify-between > button {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                justify-content: center;
                flex: 0 1 auto;
                white-space: normal;
            }
            .rental-create-page form .flex.items-center.justify-between > button {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            .rental-create-page .input-group-phone__input { width: 100%; min-width: 0; }
            .rental-create-page #products-container > div { gap: .5rem; }
            .rental-create-page #products-container .flex.items-center.gap-1 { flex: 1 1 7rem; min-width: 0; }
            .rental-create-page #products-container .flex.items-center.gap-1 select { flex: 1 1 auto; }
            .rental-create-page #products-container .flex.items-center.gap-1 span { white-space: nowrap; }
            .rental-create-page .btn-secondary, .rental-create-page .btn-primary { max-width: 100%; }
        }
    </style>
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('rentals.index') }}" class="btn-secondary">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Kembali ke Penyewaan
        </a>
    </div>
        <form method="POST" action="{{ route('rentals.store') }}" enctype="multipart/form-data" x-data="rentalForm()" @submit.prevent="submitForm()" novalidate>
        @csrf

        <!-- Satu container utama untuk seluruh form penyewaan -->
        <div class="card p-6 space-y-6">
        <!-- Customer & Rental Info -->
        <div class="space-y-4">
            <h3 class="font-playfair font-semibold text-base mb-5" style="color: var(--text-dark)">
                <i data-lucide="user" class="w-4 h-4 inline mr-2" style="color: var(--primary)"></i>
                Informasi Pelanggan & Penyewaan
            </h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">Pelanggan <span class="text-red-400">*</span></label>
                    <div class="space-y-2" @click.outside="customerDropdownOpen = false">
                        <select
                            x-model="customerListSelection"
                            @change="chooseCustomerFromList()"
                            :class="{ 'is-error': customerError }"
                            class="form-input"
                            aria-label="Pilih pelanggan dari daftar"
                        >
                            <option value="">Pilih pelanggan dari daftar...</option>
                            <template x-for="customer in customers" :key="customer.id">
                                <option :value="String(customer.id)" x-text="customer.name + ' - ' + customer.phone"></option>
                            </template>
                        </select>
                        <p class="text-xs text-slate-500">Atau cari pelanggan berdasarkan nama atau nomor HP:</p>
                        <div class="relative">
                        <input type="text"
                            x-model="customerSearch"
                            @focus="customerDropdownOpen = true"
                            @input="selectedCustomer = null; customerListSelection = ''; customerError = false"
                            :class="{ 'is-error': customerError }"
                            class="form-input"
                            placeholder="Ketik nama atau nomor HP pelanggan..."
                            autocomplete="off"
                        >
                        <input type="hidden" name="customer_id" :value="selectedCustomer?.id ?? ''">

                        <div x-show="customerDropdownOpen && customerSearch"
                             x-transition
                             class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-auto"
                             style="display: none;">
                            <template x-if="filteredCustomers.length > 0">
                                <template x-for="customer in filteredCustomers" :key="customer.id">
                                    <div @click="selectCustomer(customer)"
                                         class="px-4 py-2.5 cursor-pointer hover:bg-blue-50 text-sm flex items-center justify-between"
                                         :class="selectedCustomer?.id === customer.id ? 'bg-blue-50 text-blue-700 font-medium' : 'text-slate-700'">
                                        <span x-text="customer.name"></span>
                                        <span class="text-xs text-slate-400" x-text="customer.phone"></span>
                                    </div>
                                </template>
                            </template>

                            <div x-show="customerSearch && filteredCustomers.length === 0"
                                 @click="openAddModal()"
                                 class="px-4 py-3 cursor-pointer hover:bg-amber-50 text-sm text-amber-700 border-t border-slate-100 flex items-center gap-2">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>Tambah '<span x-text="customerSearch"></span>' sebagai pelanggan baru</span>
                            </div>
                        </div>
                        </div>
                    </div>
                    @error('customer_id')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                    <p x-show="customerError" x-transition class="text-xs text-red-400 mt-1">Pelanggan wajib dipilih</p>
                </div>

                <!-- Modal Tambah Pelanggan Baru -->
                <div x-show="showAddModal" x-transition class="fixed inset-0 z-[100] flex items-center justify-center" style="display: none;">
                    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showAddModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-auto" @click.stop>
                        <div class="flex items-center justify-between p-6 border-b border-slate-100">
                            <h3 class="font-semibold text-base text-slate-800">Tambah Pelanggan Baru</h3>
                            <button type="button" @click="showAddModal = false" class="p-2 rounded-xl hover:bg-slate-100 text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-1.5 text-slate-700">Nama Lengkap <span class="text-red-400">*</span></label>
                                <input type="text" x-model="newCustomer.name" class="form-input" @input="newCustomerNameError = false">
                                <p x-show="newCustomerNameError" x-transition class="text-xs text-red-400 mt-1">Nama lengkap wajib diisi</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5 text-slate-700">Nomor Handphone <span class="text-red-400">*</span></label>
                                <div class="input-group-phone">
                                    <span class="input-group-phone__prefix">+62</span>
                                    <input type="text"
                                        x-model="newCustomer.phone"
                                        class="input-group-phone__input"
                                        placeholder="8123456789"
                                        inputmode="numeric"
                                        @input="filterPhoneInput($event); newCustomerPhoneError = false"
                                        @keydown="preventNonNumeric($event)"
                                        autocomplete="tel"
                                    >
                                </div>
                                <p x-show="newCustomerPhoneError" x-transition class="text-xs text-red-400 mt-1">Nomor handphone wajib diisi</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5 text-slate-700">Alamat</label>
                                <textarea x-model="newCustomer.address" class="form-input" rows="2" placeholder="Alamat (opsional)"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5 text-slate-700">Catatan</label>
                                <textarea x-model="newCustomer.notes" class="form-input" rows="2" placeholder="Catatan (opsional)"></textarea>
                            </div>
                            <div class="flex items-center justify-end gap-3 pt-2">
                                <button type="button" @click="showAddModal = false" class="btn-secondary px-5 py-2.5" :disabled="addingCustomer">Batal</button>
                                <button type="button" @click="addCustomer()" class="btn-primary px-5 py-2.5" :disabled="addingCustomer">
                                    <span x-show="!addingCustomer">Simpan Pelanggan</span>
                                    <span x-show="addingCustomer" class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        Menyimpan...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">Tanggal Sewa <span class="text-red-400">*</span></label>
                    <input type="date" name="rental_date" value="{{ old('rental_date', now()->format('Y-m-d')) }}" class="form-input" @input="rentalDateError = false">
                    @error('rental_date')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                    <p x-show="rentalDateError" x-transition class="text-xs text-red-400 mt-1">Tanggal sewa wajib diisi</p>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">Durasi (Hari) <span class="text-red-400">*</span></label>
                    <input type="number" name="duration_days" value="{{ old('duration_days', 1) }}" min="1" class="form-input" @input="durationDaysError = false">
                    @error('duration_days')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                    <p x-show="durationDaysError" x-transition class="text-xs text-red-400 mt-1">Durasi hari wajib diisi</p>
                </div>
        </div>

        <!-- Produk -->
        <div class="border-t border-slate-200 pt-6">
            <h3 class="font-playfair font-semibold text-base mb-5" style="color: var(--text-dark)">
                <i data-lucide="package" class="w-4 h-4 inline mr-2" style="color: var(--primary)"></i>
                Produk
            </h3>
            <div id="products-container" class="space-y-3">
                <template x-for="(item, index) in items" :key="index">
                    <div class="flex gap-3 items-center flex-wrap">
                        <select x-model="item.product_id" :name="`items[${index}][product_id]`" class="form-input flex-1" :class="{ 'is-error': itemsErrors[index]?.product_id || itemsErrors[index]?.stock_exceeded }" @change="onProductChange(index)">
                            <option value="">Pilih Produk</option>
                            @foreach ($products as $product)
                                @php
                                    $fallbackSizes = $product->size ? [$product->size => ['stock_total' => $product->stock_total, 'stock_available' => $product->stock_available]] : [];
                                    $sizesData = $product->sizesData ?: $fallbackSizes;
                                @endphp
                                <option value="{{ $product->id }}" data-price="{{ $product->rental_price }}" data-deposit-price="{{ $product->deposit_price }}" data-stock="{{ $product->stock_available }}" data-sizes='@json($sizesData)'>{{ $product->name }} (Stok: {{ $product->stock_available }}) - Rp {{ number_format($product->rental_price, 0, ',', '.') }}/hari</option>
                            @endforeach
                        </select>
                        <p x-show="itemsErrors[index]?.product_id" x-transition class="text-xs text-red-400 mt-1">Pilih produk</p>
                        <p x-show="itemsErrors[index]?.stock_exceeded" x-transition class="text-xs text-red-400 mt-1" x-text="itemsErrors[index]?.stock_message || 'Qty melebihi stok tersedia'"></p>
                        <div class="flex items-center gap-1">
                            <select x-model="item.product_size" :name="`items[${index}][product_size]`" class="form-input w-32" :class="{ 'is-error': itemsErrors[index]?.product_size }" @change="onSizeChange(index)">
                                <option value="">Pilih Ukuran</option>
                                <template x-for="sz in sizesForItem(index)" :key="sz">
                                    <option :value="sz" x-text="sz"></option>
                                </template>
                            </select>
                            <span class="text-xs text-slate-500" x-show="item.product_size" x-text="'Stok: ' + getStockForProductAndSize(item.product_id, item.product_size)"></span>
                        </div>
                        <p x-show="itemsErrors[index]?.product_size" x-transition class="text-xs text-red-400 mt-1">Pilih ukuran</p>
                        <input type="number" x-model="item.quantity" :name="`items[${index}][quantity]`" value="1" min="1" class="form-input w-24" :max="item.product_size ? getStockForProductAndSize(item.product_id, item.product_size) : ''" :class="{ 'is-error': itemsErrors[index]?.quantity || itemsErrors[index]?.stock_exceeded }" @input="onQtyInput(index)">
                        <p x-show="itemsErrors[index]?.quantity" x-transition class="text-xs text-red-400 mt-1">Jumlah minimal 1</p>
                        <button type="button" x-show="items.length > 1" @click="removeItem(index)" class="btn-danger px-3 py-2">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </template>
            </div>
            <p x-show="productsError" x-transition class="text-xs text-red-400 mt-2">Lengkapi semua data produk yang ditambahkan</p>
            <button type="button" @click="addItem()" class="btn-secondary mt-3">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Tambah Produk
            </button>
        </div>

        <!-- Diskon & Catatan -->
        <div class="border-t border-slate-200 pt-6">
            <h3 class="font-playfair font-semibold text-base mb-5" style="color: var(--text-dark)">
                <i data-lucide="file-text" class="w-4 h-4 inline mr-2" style="color: var(--primary)"></i>
                Diskon & Catatan
            </h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">Diskon (Rp)</label>
                    <input type="number" name="discount" value="{{ old('discount', 0) }}" min="0" class="form-input">
                    @error('discount')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">Catatan</label>
                    <textarea name="notes" class="form-input" rows="1">{{ old('notes') }}</textarea>
                    @error('notes')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>
        </div>

        <!-- Jaminan & Pembayaran -->
        <div class="border-t border-slate-200 pt-6">
            <h3 class="font-playfair font-semibold text-base mb-5" style="color: var(--text-dark)">
                <i data-lucide="shield" class="w-4 h-4 inline mr-2" style="color: var(--primary)"></i>
                Jaminan & Pembayaran
            </h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">Metode Pembayaran <span class="text-red-400">*</span></label>
                    <select name="payment_method" class="form-input" @change="paymentMethodError = false" x-model="paymentMethod">
                        <option value="">Pilih Metode</option>
                        <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                        @if($qris['available'])
                        <option value="qris" {{ old('payment_method') === 'qris' ? 'selected' : '' }}>QRIS</option>
                        @endif
                        <option value="transfer" {{ old('payment_method') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                    </select>
                    @error('payment_method')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                    <p x-show="paymentMethodError" x-transition class="text-xs text-red-400 mt-1">Metode pembayaran wajib dipilih</p>
                    @if($qris['available'])
                    <div x-show="paymentMethod === 'qris'" x-transition class="mt-3 rounded-2xl border border-slate-200 bg-white p-4 text-center">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Scan QR code di bawah untuk membayar via QRIS</p>
                        <img src="{{ $qris['url'] }}" alt="QRIS Payment" class="mx-auto h-48 w-48 rounded-xl border border-slate-100 object-contain">
                    </div>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">Jenis Jaminan <span class="text-red-400">*</span></label>
                    <select name="guarantee_type" class="form-input" @change="guaranteeTypeError = false" x-model="guaranteeType">
                        <option value="">Pilih Jenis Jaminan</option>
                        <option value="ktp" {{ old('guarantee_type') === 'ktp' ? 'selected' : '' }}>KTP</option>
                        <option value="sim" {{ old('guarantee_type') === 'sim' ? 'selected' : '' }}>SIM</option>
                        <option value="deposit" {{ old('guarantee_type') === 'deposit' ? 'selected' : '' }}>Deposit Uang</option>
                        <option value="custom" {{ old('guarantee_type') === 'custom' ? 'selected' : '' }}>Jaminan Custom</option>
                    </select>
                    @error('guarantee_type')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                    <p x-show="guaranteeTypeError" x-transition class="text-xs text-red-400 mt-1">Jenis jaminan wajib dipilih</p>

                    <div x-show="guaranteeType === 'deposit'" x-transition class="mt-3">
                        <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">Jumlah Deposit <span class="text-red-400">*</span></label>
                        <input type="text" name="guarantee_deposit" x-model="guaranteeDeposit" @input="onDepositInput()" placeholder="Masukkan jumlah deposit" class="form-input">
                        <p class="text-xs text-slate-500 mt-1">Saran minimum: Rp <span x-text="suggestedDeposit"></span></p>
                        <p x-show="guaranteeDepositError" x-transition class="text-xs text-red-400 mt-1">Jumlah deposit wajib diisi</p>
                    </div>

                    <div x-show="guaranteeType === 'custom'" x-transition class="mt-3">
                        <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">Deskripsi Jaminan <span class="text-red-400">*</span></label>
                        <textarea name="guarantee_notes" rows="2" placeholder="Contoh: Motor Vario plat AD 1234 XX" class="form-input">{{ old('guarantee_notes') }}</textarea>
                        @error('guarantee_notes')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                    </div>
                </div>

                <!-- Upload Foto Identitas -->
                <div class="sm:col-span-2" x-show="guaranteeType === 'ktp' || guaranteeType === 'sim'" x-transition>
                    <label class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">
                        Upload Foto <span x-text="guaranteeType === 'ktp' ? 'KTP' : 'SIM'"></span> <span class="text-red-400">*</span>
                    </label>

                    <!-- Drop Zone -->
                    <div
                        x-ref="dropzone"
                        @dragover.prevent="dragOver = true"
                        @dragleave.prevent="dragOver = false"
                        @drop.prevent="handleDrop($event)"
                        @click="$refs.fileInput.click()"
                        :class="{
                            'border-blue-400 bg-blue-50/50': dragOver,
                            'border-green-400 bg-green-50/30': photoState.verified && !dragOver,
                            'border-red-300 bg-red-50/30': photoState.checked && !photoState.verified && !dragOver,
                            'border-dashed border-slate-300 bg-slate-50/50': !photoState.file && !dragOver
                        }"
                        class="relative flex flex-col items-center justify-center w-full h-48 rounded-xl border-2 cursor-pointer transition-all duration-200"
                    >
                        <template x-if="!photoState.file">
                            <div class="flex flex-col items-center gap-2 text-slate-400">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <p class="text-sm font-medium">Seret &amp; lepas foto di sini</p>
                                <p class="text-xs">atau klik untuk memilih file</p>
                                <p class="text-xs text-slate-300">JPG/PNG, maks 2MB</p>
                            </div>
                        </template>
                        <template x-if="photoState.file">
                            <div class="relative w-full h-full p-2">
                                <img :src="photoState.previewUrl" class="w-full h-full object-contain rounded-lg" alt="Preview">
                                <button type="button" @click.stop="removePhoto()" class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center rounded-full bg-white/90 shadow-md text-red-500 hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                <button type="button" @click.stop="$refs.fileInput.click()" class="absolute bottom-3 right-3 px-3 py-1.5 text-xs font-medium rounded-lg bg-white/90 shadow-md text-blue-600 hover:bg-blue-50 transition-colors">
                                    Ganti
                                </button>
                            </div>
                        </template>

                        <input type="file" x-ref="fileInput" name="guarantee_id_photo" accept="image/jpeg,image/png" @change="handleFileSelect($event)" class="hidden">
                    </div>
                    @error('guarantee_id_photo')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                    <p x-show="photoError" x-transition class="text-xs text-red-400 mt-1">Foto KTP/SIM wajib diupload</p>

                    <p class="mt-2 text-xs text-slate-500">Foto KTP/SIM akan dikompres otomatis jika ukurannya besar. Ukuran file setelah kompresi dibatasi maksimal 2 MB.</p>
                    <p x-show="photoState.processing" x-transition class="mt-2 text-xs text-blue-600">Mengoptimalkan ukuran foto...</p>
                    <p x-show="photoState.checked && photoState.verified" x-transition class="mt-2 text-xs text-green-600">✓ Foto siap diupload <span x-show="photoState.sizeLabel" x-text="'(' + photoState.sizeLabel + ')' "></span></p>
                    <p x-show="photoState.checked && !photoState.verified && !photoState.processing" x-transition class="mt-2 text-xs text-red-500">Foto gagal dikompres. Coba pilih foto lain.</p>
                </div>
        </div>

        </div> <!-- Penutup container utama -->

        <!-- Submit -->
        <div class="flex items-center justify-between mt-6 pt-5 border-t border-slate-200">
            <a href="{{ route('rentals.index') }}" class="btn-secondary">
                <i data-lucide="x" class="w-4 h-4"></i>
                Batal
            </a>
            <button type="submit"
                    :disabled="submitting || (photoState.file && !photoState.verified)"
                    :class="{
                        'opacity-60 pointer-events-none': submitting || (photoState.file && !photoState.verified),
                    }"
                    class="btn-primary px-8">
                <span x-show="!submitting" class="flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Simpan Penyewaan
                </span>
                <span x-show="submitting" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Menyimpan...
                </span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
@php
    $oldCustomerId = old('customer_id');
    $oldCustomer = $oldCustomerId ? $customers->firstWhere('id', $oldCustomerId) : null;
@endphp
<script>
    function rentalForm() {
        return {
            submitting: false,
            customerError: false,
            rentalDateError: false,
            durationDaysError: false,
            paymentMethodError: false,
            paymentMethod: '{{ old('payment_method', '') }}',
            guaranteeTypeError: false,
            guaranteeType: '{{ old('guarantee_type', '') }}',
            guaranteeDeposit: '{{ old('guarantee_deposit', '') }}',
            depositManualEdited: false,
            guaranteeDepositError: false,
            photoError: false,
            productsError: false,
            newCustomerNameError: false,
            newCustomerPhoneError: false,
            itemsErrors: [],
            productSizesMap: @json($products->mapWithKeys(fn($p) => [$p->id => ($p->sizesData ?: ($p->size ? [$p->size => ['stock_total' => $p->stock_total, 'stock_available' => $p->stock_available]] : []))])->toArray()),
            items: [
                { product_id: '', product_size: '', quantity: 1 }
            ],
            dragOver: false,
            stockExceeded: false,
            photoState: {
            file: null,
            previewUrl: null,
            checked: false,
            verified: false,
            processing: false,
            sizeLabel: '',
        },

        customerSearch: @json($oldCustomer ? $oldCustomer->name . ' - ' . $oldCustomer->phone : ''),
        customerListSelection: @json($oldCustomer ? (string) $oldCustomer->id : ''),
        selectedCustomer: @json($oldCustomer ? ['id' => $oldCustomer->id, 'name' => $oldCustomer->name, 'phone' => $oldCustomer->phone] : null),
        customerDropdownOpen: false,
        showAddModal: false,
        addingCustomer: false,
        newCustomer: { name: '', phone: '', address: '', notes: '' },
        customers: @json($customers->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'phone' => $c->phone])),

        get filteredCustomers() {
            if (!this.customerSearch) return this.customers;
            const q = this.customerSearch.toLowerCase();
            return this.customers.filter(c =>
                c.name.toLowerCase().includes(q) || c.phone.includes(q)
            );
        },

        get suggestedDeposit() {
            if (!this.items) return 0;
            const productIds = this.items.map(i => i.product_id).filter(Boolean);
            if (!productIds.length) return 0;
            const productOptions = Array.from(document.querySelectorAll('#products-container select[name^="items"][name$="[product_id]"] option'));
            let total = 0;
            productOptions.forEach(opt => {
                if (productIds.includes(opt.value)) {
                    total += parseFloat(opt.dataset.depositPrice || 0);
                }
            });
            return total;
        },

        onDepositInput() {
            this.depositManualEdited = true;
            this.guaranteeDepositError = false;
        },

        getStockForProduct(productId) {
            if (!productId) return 0;
            const option = Array.from(document.querySelectorAll('#products-container select[name^="items"][name$="[product_id]"] option'))
                .find(opt => opt.value === String(productId));
            return option ? parseInt(option.dataset.stock || '0', 10) : 0;
        },

        getStockForProductAndSize(productId, size) {
            const sizes = this.productSizesMap[productId];
            if (!sizes || !sizes[size]) return 0;
            return parseInt(sizes[size].stock_available, 10);
        },

        sizesForItem(index) {
            const productId = this.items[index]?.product_id;
            if (!productId) return [];
            const sizes = this.productSizesMap[productId];
            if (!sizes) return [];
            const order = ['XS','S','M','L','XL','XXL','3XL','4XL','5XL','6XL','7XL'];
            return Object.keys(sizes).sort((a, b) => order.indexOf(a) - order.indexOf(b));
        },

        onProductChange(index) {
            const item = this.items[index];
            item.product_size = '';

            if (!this.itemsErrors[index]) {
                this.itemsErrors[index] = {};
            }
            this.itemsErrors[index].product_id = false;
            this.itemsErrors[index].product_size = false;
            this.itemsErrors[index].stock_exceeded = false;
            this.itemsErrors[index].stock_message = '';
        },

        onSizeChange(index) {
            const item = this.items[index];
            if (item.product_id && item.product_size) {
                const stock = this.getStockForProductAndSize(item.product_id, item.product_size);
                if (item.quantity > stock) {
                    item.quantity = stock;
                }
                if (!this.itemsErrors[index]) {
                    this.itemsErrors[index] = {};
                }
                this.itemsErrors[index].stock_exceeded = false;
                this.itemsErrors[index].stock_message = '';
            }
        },

        onQtyInput(index) {
            const item = this.items[index];
            const stock = item.product_id && item.product_size
                ? this.getStockForProductAndSize(item.product_id, item.product_size)
                : this.getStockForProduct(item.product_id);

            if (!this.itemsErrors[index]) {
                this.itemsErrors[index] = {};
            }

            if (item.product_id && stock > 0 && item.quantity > stock) {
                this.itemsErrors[index].stock_exceeded = true;
                this.itemsErrors[index].stock_message = `Qty melebihi stok tersedia (maks: ${stock})`;
            } else {
                this.itemsErrors[index].stock_exceeded = false;
                this.itemsErrors[index].stock_message = '';
            }

            this.itemsErrors[index].quantity = !item.quantity || item.quantity < 1;
        },

        chooseCustomerFromList() {
            if (!this.customerListSelection) {
                this.selectedCustomer = null;
                this.customerSearch = '';
                this.customerError = false;
                return;
            }
            const customer = this.customers.find(c => String(c.id) === String(this.customerListSelection));
            if (customer) this.selectCustomer(customer);
        },

        selectCustomer(customer) {
            this.selectedCustomer = customer;
            this.customerListSelection = String(customer.id);
            this.customerSearch = customer.name + ' - ' + customer.phone;
            this.customerError = false;
            this.customerDropdownOpen = false;
        },

        openAddModal() {
            this.newCustomer.name = this.customerSearch;
            this.newCustomer.phone = '';
            this.newCustomer.address = '';
            this.newCustomer.notes = '';
            this.newCustomerNameError = false;
            this.newCustomerPhoneError = false;
            this.showAddModal = true;
        },

        closeAddModal() {
            this.showAddModal = false;
        },

        submitForm() {
            this.customerError = false;
            this.rentalDateError = false;
            this.durationDaysError = false;
            this.paymentMethodError = false;
            this.guaranteeTypeError = false;
            this.photoError = false;
            this.productsError = false;
            this.itemsErrors = this.items.map(() => ({ product_id: false, product_size: false, quantity: false }));

            let hasError = false;

            if (!this.selectedCustomer) {
                this.customerError = true;
                hasError = true;
            }

            const rentalDateInput = document.querySelector('input[name="rental_date"]');
            if (!rentalDateInput || !rentalDateInput.value) {
                this.rentalDateError = true;
                hasError = true;
            }

            const durationInput = document.querySelector('input[name="duration_days"]');
            if (!durationInput || !durationInput.value || parseInt(durationInput.value) < 1) {
                this.durationDaysError = true;
                hasError = true;
            }

            let itemsInvalid = false;
            this.itemsErrors = this.items.map((item) => {
                const errors = {
                    product_id: !item.product_id,
                    product_size: !item.product_size,
                    quantity: !item.quantity || item.quantity < 1,
                    stock_exceeded: false,
                    stock_message: ''
                };

                if (item.product_id && item.product_size) {
                    const stock = this.getStockForProductAndSize(item.product_id, item.product_size);
                    if (stock > 0 && item.quantity > stock) {
                        errors.stock_exceeded = true;
                        errors.stock_message = `Qty melebihi stok tersedia (maks: ${stock})`;
                        itemsInvalid = true;
                    }
                } else if (item.product_id) {
                    const stock = this.getStockForProduct(item.product_id);
                    if (stock > 0 && item.quantity > stock) {
                        errors.stock_exceeded = true;
                        errors.stock_message = `Qty melebihi stok tersedia (maks: ${stock})`;
                        itemsInvalid = true;
                    }
                }

                if (errors.product_id || errors.product_size || errors.quantity) {
                    itemsInvalid = true;
                }
                return errors;
            });
            if (itemsInvalid) {
                this.productsError = true;
                hasError = true;
            }

            const paymentInput = document.querySelector('select[name="payment_method"]');
            if (!paymentInput || !paymentInput.value) {
                this.paymentMethodError = true;
                hasError = true;
            }

            const guaranteeInput = document.querySelector('select[name="guarantee_type"]');
            if (!guaranteeInput || !guaranteeInput.value) {
                this.guaranteeTypeError = true;
                hasError = true;
            }

            if (this.guaranteeType === 'deposit') {
                const depositInput = document.querySelector('input[name="guarantee_deposit"]');
                if (!depositInput || !depositInput.value || parseFloat(depositInput.value.replace(/[^0-9]/g, '')) <= 0) {
                    this.guaranteeDepositError = true;
                    hasError = true;
                }
            }

            if (this.guaranteeType === 'ktp' || this.guaranteeType === 'sim') {
                if (!this.photoState.file) {
                    this.photoError = true;
                    hasError = true;
                }
            }

            if (this.guaranteeType === 'custom') {
                const notesInput = document.querySelector('textarea[name="guarantee_notes"]');
                if (!notesInput || !notesInput.value.trim()) {
                    this.guaranteeTypeError = true;
                    hasError = true;
                }
            }

            if (hasError) {
                this.$nextTick(() => {
                    const firstErrorInput = document.querySelector('input.is-error, select.is-error, .input-group-phone__input.is-error');
                    if (firstErrorInput) {
                        firstErrorInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
                return;
            }

            this.submitting = true;
            this.$el.submit();
        },

        async addCustomer() {
    this.addingCustomer = true;
    this.newCustomerNameError = false;
    this.newCustomerPhoneError = false;

    if (!this.newCustomer.name.trim()) {
        this.newCustomerNameError = true;
        this.addingCustomer = false;
        return;
    }

    if (!this.newCustomer.phone.trim()) {
        this.newCustomerPhoneError = true;
        this.addingCustomer = false;
        return;
    }

    try {
        const response = await fetch('{{ route('customers.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector(
                    'meta[name="csrf-token"]'
                ).content,
            },
            body: JSON.stringify(this.newCustomer),
        });

        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            const text = await response.text();

            console.error('Response bukan JSON:');
            console.error(text);

            throw new Error(
                'Server mengembalikan response HTML. Cek Console atau laravel.log untuk melihat error sebenarnya.'
            );
        }

        const data = await response.json();

        /*
        |--------------------------------------------------------------------------
        | CUSTOMER SUDAH TERDAFTAR
        |--------------------------------------------------------------------------
        |
        | Jangan membuat customer baru.
        | Gunakan customer lama untuk penyewaan ini.
        |
        */
        if (response.status === 409 && data.existing_customer && data.customer) {

            const existingCustomer = data.customer;

            // Pilih customer lama
            this.selectedCustomer = existingCustomer;

            this.customerSearch =
                existingCustomer.name + ' - ' + existingCustomer.phone;

            // Pastikan customer ada di daftar Alpine
            const alreadyExists = this.customers.some(
                customer => customer.id === existingCustomer.id
            );

            if (!alreadyExists) {
                this.customers.push(existingCustomer);
            }

            // Tutup modal
            this.showAddModal = false;
            this.customerDropdownOpen = false;
            this.customerError = false;

            // Bersihkan form
            this.newCustomer = {
                name: '',
                phone: '',
                address: '',
                notes: ''
            };

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | ERROR LAIN
        |--------------------------------------------------------------------------
        */
        if (!response.ok) {
            if (data.errors) {
                const messages = Object.values(data.errors)
                    .flat()
                    .join('\n');

                throw new Error(messages);
            }

            throw new Error(
                data.message || 'Gagal menyimpan pelanggan'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CUSTOMER BARU BERHASIL DIBUAT
        |--------------------------------------------------------------------------
        */
        this.selectedCustomer = data.customer;

        this.customerSearch =
            data.customer.name + ' - ' + data.customer.phone;

        this.showAddModal = false;
        this.customerDropdownOpen = false;
        this.customerError = false;

        // Tambahkan ke daftar customer
        const alreadyExists = this.customers.some(
            customer => customer.id === data.customer.id
        );

        if (!alreadyExists) {
            this.customers.push(data.customer);
        }

        // Kosongkan form
        this.newCustomer = {
            name: '',
            phone: '',
            address: '',
            notes: ''
        };

    } catch (error) {
        console.error('Tambah pelanggan error:', error);
        alert(error.message);
    } finally {
        this.addingCustomer = false;
    }
},


        filterPhoneInput(event) {
            const input = event.target;
            let digits = input.value.replace(/[^0-9]/g, '');
            if (digits.startsWith('0')) {
                digits = '62' + digits.slice(1);
            }
            input.value = digits;
        },

        preventNonNumeric(event) {
            if ([8, 46, 9, 27, 13].includes(event.keyCode) ||
                (event.keyCode === 65 && (event.ctrlKey || event.metaKey)) ||
                (event.keyCode === 67 && (event.ctrlKey || event.metaKey)) ||
                (event.keyCode === 86 && (event.ctrlKey || event.metaKey)) ||
                (event.keyCode === 88 && (event.ctrlKey || event.metaKey)) ||
                (event.keyCode >= 35 && event.keyCode <= 39)) {
                return;
            }
            if ((event.shiftKey || (event.keyCode < 48 || event.keyCode > 57)) && (event.keyCode < 96 || event.keyCode > 105)) {
                event.preventDefault();
            }
        },

        addItem() {
            this.items.push({ product_id: '', product_size: '', quantity: 1 });
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },

        handleDrop(event) {
            this.dragOver = false;
            const file = event.dataTransfer.files[0];
            if (file) this.processFile(file);
        },

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) this.processFile(file);
        },

        async processFile(file) {
            if (!['image/jpeg', 'image/png'].includes(file.type)) {
                alert('Format tidak didukung. Gunakan JPG atau PNG.');
                return;
            }

            this.photoState.processing = true;
            this.photoState.checked = false;
            this.photoState.verified = false;
            this.photoState.file = null;
            this.photoState.sizeLabel = '';

            try {
                const imageUrl = URL.createObjectURL(file);
                const img = new Image();
                await new Promise((resolve, reject) => {
                    img.onload = resolve;
                    img.onerror = reject;
                    img.src = imageUrl;
                });

                // Batasi dimensi agar foto besar lebih ringan, tetap cukup jelas untuk identitas.
                let scale = Math.min(1, 2000 / img.naturalWidth, 2000 / img.naturalHeight);
                let blob = null;
                const maxBytes = 1.8 * 1024 * 1024; // di bawah batas validasi server 2 MB

                for (let attempt = 0; attempt < 8; attempt++) {
                    const canvas = document.createElement('canvas');
                    canvas.width = Math.max(1, Math.round(img.naturalWidth * scale));
                    canvas.height = Math.max(1, Math.round(img.naturalHeight * scale));
                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                    let quality = 0.86;
                    for (let q = 0; q < 6; q++) {
                        blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', quality));
                        if (blob && blob.size <= maxBytes) break;
                        quality -= 0.08;
                    }
                    if (blob && blob.size <= maxBytes) break;
                    scale *= 0.82;
                }

                URL.revokeObjectURL(imageUrl);
                if (!blob || blob.size > maxBytes) {
                    throw new Error('Ukuran foto masih terlalu besar setelah dikompres.');
                }

                const compressedFile = new File([blob], (file.name.replace(/\.[^.]+$/, '') || 'foto-identitas') + '.jpg', {
                    type: 'image/jpeg',
                    lastModified: Date.now()
                });
                const previewUrl = URL.createObjectURL(blob);

                // Pastikan file yang dikirim ke server adalah hasil kompresi, bukan file asli.
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(compressedFile);
                this.$refs.fileInput.files = dataTransfer.files;

                this.photoState.file = compressedFile;
                this.photoState.previewUrl = previewUrl;
                this.photoState.checked = true;
                this.photoState.verified = true;
                this.photoState.sizeLabel = (compressedFile.size / (1024 * 1024)).toFixed(2) + ' MB';
                this.photoError = false;
            } catch (error) {
                console.error('Kompresi foto gagal:', error);
                this.photoState.file = null;
                this.photoState.previewUrl = null;
                this.photoState.checked = true;
                this.photoState.verified = false;
                this.$refs.fileInput.value = '';
                alert('Foto gagal diproses. Silakan pilih foto JPG atau PNG lainnya.');
            } finally {
                this.photoState.processing = false;
            }
        },

        removePhoto() {
            if (this.photoState.previewUrl) URL.revokeObjectURL(this.photoState.previewUrl);
            this.photoState.file = null;
            this.photoState.previewUrl = null;
            this.photoState.checked = false;
            this.photoState.verified = false;
            this.photoState.processing = false;
            this.photoState.sizeLabel = '';
            this.$refs.fileInput.value = '';
        },

        watch: {
            items: {
                handler() {
                    if (this.guaranteeType === 'deposit' && !this.depositManualEdited) {
                        this.guaranteeDeposit = this.suggestedDeposit;
                    }
                },
                deep: true
            },
            guaranteeType(newVal) {
                if (newVal === 'deposit') {
                    this.guaranteeDeposit = this.suggestedDeposit;
                    this.depositManualEdited = false;
                } else {
                    this.guaranteeDeposit = '';
                    this.depositManualEdited = false;
                }

                if (newVal === 'ktp' || newVal === 'sim') {
                    // keep photo state as is if already uploaded
                } else {
                    // reset photo state when switching away from KTP/SIM
                    this.photoState.file = null;
                    this.photoState.previewUrl = null;
                    this.photoState.checked = false;
                    this.photoState.verified = false;
                    this.photoState.resolutionOk = false;
                    this.photoState.blurOk = false;
                    this.photoState.brightnessOk = false;
                    this.photoError = false;
                }
            }
        }
    }
}
</script>
@endpush

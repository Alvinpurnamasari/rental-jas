@extends('Layouts.app')

@section('title', $customer->name)
@section('page-title', 'Detail Pelanggan')
@section('subtitle', $customer->name)

@push('styles')
<style>
.rental-status-returned{background:#F0FDF4}
.rental-status-overdue{background:#FFF1F0}
.rental-status-active{background:#EFF6FF}
.rental-status-default{background:#FFF8E7}
.rental-status-icon-returned{color:#15803D}
.rental-status-icon-overdue{color:#C0392B}
.rental-status-icon-active{color:#1D4ED8}
.rental-status-icon-default{color:#B7791F}
.btn-blacklist{background:linear-gradient(135deg,#EF4444,#DC2626);box-shadow:0 2px 8px rgba(239,68,68,0.3)}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:50;display:flex;align-items:center;justify-content:center}
.modal-box{background:white;border-radius:1rem;padding:1.5rem;max-width:40rem;width:90%;max-height:90vh;overflow:auto}

/* Responsif khusus HP; tampilan desktop tetap */
@media (max-width: 639px) {
    .customer-detail-actions > * {
        flex: 1 1 calc(50% - .5rem);
        min-width: 0;
        justify-content: center;
        text-align: center;
    }
    .customer-detail-actions > a.btn-primary {
        flex-basis: 100%;
    }
    .customer-detail-actions > form,
    .customer-detail-actions > form button {
        min-width: 0;
    }
    .customer-detail-actions > form {
        flex: 1 1 calc(50% - .5rem);
    }
    .customer-detail-actions > form button {
        width: 100%;
        justify-content: center;
    }
    .customer-rental-row {
        align-items: flex-start;
        flex-wrap: wrap;
        gap: .75rem;
    }
    .customer-rental-row .customer-rental-info {
        flex: 1 1 calc(100% - 3.25rem);
        min-width: 0;
    }
    .customer-rental-row .customer-rental-summary {
        flex: 1 1 100%;
        margin-left: 3.25rem;
        text-align: left;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .375rem .5rem;
    }
    .customer-rental-row .customer-rental-summary p,
    .customer-rental-row .customer-rental-summary span {
        margin-top: 0;
    }
    .customer-rental-row .customer-rental-info > div:first-child {
        flex-wrap: wrap;
        align-items: flex-start;
    }
    .customer-rental-row .customer-rental-info a {
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .customer-rental-row .customer-rental-info p {
        overflow-wrap: anywhere;
    }
}
</style>
@endpush

@section('content')
<div class="space-y-5" x-data="{ showBlacklist: false, showPhotoPreview: false }">
@php $isDeactivated = $customer->trashed(); @endphp

@if($isDeactivated)
<div class="flex items-center gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200">
<i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 text-amber-600"></i>
<div class="flex-1">
<p class="font-semibold text-amber-800">Pelanggan ini dalam status Dinonaktifkan</p>
@if($customer->deleted_at)<p class="text-sm text-amber-600">Dinonaktifkan pada: {{ $customer->deleted_at->format('d M Y H:i') }}</p>@endif
</div>
@auth @if(auth()->user()->isSuperAdmin())
<form method="POST" action="{{ route('customers.restore', $customer->id) }}" class="inline">
@csrf
<button type="submit" class="btn-secondary bg-amber-100 border-amber-300 text-amber-800 hover:bg-amber-200"><i data-lucide="rotate-ccw" class="w-4 h-4"></i> Pulihkan Pelanggan</button>
</form>
@endif @endauth
</div>
@endif

<div class="customer-detail-actions flex flex-wrap items-center gap-3">
<a href="{{ route('customers.index') }}" class="btn-secondary"><i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali</a>
@if(!$isDeactivated)
<a href="{{ route('customers.edit', $customer) }}" class="btn-secondary"><i data-lucide="edit-2" class="w-4 h-4"></i> Edit</a>
<a href="{{ route('rentals.create', ['customer_id' => $customer->id]) }}" class="btn-primary"><i data-lucide="plus" class="w-4 h-4"></i> Buat Penyewaan Baru</a>
<a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', str_starts_with($customer->phone, '0') ? '62'.substr($customer->phone,1) : $customer->phone) }}" target="_blank" class="btn-secondary" style="color:#25D366;border-color:#25D366"><i data-lucide="message-circle" class="w-4 h-4"></i> WhatsApp</a>
@if(auth()->user()->isSuperAdmin())
<button @click="showBlacklist = true" class="btn-secondary {{ $customer->is_blacklisted ? 'border-green-400 text-green-600' : 'border-red-300 text-red-500' }}"><i data-lucide="{{ $customer->is_blacklisted ? 'user-check' : 'user-x' }}" class="w-4 h-4"></i> {{ $customer->is_blacklisted ? 'Buka Blokir' : 'Blokir' }}</button>
@endif
@if(auth()->user()->isSuperAdmin() || auth()->user()->isAdminToko())
<button type="button" onclick="confirmDelete('deleteCustomerForm','customer {{ $customer->name }}')" class="btn-secondary text-red-500 hover:bg-red-50"><i data-lucide="trash-2" class="w-4 h-4"></i> Hapus</button>
@endif
@else
@auth @if(auth()->user()->isSuperAdmin())
<button type="button" onclick="confirmDelete('deleteCustomerForm','customer {{ $customer->name }}')" class="btn-secondary text-red-500 hover:bg-red-50"><i data-lucide="trash-2" class="w-4 h-4"></i> Hapus Permanen</button>
@endif @endauth
@endif
</div>

<div class="flex items-center gap-2 flex-wrap">
@if($isDeactivated) <span class="badge bg-amber-100 text-amber-700 border border-amber-200">Dinonaktifkan</span>
@elseif($customer->is_blacklisted) <span class="badge badge-red">Blokir</span>
@else <span class="badge badge-green">Aktif</span>
@endif
</div>

@if($customer->is_blacklisted)
<div class="flex items-center gap-3 p-4 rounded-xl" style="background:#FFF1F0;border:1px solid #FECACA">
<i data-lucide="shield-off" class="w-5 h-5 flex-shrink-0" style="color:#C0392B"></i>
<div><p class="font-semibold" style="color:#C0392B">Pelanggan ini diblokir</p>
@if($customer->blacklist_reason)<p class="text-sm" style="color:#E74C3C">Alasan: {{ $customer->blacklist_reason }}</p>@endif
</div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-1">
        <div class="ds-card p-6">
            <h3 class="font-semibold text-base mb-4" style="color:var(--text-dark)">
                <i data-lucide="user" class="w-4 h-4 inline mr-2" style="color:var(--primary)"></i> Informasi Pelanggan
            </h3>

            <div class="flex flex-col items-center mb-5">
                <div class="w-24 h-24 rounded-full border border-gold/30 bg-gold text-bark flex items-center justify-center font-bold text-2xl shadow-sm ds-hover-lift {{ $isDeactivated ? 'grayscale' : '' }}">
                    {{ strtoupper(substr($customer->name, 0, 2)) }}
                </div>
            </div>

            <div class="space-y-4">
                <div><p class="text-xs text-stone-500 mb-1">Nama Lengkap</p><p class="text-sm font-semibold text-bark-dark">{{ $customer->name }}</p></div>
                <div><p class="text-xs text-stone-500 mb-1">Nomor WhatsApp</p><p class="text-sm font-semibold text-bark-dark">{{ $customer->phone }}</p></div>
                <hr class="border-cream-sand/50 my-3">
                <div class="flex items-center justify-between gap-3">
                    <div><p class="text-xs text-stone-500 mb-1">Total Sewa</p><p class="text-lg font-bold text-bark-dark">{{ $customer->rentals->count() }}</p></div>
                    <div class="text-right"><p class="text-xs text-stone-500 mb-1">Selesai</p><p class="text-lg font-bold text-green-600">{{ $customer->rentals->where('rental_status','returned')->count() }}</p></div>
                </div>
            </div>
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="ds-card overflow-hidden">
<div class="p-5 border-b" style="border-color:var(--border)">
<div class="flex items-center justify-between">
<h3 class="font-semibold text-base" style="color:var(--text-dark)"><i data-lucide="shirt" class="w-4 h-4 inline mr-2" style="color:var(--primary)"></i> Riwayat Penyewaan</h3>
<a href="{{ route('rentals.index', ['customer_id' => $customer->id]) }}" class="text-xs font-semibold hover:underline" style="color:var(--primary)">Lihat Semua →</a>
</div>
</div>
<div class="divide-y divide-cream-sand/30">
@forelse($customer->rentals as $rental)
<div class="customer-rental-row flex items-center gap-4 p-4 transition-colors hover:bg-cream/10 ds-transition">
    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 rental-status-{{ $rental->rental_status }}">
        <i data-lucide="{{ match($rental->rental_status) { 'returned' => 'check-circle', 'overdue' => 'alert-circle', 'active' => 'clock', default => 'hourglass' } }}" class="w-5 h-5 rental-status-icon-{{ $rental->rental_status }}"></i>
    </div>
    <div class="customer-rental-info flex-1 min-w-0">
        <div class="flex items-center gap-2">
            <a href="{{ route('rentals.show', $rental) }}" class="font-mono font-semibold text-sm hover:underline" style="color:var(--primary)">{{ $rental->invoice_number }}</a>
            <span class="badge badge-{{ $rental->status_badge_color }} text-[10px]">{{ $rental->status_label }}</span>
        </div>
        <p class="text-xs mt-0.5" style="color:var(--text-soft)">{{ optional($rental->rental_date)->format('d M Y') }} → {{ optional($rental->return_due_date)->format('d M Y') }} ({{ $rental->duration_days }} hari)</p>
        <div class="flex flex-wrap gap-1 mt-1">
            @foreach($rental->items->take(3) as $item)
            <span class="text-[10px] px-1.5 py-0.5 rounded-lg" style="background:var(--secondary);color:var(--text-soft)">{{ $item->product_name }}</span>
            @endforeach
        </div>
    </div>
    <div class="customer-rental-summary text-right flex-shrink-0">
        <p class="font-bold text-sm" style="color:var(--text-dark)">Rp {{ number_format($rental->total_amount, 0, ',', '.') }}</p>
        <span class="badge {{ match($rental->payment_status) { 'paid' => 'badge-green', 'partial' => 'badge-yellow', default => 'badge-red' } }} text-[10px] mt-1">{{ $rental->payment_status_label }}</span>
        @if($rental->fine_status === 'unpaid' || $rental->fine_status === 'partial')
            <span class="badge badge-yellow text-[10px] mt-1 ml-1">Ada Denda</span>
        @endif
    </div>
</div>
@empty
<div class="py-12 text-center">
<i data-lucide="shirt" class="w-8 h-8 mx-auto mb-2" style="color:var(--border)"></i>
<p class="text-sm" style="color:var(--text-soft)">Belum ada riwayat penyewaan</p>
</div>
@endforelse
</div>
</div>

<div x-show="showBlacklist" x-cloak class="modal-overlay" @click.self="showBlacklist = false">
<div class="modal-box">
<h3 class="font-semibold text-base mb-4" style="color:var(--text-dark)">{{ $customer->is_blacklisted ? 'Buka Blokir Pelanggan' : 'Blokir Pelanggan' }}</h3>
<form method="POST" action="{{ route('customers.blacklist', $customer) }}">
@csrf @method('PATCH')
@if(!$customer->is_blacklisted)
<div class="mb-4">
<label class="block text-sm font-medium mb-1.5" style="color:var(--text-dark)">Alasan Blokir Pelanggan</label>
<textarea name="reason" rows="3" class="form-input" placeholder="Tulis alasan blokir pelanggan..." required></textarea>
</div>
@endif
<div class="flex gap-3">
<button type="button" @click="showBlacklist = false" class="btn-secondary flex-1 justify-center">Batal</button>
<button type="submit" class="btn-primary flex-1 justify-center {{ $customer->is_blacklisted ? '' : 'btn-blacklist' }}"><i data-lucide="{{ $customer->is_blacklisted ? 'user-check' : 'user-x' }}" class="w-4 h-4"></i> {{ $customer->is_blacklisted ? 'Buka Blokir Pelanggan' : 'Blokir Pelanggan' }}</button>
</div>
</form>
</div>
</div>
@endsection

<form id="deleteCustomerForm" method="POST" action="{{ $isDeactivated ? route('customers.force-destroy', $customer) : route('customers.destroy', $customer) }}" class="hidden">@csrf @method('DELETE')</form>

@push('scripts')
<script>
</script>
@endpush

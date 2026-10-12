@extends('Layouts.app')

@section('title', $branch->name)

@section('content')
<div class="w-full max-w-3xl mx-auto min-w-0 space-y-6">

    <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ route('branches.index') }}" class="btn-secondary p-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <h1 class="font-playfair text-xl sm:text-2xl font-bold break-words" style="color: var(--text-dark)">{{ $branch->name }}</h1>
                <p class="text-sm mt-0.5" style="color: var(--text-soft)">Detail cabang</p>
            </div>
        </div>
        
        <div class="grid min-w-0 grid-cols-1 gap-2 w-full sm:flex sm:flex-wrap sm:items-center sm:w-auto sm:shrink-0">
            <a href="{{ route('branches.edit', $branch) }}" class="btn-primary w-full justify-center sm:w-auto">
                <i data-lucide="pencil" class="w-4 h-4"></i>
                Edit
            </a>

<form
    id="toggleBranchStatusForm"
    method="POST"
    action="{{ route('branches.toggle-status', $branch) }}"
>
    @csrf
    @method('PATCH')

    <button
        type="button"
        onclick="confirmAction(
            'toggleBranchStatusForm',
            '{{ $branch->is_active ? 'Nonaktifkan Cabang' : 'Aktifkan Kembali Cabang' }}',
            '{{ $branch->is_active
                ? 'Apakah Anda yakin ingin menonaktifkan cabang ' . $branch->name . '?'
                : 'Apakah Anda yakin ingin mengaktifkan kembali cabang ' . $branch->name . '?' }}',
            '{{ $branch->is_active ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan' }}',
            '{{ $branch->is_active ? '#dc2626' : '#10b981' }}'
        )"
        class="inline-flex min-w-0 w-full sm:w-auto justify-center items-center gap-2 px-3 sm:px-4 py-2 rounded-xl font-semibold transition text-center whitespace-normal break-words
            {{ $branch->is_active
                ? 'bg-amber-100 text-amber-700 hover:bg-amber-200'
                : 'bg-green-100 text-green-700 hover:bg-green-200' }}"
    >
        <i
            data-lucide="{{ $branch->is_active ? 'ban' : 'check-circle' }}"
            class="w-4 h-4"
        ></i>

        {{ $branch->is_active ? 'Nonaktifkan Cabang' : 'Aktifkan Kembali' }}
    </button>
</form>


            <button
                type="button"
                onclick="confirmDelete('deleteBranchForm', 'cabang {{ $branch->name }}')"
                class="btn-secondary w-full sm:w-auto justify-center text-red-500 hover:bg-red-50"
            >
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                Hapus
            </button>
        </div>

    </div>

    <div class="card min-w-0 p-4 sm:p-6 space-y-4 overflow-hidden">
        <div class="grid min-w-0 grid-cols-2 gap-3 sm:gap-4">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:var(--text-soft)">Nama Cabang</p>
                <p class="font-semibold break-words" style="color:var(--text-dark)">{{ $branch->name }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:var(--text-soft)">Kode</p>
                <span class="badge badge-gold text-sm">{{ $branch->code }}</span>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:var(--text-soft)">Telepon</p>
                <p class="min-w-0 break-words" style="color:var(--text-dark)">{{ $branch->phone ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:var(--text-soft)">Email</p>
                <p class="min-w-0 break-all" style="color:var(--text-dark)">{{ $branch->email ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:var(--text-soft)">Kota</p>
                <p style="color:var(--text-dark)">{{ $branch->city ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:var(--text-soft)">Provinsi</p>
                <p style="color:var(--text-dark)">{{ $branch->province ?? '-' }}</p>
            </div>
            <div class="col-span-2">
                <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:var(--text-soft)">Alamat</p>
                <p class="break-words" style="color:var(--text-dark)">{{ $branch->address ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:var(--text-soft)">Status</p>
                @if($branch->is_active)
                    <span class="badge badge-green">Aktif</span>
                @else
                    <span class="badge badge-gray">Nonaktif</span>
                @endif
            </div>
        </div>

        <div class="border-t pt-4 grid grid-cols-3 gap-2 sm:gap-4" style="border-color:var(--border)">
            <div class="text-center">
                <p class="text-2xl font-bold" style="color:var(--primary)">{{ $branch->users_count }}</p>
                <p class="text-xs mt-1" style="color:var(--text-soft)">Pengguna</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold" style="color:var(--primary)">{{ $branch->products_count }}</p>
                <p class="text-xs mt-1" style="color:var(--text-soft)">Produk</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold" style="color:var(--primary)">{{ $branch->rentals_count }}</p>
                <p class="text-xs mt-1" style="color:var(--text-soft)">Total Rental</p>
            </div>
        </div>
    </div>
</div>

@endsection

<form id="deleteBranchForm" method="POST" action="{{ route('branches.destroy', $branch) }}" class="hidden">
    @csrf
    @method('DELETE')
</form>
@push('scripts')
<script>
lucide.createIcons();
</script>
@endpush

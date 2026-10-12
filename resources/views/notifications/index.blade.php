
@extends('Layouts.app')

@section('title', 'Notifikasi')

@section('content')
<div class="space-y-5 sm:space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold" style="color:var(--text-dark)">
                Notifikasi
            </h1>
            <p class="text-sm mt-1" style="color:var(--text-soft)">
                Daftar notifikasi untuk akun Anda
            </p>
        </div>

        <form method="POST"
              action="{{ route('notifications.read-all') }}"
              class="w-full sm:w-auto">
            @csrf
            <button type="submit"
                    class="btn-secondary w-full justify-center sm:w-auto">
                <i data-lucide="check" class="w-4 h-4"></i>
                Tandai semua dibaca
            </button>
        </form>
    </div>

    {{-- Filter --}}
    <div class="card p-4 sm:p-5">
        <form method="GET"
              action="{{ route('notifications.index') }}"
              class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">

            <div class="w-full sm:max-w-xs">
                <label for="notification-type"
                       class="block text-sm font-medium mb-1"
                       style="color:var(--text-soft)">
                    Tipe notifikasi
                </label>

                <select id="notification-type"
                        name="type"
                        class="form-input w-full">
                    <option value="all" {{ $type === 'all' ? 'selected' : '' }}>
                        Semua
                    </option>
                    <option value="rental_new" {{ $type === 'rental_new' ? 'selected' : '' }}>
                        Penyewaan Baru
                    </option>
                    <option value="rental_return" {{ $type === 'rental_return' ? 'selected' : '' }}>
                        Pengembalian
                    </option>
                    <option value="rental_late" {{ $type === 'rental_late' ? 'selected' : '' }}>
                        Keterlambatan
                    </option>
                    <option value="payment" {{ $type === 'payment' ? 'selected' : '' }}>
                        Pembayaran
                    </option>
                    <option value="reminder" {{ $type === 'reminder' ? 'selected' : '' }}>
                        Reminder
                    </option>
                    <option value="system" {{ $type === 'system' ? 'selected' : '' }}>
                        Sistem
                    </option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2 w-full sm:w-auto">
                <button type="submit"
                        class="btn-primary w-full justify-center">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    Terapkan
                </button>

                <a href="{{ route('notifications.index') }}"
                   class="btn-secondary w-full justify-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Daftar notifikasi --}}
    <div class="card overflow-hidden">

        @if($notifications->count() === 0)
            <div class="flex flex-col items-center justify-center py-16 px-5 text-center">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4"
                     style="background:var(--secondary)">
                    <i data-lucide="bell" class="w-8 h-8"
                       style="color:var(--primary)"></i>
                </div>

                <p class="font-semibold text-lg" style="color:var(--text-dark)">
                    Belum ada notifikasi
                </p>
                <p class="text-sm mt-1" style="color:var(--text-soft)">
                    Notifikasi akan tampil di sini jika tersedia.
                </p>
            </div>
        @else

            {{-- Tampilan desktop --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="elegant-table w-full">
                    <thead>
                        <tr>
                            <th class="text-left">Notifikasi</th>
                            <th class="text-left">Waktu</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($notifications as $n)
                            <tr>
                                <td>
                                    <div class="flex items-start gap-3 min-w-0">
                                        <div class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center {{ $n->icon_class }}"
                                             style="color:white">
                                            <span class="text-lg">{{ $n->icon_name }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold break-words"
                                               style="color:var(--text-dark)">
                                                {{ $n->title }}
                                            </p>
                                            <p class="text-sm mt-0.5 break-words"
                                               style="color:var(--text-soft)">
                                                {{ $n->message }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <p class="text-sm" style="color:var(--text-dark)">
                                        {{ $n->time_ago }}
                                    </p>
                                </td>

                                <td class="text-center">
                                    @if($n->is_read)
                                        <span class="badge badge-green">Dibaca</span>
                                    @else
                                        <span class="badge badge-blue">Belum dibaca</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="flex items-center justify-center gap-2">
                                        @if($n->action_url)
                                            <a href="{{ $n->action_url }}"
                                               class="btn-secondary btn-sm">
                                                <i data-lucide="external-link" class="w-4 h-4"></i>
                                                Buka
                                            </a>
                                        @endif

                                        <a href="{{ route('notifications.show', $n->id) }}"
                                           class="p-2 rounded-lg hover:bg-gray-100"
                                           title="Detail notifikasi"
                                           aria-label="Detail notifikasi">
                                            <i data-lucide="eye" class="w-4 h-4"
                                               style="color:var(--text-soft)"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Tampilan HP --}}
            <div class="md:hidden divide-y" style="border-color:var(--border)">
                @foreach($notifications as $n)
                    <div class="p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center {{ $n->icon_class }}"
                                 style="color:white">
                                <span class="text-lg">{{ $n->icon_name }}</span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="font-semibold text-sm break-words"
                                       style="color:var(--text-dark)">
                                        {{ $n->title }}
                                    </p>

                                    <span class="shrink-0">
                                        @if($n->is_read)
                                            <span class="badge badge-green">Dibaca</span>
                                        @else
                                            <span class="badge badge-blue">Belum dibaca</span>
                                        @endif
                                    </span>
                                </div>

                                <p class="text-sm mt-1 break-words"
                                   style="color:var(--text-soft)">
                                    {{ $n->message }}
                                </p>

                                <p class="text-xs mt-2"
                                   style="color:var(--text-soft)">
                                    {{ $n->time_ago }}
                                </p>

                                <div class="flex flex-wrap items-center gap-2 mt-3">
                                    @if($n->action_url)
                                        <a href="{{ $n->action_url }}"
                                           class="btn-secondary btn-sm">
                                            <i data-lucide="external-link" class="w-4 h-4"></i>
                                            Buka
                                        </a>
                                    @endif

                                    <a href="{{ route('notifications.show', $n->id) }}"
                                       class="btn-secondary btn-sm">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                        Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="px-4 py-4 overflow-x-auto">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    lucide.createIcons();
</script>
@endpush
@endsection

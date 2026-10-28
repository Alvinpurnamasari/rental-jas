
@extends('Layouts.app')

@section('title', 'Template Pesan - Broadcast')
@section('page-title', 'Template Pesan')
@section('subtitle', 'Kelola teks siap pakai untuk campaign broadcast')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <h2 class="text-lg font-extrabold text-slate-950">
                Template Pesan
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Template ini adalah teks siap pakai, bukan template resmi WhatsApp Business API.
            </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
            <a
                href="{{ route('broadcast-campaigns.index') }}"
                class="btn-secondary inline-flex items-center justify-center gap-2 px-4 py-2"
            >
                Kembali ke Broadcast
            </a>

            <a
                href="{{ route('broadcast-campaigns.templates.archived') }}"
                class="btn-secondary inline-flex items-center justify-center gap-2 px-4 py-2"
            >
                <i data-lucide="archive" class="h-4 w-4"></i>
                Lihat Arsip
            </a>

            <a
                href="{{ route('broadcast-campaigns.templates.create') }}"
                class="btn-primary inline-flex items-center justify-center gap-2 px-4 py-2"
            >
                <i data-lucide="plus" class="h-4 w-4"></i>
                Tambah Template
            </a>
        </div>
    </div>

    {{-- Tabel desktop --}}
    <div class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card-sm md:block">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Nama</th>
                        <th class="px-5 py-3 font-semibold">Kategori</th>
                        <th class="px-5 py-3 font-semibold">Isi Pesan</th>
                        <th class="px-5 py-3 font-semibold">Cakupan</th>
                        <th class="px-5 py-3 font-semibold">Status</th>
                        <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($templates as $template)
                        <tr class="align-top hover:bg-slate-50/60">
                            <td class="break-words px-5 py-4 font-semibold text-slate-900">
                                {{ $template->name }}
                            </td>

                            <td class="break-words px-5 py-4 text-slate-600">
                                {{ $template->category }}
                            </td>

                            <td class="px-5 py-4 text-slate-600">
                                <p class="max-w-lg whitespace-pre-wrap break-words">{{ $template->content }}</p>
                            </td>

                            <td class="px-5 py-4 text-slate-600">
                                {{ $template->branch?->name ?? 'Semua cabang' }}
                            </td>

                            <td class="px-5 py-4">
                                <span class="badge {{ $template->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $template->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                @if(
                                    auth()->user()->isSuperAdmin()
                                    || (int) $template->branch_id === (int) auth()->user()->branch_id
                                    || ($template->branch_id === null && (int) $template->created_by === (int) auth()->id())
                                )
                                    <div class="flex flex-wrap items-center justify-end gap-1">

                                        {{-- Edit --}}
                                        <a
                                            href="{{ route('broadcast-campaigns.templates.edit', $template) }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-600 transition hover:bg-blue-50 hover:text-blue-600"
                                            title="Edit template"
                                            aria-label="Edit template"
                                        >
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                        </a>

                                        {{-- Aktif / Nonaktif --}}
                                        <form
                                            method="POST"
                                            action="{{ route('broadcast-campaigns.templates.toggle', $template) }}"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-600 transition hover:bg-amber-50 hover:text-amber-600"
                                                title="{{ $template->is_active ? 'Nonaktifkan template' : 'Aktifkan template' }}"
                                                aria-label="{{ $template->is_active ? 'Nonaktifkan template' : 'Aktifkan template' }}"
                                            >
                                                <i data-lucide="{{ $template->is_active ? 'eye-off' : 'eye' }}" class="h-4 w-4"></i>
                                            </button>
                                        </form>

                                        {{-- Arsip --}}
                                        <form
                                            method="POST"
                                            action="{{ route('broadcast-campaigns.templates.destroy', $template) }}"
                                            class="archive-form"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-600 transition hover:bg-red-50 hover:text-red-600"
                                                title="Arsipkan template"
                                                aria-label="Arsipkan template"
                                            >
                                                <i data-lucide="archive" class="h-4 w-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <div class="text-right text-xs text-slate-400">
                                        Template global
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">
                                Belum ada template pesan. Tambahkan template pertama Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($templates->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">
                {{ $templates->links() }}
            </div>
        @endif
    </div>

    {{-- Kartu mobile --}}
    <div class="space-y-4 md:hidden">
        @forelse($templates as $template)
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">

                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Nama Template
                        </p>
                        <h3 class="mt-1 break-words font-bold text-slate-900">
                            {{ $template->name }}
                        </h3>
                    </div>

                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $template->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                        {{ $template->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Kategori
                        </p>
                        <p class="mt-1 break-words text-sm text-slate-700">
                            {{ $template->category }}
                        </p>
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Cakupan
                        </p>
                        <p class="mt-1 break-words text-sm text-slate-700">
                            {{ $template->branch?->name ?? 'Semua cabang' }}
                        </p>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Isi Pesan
                    </p>
                    <p class="mt-2 whitespace-pre-wrap break-words text-sm leading-relaxed text-slate-600">{{ $template->content }}</p>
                </div>

                <div class="mt-4 border-t border-slate-100 pt-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Aksi
                    </p>

                    @if(
                        auth()->user()->isSuperAdmin()
                        || (int) $template->branch_id === (int) auth()->user()->branch_id
                        || ($template->branch_id === null && (int) $template->created_by === (int) auth()->id())
                    )
                        <div class="flex flex-wrap items-center gap-2">

                            {{-- Edit --}}
                            <a
                                href="{{ route('broadcast-campaigns.templates.edit', $template) }}"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-blue-50 hover:text-blue-600"
                                title="Edit template"
                                aria-label="Edit template"
                            >
                                <i data-lucide="pencil" class="h-4 w-4"></i>
                            </a>

                            {{-- Aktif / Nonaktif --}}
                            <form
                                method="POST"
                                action="{{ route('broadcast-campaigns.templates.toggle', $template) }}"
                            >
                                @csrf
                                @method('PATCH')

                                <button
                                    type="submit"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-amber-50 hover:text-amber-600"
                                    title="{{ $template->is_active ? 'Nonaktifkan template' : 'Aktifkan template' }}"
                                    aria-label="{{ $template->is_active ? 'Nonaktifkan template' : 'Aktifkan template' }}"
                                >
                                    <i data-lucide="{{ $template->is_active ? 'eye-off' : 'eye' }}" class="h-4 w-4"></i>
                                </button>
                            </form>

                            {{-- Arsip --}}
                            <form
                                method="POST"
                                action="{{ route('broadcast-campaigns.templates.destroy', $template) }}"
                                class="archive-form"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-red-50 hover:text-red-600"
                                    title="Arsipkan template"
                                    aria-label="Arsipkan template"
                                >
                                    <i data-lucide="archive" class="h-4 w-4"></i>
                                </button>
                            </form>
                        </div>
                    @else
                        <p class="text-xs text-slate-400">
                            Template global
                        </p>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500">
                Belum ada template pesan. Tambahkan template pertama Anda.
            </div>
        @endforelse

        @if($templates->hasPages())
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                {{ $templates->links() }}
            </div>
        @endif
    </div>

</div>

{{-- Modal konfirmasi arsip --}}
<div
    id="archiveModal"
    class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/40 p-4 backdrop-blur-sm transition-opacity duration-200"
    role="dialog"
    aria-modal="true"
    aria-labelledby="archiveModalTitle"
>
    <div class="w-full max-w-sm max-h-[90vh] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl sm:p-5">

        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                <i data-lucide="archive" class="h-5 w-5"></i>
            </div>

            <div class="min-w-0">
                <h3 id="archiveModalTitle" class="text-base font-bold text-slate-900">
                    Arsipkan Template?
                </h3>

                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                    Template ini akan dipindahkan ke daftar arsip dan tidak dapat dipilih untuk campaign baru.
                </p>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button
                type="button"
                id="cancelArchive"
                class="btn-secondary inline-flex items-center justify-center px-4 py-2.5"
            >
                Batal
            </button>

            <button
                type="button"
                id="confirmArchive"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <i data-lucide="archive" class="h-4 w-4"></i>
                Ya, Arsipkan
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.lucide) {
        window.lucide.createIcons();
    }

    const modal = document.getElementById('archiveModal');
    const cancelButton = document.getElementById('cancelArchive');
    const confirmButton = document.getElementById('confirmArchive');

    if (!modal || !cancelButton || !confirmButton) {
        return;
    }

    let selectedForm = null;

    // Modal harus selalu tertutup saat halaman pertama kali dimuat.
    modal.classList.add('hidden');
    modal.classList.remove('flex');

    // Buka modal ketika form arsip dikirim.
    document.querySelectorAll('.archive-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            selectedForm = form;
            confirmButton.disabled = false;

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            if (window.lucide) {
                window.lucide.createIcons();
            }

            cancelButton.focus();
        });
    });

    // Tutup modal tanpa mengarsipkan.
    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');

        selectedForm = null;
        confirmButton.disabled = false;
    }

    cancelButton.addEventListener('click', closeModal);

    // Klik area gelap untuk menutup modal.
    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    // Tombol Escape untuk menutup modal.
    document.addEventListener('keydown', function (event) {
        if (
            event.key === 'Escape' &&
            !modal.classList.contains('hidden')
        ) {
            closeModal();
        }
    });

    // Lanjutkan pengarsipan setelah dikonfirmasi.
    confirmButton.addEventListener('click', function () {
        if (!selectedForm) {
            return;
        }

        confirmButton.disabled = true;
        selectedForm.submit();
    });
});
</script>
@endpush
@endsection

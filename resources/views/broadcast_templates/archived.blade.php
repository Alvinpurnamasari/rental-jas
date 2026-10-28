
@extends('Layouts.app')

@section('title', 'Arsip Template - Broadcast')
@section('page-title', 'Arsip Template')
@section('subtitle', 'Lihat dan pulihkan template pesan yang telah diarsipkan')

@section('content')
<div class="min-w-0 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <h2 class="text-lg font-extrabold text-slate-950">
                Arsip Template
            </h2>
            <p class="mt-1 text-sm leading-relaxed text-slate-500">
                Template di sini tidak tampil pada daftar utama dan tidak dapat dipilih untuk campaign baru.
            </p>
        </div>

        <a href="{{ route('broadcast-campaigns.templates.index') }}"
           class="btn-secondary flex w-full items-center justify-center gap-2 px-4 py-3 text-center sm:w-auto sm:shrink-0">
            <i data-lucide="arrow-left" class="h-4 w-4 shrink-0"></i>
            <span>Kembali ke Template Pesan</span>
        </a>
    </div>

    {{-- Daftar template arsip --}}
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card-sm">

        {{-- Tampilan HP --}}
        <div class="space-y-3 p-3 sm:p-4 md:hidden">
            @forelse($templates as $template)
                <article class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4">

                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Nama Template
                            </p>
                            <h3 class="mt-1 break-words text-sm font-bold text-slate-900">
                                {{ $template->name }}
                            </h3>
                        </div>

                        <span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                            Diarsipkan
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
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

                    <div class="mt-4 min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Isi Pesan
                        </p>
                        <p class="mt-1 whitespace-pre-wrap break-words text-sm leading-relaxed text-slate-600">{{ $template->content }}</p>
                    </div>

                    <div class="mt-4 border-t border-slate-100 pt-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Tanggal Diarsipkan
                        </p>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ $template->deleted_at?->format('d M Y, H:i') ?? '-' }}
                        </p>
                    </div>

                    <form method="POST"
                          action="{{ route('broadcast-campaigns.templates.restore', $template->id) }}"
                          class="restore-form mt-4">
                        @csrf
                        <button type="submit"
                                class="btn-primary flex w-full items-center justify-center gap-2 px-4 py-2.5">
                            <i data-lucide="undo-2" class="h-4 w-4"></i>
                            Pulihkan Template
                        </button>
                    </form>

                    {{-- Hapus permanen: khusus Super Admin --}}
                    @if(auth()->user()->isSuperAdmin())
                        <form method="POST"
                              action="{{ route('broadcast-campaigns.templates.force-delete', $template->id) }}"
                              class="force-delete-form mt-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                Hapus Permanen
                            </button>
                        </form>
                    @endif

                </article>
            @empty
                <div class="px-3 py-10 text-center">
                    <i data-lucide="archive" class="mx-auto h-10 w-10 text-slate-300"></i>
                    <p class="mt-3 text-sm font-medium text-slate-700">
                        Belum ada template yang diarsipkan.
                    </p>
                </div>
            @endforelse
        </div>

        {{-- Tampilan tablet/laptop --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full min-w-[850px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Nama</th>
                        <th class="px-5 py-3 font-semibold">Kategori</th>
                        <th class="px-5 py-3 font-semibold">Isi Pesan</th>
                        <th class="px-5 py-3 font-semibold">Cakupan</th>
                        <th class="px-5 py-3 font-semibold">Tanggal Diarsipkan</th>
                        <th class="px-5 py-3 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($templates as $template)
                        <tr class="align-top hover:bg-slate-50/60">
                            <td class="max-w-[180px] break-words px-5 py-4 font-semibold text-slate-900">
                                {{ $template->name }}
                            </td>

                            <td class="px-5 py-4 text-slate-600">
                                {{ $template->category }}
                            </td>

                            <td class="max-w-md px-5 py-4 text-slate-600">
                                <p class="whitespace-pre-wrap break-words">{{ $template->content }}</p>
                            </td>

                            <td class="px-5 py-4 text-slate-600">
                                {{ $template->branch?->name ?? 'Semua cabang' }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                                {{ $template->deleted_at?->format('d M Y H:i') ?? '-' }}
                            </td>
  
                            <td class="px-5 py-4 text-center">
                                <div class="flex flex-col items-center justify-center gap-2">

                                    {{-- Tombol Pulihkan --}}
                                    <form
                                        method="POST"
                                        action="{{ route('broadcast-campaigns.templates.restore', $template->id) }}"
                                        class="w-full"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="inline-flex w-28 items-center justify-center gap-1.5
                                                rounded-lg bg-blue-600 px-3 py-2
                                                text-xs font-medium text-white
                                                transition hover:bg-blue-700"
                                            title="Pulihkan template"
                                        >
                                            <i data-lucide="undo-2" class="h-4 w-4"></i>
                                            Pulihkan
                                        </button>
                                    </form>

                                    {{-- Tombol Hapus Permanen: Super Admin saja --}}
                                    @if(auth()->user()->isSuperAdmin())
                                        <form
                                            method="POST"
                                            action="{{ route('broadcast-campaigns.templates.force-delete', $template->id) }}"
                                            class="force-delete-form w-full"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="inline-flex w-28 items-center justify-center
                                                    rounded-lg bg-red-600 px-3 py-2
                                                    text-xs font-medium text-white
                                                    transition hover:bg-red-700"
                                                title="Hapus permanen"
                                            >
                                                Hapus Permanen
                                            </button>
                                        </form>
                                    @endif

                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">
                                Belum ada template yang diarsipkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($templates->hasPages())
            <div class="border-t border-slate-100 px-3 py-4 sm:px-5">
                {{ $templates->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Modal konfirmasi pemulihan --}}
<div id="restoreModal"
     class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm"
     role="dialog"
     aria-modal="true"
     aria-labelledby="restoreModalTitle">

    <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl sm:p-5">

        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                <i data-lucide="undo-2" class="h-5 w-5"></i>
            </div>

            <div class="min-w-0">
                <h3 id="restoreModalTitle" class="text-base font-bold text-slate-900">
                    Pulihkan Template?
                </h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                    Template ini akan dikembalikan ke daftar utama dan dapat digunakan kembali untuk campaign.
                </p>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button"
                    id="cancelRestore"
                    class="btn-secondary inline-flex items-center justify-center px-4 py-2.5">
                Batal
            </button>

            <button type="button"
                    id="confirmRestore"
                    class="btn-primary inline-flex items-center justify-center gap-2 px-4 py-2.5">
                <i data-lucide="undo-2" class="h-4 w-4"></i>
                Ya, Pulihkan
            </button>
        </div>
    </div>
</div>

{{-- Modal konfirmasi hapus permanen --}}
<div id="deleteTemplateModal"
     class="fixed inset-0 z-[110] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
     role="dialog"
     aria-modal="true"
     aria-labelledby="deleteTemplateModalTitle">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl sm:p-6">
        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                <i data-lucide="trash-2" class="h-5 w-5"></i>
            </div>
            <div class="min-w-0">
                <h3 id="deleteTemplateModalTitle" class="text-base font-bold text-slate-900">
                    Hapus Template Permanen?
                </h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                    Template ini akan dihapus secara permanen dan tidak dapat dipulihkan lagi. Pastikan Anda benar-benar ingin melanjutkan.
                </p>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button"
                    id="cancelDeleteTemplate"
                    class="btn-secondary inline-flex items-center justify-center px-4 py-2.5">
                Batal
            </button>
            <button type="button"
                    id="confirmDeleteTemplate"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">
                <i data-lucide="trash-2" class="h-4 w-4"></i>
                Ya, Hapus Permanen
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

    const modal = document.getElementById('restoreModal');
    const cancelButton = document.getElementById('cancelRestore');
    const confirmButton = document.getElementById('confirmRestore');

    if (!modal || !cancelButton || !confirmButton) {
        return;
    }

    let selectedForm = null;

    // Modal hanya muncul setelah tombol Pulihkan ditekan.
    document.querySelectorAll('.restore-form').forEach(function (form) {
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

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        selectedForm = null;
        confirmButton.disabled = false;
    }

    cancelButton.addEventListener('click', closeModal);

    // Tutup jika area gelap di luar kotak diklik.
    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    // Tutup dengan tombol Escape.
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    // Kirim form pulihkan hanya setelah dikonfirmasi.
    confirmButton.addEventListener('click', function () {
        if (!selectedForm) {
            return;
        }

        confirmButton.disabled = true;
        selectedForm.submit();
    });

    // Modal konfirmasi hapus permanen, dipakai pada tampilan HP dan desktop.
    const deleteModal = document.getElementById('deleteTemplateModal');
    const cancelDeleteButton = document.getElementById('cancelDeleteTemplate');
    const confirmDeleteButton = document.getElementById('confirmDeleteTemplate');
    let selectedDeleteForm = null;

    if (deleteModal && cancelDeleteButton && confirmDeleteButton) {
        document.querySelectorAll('.force-delete-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                selectedDeleteForm = form;
                confirmDeleteButton.disabled = false;
                deleteModal.classList.remove('hidden');
                deleteModal.classList.add('flex');

                if (window.lucide) {
                    window.lucide.createIcons();
                }

                cancelDeleteButton.focus();
            });
        });

        function closeDeleteModal() {
            deleteModal.classList.add('hidden');
            deleteModal.classList.remove('flex');
            selectedDeleteForm = null;
            confirmDeleteButton.disabled = false;
        }

        cancelDeleteButton.addEventListener('click', closeDeleteModal);

        deleteModal.addEventListener('click', function (event) {
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !deleteModal.classList.contains('hidden')) {
                closeDeleteModal();
            }
        });

        confirmDeleteButton.addEventListener('click', function () {
            if (!selectedDeleteForm) {
                return;
            }

            confirmDeleteButton.disabled = true;
            selectedDeleteForm.submit();
        });
    }
});
</script>
@endpush
@endsection

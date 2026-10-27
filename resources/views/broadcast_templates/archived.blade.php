@extends('Layouts.app')

@section('title', 'Arsip Template - Broadcast')
@section('page-title', 'Arsip Template')
@section('subtitle', 'Lihat dan pulihkan template pesan yang telah diarsipkan')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-extrabold text-slate-950">Arsip Template</h2>
            <p class="mt-1 text-sm text-slate-500">
                Template di sini tidak tampil pada daftar utama dan tidak dapat dipilih untuk campaign baru.
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('broadcast-campaigns.templates.index') }}" class="btn-secondary px-4 py-2">
                Kembali ke Template Pesan
            </a>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white shadow-card-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Nama</th>
                        <th class="px-5 py-3 font-semibold">Kategori</th>
                        <th class="px-5 py-3 font-semibold">Isi Pesan</th>
                        <th class="px-5 py-3 font-semibold">Cakupan</th>
                        <th class="px-5 py-3 font-semibold">Tanggal Diarsipkan</th>
                        <th class="px-5 py-3 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($templates as $template)
                        <tr class="align-top hover:bg-slate-50/60">
                            <td class="px-5 py-3 font-semibold text-slate-900">{{ $template->name }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $template->category }}</td>
                            <td class="px-5 py-3 text-slate-600">
                                <p class="max-w-lg whitespace-pre-wrap">{{ $template->content }}</p>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $template->branch?->name ?? 'Semua cabang' }}</td>
                            <td class="px-5 py-3 text-slate-500">
                                {{ $template->deleted_at?->format('d M Y H:i') ?? '-' }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST"
                                      action="{{ route('broadcast-campaigns.templates.restore', $template->id) }}"
                                      onsubmit="return confirm('Pulihkan template ini ke daftar utama?')">
                                    @csrf
                                    <button type="submit" class="btn-primary px-3 py-1.5 text-xs">
                                        <i data-lucide="undo-2" class="mr-1 inline h-3.5 w-3.5"></i>
                                        Pulihkan
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-sm text-slate-500">
                                Belum ada template yang diarsipkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($templates->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $templates->links() }}</div>
        @endif
    </div>
</div>
@endsection

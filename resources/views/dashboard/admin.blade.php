 
@extends('Layouts.app')

@section('title', 'Dashboard Admin Cabang')
@section('page-title', 'Dashboard')
@section('subtitle', 'Selamat datang kembali, ' . auth()->user()->name)

@section('content')

    @php
        $user = auth()->user();
        $branch = $user->branch ?? null;
        $branchInactive = $branch && !$branch->is_active;
    @endphp

    @if($branchInactive)
        <div class="mx-6 mt-4 rounded-xl border border-red-200 bg-red-50 p-5 text-red-800">
            <div class="flex items-start gap-3">
                <i data-lucide="alert-triangle" class="mt-0.5 h-6 w-6 shrink-0"></i>

                <div>
                    <p class="font-bold text-red-900">
                        Akun Cabang Dinonaktifkan
                    </p>

                    <p class="mt-1 text-sm">
                        Cabang tempat akun Anda terdaftar sedang dinonaktifkan oleh Super Administrator.
                        Silakan hubungi Super Administrator untuk mengaktifkan kembali akses cabang Anda.
                    </p>
                </div>
            </div>
        </div>
    @else
        @php($role = 'admin_toko')
        @include('dashboard._dashboard_common', ['role' => $role])
    @endif

@endsection
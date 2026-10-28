@extends('Layouts.app')

@section('title', 'Edit Profil — Pengaturan')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        cream: { 50:'#FDFCFA', 100:'#FAF7F2', 200:'#F3EDE4', 300:'#E8E2D8', 400:'#D6CBBC', 500:'#BEB09E' },
        stone: { 400:'#9A8A77', 500:'#7A6A55', 600:'#5C4E3A', 700:'#3D3120', 800:'#261E10', 900:'#1A1208' },
        copper: { 50:'#FDF4EC', 100:'#FAE6CA', 200:'#F3C98A', 300:'#E8A84E', 400:'#C27B3E', 500:'#A86528', 600:'#8A4F1A' },
      },
      fontFamily: {
        serif: ['Fraunces', 'Georgia', 'serif'],
        sans: ['DM Sans', 'system-ui', 'sans-serif'],
      },
    }
  }
}
</script>
<style>
  body { font-family: 'DM Sans', sans-serif; }

  ::-webkit-scrollbar { width: 4px; height: 4px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: #D6CBBC; border-radius: 99px; }

  .tab-panel { display: none; animation: panelIn .22s ease; }
  .tab-panel.active { display: block; }
  @keyframes panelIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

  .nav-item { transition: background .15s, color .15s; border-radius: 10px; }
  .nav-item:hover:not(.active):not(.danger) { background: #FAF7F2; }
  .nav-item.active:not(.danger) { background: #FDF4EC; color: #A86528; }
  .nav-item.active:not(.danger) .nav-icon { background: #FAE6CA; color: #A86528; }
  .nav-item.danger { color: #B91C1C; }
  .nav-item.danger:hover, .nav-item.danger.active { background: #FEF2F2; }

  .field-input { transition: border-color .15s, box-shadow .15s; background: #FDFCFA; }
  .field-input:focus { outline: none; border-color: #C27B3E; box-shadow: 0 0 0 3px rgba(194,123,62,.14); }
  .field-input.is-invalid { border-color: #DC2626 !important; }
  .field-input.is-invalid:focus { box-shadow: 0 0 0 3px rgba(220,38,38,.12) !important; }

  .pwd-toggle { transition: color .15s; }
  .pwd-toggle:hover { color: #C27B3E; }

  .btn-primary { transition: all .15s; }
  .btn-primary:hover { filter: brightness(.92); }
  .btn-primary:active { transform: scale(.97); }

  .mobile-nav-scroll { overflow-x: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
  .mobile-nav-scroll::-webkit-scrollbar { display: none; }

  .card { box-shadow: 0 1px 4px rgba(38,30,16,.06), 0 2px 10px rgba(38,30,16,.04); }

  .avatar-ring { background: linear-gradient(135deg, #E8A84E 0%, #C27B3E 50%, #8A4F1A 100%); padding: 2.5px; border-radius: 50%; }

  .strength-bar { height: 4px; border-radius: 99px; transition: width .3s, background .3s; }

  .invalid-msg { font-size: 12px; color: #DC2626; margin-top: 4px; }
</style>
@endpush

@section('content')

{{-- ══ PAGE HEADING ══ --}}
<div class="mb-7">
    <div class="flex items-center gap-1.5 mb-1.5">
        <span class="text-xs text-stone-400 font-medium">Dashboard</span>
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" class="text-stone-300">
            <path d="M4 2l4 4-4 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="text-xs text-stone-500 font-medium">Profil</span>
    </div>
    <h1 class="font-serif text-2xl lg:text-[28px] font-semibold text-stone-900 leading-tight">Pengaturan Profil</h1>
    <p class="text-stone-400 text-sm mt-1">Kelola informasi akun, keamanan, dan preferensi Anda.</p>
</div>

{{-- ══ SUCCESS TOAST ══ --}}
@if (session('status'))
<div id="successToast" class="mb-5 flex items-start gap-3 px-4 py-3 rounded-xl border text-sm"
     style="background:#F0FDF4; border-color:#BBF7D0; color:#166534;">
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" class="flex-shrink-0 mt-0.5">
        <circle cx="8" cy="8" r="7" fill="#22C55E"/>
        <path d="M5 8l2 2 4-4" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <span class="flex-1 font-medium">{{ session('status') }}</span>
    <button onclick="this.closest('#successToast').classList.add('hidden')" class="text-green-400 hover:text-green-600 leading-none">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
            <path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
    </button>
</div>
@endif

{{-- ══ MAIN GRID ══ --}}
<div class="flex flex-col lg:flex-row gap-5 lg:gap-6 items-start">

    {{-- ══ SIDEBAR ══ --}}
    <aside class="w-full lg:w-[276px] xl:w-[296px] flex-shrink-0">
        <div class="card bg-white rounded-2xl overflow-hidden border border-cream-300">

            
{{-- Profile header --}}
<div class="pt-6 pb-5 px-5 text-center"
style="background:linear-gradient(180deg,#FAF7F2 0%,#FFFFFF 100%)">

@php
   $avatarUrl = $user->avatar_url ?? null;

   if (!empty($avatarUrl) && !\Illuminate\Support\Str::startsWith($avatarUrl, ['http://', 'https://'])) {
       $avatarUrl = \Illuminate\Support\Facades\Storage::url($avatarUrl);
   }
@endphp

<div class="avatar-ring w-fit mx-auto mb-3.5">
   <div class="w-[68px] h-[68px] rounded-full flex items-center justify-center overflow-hidden"
        style="background:linear-gradient(135deg,#C27B3E,#3D3120)">

       @if (!empty($avatarUrl))
           <img
               src="{{ $avatarUrl }}"
               alt="Foto profil {{ $user->name }}"
               class="w-full h-full object-cover"
               onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
           >
       @endif

       <span
           class="text-white font-bold text-[22px] font-serif leading-none items-center justify-center {{ !empty($avatarUrl) ? 'hidden' : 'flex' }}"
           style="{{ !empty($avatarUrl) ? 'display:none;' : '' }}"
       >
           {{ strtoupper(substr($user->name ?? 'US', 0, 2)) }}
       </span>
   </div>
</div>

<h2 class="font-serif text-[17px] font-semibold text-stone-900 leading-tight mb-0.5">
   {{ $user->name }}
</h2>

<p class="text-stone-400 text-[13px] mb-3.5">
   {{ $user->email }}
</p>

@if ($user->branch)
   <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[12px] font-medium border"
         style="background:#FDF4EC; border-color:#F3C98A; color:#8A4F1A;">
       <svg width="11" height="11" viewBox="0 0 16 16" fill="none">
           <rect x="2" y="7" width="12" height="8" rx="1" stroke="currentColor" stroke-width="1.5"/>
           <path d="M5 7V5a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
       </svg>
       {{ $user->branch->name ?? '-' }}
   </span>
@endif
</div>


            <div class="border-t border-cream-200 mx-5"></div>

            {{-- Meta info --}}
            <div class="px-5 py-4 space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#F0FDF4;">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                            <rect x="2" y="2" width="12" height="13" rx="1.5" stroke="#16A34A" stroke-width="1.4"/>
                            <path d="M5 1v3M11 1v3M2 7h12" stroke="#16A34A" stroke-width="1.4" stroke-linecap="round"/>
                            <circle cx="6" cy="10.5" r="1" fill="#16A34A"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-widest text-stone-400 font-semibold">Bergabung sejak</div>
                        <div class="text-[13px] font-semibold text-stone-700 mt-0.5">
                            {{ $user->created_at->format('d M Y') }}
                        </div>
                    </div>
                </div>

                @if($user->role === 'sales')
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#FDF4EC;">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                            <path d="M8 2l1.5 4.5H14l-3.7 2.7 1.4 4.3L8 11.2l-3.7 2.3 1.4-4.3L2 6.5h4.5z" stroke="#C27B3E" stroke-width="1.3" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-widest text-stone-400 font-semibold">Total Poin</div>
                        <div class="text-[13px] font-semibold text-stone-700 mt-0.5">
                            {{ number_format($user->total_points, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
                @endif

                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#FDF4EC;">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                            <path d="M8 2l1.5 4.5H14l-3.7 2.7 1.4 4.3L8 11.2l-3.7 2.3 1.4-4.3L2 6.5h4.5z"
                                  stroke="#C27B3E" stroke-width="1.3" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-widest text-stone-400 font-semibold">Status Akun</div>
                        @if($user->is_active ?? true)
                            <div class="text-[13px] font-semibold mt-0.5" style="color:#16A34A;">● Aktif</div>
                        @else
                            <div class="text-[13px] font-semibold mt-0.5" style="color:#DC2626;">● Nonaktif</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="border-t border-cream-200 mx-5"></div>

            {{-- DESKTOP NAVIGATION (vertikal) --}}
            <nav class="p-3 hidden lg:block" id="desktopNav">
                <button class="nav-item active w-full flex items-center gap-3 px-3 py-3 text-left text-stone-700 font-medium" data-tab="info">
                    <span class="nav-icon w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors" style="background:#F3EDE4; color:#7A6A55;">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                            <circle cx="8" cy="5" r="2.5" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M3 13c0-2.76 2.24-5 5-5s5 2.24 5 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <div>
                        <div class="text-[13.5px] font-semibold leading-tight">Informasi Akun</div>
                        <div class="text-[11px] text-stone-400 mt-0.5">Nama &amp; alamat email</div>
                    </div>
                </button>
                <button class="nav-item w-full flex items-center gap-3 px-3 py-3 text-left text-stone-600 font-medium" data-tab="password">
                    <span class="nav-icon w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors" style="background:#FFFBEB; color:#D97706;">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                            <rect x="3" y="7" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M5 7V5a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                            <circle cx="8" cy="10.5" r="1" fill="currentColor"/>
                        </svg>
                    </span>
                    <div>
                        <div class="text-[13.5px] font-semibold leading-tight">Ubah Password</div>
                        <div class="text-[11px] text-stone-400 mt-0.5">Keamanan akun Anda</div>
                    </div>
                </button>
                <button class="nav-item danger w-full flex items-center gap-3 px-3 py-3 text-left font-medium" data-tab="danger">
                    <span class="nav-icon w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#FEF2F2; color:#DC2626;">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                            <path d="M8 6v3.5M8 11.5v.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M7.13 2.5L1.3 12.5a1 1 0 0 0 .87 1.5h11.66a1 1 0 0 0 .87-1.5L8.87 2.5a1 1 0 0 0-1.74 0z"
                                  stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div>
                        <div class="text-[13.5px] font-semibold leading-tight">Zona Berbahaya</div>
                        <div class="text-[11px] text-red-300 mt-0.5">Hapus akun permanen</div>
                    </div>
                </button>
            </nav>

            {{-- MOBILE NAVIGATION (horizontal scroll) --}}
            <div class="p-3 lg:hidden">
                <div class="mobile-nav-scroll flex gap-2 pb-0.5" id="mobileNav">
                    <button class="nav-item active flex-shrink-0 flex items-center gap-2 px-4 py-2.5 border border-copper-200 text-[13px] font-semibold text-copper-600"
                            style="background:#FDF4EC;" data-tab="info">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                            <circle cx="8" cy="5" r="2.5" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M3 13c0-2.76 2.24-5 5-5s5 2.24 5 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                        </svg>
                        Informasi
                    </button>
                    <button class="nav-item flex-shrink-0 flex items-center gap-2 px-4 py-2.5 border border-cream-300 text-[13px] font-medium text-stone-600" data-tab="password">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                            <rect x="3" y="7" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M5 7V5a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                        </svg>
                        Password
                    </button>
                    <button class="nav-item danger flex-shrink-0 flex items-center gap-2 px-4 py-2.5 border border-red-200 text-[13px] font-medium" data-tab="danger">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                            <path d="M8 6v3.5M8 11.5v.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M7.13 2.5L1.3 12.5a1 1 0 0 0 .87 1.5h11.66a1 1 0 0 0 .87-1.5L8.87 2.5a1 1 0 0 0-1.74 0z"
                                  stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                        </svg>
                        Bahaya
                    </button>
                </div>
            </div>

        </div>
    </aside>

    {{-- ══ MAIN CONTENT ══ --}}
    <div class="flex-1 min-w-0 space-y-5">

        {{-- ─── TAB 1: Informasi Akun ─── --}}
        <div class="tab-panel {{ $errors->has('name') || $errors->has('email') ? 'active' : (session('_tab') === 'info' || !session('_tab') ? 'active' : '') }}" id="panel-info">
            <div class="card bg-white rounded-2xl border border-cream-300 overflow-hidden">

                <div class="px-5 sm:px-6 py-4 border-b border-cream-200 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#FDF4EC;">
                        <svg width="17" height="17" viewBox="0 0 16 16" fill="none">
                            <path d="M11 2H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1z" stroke="#C27B3E" stroke-width="1.4"/>
                            <path d="M7 5.5h2M6 8h4M6 10.5h3" stroke="#C27B3E" stroke-width="1.4" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-stone-800 text-[15px] leading-tight">Edit Informasi Akun</h3>
                        <p class="text-stone-400 text-[12px] mt-0.5">Perbarui nama lengkap dan alamat email Anda</p>
                    </div>
                </div>

                <div class="px-5 sm:px-6 py-6">
                    <form method="POST"
                    action="{{ route('profile.update') }}"
                    enctype="multipart/form-data">
                  @csrf
                  @method('PATCH')

                        {{-- Nama --}}
                        <div>
                            <label for="name" class="block text-[13px] font-semibold text-stone-600 mb-2">
                                Nama Lengkap <span class="text-red-400 ml-0.5">*</span>
                            </label>
                            <div class="relative flex items-center">
                                <span class="absolute left-4 flex items-center justify-center h-full text-stone-400 pointer-events-none">
                                    <svg width="20" height="20" viewBox="0 0 16 16" fill="none">
                                        <circle cx="8" cy="5.5" r="2.5" stroke="currentColor" stroke-width="1.4"/>
                                        <path d="M2.5 13.5c0-3.04 2.46-5.5 5.5-5.5s5.5 2.46 5.5 5.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                                    </svg>
                                </span>
                                <input type="text" id="name" name="name"
                                    class="field-input w-full pr-4 py-2.5 border border-cream-300 rounded-xl text-[14px] text-stone-800 placeholder-stone-400 @error('name') is-invalid @enderror"
                                    value="{{ old('name', $user->name) }}"
                                    placeholder="Masukkan nama lengkap Anda" required autofocus
                                    style="padding-left: 52px !important;">
                            </div>
                            @error('name')
                                <div class="invalid-msg">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label for="email" class="block text-[13px] font-semibold text-stone-600 mb-2">
                                Alamat Email <span class="text-red-400 ml-0.5">*</span>
                            </label>
                            <div class="relative flex items-center">
                                <span class="absolute left-4 flex items-center justify-center h-full text-stone-400 pointer-events-none">
                                    <svg width="20" height="20" viewBox="0 0 16 16" fill="none">
                                        <rect x="2" y="4" width="12" height="9" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                                        <path d="M2 5.5l6 4 6-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <input type="email" id="email" name="email"
                                    class="field-input w-full pr-4 py-2.5 border border-cream-300 rounded-xl text-[14px] text-stone-800 placeholder-stone-400 @error('email') is-invalid @enderror"
                                    value="{{ old('email', $user->email) }}"
                                    placeholder="Masukkan alamat email" required
                                    style="padding-left: 52px !important;">
                            </div>
                            @error('email')
                                <div class="invalid-msg">{{ $message }}</div>
                            @enderror
                        </div>

 
{{-- Foto Profil --}}
<div class="mt-5 mb-6">
    <label class="block text-[13px] font-semibold text-stone-600 mb-3">
        Foto Profil
    </label>

    <div class="flex flex-wrap items-center gap-3">
        {{-- Tombol upload --}}
        <label
            for="avatar"
            class="inline-flex cursor-pointer items-center gap-2
                   rounded-lg border border-blue-200 bg-blue-50
                   px-4 py-2.5 text-sm font-semibold text-blue-600
                   transition hover:bg-blue-100"
        >
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 16V4m0 0L7 9m5-5 5 5M5 15v4a1 1 0 001 1h12a1 1 0 001-1v-4"/>
            </svg>
            Klik untuk upload foto profil
        </label>

        <input
            id="avatar"
            name="avatar"
            type="file"
            accept=".jpg,.jpeg,.png,.webp"
            class="hidden"
        >

        {{-- Tombol hapus foto --}}
        
        <button
            type="button"
            onclick="openDeleteAvatarModal()"
            class="inline-flex items-center gap-1.5 rounded-lg
                border border-red-200 bg-red-50 px-3 py-2.5
                text-sm font-medium text-red-600
                transition hover:bg-red-100"
            >
            <svg xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M6 7h12m-10 0V5h8v2m-9 0 1 13h8l1-13M10 11v5m4-5v5"/>
            </svg>
            Hapus Foto Profil
        </button>

    </div>

    <p class="mt-2 text-xs text-gray-500">
        JPG, PNG, atau WEBP. Maksimal 2 MB.
    </p>

    @error('avatar')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

                        {{-- Info notice --}}
                        <div class="flex items-start gap-3 px-4 py-3 rounded-xl border border-cream-300" style="background:#FAF7F2;">
                            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" class="flex-shrink-0 mt-0.5 text-copper-400">
                                <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.3"/>
                                <path d="M8 7v5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <circle cx="8" cy="4.5" r=".8" fill="currentColor"/>
                            </svg>
                            <p class="text-[12.5px] text-stone-500 leading-relaxed">
                                Perubahan pada alamat email akan memerlukan verifikasi ulang. Pastikan email yang Anda masukkan aktif dan dapat diakses.
                            </p>
                        </div>

                        {{-- Actions --}}
                        <div class="flex flex-col sm:flex-row justify-end gap-2.5 pt-1">
                            <a href="{{ route('profile.edit') }}"
                               class="w-full sm:w-auto flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border border-cream-300 text-[13.5px] font-medium text-stone-600 hover:bg-cream-100 transition-colors">
                                Reset
                            </a>
                            <button type="submit"
                                class="btn-primary w-full sm:w-auto flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-[13.5px] font-semibold text-white"
                                style="background:linear-gradient(135deg,#C27B3E,#A86528);">
                                <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                                    <path d="M13 5L6.5 11.5 3 8" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Simpan Perubahan
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        {{-- ─── TAB 2: Ubah Password ─── --}}
        <div class="tab-panel {{ $errors->has('current_password') || $errors->has('password') ? 'active' : '' }}" id="panel-password">
            <div class="card bg-white rounded-2xl border border-cream-300 overflow-hidden">

                <div class="px-5 sm:px-6 py-4 border-b border-cream-200 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#FFFBEB;">
                        <svg width="17" height="17" viewBox="0 0 16 16" fill="none">
                            <rect x="3" y="7" width="10" height="7.5" rx="1.5" stroke="#D97706" stroke-width="1.4"/>
                            <path d="M5.5 7V5A2.5 2.5 0 0 1 10.5 5v2" stroke="#D97706" stroke-width="1.4" stroke-linecap="round"/>
                            <circle cx="8" cy="10.5" r="1.2" fill="#D97706"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-stone-800 text-[15px] leading-tight">Ubah Password</h3>
                        <p class="text-stone-400 text-[12px] mt-0.5">Pastikan password baru minimal 8 karakter</p>
                    </div>
                </div>

                <div class="px-5 sm:px-6 py-6">
                    <form method="POST" action="{{ route('profile.password') }}" class="space-y-5">
                        @csrf
                        @method('PUT')

                        {{-- Password saat ini --}}
                        <div>
                            <label for="current_password" class="block text-[13px] font-semibold text-stone-600 mb-2">
                                Password Saat Ini <span class="text-red-400 ml-0.5">*</span>
                            </label>
                            <div class="relative flex items-center">
                                <span class="absolute left-4 flex items-center justify-center h-full text-stone-400 pointer-events-none">
                                    <svg width="20" height="20" viewBox="0 0 16 16" fill="none">
                                        <rect x="3" y="7" width="10" height="7.5" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                                        <path d="M5.5 7V5A2.5 2.5 0 0 1 10.5 5v2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                                    </svg>
                                </span>
                                <input type="password" id="current_password" name="current_password"
                                    class="field-input w-full pr-11 py-2.5 border border-cream-300 rounded-xl text-[14px] text-stone-800 placeholder-stone-400 @error('current_password') is-invalid @enderror"
                                    placeholder="Masukkan password saat ini" required
                                    style="padding-left: 52px !important;">
                                <button type="button" class="pwd-toggle absolute right-3.5 top-1/2 -translate-y-1/2 text-stone-400"
                                    data-target="current_password" aria-label="Tampilkan/sembunyikan password">
                                    <svg class="eye-icon" width="17" height="17" viewBox="0 0 16 16" fill="none">
                                        <ellipse cx="8" cy="8" rx="6" ry="4" stroke="currentColor" stroke-width="1.4"/>
                                        <circle cx="8" cy="8" r="1.8" stroke="currentColor" stroke-width="1.3"/>
                                    </svg>
                                </button>
                            </div>
                            @error('current_password')
                                <div class="invalid-msg">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Password baru + konfirmasi --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="password" class="block text-[13px] font-semibold text-stone-600 mb-2">
                                    Password Baru <span class="text-red-400 ml-0.5">*</span>
                                </label>
                                <div class="relative flex items-center">
                                <span class="absolute left-4 flex items-center justify-center h-full text-stone-400 pointer-events-none">
                                    <svg width="20" height="20" viewBox="0 0 16 16" fill="none">
                                        <path d="M10.5 2.5l3 3-7.5 7.5H3v-3l7.5-7.5z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <input type="password" id="password" name="password"
                                    class="field-input w-full pr-11 py-2.5 border border-cream-300 rounded-xl text-[14px] text-stone-800 placeholder-stone-400 @error('password') is-invalid @enderror"
                                    placeholder="Password baru" oninput="checkStrength(this.value)" required
                                    style="padding-left: 52px !important;">
                                    <button type="button" class="pwd-toggle absolute right-3.5 top-1/2 -translate-y-1/2 text-stone-400"
                                        data-target="password" aria-label="Tampilkan/sembunyikan password">
                                        <svg class="eye-icon" width="17" height="17" viewBox="0 0 16 16" fill="none">
                                            <ellipse cx="8" cy="8" rx="6" ry="4" stroke="currentColor" stroke-width="1.4"/>
                                            <circle cx="8" cy="8" r="1.8" stroke="currentColor" stroke-width="1.3"/>
                                        </svg>
                                    </button>
                                </div>
                                {{-- Strength bar --}}
                                <div class="mt-2 flex gap-1">
                                    <div class="strength-bar flex-1" id="bar1" style="background:#E8E2D8;"></div>
                                    <div class="strength-bar flex-1" id="bar2" style="background:#E8E2D8;"></div>
                                    <div class="strength-bar flex-1" id="bar3" style="background:#E8E2D8;"></div>
                                    <div class="strength-bar flex-1" id="bar4" style="background:#E8E2D8;"></div>
                                </div>
                                <div class="text-[11px] text-stone-400 mt-1" id="strengthLabel">Masukkan password baru</div>
                                @error('password')
                                    <div class="invalid-msg">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="block text-[13px] font-semibold text-stone-600 mb-2">
                                    Konfirmasi Password <span class="text-red-400 ml-0.5">*</span>
                                </label>
                                <div class="relative flex items-center">
                                <span class="absolute left-4 flex items-center justify-center h-full text-stone-400 pointer-events-none">
                                    <svg width="20" height="20" viewBox="0 0 16 16" fill="none">
                                        <path d="M13 5L6.5 11.5 3 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                        <rect x="1" y="1" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.3"/>
                                    </svg>
                                </span>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                    class="field-input w-full pr-11 py-2.5 border border-cream-300 rounded-xl text-[14px] text-stone-800 placeholder-stone-400"
                                    placeholder="Ulangi password baru" required
                                    style="padding-left: 52px !important;">
                                    <button type="button" class="pwd-toggle absolute right-3.5 top-1/2 -translate-y-1/2 text-stone-400"
                                        data-target="password_confirmation" aria-label="Tampilkan/sembunyikan password">
                                        <svg class="eye-icon" width="17" height="17" viewBox="0 0 16 16" fill="none">
                                            <ellipse cx="8" cy="8" rx="6" ry="4" stroke="currentColor" stroke-width="1.4"/>
                                            <circle cx="8" cy="8" r="1.8" stroke="currentColor" stroke-width="1.3"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Tips --}}
                        <div class="p-4 rounded-xl border border-amber-100" style="background:#FFFBEB;">
                            <div class="flex items-center gap-2 mb-2.5">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                                    <circle cx="8" cy="8" r="6.5" stroke="#D97706" stroke-width="1.3"/>
                                    <path d="M8 7v5" stroke="#D97706" stroke-width="1.5" stroke-linecap="round"/>
                                    <circle cx="8" cy="4.5" r=".8" fill="#D97706"/>
                                </svg>
                                <span class="text-[12px] font-bold text-amber-700">Tips Keamanan Password</span>
                            </div>
                            <ul class="space-y-1.5">
                                <li class="flex items-center gap-2 text-[12px] text-amber-600">
                                    <span class="w-4 h-4 rounded-full flex items-center justify-center flex-shrink-0 text-[9px] font-bold" style="background:#FDE68A; color:#92400E;">1</span>
                                    Minimal 8 karakter
                                </li>
                                <li class="flex items-center gap-2 text-[12px] text-amber-600">
                                    <span class="w-4 h-4 rounded-full flex items-center justify-center flex-shrink-0 text-[9px] font-bold" style="background:#FDE68A; color:#92400E;">2</span>
                                    Kombinasi huruf besar, kecil, dan angka
                                </li>
                                <li class="flex items-center gap-2 text-[12px] text-amber-600">
                                    <span class="w-4 h-4 rounded-full flex items-center justify-center flex-shrink-0 text-[9px] font-bold" style="background:#FDE68A; color:#92400E;">3</span>
                                    Tambahkan karakter khusus (!@#$%) untuk keamanan ekstra
                                </li>
                            </ul>
                        </div>

                        <div class="flex flex-col sm:flex-row justify-end pt-1">
                            <button type="submit"
                                class="btn-primary w-full sm:w-auto flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-[13.5px] font-semibold text-white"
                                style="background:linear-gradient(135deg,#D97706,#B45309);">
                                <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                                    <rect x="3" y="7" width="10" height="7.5" rx="1.5" stroke="white" stroke-width="1.4"/>
                                    <path d="M5.5 7V5A2.5 2.5 0 0 1 10.5 5v2" stroke="white" stroke-width="1.4" stroke-linecap="round"/>
                                    <circle cx="8" cy="10.5" r="1.2" fill="white"/>
                                </svg>
                                Update Password
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        {{-- ─── TAB 3: Zona Berbahaya ─── --}}
        <div class="tab-panel" id="panel-danger">
            <div class="card bg-white rounded-2xl border border-cream-300 overflow-hidden">

                <div class="px-5 sm:px-6 py-4 border-b border-red-100 flex items-center gap-3" style="background:#FEF2F2;">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#FEE2E2;">
                        <svg width="17" height="17" viewBox="0 0 16 16" fill="none">
                            <path d="M8 6v3.5M8 11.5v.5" stroke="#DC2626" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M7.13 2.5L1.3 12.5a1 1 0 0 0 .87 1.5h11.66a1 1 0 0 0 .87-1.5L8.87 2.5a1 1 0 0 0-1.74 0z"
                                  stroke="#DC2626" stroke-width="1.4" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-red-700 text-[15px] leading-tight">Zona Berbahaya</h3>
                        <p class="text-red-300 text-[12px] mt-0.5">Tindakan di sini bersifat permanen dan tidak dapat diurungkan</p>
                    </div>
                </div>

                <div class="px-5 sm:px-6 py-6">
                    <div class="rounded-xl border-2 border-red-100 overflow-hidden">
                        <div class="px-5 py-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4" style="background:#FFFAFA;">
                            <div class="flex items-start gap-3.5">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#FEE2E2;">
                                    <svg width="18" height="18" viewBox="0 0 16 16" fill="none">
                                        <path d="M3 5h10M6 5V3.5A.5.5 0 0 1 6.5 3h3a.5.5 0 0 1 .5.5V5M5 5l.75 8h4.5L11 5"
                                              stroke="#DC2626" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-red-700 text-[14px] mb-1">Hapus Akun Ini</h4>
                                    <p class="text-stone-500 text-[12.5px] leading-relaxed max-w-md">
                                        Setelah dihapus, semua data akun Anda — termasuk riwayat, pengaturan, dan file — akan hilang secara permanen dari sistem kami.
                                    </p>
                                </div>
                            </div>
                            <button type="button" onclick="openDeleteModal()"
                                class="w-full sm:w-auto flex-shrink-0 flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border-2 border-red-300 text-[13.5px] font-semibold text-red-600 hover:bg-red-50 transition-colors">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                                    <path d="M3 5h10M6 5V3.5A.5.5 0 0 1 6.5 3h3a.5.5 0 0 1 .5.5V5M5 5l.75 8h4.5L11 5"
                                          stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Hapus Akun
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- end main content --}}
</div>{{-- end grid --}}

{{-- ══ MODAL HAPUS AKUN ══ --}}
<div id="deleteModal"
     style="display:none; position:fixed; inset:0; z-index:50; padding:16px; align-items:center; justify-content:center; background:rgba(26,18,8,.4); backdrop-filter:blur(6px);">
    <div class="card bg-white rounded-2xl border border-cream-300 w-full max-w-md overflow-hidden" style="position:relative;">

        <div class="px-5 py-4 border-b border-cream-200 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:#FEE2E2;">
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                        <path d="M3 5h10M6 5V3.5A.5.5 0 0 1 6.5 3h3a.5.5 0 0 1 .5.5V5M5 5l.75 8h4.5L11 5"
                              stroke="#DC2626" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h5 class="font-semibold text-red-700 text-[15px]">Hapus Akun</h5>
            </div>
            <button onclick="closeDeleteModal()"
                class="w-8 h-8 rounded-lg hover:bg-cream-100 flex items-center justify-center text-stone-400 hover:text-stone-600 transition-colors">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <div class="px-5 py-5 space-y-4">
            <div class="flex items-start gap-3 p-3.5 rounded-xl border border-red-100" style="background:#FEF2F2;">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" class="flex-shrink-0 mt-0.5">
                    <path d="M8 6v3.5M8 11.5v.5" stroke="#DC2626" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M7.13 2.5L1.3 12.5a1 1 0 0 0 .87 1.5h11.66a1 1 0 0 0 .87-1.5L8.87 2.5a1 1 0 0 0-1.74 0z"
                          stroke="#DC2626" stroke-width="1.4" stroke-linejoin="round"/>
                </svg>
                <p class="text-[12.5px] text-red-700 leading-relaxed">
                    Tindakan ini <strong>tidak dapat dibatalkan</strong>. Semua data akun akan dihapus secara permanen dari server kami.
                </p>
            </div>

            <form method="POST" action="{{ route('profile.destroy') }}" id="deleteAccountForm">
                @csrf
                @method('DELETE')
                <label class="block text-[13px] font-semibold text-stone-600 mb-2">
                    Konfirmasi dengan Password <span class="text-red-400 ml-0.5">*</span>
                </label>
                <div class="relative flex items-center">
                    <span class="absolute left-4 flex items-center justify-center h-full text-stone-400 pointer-events-none">
                        <svg width="20" height="20" viewBox="0 0 16 16" fill="none">
                            <rect x="3" y="7" width="10" height="7.5" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M5.5 7V5A2.5 2.5 0 0 1 10.5 5v2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <input type="password" id="delete_password" name="password"
                                    class="field-input w-full pr-11 py-2.5 border border-cream-300 rounded-xl text-[14px] text-stone-800 placeholder-stone-400 @error('password', 'userDeletion') is-invalid @enderror"
                                    placeholder="Masukkan password Anda" required
                                    style="padding-left: 52px !important;">
                    <button type="button" class="pwd-toggle absolute right-3.5 top-1/2 -translate-y-1/2 text-stone-400"
                        data-target="delete_password" aria-label="Tampilkan/sembunyikan">
                        <svg class="eye-icon" width="17" height="17" viewBox="0 0 16 16" fill="none">
                            <ellipse cx="8" cy="8" rx="6" ry="4" stroke="currentColor" stroke-width="1.4"/>
                            <circle cx="8" cy="8" r="1.8" stroke="currentColor" stroke-width="1.3"/>
                        </svg>
                    </button>
                </div>
                @error('password', 'userDeletion')
                    <div class="invalid-msg">{{ $message }}</div>
                @enderror
            </form>
        </div>

        <div class="px-5 py-4 border-t border-cream-200 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
            <button onclick="closeDeleteModal()"
                class="w-full sm:w-auto flex items-center justify-center px-5 py-2.5 rounded-xl border border-cream-300 text-[13.5px] font-medium text-stone-600 hover:bg-cream-100 transition-colors">
                Batal
            </button>
            <button type="submit" form="deleteAccountForm"
                class="btn-primary w-full sm:w-auto flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-[13.5px] font-semibold text-white"
                style="background:linear-gradient(135deg,#DC2626,#B91C1C);">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                    <path d="M3 5h10M6 5V3.5A.5.5 0 0 1 6.5 3h3a.5.5 0 0 1 .5.5V5M5 5l.75 8h4.5L11 5"
                          stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Ya, Hapus Akun Saya
            </button>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
/* ── Tab switching ── */
function switchTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');

    document.querySelectorAll('#desktopNav .nav-item').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tab);
    });

    document.querySelectorAll('#mobileNav .nav-item').forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = '';
        btn.style.borderColor = '';
        btn.style.color = '';

        if (btn.dataset.tab === tab) {
            btn.classList.add('active');
            if (tab === 'danger') {
                btn.style.background = '#FEF2F2';
                btn.style.borderColor = '#FCA5A5';
                btn.style.color = '#DC2626';
            } else {
                btn.style.background = '#FDF4EC';
                btn.style.borderColor = '#F3C98A';
                btn.style.color = '#8A4F1A';
            }
        } else if (btn.classList.contains('danger')) {
            btn.style.borderColor = '#FCA5A5';
            btn.style.color = '#DC2626';
        } else {
            btn.style.borderColor = '#E8E2D8';
            btn.style.color = '#5C4E3A';
        }
    });
}

document.querySelectorAll('.nav-item[data-tab]').forEach(btn => {
    btn.addEventListener('click', () => switchTab(btn.dataset.tab));
});

/* ── Auto-open tab berdasarkan error validasi ── */
// @if ($errors->has('current_password') || ($errors->has('password') && old('_method') !== 'DELETE'))
    switchTab('password');
// @elseif ($errors->has('name') || $errors->has('email'))
    switchTab('info');
// @endif

/* ── Auto-buka modal jika ada error delete ── */
// @if ($errors->hasAny(['password']) && old('_method') === 'DELETE')
    switchTab('danger');
    document.addEventListener('DOMContentLoaded', function () {
        openDeleteModal();
    });
// @endif

/* ── Password toggle ── */
document.querySelectorAll('.pwd-toggle').forEach(btn => {
    btn.addEventListener('click', function () {
        const input = document.getElementById(this.dataset.target);
        const isText = input.type === 'text';
        input.type = isText ? 'password' : 'text';
        const svg = this.querySelector('svg');
        svg.innerHTML = isText
            ? '<ellipse cx="8" cy="8" rx="6" ry="4" stroke="currentColor" stroke-width="1.4"/><circle cx="8" cy="8" r="1.8" stroke="currentColor" stroke-width="1.3"/>'
            : '<ellipse cx="8" cy="8" rx="6" ry="4" stroke="currentColor" stroke-width="1.4"/><line x1="2" y1="2" x2="14" y2="14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>';
    });
});

/* ── Password strength ── */
function checkStrength(val) {
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const active = ['#F87171','#FBBF24','#34D399','#22C55E'];
    const labels = ['Masukkan password baru','Lemah','Cukup','Kuat','Sangat kuat'];
    const labelColors = ['#9A8A77','#EF4444','#D97706','#10B981','#16A34A'];

    for (let i = 1; i <= 4; i++) {
        document.getElementById('bar' + i).style.background = i <= score ? active[score - 1] : '#E8E2D8';
    }
    const lbl = document.getElementById('strengthLabel');
    lbl.textContent = val.length === 0 ? labels[0] : labels[score];
    lbl.style.color  = val.length === 0 ? '#9A8A77' : labelColors[score];
}

/* ── Modal ── */
function openDeleteModal() {
    const m = document.getElementById('deleteModal');
    m.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('deleteModal').addEventListener('click', function (e) {
    if (e.target === this) closeDeleteModal();
});
</script>

{{-- Modal konfirmasi hapus foto profil --}}
<div id="deleteAvatarModal"
     class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50 p-4">

    <div class="w-full max-w-md rounded-xl bg-white p-7 text-center shadow-2xl">

        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center
                    rounded-full border-2 border-orange-300 text-orange-400">
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="h-10 w-10" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 8v4m0 4h.01M10.3 3.9 2.8 17a2 2 0 001.7 3h15a2 2 0 001.7-3l-7.5-13.1a2 2 0 00-3.4 0z"/>
            </svg>
        </div>

        <h2 class="mb-3 text-2xl font-semibold text-stone-700">
            Hapus Foto Profil
        </h2>

        <p class="mb-6 text-sm leading-relaxed text-stone-500">
            Apakah Anda yakin ingin menghapus foto profil?
            Foto yang dihapus tidak dapat dipulihkan.
        </p>

        <div class="flex justify-center gap-3">
            <button type="button"
                    onclick="closeDeleteAvatarModal()"
                    class="rounded-md bg-slate-500 px-5 py-2.5
                           text-sm font-semibold text-white hover:bg-slate-600">
                Batal
            </button>

            <button type="button"
                    onclick="confirmDeleteAvatar()"
                    class="rounded-md bg-red-600 px-5 py-2.5
                           text-sm font-semibold text-white hover:bg-red-700">
                Ya, Hapus
            </button>
        </div>
    </div>
</div>

<script>
    function openDeleteAvatarModal() {
        const modal = document.getElementById('deleteAvatarModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeDeleteAvatarModal() {
        const modal = document.getElementById('deleteAvatarModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function confirmDeleteAvatar() {
        const form = document.querySelector('input[name="avatar"]')
            ?.closest('form');

        if (!form) return;

        let deleteInput = form.querySelector(
            'input[name="delete_avatar"][type="hidden"]'
        );

        if (!deleteInput) {
            deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_avatar';
            deleteInput.value = '1';
            form.appendChild(deleteInput);
        }

        form.requestSubmit();
    }
</script>

@endpush
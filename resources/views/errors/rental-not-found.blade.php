<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Tidak Ditemukan - SewaJas</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 flex items-center justify-center p-6">

    <div class="w-full max-w-lg">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 text-center">

            <div class="mx-auto w-20 h-20 rounded-full bg-blue-50 flex items-center justify-center mb-6">
                <svg
                    class="w-10 h-10 text-blue-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v3.75m0 3.75h.007M10.29 3.86l-7.06 12.25A1.5 1.5 0 004.53 18.5h14.94a1.5 1.5 0 001.3-2.25L13.71 3.86a2 2 0 00-3.42 0z"
                    />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-slate-800 mb-3">
                Data Penyewaan Tidak Ditemukan
            </h1>

            <p class="text-sm text-slate-500 leading-6 mb-6">
                QR Code tersebut bukan milik cabang Anda atau data penyewaan
                tidak tersedia untuk akun Anda.
            </p>

            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6">
                <p class="text-sm text-blue-700">
                    Silakan scan QR Code penyewaan yang terdaftar
                    pada cabang Anda.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">

                <a
                    href="{{ route('rentals.scan') }}"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition"
                >
                    Scan QR Lagi
                </a>

                <a
                    href="{{ route('rentals.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-slate-200 bg-white text-slate-700 text-sm font-semibold hover:bg-slate-50 transition"
                >
                    Kembali ke Penyewaan
                </a>

            </div>

        </div>

        <p class="text-center text-xs text-slate-400 mt-5">
            SewaJas
        </p>
    </div>

</body>
</html>
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Notification;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


/**
 * Hapus notifikasi yang umurnya lebih dari 3 bulan.
 * Jadwal penghapusan dijalankan setiap 3 bulan.
 */
Schedule::call(function () {
    Notification::where('created_at', '<', now()->subMonths(3))->delete();
})
    ->name('reset-semua-notifikasi')
    ->quarterly()
    ->withoutOverlapping();


/**
 * Simulasi penghapusan tanpa menghapus data.
 */
Artisan::command('notifikasi:simulasi-reset', function () {
    $batasWaktu = now()->subMonths(3);

    $notifikasiLama = Notification::where('created_at', '<', $batasWaktu)->count();

    $notifikasiDipertahankan = Notification::where('created_at', '>=', $batasWaktu)->count();

    $this->info('SIMULASI RESET NOTIFIKASI');
    $this->info("Notifikasi berumur lebih dari 3 bulan yang akan dihapus: {$notifikasiLama}");
    $this->info("Notifikasi yang akan dipertahankan: {$notifikasiDipertahankan}");
    $this->info('Mode simulasi: tidak ada data yang dihapus.');
})->purpose('Simulasi reset notifikasi tanpa menghapus data');

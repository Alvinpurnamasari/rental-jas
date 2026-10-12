<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureBranchScope
 * Memastikan user yang bukan super_admin hanya bisa akses
 * data yang sesuai dengan branch_id mereka.
 */
class EnsureBranchScope
{
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();

    if (!$user) {
        return redirect()->route('login');
    }

    // Periksa status akun pengguna
    if (!$user->is_active) {
        auth()->logout();

        return redirect()->route('login')
            ->with('error', 'Akun Anda telah dinonaktifkan oleh admin.');
    }

    // Super Admin tidak dibatasi oleh status cabang
    if ($user->isSuperAdmin()) {
        return $next($request);
    }

    // Periksa status cabang utama pengguna
    if ($user->branch_id) {
        $branch = \App\Models\Branch::find($user->branch_id);

        if (!$branch || !$branch->is_active) {
            // Dashboard tetap bisa dibuka untuk menampilkan pemberitahuan
            if ($request->routeIs('dashboard')) {
                return $next($request);
            }

            // Akses operasional ditolak
            return redirect()->route('dashboard')
                ->with('error', 'Cabang Anda telah dinonaktifkan oleh Super Admin. Hubungi admin untuk mengaktifkan kembali.');
        }
    }

    if (
        $user->isAdminToko()
        || $user->branch_access_type === 'all'
    ) {
        return $next($request);
    }

    if (
        is_null($user->branch_id)
        && !$user->accessibleBranches()->exists()
    ) {
        abort(
            403,
            'Akun Anda belum dikaitkan dengan cabang manapun. Hubungi Super Admin.'
        );
    }

    return $next($request);
}

}

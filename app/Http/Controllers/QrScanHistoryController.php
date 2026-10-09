<?php

namespace App\Http\Controllers;

use App\Models\QrScanHistory;
use Illuminate\Http\Request;

class QrScanHistoryController extends Controller
{
    public function index(Request $request)
{
    $query = QrScanHistory::where('user_id', $request->user()->id)
        ->latest('scanned_at');

    if (!$request->boolean('all')) {
        $query->take(10);
    }

    return response()->json($query->get());
}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $history = QrScanHistory::create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
            'scanned_at' => now(),
        ]);

        return response()->json($history, 201);
    }

    /**
     * Hapus satu riwayat scan milik user yang sedang login.
     */
    public function destroy(Request $request, QrScanHistory $history)
    {
        if ($history->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Tidak diizinkan menghapus riwayat ini.'
            ], 403);
        }

        $history->delete();

        return response()->json([
            'message' => 'Riwayat scan berhasil dihapus.'
        ]);
    }

    /**
     * Hapus seluruh riwayat scan milik user yang sedang login.
     */
    public function destroyAll(Request $request)
    {
        QrScanHistory::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'message' => 'Semua riwayat scan berhasil dihapus.'
        ]);
    }
}
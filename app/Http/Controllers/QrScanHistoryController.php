<?php

namespace App\Http\Controllers;

use App\Models\QrScanHistory;
use Illuminate\Http\Request;

class QrScanHistoryController extends Controller
{
    public function index(Request $request)
    {
        $histories = QrScanHistory::where('user_id', $request->user()->id)
            ->latest('scanned_at')
            ->take(10)
            ->get();

        return response()->json($histories);
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
}
<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BroadcastController extends Controller
{
    public function __construct(private NotificationService $notificationService)
    {
        $this->middleware(\App\Http\Middleware\CheckRole::class . ':super_admin,admin_toko')
            ->only(['index', 'store', 'destroy']);
    }

    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $broadcasts = Notification::query()
            ->where('type', 'system')
            ->where('title', 'like', 'Broadcast:%')
            ->when(!$user->isSuperAdmin() && $user->branch_access_type === 'specific', fn (Builder $q) => $q->whereIn('branch_id', $user->accessible_branch_ids))
            ->latest()
            ->limit(40)
            ->get();

        $branches = $user->isSuperAdmin()
            ? Branch::orderBy('name')->get(['id', 'name'])
            : Branch::whereIn('id', $user->accessible_branch_ids)->get(['id', 'name']);

        $roleOptions = [
            User::ROLE_ADMIN_TOKO => 'Admin Cabang',
            User::ROLE_SALES => 'Sales',
        ];

        if ($user->isSuperAdmin()) {
            $roleOptions = [User::ROLE_SUPER_ADMIN => 'Super Admin'] + $roleOptions;
        }

        return view('broadcasts.index', compact('broadcasts', 'branches', 'roleOptions'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:1200'],
            'target' => ['required', Rule::in(['all', 'role', 'branch'])],
            'role' => ['nullable', Rule::in([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN_TOKO, User::ROLE_SALES])],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'action_url' => ['nullable', 'url', 'max:255'],
        ]);

        $recipients = User::query()->active();

        if (!$user->isSuperAdmin() && $user->branch_access_type === 'specific') {
            $recipients->whereIn('branch_id', $user->accessible_branch_ids)
                ->whereIn('role', [User::ROLE_ADMIN_TOKO, User::ROLE_SALES]);
            $validated['branch_id'] = $user->branch_id;
        }

        if ($validated['target'] === 'role') {
            $recipients->where('role', $validated['role']);
        }

        if ($validated['target'] === 'branch') {
            $branchId = $user->isSuperAdmin() ? ($validated['branch_id'] ?? null) : $user->branch_id;
            if ($branchId !== null) {
                $recipients->where('branch_id', $branchId);
            }
            $validated['branch_id'] = $branchId;
        }

        if ($validated['target'] === 'all' && !$user->isSuperAdmin()) {
            $validated['branch_id'] = $user->branch_id;
        }

        $recipientIds = $recipients->pluck('id')->all();

        if ($recipientIds === []) {
            return back()
                ->withInput()
                ->with('error', 'Tidak ada penerima yang cocok dengan target broadcast.');
        }

        $this->notificationService->broadcast(
            $recipientIds,
            'system',
            'Broadcast: ' . $validated['title'],
            $validated['message'],
            [
                'broadcast' => true,
                'sender_id' => $user->id,
                'sender_name' => $user->name,
                'target' => $validated['target'],
                'role' => $validated['role'] ?? null,
                'branch_id' => $validated['branch_id'] ?? null,
                'recipient_count' => count($recipientIds),
            ],
            $validated['branch_id'] ?? null,
            $validated['action_url'] ?? null,
            'send',
            'blue'
        );

        return redirect()
            ->route('broadcasts.index')
            ->with('success', 'Broadcast berhasil dikirim ke ' . count($recipientIds) . ' pengguna.');
    }

    public function destroy(Request $request, Notification $broadcast)
    {
        $user = $request->user();
        if (!$user || (!$user->isSuperAdmin() && !$user->isAdminToko())) {
            abort(403, 'Unauthorized action.');
        }

        if (!$broadcast->title || !str_starts_with($broadcast->title, 'Broadcast:')) {
            abort(404);
        }

        $broadcast->delete();

        return redirect()->route('broadcasts.index')
            ->with('success', 'Broadcast berhasil dihapus.');
    }
}

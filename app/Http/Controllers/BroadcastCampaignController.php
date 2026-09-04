<?php

namespace App\Http\Controllers;

use App\Models\Broadcast\BroadcastCampaign;
use App\Models\Broadcast\BroadcastLog;
use App\Models\Broadcast\BroadcastProviderConfig;
use App\Models\Broadcast\BroadcastTemplate;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BroadcastCampaignController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $campaigns = BroadcastCampaign::query()
            ->when(!$user->isSuperAdmin(), fn ($q) => $q->where('branch_id', $user->branch_id))
            ->latest()
            ->paginate(20);

        return view('broadcast_campaigns.index', compact('campaigns'));
    }

    public function create(Request $request)
    {
        $user = $request->user();

        $templates = BroadcastTemplate::query()
            ->where('is_active', true)
            ->when(!$user->isSuperAdmin(), fn ($q) => $q->where('branch_id', $user->branch_id))
            ->get(['id', 'name', 'category', 'variables']);

        $providers = BroadcastProviderConfig::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('priority')
            ->get(['id', 'provider_key', 'label', 'is_default']);

        $branches = $user->isSuperAdmin()
            ? Branch::orderBy('name')->get(['id', 'name'])
            : Branch::where('id', $user->branch_id)->get(['id', 'name']);

        $customers = Customer::query()
            ->when(!$user->isSuperAdmin(), fn ($q) => $q->where('branch_id', $user->branch_id))
            ->get(['id', 'name', 'phone', 'branch_id']);

        $users = User::query()
            ->where('is_active', true)
            ->when(!$user->isSuperAdmin(), fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($user->isSuperAdmin(), fn ($q) => $q->where('id', '!=', $user->id))
            ->when($user->isAdminToko(), function ($q) use ($user) {
                $q->whereIn('role', [User::ROLE_ADMIN_TOKO, User::ROLE_SALES])
                  ->where('id', '!=', $user->id);
            })
            ->get(['id', 'name', 'phone', 'role', 'branch_id']);

        return view('broadcast_campaigns.create', [
            'templates' => $templates,
            'providers' => $providers,
            'branches' => $branches,
            'customers' => $customers,
            'users' => $users,
            'channels' => [
                ['value' => 'in_app', 'label' => 'Notifikasi In-App'],
                ['value' => 'whatsapp', 'label' => 'WhatsApp'],
            ],
            'recipientTypeOptions' => [
                ['value' => 'customer', 'label' => 'Customer'],
                ['value' => 'user', 'label' => 'User / Sales'],
                ['value' => 'both', 'label' => 'Customer + User'],
            ],
            'targetTypeOptions' => [
                ['value' => 'all', 'label' => 'Semua'],
                ['value' => 'branch', 'label' => 'Per Cabang'],
                ['value' => 'sales', 'label' => 'Sales'],
                ['value' => 'rental_status', 'label' => 'Status Rental'],
                ['value' => 'product', 'label' => 'Produk'],
                ['value' => 'category', 'label' => 'Kategori'],
                ['value' => 'customer', 'label' => 'Pilih Customer'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $channel = $request->input('channel');
        $providerRule = $channel === 'whatsapp'
            ? ['required', 'string', 'max:120']
            : ['nullable', 'string', 'max:120'];

        $customMessageRule = $channel === 'whatsapp'
            ? ['required', 'array', 'min:1']
            : ['nullable', 'string', 'max:2000'];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'channel' => ['required', 'in:in_app,whatsapp'],
            'provider' => $providerRule,
            'recipient_type' => ['required', 'in:customer,user,both'],
            'target_type' => ['required', 'in:all,branch,sales,rental_status,product,category,customer'],
            'template_id' => ['nullable', 'exists:broadcast_templates,id'],
            'custom_message' => $customMessageRule,
            'message_template' => ['nullable', 'string', 'max:2000'],
            'target_filters' => ['nullable', 'array'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'recipient_ids' => ['nullable', 'array'],
            'recipient_ids.*' => ['integer'],
            'delay_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ]);

        if ($channel === 'whatsapp') {
            if ($validated['recipient_type'] !== 'customer') {
                return back()->withErrors(['channel' => 'Channel WhatsApp hanya bisa digunakan untuk target Customer.'])->withInput();
            }
            if ($validated['target_type'] === 'sales') {
                return back()->withErrors(['channel' => 'Channel WhatsApp tidak mendukung target Sales.'])->withInput();
            }
        }

        if ($channel === 'in_app') {
            if ($validated['recipient_type'] !== 'user') {
                return back()->withErrors(['channel' => 'Channel Notifikasi In-App hanya bisa digunakan untuk target User/Sales.'])->withInput();
            }

            $selectedUserIds = $validated['target_filters']['recipient_ids'] ?? [];
            
            if (!empty($selectedUserIds)) {
                $allowedUserIds = User::query()
                    ->where('is_active', true)
                    ->when(!$user->isSuperAdmin(), fn ($q) => $q->where('branch_id', $user->branch_id))
                    ->when($user->isSuperAdmin(), fn ($q) => $q->where('id', '!=', $user->id))
                    ->when($user->isAdminToko(), function ($q) use ($user) {
                        $q->whereIn('role', [User::ROLE_ADMIN_TOKO, User::ROLE_SALES])
                          ->where('id', '!=', $user->id);
                    })
                    ->pluck('id')
                    ->toArray();
                
                $invalidUsers = array_diff($selectedUserIds, $allowedUserIds);
                
                if (!empty($invalidUsers)) {
                    return back()->withErrors(['recipient_ids' => 'Anda tidak memiliki akses untuk memilih beberapa user yang dipilih.'])->withInput();
                }
            }
        }

        $channels = [$channel];

        $validated['created_by'] = $user->id;
        $validated['branch_id'] = $validated['branch_id'] ?? $user->branch_id;
        $validated['status'] = $validated['scheduled_at'] ?? null ? 'scheduled' : 'draft';
        $validated['type'] = 'manual';
        $validated['channels'] = $channels;
        $validated['provider'] = $validated['provider'] ?? 'in_app';
        $validated['delay_seconds'] = (int) ($validated['delay_seconds'] ?? 0);

        $campaign = BroadcastCampaign::create($validated);

        if (!$campaign->scheduled_at) {
            $campaign->queueMessages();
        }

        return redirect()
            ->route('broadcast-campaigns.show', $campaign)
            ->with('success', 'Campaign broadcast berhasil dibuat.');
    }

    public function show(Request $request, BroadcastCampaign $broadcastCampaign)
    {
        $campaign = $broadcastCampaign->load(['logs.recipient', 'template', 'creator', 'branch']);

        return view('broadcast_campaigns.show', compact('campaign'));
    }

    public function destroy(Request $request, BroadcastCampaign $broadcastCampaign)
    {
        $user = $request->user();

        if (!$user->isSuperAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        DB::transaction(function () use ($broadcastCampaign) {
            BroadcastLog::where('campaign_id', $broadcastCampaign->id)->delete();
            $broadcastCampaign->delete();
        });

        return response()->json(['status' => 'ok']);
    }

    public function handleOptOut(Request $request)
    {
        $token = $request->header('X-Worker-Token') ?? $request->query('token');
        if ($token !== env('BROADCAST_WORKER_TOKEN')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'phone' => ['required', 'string'],
            'type' => ['required', 'in:customer,user'],
        ]);

        $phone = preg_replace('/\D/', '', $validated['phone']);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        if ($validated['type'] === 'customer') {
            $customer = Customer::where('phone', $phone)->orWhere('phone', '+' . $phone)->first();
            if ($customer) {
                $customer->update(['opt_out' => true]);
                return response()->json(['status' => 'ok', 'message' => 'Customer opted out']);
            }
        } else {
            $user = User::where('phone', $phone)->orWhere('phone', '+' . $phone)->first();
            if ($user) {
                $user->update(['opt_out' => true]);
                return response()->json(['status' => 'ok', 'message' => 'User opted out']);
            }
        }

        return response()->json(['status' => 'not_found'], 404);
    }

    public function checkDailyLimit(Request $request)
    {
        $token = $request->header('X-Worker-Token') ?? $request->query('token');
        if ($token !== env('BROADCAST_WORKER_TOKEN')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $dailyLimit = (int) env('BROADCAST_DAILY_LIMIT', 50);
        $todaySent = \App\Models\Broadcast\BroadcastLog::whereIn('status', ['submitted', 'sent', 'delivered', 'read'])
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return response()->json([
            'limit' => $dailyLimit,
            'sent_today' => $todaySent,
            'remaining' => max(0, $dailyLimit - $todaySent),
        ]);
    }

    public function updateMessageStatus(Request $request)
    {
        $token = $request->header('X-Worker-Token') ?? $request->query('token');
        if ($token !== env('BROADCAST_WORKER_TOKEN')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'provider_message_id' => ['required', 'string'],
            'phone' => ['nullable', 'string'],
            'status' => ['required', 'in:submitted,sent,delivered,read,failed'],
            'error_message' => ['nullable', 'string'],
        ]);

        $log = BroadcastLog::where('provider_message_id', $validated['provider_message_id'])->first();

        if (!$log) {
            return response()->json(['error' => 'Log not found'], 404);
        }

        $log->update([
            'status' => $validated['status'],
            'error_message' => $validated['error_message'],
        ]);

        if (in_array($validated['status'], ['delivered', 'read'])) {
            $log->update(['sent_at' => $log->sent_at ?? now()]);
        }

        Log::channel('broadcast')->info('Worker status update received', [
            'log_id' => $log->id,
            'campaign_id' => $log->campaign_id,
            'provider_message_id' => $validated['provider_message_id'],
            'status' => $validated['status'],
            'phone' => $validated['phone'],
        ]);

        $campaign = $log->campaign;
        if ($campaign) {
            $campaign->update([
                'total_success' => $campaign->logs()->whereIn('status', ['submitted', 'sent', 'delivered', 'read'])->count(),
                'total_failed' => $campaign->logs()->where('status', 'failed')->count(),
            ]);

            $campaign->checkCompletion();
        }

        return response()->json(['status' => 'ok']);
    }
}

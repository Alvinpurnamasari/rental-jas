<?php

declare(strict_types=1);

namespace App\Models\Broadcast;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use App\Jobs\SendBroadcastMessage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BroadcastCampaign extends Model
{
    use HasFactory;

    protected $table = 'broadcast_campaigns';

    protected $fillable = [
        'name',
        'type',
        'trigger_event',
        'template_id',
        'custom_message',
        'message_template',
        'target_type',
        'recipient_type',
        'target_filters',
        'provider',
        'channels',
        'status',
        'total_target',
        'total_success',
        'total_failed',
        'scheduled_at',
        'started_at',
        'completed_at',
        'created_by',
        'branch_id',
        'delay_seconds',
    ];

    protected $casts = [
        'target_filters' => 'array',
        'channels' => 'array',
        'custom_message' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'delay_seconds' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(BroadcastTemplate::class, 'template_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BroadcastLog::class, 'campaign_id');
    }

    public function queueMessages(): void
    {
        $recipients = $this->resolveRecipients();

        $this->update([
            'status' => 'processing',
            'started_at' => now(),
            'total_target' => $recipients->count(),
        ]);

        $message = $this->resolveMessage();
        $channels = $this->channels ?? ['whatsapp'];

        if (in_array('in_app', $channels, true)) {
            $inAppRecipients = $recipients->filter(fn ($r) => in_array($r['type'], ['user', 'customer'], true))->values();

            if ($inAppRecipients->isNotEmpty()) {
                app(\App\Services\NotificationService::class)->broadcast(
                    $inAppRecipients->map(fn ($r) => [
                        'type' => $r['type'] === 'user' ? \App\Models\User::class : \App\Models\Customer::class,
                        'id' => $r['id'],
                    ])->toArray(),
                    'system',
                    'Broadcast: ' . $this->name,
                    $message,
                    [
                        'broadcast' => true,
                        'sender_id' => $this->created_by,
                        'sender_name' => $this->created_by ? (\App\Models\User::find($this->created_by)?->name ?? null) : null,
                        'target' => $this->target_type,
                        'role' => null,
                        'branch_id' => $this->branch_id,
                        'recipient_count' => $inAppRecipients->count(),
                    ],
                    $this->branch_id,
                    null,
                    'send',
                    'blue'
                );
            }
        }

        if (in_array('whatsapp', $channels, true)) {
            $whatsappRecipients = $recipients->filter(fn ($r) => $r['type'] === 'customer')->values();

            $delaySeconds = (int) ($this->delay_seconds ?? 0);

            foreach ($whatsappRecipients as $index => $recipient) {
                $renderedMessage = $this->pickRandomVariant($recipient);

                $log = $this->logs()->create([
                    'recipient_type' => $recipient['type'],
                    'recipient_id' => $recipient['id'],
                    'phone' => $recipient['phone'],
                    'rendered_message' => $renderedMessage,
                    'provider' => $this->provider,
                    'status' => 'pending',
                ]);

                $dispatch = SendBroadcastMessage::dispatch($log);

                if ($delaySeconds > 0) {
                    $dispatch->delay(now()->addSeconds($delaySeconds * $index));
                }
            }
        }
    }

    private function resolveRecipients()
    {
        $filters = $this->target_filters ?? [];
        $branchId = $filters['branch_id'] ?? $this->branch_id;

        $customers = collect();
        $users = collect();

        if (in_array($this->recipient_type, ['customer', 'both'])) {
            $query = Customer::query()->where('opt_out', false);

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            if ($this->target_type === 'customer') {
                $selectedIds = $filters['recipient_ids'] ?? [];
                if ($selectedIds) {
                    $query->whereIn('id', $selectedIds);
                }
            }

            $customers = $query->get(['id', 'name', 'phone', 'branch_id'])->map(fn ($c) => [
                'id' => $c->id,
                'type' => 'customer',
                'phone' => $c->phone,
                'name' => $c->name,
            ]);
        }

        if (in_array($this->recipient_type, ['user', 'both'])) {
            $query = User::query()->active()->where('opt_out', false);

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            if ($this->target_type === 'sales') {
                $query->whereIn('role', [User::ROLE_ADMIN_TOKO, User::ROLE_SALES]);
            }

            if ($this->target_type === 'customer' && $this->recipient_type === 'both') {
                $selectedIds = $filters['recipient_ids'] ?? [];
                if ($selectedIds) {
                    $query->whereIn('id', $selectedIds);
                }
            }

            $users = $query->get(['id', 'name', 'phone', 'role', 'branch_id'])->map(fn ($u) => [
                'id' => $u->id,
                'type' => 'user',
                'phone' => $u->phone,
                'name' => $u->name,
            ]);
        }

        $allRecipients = $customers->merge($users)->values();

        $dailyLimit = (int) env('BROADCAST_DAILY_LIMIT', 50);
        $todaySent = BroadcastLog::whereIn('status', ['submitted', 'sent', 'delivered', 'read'])
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $remaining = max(0, $dailyLimit - $todaySent);

        return $allRecipients->take($remaining);
    }

    private function resolveMessage(): array|string
    {
        if ($this->message_template) {
            return $this->message_template;
        }

        if ($this->template_id && $this->template) {
            return $this->template->content ?? '';
        }

        if (is_array($this->custom_message)) {
            $nonEmpty = array_values(array_filter($this->custom_message, fn ($m) => is_string($m) && $m !== ''));
            if (count($nonEmpty) > 0) {
                return $nonEmpty;
            }
        }

        return '';
    }

    private function renderTemplate(string $template, array $recipient): string
    {
        $replacements = [
            '{{name}}' => $recipient['name'] ?? '',
            '{{phone}}' => $recipient['phone'] ?? '',
            '{{sender_name}}' => $this->created_by ? (\App\Models\User::find($this->created_by)?->name ?? '') : '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private function pickRandomVariant(array $recipient): string
    {
        $message = $this->resolveMessage();

        if (is_string($message)) {
            return $this->renderTemplate($message, $recipient);
        }

        if (is_array($message)) {
            $filtered = array_values(array_filter($message, fn ($m) => is_string($m) && $m !== ''));
            if (count($filtered) > 0) {
                $variant = $filtered[array_rand($filtered)];
                return $this->renderTemplate($variant, $recipient);
            }
        }

        return '';
    }

    public function checkCompletion(): void
    {
        $total = $this->logs()->count();
        $finished = $this->logs()->whereIn('status', ['submitted', 'sent', 'delivered', 'read', 'failed'])->count();

        if ($finished > 0 && $finished === $total) {
            $failed = $this->logs()->where('status', 'failed')->count();
            $this->update([
                'status' => $failed === $total ? 'failed' : 'completed',
                'completed_at' => now(),
            ]);
        }
    }
}



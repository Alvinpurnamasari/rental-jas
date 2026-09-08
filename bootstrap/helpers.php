<?php

use App\Models\Broadcast\BroadcastCampaign;

if (!function_exists('normalizePhoneForWhatsApp')) {
    function normalizePhoneForWhatsApp(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        return $digits;
    }
}

if (!function_exists('buildWhatsAppLink')) {
    function buildWhatsAppLink(string $phone, string $message): string
    {
        $normalizedPhone = normalizePhoneForWhatsApp($phone);
        $encodedMessage = urlencode($message);

        return "https://wa.me/{$normalizedPhone}?text={$encodedMessage}";
    }
}

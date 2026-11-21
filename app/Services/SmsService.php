<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function isEnabled(): bool
    {
        return (bool) config('services.sms.enabled')
            && config('services.sms.driver') === 'twilio'
            && filled(config('services.twilio.sid'))
            && filled(config('services.twilio.token'))
            && filled(config('services.twilio.from'));
    }

    public function send(?string $phone, string $message): bool
    {
        if (! filled($phone)) {
            return false;
        }

        $message = trim($message);
        if ($message === '') {
            return false;
        }

        if (! config('services.sms.enabled')) {
            Log::info('SMS skipped (disabled)', ['phone' => $phone, 'message' => $message]);

            return false;
        }

        if (config('services.sms.driver') === 'log') {
            Log::info('SMS (log driver)', ['phone' => $phone, 'message' => $message]);

            return true;
        }

        if (! $this->isEnabled()) {
            Log::warning('SMS not sent: Twilio is not configured.', ['phone' => $phone]);

            return false;
        }

        $to = $this->normalizePhone($phone);
        if (! $to) {
            Log::warning('SMS not sent: invalid phone number.', ['phone' => $phone]);

            return false;
        }

        try {
            $sid = config('services.twilio.sid');
            $response = Http::withBasicAuth($sid, config('services.twilio.token'))
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => config('services.twilio.from'),
                    'To' => $to,
                    'Body' => mb_substr($message, 0, 480),
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Twilio SMS failed', [
                'phone' => $to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Twilio SMS exception', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    public function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if ($digits === '') {
            return null;
        }

        // Philippines: 09XX -> +639XX
        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return '+63'.substr($digits, 1);
        }

        if (str_starts_with($digits, '639') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        if (str_starts_with($phone, '+') && strlen($digits) >= 10) {
            return '+'.$digits;
        }

        if (strlen($digits) >= 10) {
            return '+'.$digits;
        }

        return null;
    }
}

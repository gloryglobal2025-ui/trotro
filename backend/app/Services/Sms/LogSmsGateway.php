<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Log;

class LogSmsGateway implements SmsGateway
{
    public function send(string $to, string $message): void
    {
        // Warning level so local testing still shows the SMS with LOG_LEVEL=warning.
        Log::warning('SMS sent (log driver)', ['to' => $to, 'message' => $message]);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySmsWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('sms.webhook_secret');

        if ($secret === '') {
            return response()->json([
                'message' => 'SMS and USSD webhooks are not configured yet.',
            ], 503);
        }

        $provided = (string) ($request->header('X-Sms-Secret') ?? $request->query('secret', ''));

        if ($provided === '' || ! hash_equals($secret, $provided)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}

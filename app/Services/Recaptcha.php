<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Recaptcha
{
    public function verifyV3(string $token, string $action, float $minScore): array
    {
        $resp = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'   => config('services.recaptcha.v3.secret'),
            'response' => $token,
        ])->json();

        Log::info('recaptcha_v3', [
            'action' => $action,
            'score'  => $resp['score'] ?? null,
            'host'   => $resp['hostname'] ?? null,
            'ip'     => request()->ip(),
        ]);

        // expected keys: success, score, action, hostname
        $ok = ($resp['success'] ?? false)
            && ($resp['action'] ?? null) === $action
            && ($resp['score'] ?? 0) >= $minScore;

        return ['ok' => $ok, 'raw' => $resp];
    }

    // public function verifyV2(string $response): bool
    // {
    //     $resp = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
    //         'secret'   => config('services.recaptcha.v2.secret'),
    //         'response' => $response,
    //     ])->json();

    //     return (bool) ($resp['success'] ?? false);
    // }
}

<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Services\Recaptcha;

class RecaptchaV3 implements ValidationRule
{
    public function __construct(
        private string $action,
        private float $threshold
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || $value === '') {
            $fail('ReCAPTCHA token missing.');
            return;
        }

        $svc = app(Recaptcha::class);
        $res = $svc->verifyV3((string)$value, $this->action, $this->threshold);

        if (!$res['ok']) {
            $fail('Captcha verification failed. Please try again.');
        }
    }
}

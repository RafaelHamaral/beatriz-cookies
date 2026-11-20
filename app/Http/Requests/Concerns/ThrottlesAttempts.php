<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

trait ThrottlesAttempts
{
    public function ensureIsNotRateLimited(string $key, string $field, int $maxAttempts = 5): void
    {
        if (! RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            $field => "Muitas tentativas. Aguarde {$seconds} segundos e tente novamente.",
        ]);
    }

    public function hitAttempts(string $key, int $decaySeconds = 300): void
    {
        RateLimiter::hit($key, $decaySeconds);
    }

    public function clearAttempts(string $key): void
    {
        RateLimiter::clear($key);
    }
}

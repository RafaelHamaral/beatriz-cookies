<?php

namespace Tests;

use App\Models\User;
use App\Services\TwoFactorAuthentication;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    protected function confirmTwoFactor(User $user): User
    {
        $secret = app(TwoFactorAuthentication::class)->generateSecret();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [
                Hash::make('ABCD-EFGH'),
            ],
        ])->save();

        return $user->fresh();
    }
}

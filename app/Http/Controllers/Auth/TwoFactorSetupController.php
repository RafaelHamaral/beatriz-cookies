<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmTwoFactorRequest;
use App\Services\AuditLogger;
use App\Services\TwoFactorAuthentication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TwoFactorSetupController extends Controller
{
    public function __construct(private readonly TwoFactorAuthentication $twoFactor) {}

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isAdmin(), 403);

        if ($user->hasConfirmedTwoFactor()) {
            return redirect()->route('dashboard');
        }

        if (! is_string($user->two_factor_secret) || $user->two_factor_secret === '') {
            $user->forceFill([
                'two_factor_secret' => $this->twoFactor->generateSecret(),
            ])->save();
        }

        return view('auth.two-factor-setup', [
            'secret' => $user->two_factor_secret,
            'qrSvg' => $this->twoFactor->qrSvg($user, $user->two_factor_secret),
        ]);
    }

    public function store(ConfirmTwoFactorRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited($request->throttleKey(), 'code');

        $user = $request->user();
        abort_unless($user?->isAdmin(), 403);

        $secret = (string) $user->two_factor_secret;
        if ($secret === '' || ! $this->twoFactor->verify($secret, $request->string('code')->toString())) {
            $request->hitAttempts($request->throttleKey());

            return back()->withErrors([
                'code' => 'Código inválido. Confira o aplicativo autenticador e tente de novo.',
            ]);
        }

        $plainCodes = $this->twoFactor->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $this->twoFactor->hashRecoveryCodes($plainCodes),
        ])->save();

        $request->clearAttempts($request->throttleKey());
        $request->session()->flash('recovery_codes', $plainCodes);

        AuditLogger::record('user.two_factor_enabled', $user);

        return redirect()->route('two-factor.recovery');
    }

    public function recovery(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isAdmin() && $user->hasConfirmedTwoFactor(), 403);

        $codes = $request->session()->pull('recovery_codes', []);
        if (! is_array($codes) || $codes === []) {
            return redirect()->route('dashboard')->with('status', 'Os códigos de recuperação já foram exibidos.');
        }

        return view('auth.two-factor-recovery', [
            'codes' => $codes,
        ]);
    }
}

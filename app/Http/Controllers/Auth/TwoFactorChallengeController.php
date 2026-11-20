<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TwoFactorAuthentication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function __construct(private readonly TwoFactorAuthentication $twoFactor) {}

    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.two_factor_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(TwoFactorChallengeRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited($request->throttleKey(), 'code');

        $user = User::query()->find($request->session()->get('login.two_factor_id'));

        if (! $user instanceof User || ! $user->isAdmin() || ! $user->hasConfirmedTwoFactor()) {
            $request->session()->forget('login.two_factor_id');

            return redirect()->route('login')->withErrors([
                'email' => 'A sessão de verificação expirou. Entre novamente.',
            ]);
        }

        $code = $request->string('code')->toString();
        $secret = (string) $user->two_factor_secret;
        $valid = $this->twoFactor->verify($secret, $code)
            || $this->twoFactor->consumeRecoveryCode($user, $code);

        if (! $valid) {
            $request->hitAttempts($request->throttleKey());

            return back()->withErrors([
                'code' => 'Código inválido.',
            ]);
        }

        $request->clearAttempts($request->throttleKey());
        $request->session()->forget('login.two_factor_id');

        Auth::login($user);
        $request->session()->regenerate();

        AuditLogger::record('user.two_factor_challenged', $user);

        return redirect()->intended(route('dashboard'));
    }
}

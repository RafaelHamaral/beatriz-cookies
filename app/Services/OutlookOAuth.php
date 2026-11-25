<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OutlookMailSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class OutlookOAuth
{
    public const BASE = 'https://login.microsoftonline.com/consumers/oauth2/v2.0';
    public const SCOPE = 'https://outlook.office.com/SMTP.Send offline_access';

    public function exchange(OutlookMailSetting $settings, string $code, string $verifier): array
    {
        $tokens = $this->requestTokens($settings, [
            'grant_type' => 'authorization_code', 'code' => $code, 'code_verifier' => $verifier,
            'redirect_uri' => route('mail-settings.callback'),
        ]);
        if (empty($tokens['refresh_token'])) {
            throw new RuntimeException('A Microsoft não concedeu autorização para envios em segundo plano.');
        }

        return $tokens;
    }

    public function accessToken(?string $expectedFingerprint = null): string
    {
        return DB::transaction(function () use ($expectedFingerprint) {
            $settings = OutlookMailSetting::lockForUpdate()->findOrFail(1);
            if ($expectedFingerprint !== null && ! hash_equals($expectedFingerprint, $settings->fingerprint())) {
                throw new RuntimeException('As configurações de e-mail foram alteradas. Tente novamente.');
            }
            if (! $settings->client_id || ! $settings->client_secret || ! $settings->refresh_token) {
                throw new RuntimeException('Conecte a conta Outlook na configuração de e-mail.');
            }
            if ($settings->access_token && $settings->token_expires_at?->isAfter(now()->addMinutes(2))) {
                return $settings->access_token;
            }
            $tokens = $this->requestTokens($settings, [
                'grant_type' => 'refresh_token', 'refresh_token' => $settings->refresh_token,
            ]);
            $this->storeTokens($settings, $tokens);

            return $settings->access_token;
        });
    }

    public function storeTokens(OutlookMailSetting $settings, array $tokens): void
    {
        $settings->access_token = $tokens['access_token'];
        $settings->refresh_token = $tokens['refresh_token'] ?? $settings->refresh_token;
        $settings->token_expires_at = now()->addSeconds((int) $tokens['expires_in']);
        $settings->save();
    }

    private function requestTokens(OutlookMailSetting $settings, array $grant): array
    {
        try {
            $response = Http::asForm()->timeout(20)->post(self::BASE.'/token', [
                'client_id' => $settings->client_id, 'client_secret' => $settings->client_secret,
                'scope' => self::SCOPE, ...$grant,
            ]);
            $tokens = $response->json();
            if (! $response->successful() || ! is_array($tokens)
                || ! is_string($tokens['access_token'] ?? null) || $tokens['access_token'] === ''
                || ! is_numeric($tokens['expires_in'] ?? null) || $tokens['expires_in'] <= 0
                || (isset($tokens['refresh_token']) && ! is_string($tokens['refresh_token']))) {
                throw new RuntimeException;
            }

            return $tokens;
        } catch (Throwable) {
            // Never propagate provider response bodies or request credentials to logs.
            throw new RuntimeException('Não foi possível autorizar a conta Outlook. Verifique o aplicativo e reconecte a conta.');
        }
    }
}

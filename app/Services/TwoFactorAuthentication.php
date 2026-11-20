<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthentication
{
    public function __construct(private readonly Google2FA $google2fa) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        return $this->google2fa->verifyKey($secret, $code);
    }

    public function currentCode(string $secret): string
    {
        return $this->google2fa->getCurrentOtp($secret);
    }

    public function qrSvg(User $user, string $secret): string
    {
        $otpauth = $this->google2fa->getQRCodeUrl(
            (string) config('app.name'),
            $user->email,
            $secret,
        );

        $writer = new Writer(new ImageRenderer(
            new RendererStyle(180),
            new SvgImageBackEnd(),
        ));

        return $writer->writeString($otpauth);
    }

    /**
     * @return list<string>
     */
    public function generateRecoveryCodes(): array
    {
        $codes = [];

        for ($index = 0; $index < 8; $index++) {
            $codes[] = Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4));
        }

        return $codes;
    }

    /**
     * @param  list<string>  $plainCodes
     * @return list<string>
     */
    public function hashRecoveryCodes(array $plainCodes): array
    {
        return array_map(fn (string $code) => Hash::make($code), $plainCodes);
    }

    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $code = strtoupper(trim($code));
        $hashes = array_values($user->two_factor_recovery_codes ?? []);

        foreach ($hashes as $index => $hash) {
            if (! is_string($hash) || ! Hash::check($code, $hash)) {
                continue;
            }

            unset($hashes[$index]);
            $user->forceFill([
                'two_factor_recovery_codes' => array_values($hashes),
            ])->save();

            return true;
        }

        return false;
    }
}

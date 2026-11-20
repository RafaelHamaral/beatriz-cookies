<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\ThrottlesAttempts;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    use ThrottlesAttempts;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'Informe a senha.',
        ];
    }

    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited($this->throttleKey(), 'email');

        $user = User::query()->where('email', (string) $this->string('email'))->first();
        $password = (string) $this->string('password');
        $valid = Hash::check($password, $user?->password ?? self::dummyHash());

        if (! $user instanceof User || ! $valid) {
            $this->hitAttempts($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha inválidos.',
            ]);
        }

        $this->clearAttempts($this->throttleKey());

        return $user;
    }

    private static function dummyHash(): string
    {
        static $hash;

        return $hash ??= Hash::make('invalid-password');
    }

    public function throttleKey(): string
    {
        return 'login|'.Str::lower((string) $this->string('email')).'|'.$this->ip();
    }
}

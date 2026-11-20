<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\ThrottlesAttempts;
use Illuminate\Foundation\Http\FormRequest;

class TwoFactorChallengeRequest extends FormRequest
{
    use ThrottlesAttempts;

    public function authorize(): bool
    {
        return $this->session()->has('login.two_factor_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Informe o código do autenticador ou um código de recuperação.',
        ];
    }

    public function throttleKey(): string
    {
        return 'two-factor|'.$this->session()->get('login.two_factor_id').'|'.$this->ip();
    }
}

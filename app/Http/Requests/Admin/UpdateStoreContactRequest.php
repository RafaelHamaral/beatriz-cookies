<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->managesCatalog() === true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ()-]{8,30}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'whatsapp' => ['nullable', 'string', 'regex:/^[1-9][0-9]{9,14}$/'],
            'instagram' => ['nullable', 'url:https', 'max:500'],
            'facebook' => ['nullable', 'url:https', 'max:500'],
            'tiktok' => ['nullable', 'url:https', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'whatsapp.regex' => 'Informe o WhatsApp com código do país e DDD, somente números (ex.: 5511999999999).',
            'phone.regex' => 'Informe um telefone válido, usando números, espaços, parênteses, + ou hífen.',
            '*.url' => 'Informe um endereço completo iniciado por https://.',
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOutlookMailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:120', 'not_regex:/[\r\n]/'],
            'recipient' => ['required', 'email', 'max:255'],
            'client_id' => ['nullable', 'uuid'],
            'client_secret' => ['nullable', 'string', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => 'Preencha este campo.',
            '*.email' => 'Informe um endereço de e-mail válido.',
            'client_id.uuid' => 'Informe o ID do aplicativo Microsoft no formato UUID.',
            'from_name.not_regex' => 'O nome do remetente não pode conter quebras de linha.',
        ];
    }
}

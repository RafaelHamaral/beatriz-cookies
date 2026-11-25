<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\StoreContact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateOpeningHoursRequest extends FormRequest
{
    private array $schedule = [];

    public function authorize(): bool
    {
        return $this->user()?->managesCatalog() === true;
    }

    public function rules(): array
    {
        $rules = ['hours' => ['required', 'array:'.implode(',', array_keys(StoreContact::WEEKDAYS))]];
        foreach (StoreContact::WEEKDAYS as $day => $label) {
            $rules["hours.$day"] = ['required', 'array:status,intervals'];
            $rules["hours.$day.status"] = ['required', 'in:closed,open'];
            $rules["hours.$day.intervals"] = ['nullable', 'string', 'max:2000'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach (StoreContact::WEEKDAYS as $day => $label) {
                $data = $this->input("hours.$day");
                $intervals = [];
                $key = "hours.$day.intervals";
                if ($data['status'] === 'open') {
                    $lines = preg_split('/\R/u', trim($data['intervals'] ?? ''));
                    foreach ($lines as $line) {
                        if (! preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9])\s*[-–]\s*([01][0-9]|2[0-3]):([0-5][0-9])$/uD', trim($line), $matches)) {
                            $validator->errors()->add($key, "$label: informe um intervalo por linha, no formato 08:00–12:00.");
                            break;
                        }
                        $start = "$matches[1]:$matches[2]";
                        $end = "$matches[3]:$matches[4]";
                        if ($start >= $end) {
                            $validator->errors()->add($key, "$label: a abertura deve ser anterior ao fechamento, no mesmo dia.");
                            break;
                        }
                        $intervals[] = ['start' => $start, 'end' => $end];
                    }
                    usort($intervals, fn ($a, $b) => strcmp($a['start'], $b['start']));
                    for ($i = 1; $i < count($intervals); $i++) {
                        if ($intervals[$i]['start'] < $intervals[$i - 1]['end']) {
                            $validator->errors()->add($key, "$label: os intervalos não podem se sobrepor.");
                            break;
                        }
                    }
                }
                $this->schedule[$day] = ['closed' => $data['status'] === 'closed', 'intervals' => $intervals];
            }
        }];
    }

    public function schedule(): array
    {
        return $this->schedule;
    }

    public function messages(): array
    {
        return [
            'hours.required' => 'Informe os horários dos sete dias da semana.',
            'hours.*.required' => 'Informe a situação de cada dia da semana.',
            'hours.*.status.required' => 'Escolha Fechado ou Aberto para este dia.',
            'hours.*.status.in' => 'Escolha Fechado ou Aberto para este dia.',
            'hours.*.intervals.string' => 'Informe os intervalos como texto, um por linha.',
            'hours.*.intervals.max' => 'Os intervalos de cada dia devem ter no máximo 2000 caracteres.',
        ];
    }
}

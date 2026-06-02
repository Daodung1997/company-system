<?php

namespace App\Http\Requests\Timesheet;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class CheckInTimesheetRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'timezone' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}

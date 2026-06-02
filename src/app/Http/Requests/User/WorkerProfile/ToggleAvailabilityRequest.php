<?php

namespace App\Http\Requests\User\WorkerProfile;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ToggleAvailabilityRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ];
    }
}

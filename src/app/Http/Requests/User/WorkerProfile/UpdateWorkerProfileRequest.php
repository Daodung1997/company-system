<?php

namespace App\Http\Requests\User\WorkerProfile;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkerProfileRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'avatar_code' => ['nullable', 'string', 'exists:t_images,code'],
            'experience_years' => 'nullable|integer|min:0|max:100',
            'skill_description' => 'nullable|string|max:10000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'time_slots' => 'nullable|array|max:6',
            'time_slots.*' => 'string|in:'.implode(',', \App\Constants\Master\Models\Job\JobTimeSlotConst::getValues()),
            'certificates' => 'nullable|array',
            'certificates.*' => 'string|max:100',
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}

<?php

namespace App\Http\Requests\User\WorkerRegistration;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ResubmitRegistrationRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^0[0-9]{9,10}$/'],
            'dob' => ['required', 'date', 'before:today'],
            'id_card_number' => ['required', 'string', 'max:20'],
            'id_card_issue_date' => ['required', 'date', 'before:today'],
            'permanent_address' => ['required', 'string', 'max:500'],
            'selfie_id' => ['required', 'integer', 'exists:t_images,id'],
            'id_card_front_id' => ['required', 'integer', 'exists:t_images,id'],
            'id_card_back_id' => ['required', 'integer', 'exists:t_images,id'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['required', 'integer', 'exists:m_service_categories,id'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:50'],
            'skill_description' => ['required', 'string', 'max:2000'],
            'area_ids' => ['required', 'array', 'min:1'],
            'area_ids.*' => ['required', 'integer', 'exists:m_areas,id'],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}

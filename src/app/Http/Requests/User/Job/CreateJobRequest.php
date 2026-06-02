<?php

namespace App\Http\Requests\User\Job;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateJobRequest extends FormRequest
{
    use RequestTrait;

    protected function prepareForValidation()
    {
        if (! $this->has('work_time_type') && $this->has('time_slot')) {
            $timeSlot = $this->input('time_slot');
            $workTimeType = null;
            if (in_array($timeSlot, ['08:00-10:00', '10:00-12:00'])) {
                $workTimeType = 'MORNING';
            } elseif (in_array($timeSlot, ['13:00-15:00', '15:00-17:00'])) {
                $workTimeType = 'AFTERNOON';
            } elseif (in_array($timeSlot, ['17:00-19:00', '19:00-21:00'])) {
                $workTimeType = 'EVENING';
            }

            if ($workTimeType) {
                $this->merge([
                    'work_time_type' => $workTimeType,
                ]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', Rule::exists('m_service_categories', 'id')->where('status', 'active')],
            'description' => 'required|string|max:2000',
            'user_address_id' => 'nullable|integer|exists:m_user_addresses,id',
            'area_id' => 'required_without:user_address_id|nullable|exists:m_areas,id',
            'address' => 'required_without:user_address_id|nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'work_time_type' => [
                'required',
                'string',
                'in:MORNING,AFTERNOON,EVENING,CUSTOM',
                function ($attribute, $value, $fail) {
                    if ($this->scheduled_date && $this->scheduled_date === now()->format('Y-m-d')) {
                        $currentTime = now()->format('H:i');
                        $startTime = null;
                        if ($value === 'MORNING') {
                            $startTime = '08:00';
                        } elseif ($value === 'AFTERNOON') {
                            $startTime = '13:30';
                        } elseif ($value === 'EVENING') {
                            $startTime = '18:00';
                        }
                        if ($startTime && $startTime <= $currentTime) {
                            $fail('work_time_type.past_time');
                        }
                    }
                },
            ],
            'work_start_time' => [
                'required_if:work_time_type,CUSTOM',
                'nullable',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    if ($this->work_time_type === 'CUSTOM' && $this->scheduled_date && $this->scheduled_date === now()->format('Y-m-d')) {
                        $currentTime = now()->format('H:i');
                        if ($value && $value <= $currentTime) {
                            $fail('work_start_time.past_time');
                        }
                    }
                },
            ],
            'work_end_time' => 'required_if:work_time_type,CUSTOM|nullable|date_format:H:i|after:work_start_time',
            'time_slot' => [
                'nullable',
                'string',
            ],
            'discount_code' => 'nullable|string|max:50',
            'media_codes' => 'nullable|array|max:5',
            'media_codes.*' => 'string|exists:t_images,code',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        $messages = $this->renderMessageFromRule($this->rules());

        // Custom manual mapping for complex rule keys
        $messages['work_start_time.required_if'] = 'work_start_time.required_if';
        $messages['work_start_time.date_format'] = 'work_start_time.date_format';
        $messages['work_end_time.required_if'] = 'work_end_time.required_if';
        $messages['work_end_time.date_format'] = 'work_end_time.date_format';
        $messages['work_end_time.after'] = 'work_end_time.after';

        return $messages;
    }
}

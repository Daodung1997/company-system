<?php

namespace App\Http\Requests\Admin\Configuration;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJobAssignmentConfigRequest extends FormRequest
{
    use RequestTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'scan_radius' => 'required|numeric|min:1',
            'max_workers_per_job' => 'required|integer|min:1',
            'rating_weight' => 'required|numeric|min:0|max:1',
            'distance_weight' => 'required|numeric|min:0|max:1',
            'response_rate_weight' => 'required|numeric|min:0|max:1',
        ];
    }

    public function messages()
    {
        return $this->renderMessageFromRule($this->rules());
    }
}

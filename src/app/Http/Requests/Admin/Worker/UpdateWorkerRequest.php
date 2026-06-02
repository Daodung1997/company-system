<?php

namespace App\Http\Requests\Admin\Worker;

use App\Http\Requests\BaseRequest;

class UpdateWorkerRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'avatar_url' => 'nullable|string|max:255',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'skill_description' => 'nullable|string|max:2000',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|exists:m_service_categories,id',
            'area_ids' => 'nullable|array',
            'area_ids.*' => 'integer|exists:m_areas,id',
        ];
    }
}

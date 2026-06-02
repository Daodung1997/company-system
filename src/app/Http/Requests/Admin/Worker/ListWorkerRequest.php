<?php

namespace App\Http\Requests\Admin\Worker;

use App\Constants\Master\Models\WorkerProfile\WorkerActivityStatus;
use App\Constants\Master\Models\WorkerProfile\WorkerProfileStatus;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class ListWorkerRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'limit' => 'nullable|integer|min:1|max:100',
            'filters' => 'nullable|array',
            'filters.profile_status' => ['nullable', 'string', Rule::in(WorkerProfileStatus::getValues())],
            'filters.activity_status' => ['nullable', 'string', Rule::in(WorkerActivityStatus::getValues())],
            'search' => 'nullable|array',
            'search.name' => 'nullable|string|max:255',
            'search.email' => 'nullable|string|max:255',
            'search.phone' => 'nullable|string|max:255',
            'sorts' => 'nullable|array',
        ];
    }
}

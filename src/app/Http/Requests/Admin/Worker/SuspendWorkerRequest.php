<?php

namespace App\Http\Requests\Admin\Worker;

use App\Http\Requests\BaseRequest;

class SuspendWorkerRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:1000',
        ];
    }
}

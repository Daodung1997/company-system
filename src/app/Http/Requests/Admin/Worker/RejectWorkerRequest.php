<?php

namespace App\Http\Requests\Admin\Worker;

use App\Http\Requests\BaseRequest;

class RejectWorkerRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'reason' => 'required|string|min:5|max:1000',
        ];
    }
}

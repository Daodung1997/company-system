<?php

namespace App\Http\Requests\Admin\Customer;

use App\Constants\Master\Models\User\UserStatusConst;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ToggleStatusRequest extends FormRequest
{
    use RequestTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status' => 'required|string|in:'.UserStatusConst::ACTIVE.','.UserStatusConst::BLOCKED,
            'reason' => 'nullable|string|max:500|required_if:status,'.UserStatusConst::BLOCKED,
        ];
    }
}

<?php

namespace App\Http\Requests\User\Job;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class SubmitComplaintRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|max:2000',
            'media_codes' => 'nullable|array|max:5',
            'media_codes.*' => 'string|exists:t_images,code',
        ];
    }
}

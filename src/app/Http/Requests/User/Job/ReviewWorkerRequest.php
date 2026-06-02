<?php

namespace App\Http\Requests\User\Job;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ReviewWorkerRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:2000',
        ];
    }
}

<?php

namespace App\Http\Requests\User\WorkerProfile;

use App\Supports\Facades\Response\Response;
use App\Traits\RequestTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateWorkerServicesRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_category_ids' => 'required|array',
            'service_category_ids.*' => ['required', 'integer', Rule::exists('m_service_categories', 'id')->where('status', 'active')],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            Response::failure($validator->errors()->toArray(), 422)
        );
    }
}

<?php

namespace App\Http\Requests\User\WorkerProfile;

use App\Supports\Facades\Response\Response;
use App\Traits\RequestTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateWorkerAreasRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'area_ids' => 'required|array',
            'area_ids.*' => 'required|integer|exists:m_areas,id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            Response::failure($validator->errors()->toArray(), 422)
        );
    }
}

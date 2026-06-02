<?php

namespace App\Http\Requests\Master;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'name_kana' => ['nullable', 'string', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:50'],
            'corporate_number' => ['nullable', 'string', 'max:50'],
            'address_registered' => ['nullable', 'string', 'max:500'],
            'legal_representative' => ['nullable', 'string', 'max:150'],
            'hanko_seal_path' => ['nullable', 'string', 'max:255'],
            'fax' => ['nullable', 'string', 'max:20'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'postcode' => ['nullable', 'string', 'max:10'],
            'address' => ['nullable', 'string', 'max:500'],
            'email' => ['nullable', 'email', 'max:150'],
            'note' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in(['ACTIVE', 'INACTIVE', 'active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}

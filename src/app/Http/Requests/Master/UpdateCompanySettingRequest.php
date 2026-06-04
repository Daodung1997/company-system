<?php

namespace App\Http\Requests\Master;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'company_name' => ['required', 'string', 'max:255'],
            'company_name_kana' => ['nullable', 'string', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:50'],
            'corporate_number' => ['nullable', 'string', 'max:50'],
            'address_registered' => ['nullable', 'string', 'max:500'],
            'legal_representative' => ['nullable', 'string', 'max:150'],
            'representative_title' => ['nullable', 'string', 'max:100'],
            'representative_id_number' => ['nullable', 'string', 'max:50'],
            'representative_id_date' => ['nullable', 'date_format:Y-m-d'],
            'representative_id_place' => ['nullable', 'string', 'max:255'],
            'charter_capital' => ['nullable', 'string', 'max:100'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'fax' => ['nullable', 'string', 'max:20'],
            'postcode' => ['nullable', 'string', 'max:10'],
            'address' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'string', 'max:255'],
            'hanko_seal_path' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}

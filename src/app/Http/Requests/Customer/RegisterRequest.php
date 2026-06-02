<?php

namespace App\Http\Requests\Customer;

use App\Constants\Commons\App;
use App\Constants\Commons\CommonLocaleConst;
use App\Constants\Commons\CommonMemberTypeConst;
use App\Constants\Commons\CommonStatusConst;
use App\Constants\Commons\CommonTable;
use App\Constants\Master\Models\Customer\CustomerColumn;
use App\Models\Customer;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            CustomerColumn::FIRST_NAME => ['required', 'max:'.App::MAX_TWENTY],
            CustomerColumn::LAST_NAME => ['required', 'max:'.App::MAX_TWENTY],
            CustomerColumn::EMAIL => [
                'required',
                'email',
                'max:'.App::MAX_TWO_HUNDRED_FIFTY_FIVE,
                Rule::unique(Customer::TABLE_NAME, CustomerColumn::EMAIL)->whereNull(CommonTable::DELETED_AT),
            ],
            CustomerColumn::PHONE => ['required', 'max:'.App::MAX_TWENTY],
            CustomerColumn::PASSWORD => ['required', 'string', 'max:'.App::MAX_TWENTY, \App\Constants\Commons\Rule::CONFIRMED],
            CustomerColumn::NATION => ['required', 'string', 'max:'.App::MAX_TWO_HUNDRED_FIFTY_FIVE],
            CustomerColumn::CITY => ['required', 'string', 'max:'.App::MAX_TWO_HUNDRED_FIFTY_FIVE],
            CustomerColumn::WARD => ['nullable', 'string', 'max:'.App::MAX_TWO_HUNDRED_FIFTY_FIVE],
            CustomerColumn::STREET => ['nullable', 'string', 'max:'.App::MAX_TWO_HUNDRED_FIFTY_FIVE],
            CustomerColumn::ADDRESS => ['nullable', 'string', 'max:'.App::MAX_TWO_HUNDRED_FIFTY_FIVE],
            CustomerColumn::MEMBER_TYPE => ['nullable', 'in:'.implode(',', CommonMemberTypeConst::getValues())],
            CustomerColumn::STATUS => ['required', 'in:'.implode(',', CommonStatusConst::getValues())],
            CustomerColumn::NOTE => ['nullable', 'max:'.App::MAX_NOTE],
            CustomerColumn::LOCALE => [
                'nullable',
                'string',
                'in:'.implode(',', CommonLocaleConst::getValues()),
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}

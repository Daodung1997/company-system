<?php

namespace App\Http\Requests\Admin\Configuration\ServiceCategory;

use App\Http\Requests\BaseRequest;

class ListServiceCategoryRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'limit' => 'nullable|integer|min:1|max:100',
            'filters' => 'nullable|array',
            'sorts' => 'nullable|array',
            'search' => 'nullable|array',
        ];
    }
}

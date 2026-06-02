<?php

namespace App\Http\Requests\User\Notification;

use App\Http\Requests\BaseRequest;

class ListNotificationsRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'limit' => 'nullable|integer|min:1|max:100',
            'filters' => 'nullable|array',
            'filters.is_read' => 'nullable|boolean',
            'sorts' => 'nullable|array',
            'search' => 'nullable|array',
        ];
    }
}

<?php

namespace App\Http\Requests\User\Review;

use App\Constants\Master\Models\Job\JobStatusConst;
use App\Http\Requests\BaseRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CreateReviewRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = Auth::id();

        return [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'job_id' => [
                'required',
                'integer',
                // Check job exists and belongs to customer
                Rule::exists('t_jobs', 'id')->where(function ($query) use ($userId) {
                    $query->where('customer_id', $userId);
                }),
                // Check job is completed
                Rule::exists('t_jobs', 'id')->where(function ($query) {
                    $query->where('status', JobStatusConst::COMPLETED);
                }),
            ],
        ];
    }

    public function messages()
    {
        return $this->renderMessageFromRule($this->rules());
    }
}

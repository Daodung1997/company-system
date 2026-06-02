<?php

namespace App\Http\Requests\Common\Image;

use App\Constants\File;
use Illuminate\Foundation\Http\FormRequest;

class UploadMultiFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'files' => 'required',
            'files.*' => 'required|file|max:'.File::MAX_SIZE_UPLOAD_FILE,
        ];
    }

    public function messages()
    {
        $messages = [
            'files.required' => __('files.required'),
        ];

        foreach ($this->files as $key => $value) {
            $messages['files.'.$key.'.required'] = __('files.'.$key.'.required');
            $messages['files.'.$key.'.file'] = __('files.'.$key.'.file');
            $messages['files.'.$key.'.max'] = __('files.'.$key.'.max');
        }

        return $messages;
    }
}

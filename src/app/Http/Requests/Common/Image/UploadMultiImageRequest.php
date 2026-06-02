<?php

namespace App\Http\Requests\Common\Image;

use App\Constants\Commons\File;
use Illuminate\Foundation\Http\FormRequest;

class UploadMultiImageRequest extends FormRequest
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
            File::PARAM_MULTI_IMAGE => 'required',
            File::PARAM_MULTI_IMAGE.'.*' => 'required|mimes:png,jpg,jpeg,svg,mp4,mpeg,mkv,avi,3gp,doc,docx,pdf,xlsx|max:'.File::MAX_SIZE_UPLOAD,
        ];
    }

    public function messages()
    {
        $messages = [
            File::PARAM_MULTI_IMAGE.'.required' => __(File::PARAM_MULTI_IMAGE.'.required'),
        ];
        foreach ($this->images as $key => $value) {
            $messages[File::PARAM_MULTI_IMAGE.'.'.$key.'.required'] = __(File::PARAM_MULTI_IMAGE.'.'.$key.'.required');
            $messages[File::PARAM_MULTI_IMAGE.'.'.$key.'.image'] = __(File::PARAM_MULTI_IMAGE.'.'.$key.'.image');
            $messages[File::PARAM_MULTI_IMAGE.'.'.$key.'.mimes'] = __(File::PARAM_MULTI_IMAGE.'.'.$key.'.mimes');
            $messages[File::PARAM_MULTI_IMAGE.'.'.$key.'.max'] = __(File::PARAM_MULTI_IMAGE.'.'.$key.'.max');
            $messages[File::PARAM_MULTI_IMAGE.'.'.$key.'.mimetypes'] = __(File::PARAM_MULTI_IMAGE.'.'.$key.'.mimetypes');
        }

        return $messages;
    }
}

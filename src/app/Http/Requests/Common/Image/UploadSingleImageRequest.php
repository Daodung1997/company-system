<?php

namespace App\Http\Requests\Common\Image;

use App\Constants\Commons\File;
use Illuminate\Foundation\Http\FormRequest;

class UploadSingleImageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        //        dd($this);
        return [
            File::PARAM_SINGLE_IMAGE => 'required|max:'.File::MAX_SIZE_UPLOAD.'|mimes:png,jpg,jpeg,svg,mp4,mpeg,mkv,avi,3gp,doc,docx,pdf,xlsx',
        ];
    }

    public function messages()
    {
        return [
            File::PARAM_SINGLE_IMAGE.'.required' => __(File::PARAM_SINGLE_IMAGE.'.required'),
            File::PARAM_SINGLE_IMAGE.'.max' => __(File::PARAM_SINGLE_IMAGE.'.max'),
            File::PARAM_SINGLE_IMAGE.'.mimes' => __(File::PARAM_SINGLE_IMAGE.'.mimes'),
        ];
    }
}

<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class AttachDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_id' => 'required|integer|exists:t_documents,id',
            'documentable_type' => 'required|string',
            'documentable_id' => 'required|integer',
        ];
    }
}

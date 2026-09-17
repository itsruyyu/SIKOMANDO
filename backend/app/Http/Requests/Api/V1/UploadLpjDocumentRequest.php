<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UploadLpjDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => [
                'required',
                'string',
                'max:100',
            ],
            'document_title' => [
                'required',
                'string',
                'max:255',
            ],
            'original_filename' => [
                'nullable',
                'string',
                'max:255',
            ],
            'stored_filename' => [
                'nullable',
                'string',
                'max:255',
            ],
            'storage_path' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'disk' => [
                'nullable',
                'string',
                'max:50',
            ],
            'mime_type' => [
                'nullable',
                'string',
                'max:150',
            ],
            'file_size' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}

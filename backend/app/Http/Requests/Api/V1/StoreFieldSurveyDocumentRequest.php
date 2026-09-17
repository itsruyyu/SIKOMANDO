<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreFieldSurveyDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_title' => [
                'required',
                'string',
                'max:255',
            ],
            'original_filename' => [
                'required',
                'string',
                'max:255',
            ],
            'stored_filename' => [
                'nullable',
                'string',
                'max:255',
            ],
            'storage_path' => [
                'required',
                'string',
                'max:500',
            ],
            'mime_type' => [
                'required',
                'string',
                'max:100',
            ],
            'file_size' => [
                'required',
                'integer',
                'min:1',
            ],
            'document_type_id' => [
                'nullable',
                'uuid',
                'exists:document_types,id',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'document_title.required' => 'Judul dokumen wajib diisi.',
            'original_filename.required' => 'Nama file asli wajib diisi.',
            'storage_path.required' => 'Lokasi penyimpanan file (storage path) wajib diisi.',
            'mime_type.required' => 'Tipe MIME file wajib diisi.',
            'file_size.required' => 'Ukuran file wajib diisi.',
            'document_type_id.exists' => 'Tipe dokumen tidak valid.',
        ];
    }
}

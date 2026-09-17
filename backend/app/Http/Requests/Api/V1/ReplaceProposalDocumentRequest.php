<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReplaceProposalDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'nullable',
                'file',
                'max:10240',
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
                'max:500',
            ],
            'storage_disk' => [
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
            ],
            'file_hash' => [
                'nullable',
                'string',
                'max:128',
            ],
            'change_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->hasFile('file') && empty($this->input('original_filename'))) {
                    $validator->errors()->add(
                        'file',
                        'File fisik pengganti atau metadata original_filename wajib disertakan.'
                    );
                }

                if ($this->hasFile('file')) {
                    $clientName = $this->file('file')->getClientOriginalName();
                    if (str_contains($clientName, '..') || str_contains($clientName, '/') || str_contains($clientName, '\\') || str_contains($clientName, "\0")) {
                        $validator->errors()->add('file', 'Nama file tidak valid atau mengandung indikasi path traversal.');
                    }
                }

                $filenameToCheck = $this->input('original_filename');
                if ($filenameToCheck) {
                    if (str_contains($filenameToCheck, '..') || str_contains($filenameToCheck, '/') || str_contains($filenameToCheck, '\\') || str_contains($filenameToCheck, "\0")) {
                        $validator->errors()->add('original_filename', 'Nama file tidak valid atau mengandung indikasi path traversal.');
                    }
                }
            },
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\LpjDocument;
use App\Models\LpjSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LpjDocument>
 */
class LpjDocumentFactory extends Factory
{
    protected $model = LpjDocument::class;

    public function definition(): array
    {
        return [
            'lpj_submission_id' => LpjSubmission::factory(),
            'uploaded_by' => User::factory(),
            'document_type' => 'financial_report',
            'document_title' => 'Laporan Keuangan & Realisasi Kas',
            'original_filename' => 'laporan_keuangan.pdf',
            'stored_filename' => 'lpj_'.Str::random(12).'.pdf',
            'disk' => 'private',
            'storage_path' => 'lpj/documents/'.Str::random(12).'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 204800,
            'file_hash' => Str::random(32),
            'version' => 1,
            'status' => 'uploaded',
            'notes' => fake()->sentence(),
        ];
    }
}

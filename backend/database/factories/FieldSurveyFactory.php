<?php

namespace Database\Factories;

use App\Enums\FieldSurveyItemResult;
use App\Enums\FieldSurveyResult;
use App\Enums\FieldSurveyStatus;
use App\Models\FieldSurvey;
use App\Models\FieldSurveyItem;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FieldSurvey>
 */
class FieldSurveyFactory extends Factory
{
    protected $model = FieldSurvey::class;

    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'surveyor_id' => User::factory(),
            'survey_number' => 'SRV-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'status' => FieldSurveyStatus::ASSIGNED,
            'result' => FieldSurveyResult::PENDING,
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'started_at' => null,
            'completed_at' => null,
            'location_name' => fake()->company(),
            'location_address' => fake()->address(),
            'latitude' => fake()->latitude(-5, 0),
            'longitude' => fake()->longitude(100, 115),
            'summary' => null,
            'recommendation' => null,
            'notes' => fake()->sentence(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (FieldSurvey $survey): void {
            $defaultItems = [
                [
                    'item_code' => 'SRV-LOC',
                    'item_name' => 'Kesesuaian Lokasi dan Keberadaan Fisik Organisasi',
                    'description' => 'Pemeriksaan keabsahan lokasi fisik dan sekretariat organisasi pemohon.',
                ],
                [
                    'item_code' => 'SRV-OBJ',
                    'item_name' => 'Kelayakan Fisik Sasaran Kegiatan / Objek Hibah',
                    'description' => 'Pemeriksaan kondisi riil objek atau lokasi sasaran bantuan hibah.',
                ],
                [
                    'item_code' => 'SRV-MGT',
                    'item_name' => 'Keabsahan dan Keberadaan Pengurus Pemohon',
                    'description' => 'Konfirmasi tatap muka dengan pengurus pemohon di lokasi kegiatan.',
                ],
                [
                    'item_code' => 'SRV-BDG',
                    'item_name' => 'Kesesuaian Kebutuhan Anggaran dengan Realitas Lapangan',
                    'description' => 'Verifikasi kewajaran usulan biaya terhadap kondisi nyata lapangan.',
                ],
            ];

            foreach ($defaultItems as $item) {
                FieldSurveyItem::query()->firstOrCreate(
                    [
                        'field_survey_id' => $survey->id,
                        'item_code' => $item['item_code'],
                    ],
                    [
                        'item_name' => $item['item_name'],
                        'description' => $item['description'],
                        'result' => FieldSurveyItemResult::PENDING,
                    ]
                );
            }

            $survey->unsetRelation('items');
        });
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => FieldSurveyStatus::IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'status' => FieldSurveyStatus::SUBMITTED,
            'started_at' => now()->subDay(),
            'summary' => 'Hasil verifikasi lapangan telah memenuhi semua aspek kelayakan.',
            'recommendation' => 'Direkomendasikan untuk menerima bantuan hibah sesuai permohonan.',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => FieldSurveyStatus::COMPLETED,
            'result' => FieldSurveyResult::RECOMMENDED,
            'started_at' => now()->subDays(2),
            'completed_at' => now(),
            'summary' => 'Survei lapangan selesai dengan hasil memuaskan.',
            'recommendation' => 'Rekomendasi pencairan penuh.',
        ]);
    }
}

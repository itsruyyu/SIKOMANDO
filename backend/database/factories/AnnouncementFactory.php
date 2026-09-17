<?php

namespace Database\Factories;

use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use App\Models\GrantProgram;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'grant_program_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('####'),
            'category' => fake()->randomElement(['general', 'schedule', 'guideline', 'selection_result']),
            'excerpt' => fake()->paragraph(1),
            'content' => fake()->paragraphs(3, true),
            'status' => AnnouncementStatus::PUBLISHED,
            'is_pinned' => false,
            'published_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => AnnouncementStatus::DRAFT,
            'published_at' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => AnnouncementStatus::ARCHIVED,
            'published_at' => now()->subMonths(2),
        ]);
    }

    public function future(): static
    {
        return $this->state(fn () => [
            'status' => AnnouncementStatus::PUBLISHED,
            'published_at' => now()->addDays(7),
        ]);
    }

    public function pinned(): static
    {
        return $this->state(fn () => [
            'is_pinned' => true,
        ]);
    }
}


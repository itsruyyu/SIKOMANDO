<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Announcement
 */
class PublicAnnouncementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'category' => $this->category,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'is_pinned' => (bool) $this->is_pinned,
            'published_at' => $this->published_at?->toISOString(),
            'grant_program' => $this->whenLoaded('grantProgram', fn () => $this->grantProgram ? [
                'id' => $this->grantProgram->id,
                'code' => $this->grantProgram->code,
                'name' => $this->grantProgram->name,
                'fiscal_year' => $this->grantProgram->fiscal_year,
            ] : null),
        ];
    }
}


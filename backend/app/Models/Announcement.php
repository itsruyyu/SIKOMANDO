<?php

namespace App\Models;

use App\Enums\AnnouncementStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'grant_program_id',
        'title',
        'slug',
        'category',
        'excerpt',
        'content',
        'status',
        'is_pinned',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => AnnouncementStatus::class,
            'is_pinned' => 'boolean',
            'published_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }

    public function grantProgram(): BelongsTo
    {
        return $this->belongsTo(GrantProgram::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to only include published announcements whose publication date has arrived.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', AnnouncementStatus::PUBLISHED->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope to order pinned items first, then by newest published_at.
     */
    public function scopeOrderedForPublic(Builder $query): Builder
    {
        return $query->orderByDesc('is_pinned')
            ->orderByDesc('published_at');
    }
}


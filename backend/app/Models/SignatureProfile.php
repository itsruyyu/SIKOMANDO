<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SignatureProfile extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id',
        'name',
        'position',
        'nip',
        'signature_image_path',
        'disk',
        'status',
        'authority_level',
        'effective_start_date',
        'effective_end_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_start_date' => 'date',
            'effective_end_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(DigitalSignature::class, 'signature_profile_id');
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $today = now()->toDateString();

        if ($this->effective_start_date && $this->effective_start_date->toDateString() > $today) {
            return false;
        }

        if ($this->effective_end_date && $this->effective_end_date->toDateString() < $today) {
            return false;
        }

        return true;
    }
}


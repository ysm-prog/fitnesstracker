<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WorkoutTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'is_active'])]
class WorkoutTemplate extends Model
{
    /** @use HasFactory<WorkoutTemplateFactory> */
    use HasFactory;

    /**
     * Mirrors the column default so a row created this request reads back as
     * active rather than null until it is reloaded.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'is_active' => '1',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Always ordered by position. A program read back in a different order from
     * the one it was written in is a different program.
     *
     * @return HasMany<TemplateExercise, $this>
     */
    public function templateExercises(): HasMany
    {
        return $this->hasMany(TemplateExercise::class)->orderBy('position');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /** @param  Builder<WorkoutTemplate>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }
}

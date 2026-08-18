<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Equipment;
use App\Enums\LoadingType;
use App\Enums\MuscleGroup;
use Database\Factories\ExerciseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A movement in the library.
 *
 * `user_id` is absent from the fillable list, as everywhere else in this
 * application: a null owner means a system exercise, and a payload must never
 * be able to claim one.
 */
#[Fillable([
    'name',
    'primary_muscle',
    'secondary_muscles',
    'equipment',
    'instructions',
    'loading_type',
    'default_weight_increment_kg',
    'is_unilateral',
    'is_bodyweight',
    'default_rest_seconds',
])]
class Exercise extends Model
{
    /** @use HasFactory<ExerciseFactory> */
    use HasFactory;

    /** @var array<string, string> */
    protected $attributes = [
        'loading_type' => 'external_weight',
        'default_weight_increment_kg' => '2.5',
        'default_rest_seconds' => '120',
        'is_unilateral' => '0',
        'is_bodyweight' => '0',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'primary_muscle' => MuscleGroup::class,
            'secondary_muscles' => 'array',
            'equipment' => Equipment::class,
            'loading_type' => LoadingType::class,
            'default_weight_increment_kg' => 'float',
            'is_unilateral' => 'boolean',
            'is_bodyweight' => 'boolean',
            'default_rest_seconds' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<TemplateExercise, $this> */
    public function templateExercises(): HasMany
    {
        return $this->hasMany(TemplateExercise::class);
    }

    public function isSystem(): bool
    {
        return $this->user_id === null;
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /** Is anything depending on this exercise continuing to exist? */
    public function isReferenced(): bool
    {
        return $this->templateExercises()->exists();
    }

    /**
     * The library a given user can see: the system set plus their own.
     *
     * @param  Builder<Exercise>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->where(function (Builder $scoped) use ($user): void {
            $scoped->whereNull('user_id')->orWhere('user_id', $user->getKey());
        });
    }

    /** @param  Builder<Exercise>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Equipment;
use App\Enums\MuscleGroup;
use App\Http\Requests\Exercise\StoreExerciseRequest;
use App\Http\Requests\Exercise\UpdateExerciseRequest;
use App\Http\Resources\ExerciseResource;
use App\Models\Exercise;
use App\Support\CorrelationId;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ExerciseController extends Controller
{
    /**
     * The library: system exercises plus this user's own, archived ones hidden
     * unless asked for. Paginated, because a library grows without limit and an
     * unbounded list is a slow page waiting to happen.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Exercise::class);

        $query = Exercise::query()->visibleTo($request->user());

        if (! $request->boolean('include_archived')) {
            $query->active();
        }

        if (is_string($search = $request->query('q')) && trim($search) !== '') {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], trim($search)).'%';
            $query->where('name', 'like', $term);
        }

        if (in_array($muscle = $request->query('primary_muscle'), MuscleGroup::values(), true)) {
            $query->where('primary_muscle', $muscle);
        }

        if (in_array($equipment = $request->query('equipment'), Equipment::values(), true)) {
            $query->where('equipment', $equipment);
        }

        $exercises = $query->orderBy('name')->paginate(
            perPage: min((int) $request->query('per_page', 50), 100),
        )->withQueryString();

        return response()->json([
            'exercises' => ExerciseResource::collection($exercises->items()),
            'meta' => [
                'current_page' => $exercises->currentPage(),
                'last_page' => $exercises->lastPage(),
                'per_page' => $exercises->perPage(),
                'total' => $exercises->total(),
            ],
        ]);
    }

    public function show(Request $request, Exercise $exercise): JsonResponse
    {
        $this->authorize('view', $exercise);

        return response()->json(['exercise' => new ExerciseResource($exercise)]);
    }

    public function store(StoreExerciseRequest $request): JsonResponse
    {
        $this->authorize('create', Exercise::class);

        // Ownership from the session. A custom exercise is created through the
        // user's own relation, so there is no user_id to supply or to forge.
        $exercise = $request->user()->exercises()->create($request->safe()->all());

        return response()->json(['exercise' => new ExerciseResource($exercise)], 201);
    }

    public function update(UpdateExerciseRequest $request, Exercise $exercise): JsonResponse
    {
        $this->authorize('update', $exercise);

        $exercise->fill($request->safe()->all())->save();

        return response()->json(['exercise' => new ExerciseResource($exercise->fresh())]);
    }

    /**
     * Archive, or delete outright when nothing depends on it.
     *
     * A program that prescribes an exercise must keep being able to name it, and
     * from Milestone 3 so must every workout ever performed with it. The
     * response says which of the two happened rather than leaving the client to
     * guess from a 204.
     */
    public function destroy(Request $request, Exercise $exercise): JsonResponse
    {
        $this->authorize('delete', $exercise);

        if ($exercise->isReferenced()) {
            $exercise->forceFill(['archived_at' => now()])->save();

            return response()->json([
                'action' => 'archived',
                'message' => 'This exercise is used by a program, so it was archived rather than deleted.',
                'exercise' => new ExerciseResource($exercise->fresh()),
            ]);
        }

        $exercise->delete();

        return response()->json(['action' => 'deleted']);
    }

    public function restore(Request $request, Exercise $exercise): JsonResponse
    {
        $this->authorize('restore', $exercise);

        if (! $exercise->isArchived()) {
            return response()->json([
                'error_code' => ErrorCode::CONFLICT,
                'message' => 'That exercise is not archived.',
                'correlation_id' => CorrelationId::current(),
            ], 409);
        }

        $exercise->forceFill(['archived_at' => null])->save();

        return response()->json(['exercise' => new ExerciseResource($exercise->fresh())]);
    }
}

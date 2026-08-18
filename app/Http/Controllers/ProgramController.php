<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Program\StoreProgramRequest;
use App\Http\Requests\Program\UpdateProgramRequest;
use App\Http\Resources\ProgramResource;
use App\Models\WorkoutTemplate;
use App\Services\ProgramDuplicator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class ProgramController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WorkoutTemplate::class);

        $query = $request->user()->workoutTemplates()->getQuery();

        if (! $request->boolean('include_archived')) {
            $query->active();
        }

        $programs = $query->with('templateExercises.exercise')->orderBy('name')->get();

        return response()->json(['programs' => ProgramResource::collection($programs)]);
    }

    public function show(Request $request, WorkoutTemplate $program): JsonResponse
    {
        $this->authorize('view', $program);

        $program->load('templateExercises.exercise');

        return response()->json(['program' => new ProgramResource($program)]);
    }

    public function store(StoreProgramRequest $request): JsonResponse
    {
        $this->authorize('create', WorkoutTemplate::class);

        $program = $request->user()->workoutTemplates()->create($request->safe()->all());
        $program->load('templateExercises.exercise');

        return response()->json(['program' => new ProgramResource($program)], 201);
    }

    /**
     * Editing a program changes what the next session will ask for. It never
     * changes what a past session recorded — from Milestone 3 the prescription
     * is snapshotted into the workout when the session starts, and the snapshot
     * is never written to again.
     */
    public function update(UpdateProgramRequest $request, WorkoutTemplate $program): JsonResponse
    {
        $this->authorize('update', $program);

        $program->fill($request->safe()->all())->save();
        $program->load('templateExercises.exercise');

        return response()->json(['program' => new ProgramResource($program->fresh('templateExercises'))]);
    }

    public function destroy(Request $request, WorkoutTemplate $program): JsonResponse
    {
        $this->authorize('delete', $program);

        // Programs are archived, never destroyed: workout history refers to the
        // program it came from, and a dangling reference is worse than a row
        // nobody looks at any more.
        $program->forceFill(['archived_at' => now(), 'is_active' => false])->save();

        return response()->json([
            'action' => 'archived',
            'program' => new ProgramResource($program->fresh()),
        ]);
    }

    public function restore(Request $request, WorkoutTemplate $program): JsonResponse
    {
        $this->authorize('restore', $program);

        $program->forceFill(['archived_at' => null])->save();

        return response()->json(['program' => new ProgramResource($program->fresh())]);
    }

    public function duplicate(Request $request, WorkoutTemplate $program, ProgramDuplicator $duplicator): JsonResponse
    {
        $this->authorize('view', $program);
        $this->authorize('create', WorkoutTemplate::class);

        $name = trim((string) $request->input('name', $program->name.' (copy)'));

        if ($name === '' || mb_strlen($name) > 255) {
            throw ValidationException::withMessages([
                'name' => 'Give the copy a name of up to 255 characters.',
            ]);
        }

        $taken = $request->user()->workoutTemplates()->whereRaw('lower(name) = ?', [mb_strtolower($name)])->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'name' => 'You already have a program with that name.',
            ]);
        }

        $copy = $duplicator->duplicate($program, $name);
        $copy->load('templateExercises.exercise');

        return response()->json(['program' => new ProgramResource($copy)], 201);
    }
}

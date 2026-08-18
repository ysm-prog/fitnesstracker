<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Program\ReorderTemplateExercisesRequest;
use App\Http\Requests\Program\StoreTemplateExerciseRequest;
use App\Http\Requests\Program\UpdateTemplateExerciseRequest;
use App\Http\Resources\ProgramResource;
use App\Http\Resources\TemplateExerciseResource;
use App\Models\TemplateExercise;
use App\Models\WorkoutTemplate;
use App\Services\ProgramExerciseSequencer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TemplateExerciseController extends Controller
{
    public function __construct(private readonly ProgramExerciseSequencer $sequencer) {}

    public function store(
        StoreTemplateExerciseRequest $request,
        WorkoutTemplate $program,
    ): JsonResponse {
        $this->authorize('update', $program);

        $templateExercise = $this->sequencer->append($program, $request->safe()->all());
        $templateExercise->load('exercise');

        return response()->json(
            ['template_exercise' => new TemplateExerciseResource($templateExercise)],
            201,
        );
    }

    public function update(
        UpdateTemplateExerciseRequest $request,
        WorkoutTemplate $program,
        TemplateExercise $templateExercise,
    ): JsonResponse {
        $this->assertBelongsToProgram($program, $templateExercise);
        $this->authorize('update', $templateExercise);

        $templateExercise->fill($request->safe()->all())->save();
        $templateExercise->load('exercise');

        return response()->json(['template_exercise' => new TemplateExerciseResource($templateExercise)]);
    }

    public function destroy(
        Request $request,
        WorkoutTemplate $program,
        TemplateExercise $templateExercise,
    ): JsonResponse {
        $this->assertBelongsToProgram($program, $templateExercise);
        $this->authorize('delete', $templateExercise);

        $this->sequencer->remove($templateExercise);

        $program->load('templateExercises.exercise');

        return response()->json(['program' => new ProgramResource($program)]);
    }

    public function reorder(
        ReorderTemplateExercisesRequest $request,
        WorkoutTemplate $program,
    ): JsonResponse {
        $this->authorize('update', $program);

        $this->sequencer->reorder(
            $program,
            array_map('intval', $request->validated('template_exercise_ids')),
        );

        $program->load('templateExercises.exercise');

        return response()->json(['program' => new ProgramResource($program->fresh('templateExercises.exercise'))]);
    }

    /**
     * A prescription reached through the wrong program is not a permission
     * problem to explain, it is a URL that does not identify anything. 404 also
     * avoids confirming that the identifier exists somewhere else.
     */
    private function assertBelongsToProgram(WorkoutTemplate $program, TemplateExercise $templateExercise): void
    {
        if ($templateExercise->workout_template_id !== $program->getKey()) {
            throw new NotFoundHttpException;
        }
    }
}

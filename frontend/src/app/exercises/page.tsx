"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState, type FormEvent } from "react";
import { api, ApiError } from "@/lib/api";
import { EQUIPMENT_LABELS, LOADING_LABELS, LOADING_PROGRESSION, MUSCLE_LABELS, restLabel } from "@/lib/labels";
import { exerciseSchema, fieldErrorsFrom } from "@/lib/schemas";
import type { Equipment, Exercise, LoadingType, MuscleGroup, Paginated } from "@/lib/types";
import { AppShell } from "@/components/app-shell";
import { Button, Card, Empty, ErrorNote, Field, Loading, Pill, Select, TextInput } from "@/components/ui";

export default function ExercisesPage() {
  const [search, setSearch] = useState("");
  const [muscle, setMuscle] = useState("");
  const [creating, setCreating] = useState(false);

  const query = new URLSearchParams({ per_page: "100" });
  if (search.trim()) query.set("q", search.trim());
  if (muscle) query.set("primary_muscle", muscle);

  const exercises = useQuery({
    queryKey: ["exercises", search.trim(), muscle],
    queryFn: () => api.get<Paginated<Exercise>>(`/exercises?${query.toString()}`),
  });

  return (
    <AppShell
      title="Exercises"
      action={
        <Button variant={creating ? "secondary" : "primary"} onClick={() => setCreating((open) => !open)}>
          {creating ? "Cancel" : "New"}
        </Button>
      }
    >
      <div className="flex flex-col gap-4">
        {creating && <NewExerciseForm onDone={() => setCreating(false)} />}

        <div className="flex flex-col gap-2">
          <TextInput
            placeholder="Search the library"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            aria-label="Search exercises"
            type="search"
          />
          <Select value={muscle} onChange={(event) => setMuscle(event.target.value)} aria-label="Filter by muscle">
            <option value="">All muscles</option>
            {Object.entries(MUSCLE_LABELS).map(([value, label]) => (
              <option key={value} value={value}>
                {label}
              </option>
            ))}
          </Select>
        </div>

        {exercises.isPending && <Loading />}

        {exercises.isError && (
          <ErrorNote
            message={exercises.error instanceof ApiError ? exercises.error.message : "Could not load the library."}
            correlationId={exercises.error instanceof ApiError ? exercises.error.correlationId : undefined}
          />
        )}

        {exercises.isSuccess && exercises.data.exercises.length === 0 && (
          <Empty
            title="Nothing matches"
            body={search || muscle ? "Try a different search or filter." : "The library is empty."}
          />
        )}

        <ul className="flex flex-col gap-2">
          {exercises.data?.exercises.map((exercise) => (
            <li key={exercise.id}>
              <ExerciseRow exercise={exercise} />
            </li>
          ))}
        </ul>

        {exercises.isSuccess && (
          <p className="pt-1 text-center text-xs" style={{ color: "var(--ink-muted)" }}>
            {exercises.data.meta.total} in your library
          </p>
        )}
      </div>
    </AppShell>
  );
}

function ExerciseRow({ exercise }: { exercise: Exercise }) {
  return (
    <Card>
      <div className="flex items-start justify-between gap-3">
        <div className="min-w-0">
          <p className="font-semibold">{exercise.name}</p>
          <p className="mt-0.5 text-sm" style={{ color: "var(--ink-muted)" }}>
            {MUSCLE_LABELS[exercise.primary_muscle]} · {EQUIPMENT_LABELS[exercise.equipment]} ·{" "}
            {restLabel(exercise.default_rest_seconds)}
          </p>
          {/* How it gets harder is the thing the coach will act on, so it is on
              the card rather than buried in a detail view. */}
          <p className="mt-1 text-xs" style={{ color: "var(--ink-muted)" }}>
            {LOADING_PROGRESSION[exercise.loading_type]}
            {exercise.loading_type === "external_weight" && ` · +${exercise.default_weight_increment_kg} kg steps`}
          </p>
        </div>
        <div className="flex flex-col items-end gap-1">
          <Pill tone={exercise.is_system ? "muted" : "accent"}>{exercise.is_system ? "System" : "Yours"}</Pill>
          {exercise.is_unilateral && <Pill>Per side</Pill>}
        </div>
      </div>
    </Card>
  );
}

const BODYWEIGHT_TYPES: LoadingType[] = ["bodyweight", "assisted_bodyweight"];

function NewExerciseForm({ onDone }: { onDone: () => void }) {
  const queryClient = useQueryClient();
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [loadingType, setLoadingType] = useState<LoadingType>("external_weight");

  const create = useMutation({
    mutationFn: (input: unknown) => api.post<{ exercise: Exercise }>("/exercises", input),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["exercises"] });
      onDone();
    },
  });

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);

    const parsed = exerciseSchema.safeParse({
      name: form.get("name"),
      primary_muscle: form.get("primary_muscle"),
      equipment: form.get("equipment"),
      loading_type: loadingType,
      default_weight_increment_kg: form.get("default_weight_increment_kg"),
      default_rest_seconds: form.get("default_rest_seconds"),
      // The API rejects a bodyweight movement that claims external loading, so
      // the flag is derived from the loading type rather than asked for twice.
      is_bodyweight: BODYWEIGHT_TYPES.includes(loadingType),
      is_unilateral: form.get("is_unilateral") === "on",
      instructions: (form.get("instructions") as string) || undefined,
    });

    if (!parsed.success) {
      setErrors(fieldErrorsFrom(parsed.error));
      return;
    }

    setErrors({});
    create.mutate(parsed.data);
  }

  const apiError = create.error instanceof ApiError ? create.error : null;

  return (
    <Card>
      <h2 className="font-semibold">New exercise</h2>

      {apiError && !apiError.isValidation && (
        <div className="mt-3">
          <ErrorNote message={apiError.message} correlationId={apiError.correlationId} />
        </div>
      )}

      <form onSubmit={onSubmit} className="mt-3 flex flex-col gap-3" noValidate>
        <Field label="Name" error={errors.name ?? apiError?.fieldError("name")}>
          <TextInput name="name" required />
        </Field>

        <div className="grid grid-cols-2 gap-3">
          <Field label="Primary muscle" error={errors.primary_muscle ?? apiError?.fieldError("primary_muscle")}>
            <Select name="primary_muscle" defaultValue="chest">
              {Object.entries(MUSCLE_LABELS).map(([value, label]) => (
                <option key={value} value={value as MuscleGroup}>
                  {label}
                </option>
              ))}
            </Select>
          </Field>

          <Field label="Equipment" error={errors.equipment ?? apiError?.fieldError("equipment")}>
            <Select name="equipment" defaultValue="barbell">
              {Object.entries(EQUIPMENT_LABELS).map(([value, label]) => (
                <option key={value} value={value as Equipment}>
                  {label}
                </option>
              ))}
            </Select>
          </Field>
        </div>

        <Field
          label="How it progresses"
          hint={LOADING_PROGRESSION[loadingType]}
          error={apiError?.fieldError("loading_type")}
        >
          <Select value={loadingType} onChange={(event) => setLoadingType(event.target.value as LoadingType)}>
            {Object.entries(LOADING_LABELS).map(([value, label]) => (
              <option key={value} value={value}>
                {label}
              </option>
            ))}
          </Select>
        </Field>

        <div className="grid grid-cols-2 gap-3">
          <Field
            label="Weight step (kg)"
            hint="The only jump the coach may add."
            error={errors.default_weight_increment_kg}
          >
            <TextInput
              name="default_weight_increment_kg"
              type="number"
              step="0.5"
              min="0.5"
              defaultValue="2.5"
              inputMode="decimal"
            />
          </Field>

          <Field label="Rest (seconds)" error={errors.default_rest_seconds}>
            <TextInput name="default_rest_seconds" type="number" step="15" min="0" max="900" defaultValue="120" inputMode="numeric" />
          </Field>
        </div>

        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" name="is_unilateral" className="size-5" />
          <span>One side at a time</span>
        </label>

        <Field label="Instructions" error={errors.instructions}>
          <textarea
            name="instructions"
            rows={2}
            className="rounded-lg border px-3 py-2"
            style={{ background: "var(--surface)", borderColor: "var(--line)", color: "var(--ink)" }}
          />
        </Field>

        <div className="flex gap-2">
          <Button type="submit" loading={create.isPending}>
            Add to library
          </Button>
          <Button type="button" variant="secondary" onClick={onDone}>
            Cancel
          </Button>
        </div>
      </form>
    </Card>
  );
}

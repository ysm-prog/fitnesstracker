"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { use, useState, type FormEvent } from "react";
import { api, ApiError } from "@/lib/api";
import { LOADING_PROGRESSION, MUSCLE_LABELS, repRangeLabel, restLabel } from "@/lib/labels";
import { fieldErrorsFrom, prescriptionSchema } from "@/lib/schemas";
import type { Exercise, Paginated, Program, TemplateExercise } from "@/lib/types";
import { AppShell } from "@/components/app-shell";
import { Button, Card, Empty, ErrorNote, Field, Loading, Pill, Select, TextInput } from "@/components/ui";

export default function ProgramPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params);
  const queryClient = useQueryClient();
  const [adding, setAdding] = useState(false);

  const program = useQuery({
    queryKey: ["program", id],
    queryFn: () => api.get<{ program: Program }>(`/programs/${id}`),
  });

  const invalidate = async () => {
    await queryClient.invalidateQueries({ queryKey: ["program", id] });
    await queryClient.invalidateQueries({ queryKey: ["programs"] });
  };

  const setActive = useMutation({
    mutationFn: (isActive: boolean) => api.patch<{ program: Program }>(`/programs/${id}`, { is_active: isActive }),
    onSuccess: invalidate,
  });

  const reorder = useMutation({
    // The API takes the complete sequence, not a pair to swap: partial input is
    // how two clients drift out of agreement about the order.
    mutationFn: (ids: number[]) =>
      api.put<{ program: Program }>(`/programs/${id}/exercises/reorder`, { template_exercise_ids: ids }),
    onSuccess: invalidate,
  });

  const remove = useMutation({
    mutationFn: (templateExerciseId: number) =>
      api.delete<{ program: Program }>(`/programs/${id}/exercises/${templateExerciseId}`),
    onSuccess: invalidate,
  });

  if (program.isPending) {
    return (
      <AppShell title="Program">
        <Loading />
      </AppShell>
    );
  }

  if (program.isError) {
    const error = program.error instanceof ApiError ? program.error : null;
    return (
      <AppShell title="Program">
        <ErrorNote
          message={error?.status === 404 ? "That program does not exist." : (error?.message ?? "Could not load it.")}
          correlationId={error?.correlationId}
        />
      </AppShell>
    );
  }

  const current = program.data.program;
  const order = current.exercises.map((item) => item.id);

  function move(index: number, direction: -1 | 1) {
    const next = [...order];
    const target = index + direction;
    if (target < 0 || target >= next.length) return;
    [next[index], next[target]] = [next[target], next[index]];
    reorder.mutate(next);
  }

  return (
    <AppShell
      title={current.name}
      action={
        <Button variant="secondary" onClick={() => setActive.mutate(!current.is_active)} loading={setActive.isPending}>
          {current.is_active ? "Pause" : "Activate"}
        </Button>
      }
    >
      <div className="flex flex-col gap-4">
        <div className="flex items-center gap-2">
          <Pill tone={current.is_active ? "accent" : "muted"}>{current.is_active ? "Active" : "Paused"}</Pill>
          {current.description && (
            <span className="text-sm" style={{ color: "var(--ink-muted)" }}>
              {current.description}
            </span>
          )}
        </div>

        {current.exercises.length === 0 && !adding && (
          <Empty
            title="No exercises yet"
            body="Add the movements this session asks for, in the order you do them."
            action={<Button onClick={() => setAdding(true)}>Add the first</Button>}
          />
        )}

        <ol className="flex flex-col gap-2">
          {current.exercises.map((item, index) => (
            <li key={item.id}>
              <PrescriptionCard
                item={item}
                isFirst={index === 0}
                isLast={index === current.exercises.length - 1}
                busy={reorder.isPending || remove.isPending}
                onMoveUp={() => move(index, -1)}
                onMoveDown={() => move(index, 1)}
                onRemove={() => remove.mutate(item.id)}
              />
            </li>
          ))}
        </ol>

        {adding ? (
          <AddPrescriptionForm programId={id} onDone={() => setAdding(false)} />
        ) : (
          current.exercises.length > 0 && <Button onClick={() => setAdding(true)}>Add an exercise</Button>
        )}
      </div>
    </AppShell>
  );
}

function PrescriptionCard({
  item,
  isFirst,
  isLast,
  busy,
  onMoveUp,
  onMoveDown,
  onRemove,
}: {
  item: TemplateExercise;
  isFirst: boolean;
  isLast: boolean;
  busy: boolean;
  onMoveUp: () => void;
  onMoveDown: () => void;
  onRemove: () => void;
}) {
  const { prescription: p, exercise } = item;

  return (
    <Card>
      <div className="flex items-start gap-3">
        <span
          className="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-md text-sm font-semibold tabular-nums"
          style={{ background: "var(--surface-2)", color: "var(--ink-mid)" }}
        >
          {item.position}
        </span>

        <div className="min-w-0 flex-1">
          <div className="flex items-start justify-between gap-2">
            <p className="font-semibold">{exercise.name}</p>
            {item.is_optional && <Pill>Optional</Pill>}
          </div>

          <p className="mt-1 font-medium tabular-nums" style={{ color: "var(--accent-ink)" }}>
            {p.target_sets} × {repRangeLabel(p.min_reps, p.max_reps)}
            {p.target_rir !== null && ` @ RIR ${p.target_rir}`}
          </p>

          <p className="mt-0.5 text-sm" style={{ color: "var(--ink-muted)" }}>
            {MUSCLE_LABELS[exercise.primary_muscle]} · {restLabel(p.rest_seconds)}
          </p>

          {item.notes && (
            <p className="mt-1 text-sm" style={{ color: "var(--ink-mid)" }}>
              {item.notes}
            </p>
          )}

          <div className="mt-3 flex flex-wrap gap-2">
            <Button variant="secondary" onClick={onMoveUp} disabled={isFirst || busy} aria-label="Move up">
              ↑
            </Button>
            <Button variant="secondary" onClick={onMoveDown} disabled={isLast || busy} aria-label="Move down">
              ↓
            </Button>
            <Button variant="danger" onClick={onRemove} disabled={busy}>
              Remove
            </Button>
          </div>
        </div>
      </div>
    </Card>
  );
}

function AddPrescriptionForm({ programId, onDone }: { programId: string; onDone: () => void }) {
  const queryClient = useQueryClient();
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [selectedId, setSelectedId] = useState<string>("");

  const exercises = useQuery({
    queryKey: ["exercises", "", ""],
    queryFn: () => api.get<Paginated<Exercise>>("/exercises?per_page=100"),
  });

  const create = useMutation({
    mutationFn: (input: unknown) => api.post(`/programs/${programId}/exercises`, input),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["program", programId] });
      await queryClient.invalidateQueries({ queryKey: ["programs"] });
      onDone();
    },
  });

  const selected = exercises.data?.exercises.find((exercise) => String(exercise.id) === selectedId);

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const rir = form.get("target_rir") as string;

    const parsed = prescriptionSchema.safeParse({
      exercise_id: form.get("exercise_id"),
      target_sets: form.get("target_sets"),
      min_reps: form.get("min_reps"),
      max_reps: form.get("max_reps"),
      target_rir: rir === "" ? null : rir,
      rest_seconds: form.get("rest_seconds"),
      is_optional: form.get("is_optional") === "on",
      notes: (form.get("notes") as string) || undefined,
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
      <h2 className="font-semibold">Add an exercise</h2>

      {apiError && !apiError.isValidation && (
        <div className="mt-3">
          <ErrorNote message={apiError.message} correlationId={apiError.correlationId} />
        </div>
      )}

      <form onSubmit={onSubmit} className="mt-3 flex flex-col gap-3" noValidate>
        <Field
          label="Exercise"
          hint={selected ? LOADING_PROGRESSION[selected.loading_type] : undefined}
          error={errors.exercise_id ?? apiError?.fieldError("exercise_id")}
        >
          <Select
            name="exercise_id"
            value={selectedId}
            onChange={(event) => setSelectedId(event.target.value)}
            required
          >
            <option value="">Choose…</option>
            {exercises.data?.exercises.map((exercise) => (
              <option key={exercise.id} value={exercise.id}>
                {exercise.name}
              </option>
            ))}
          </Select>
        </Field>

        <div className="grid grid-cols-3 gap-3">
          <Field label="Sets" error={errors.target_sets ?? apiError?.fieldError("target_sets")}>
            <TextInput name="target_sets" type="number" min="1" max="20" defaultValue="3" inputMode="numeric" />
          </Field>
          <Field label="Min reps" error={errors.min_reps ?? apiError?.fieldError("min_reps")}>
            <TextInput name="min_reps" type="number" min="1" max="100" defaultValue="8" inputMode="numeric" />
          </Field>
          <Field label="Max reps" error={errors.max_reps ?? apiError?.fieldError("max_reps")}>
            <TextInput name="max_reps" type="number" min="1" max="100" defaultValue="10" inputMode="numeric" />
          </Field>
        </div>

        <div className="grid grid-cols-2 gap-3">
          <Field
            label="Target RIR"
            hint="Reps left in the tank."
            error={errors.target_rir ?? apiError?.fieldError("target_rir")}
          >
            <Select name="target_rir" defaultValue="2">
              <option value="">Not set</option>
              {[0, 1, 2, 3, 4, 5].map((value) => (
                <option key={value} value={value}>
                  {value}
                </option>
              ))}
            </Select>
          </Field>

          <Field label="Rest (seconds)" error={errors.rest_seconds ?? apiError?.fieldError("rest_seconds")}>
            <TextInput
              name="rest_seconds"
              type="number"
              min="0"
              max="900"
              step="15"
              defaultValue={selected?.default_rest_seconds ?? 180}
              key={selected?.id ?? "none"}
              inputMode="numeric"
            />
          </Field>
        </div>

        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" name="is_optional" className="size-5" />
          <span>Optional — excluded from adherence</span>
        </label>

        <Field label="Notes" error={errors.notes}>
          <TextInput name="notes" placeholder="Leave one in the tank on the last set" />
        </Field>

        <div className="flex gap-2">
          <Button type="submit" loading={create.isPending}>
            Add
          </Button>
          <Button type="button" variant="secondary" onClick={onDone}>
            Cancel
          </Button>
        </div>
      </form>
    </Card>
  );
}

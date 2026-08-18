"use client";

import Link from "next/link";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState, type FormEvent } from "react";
import { api, ApiError } from "@/lib/api";
import { fieldErrorsFrom, programSchema } from "@/lib/schemas";
import type { Program } from "@/lib/types";
import { AppShell } from "@/components/app-shell";
import { Button, Card, Empty, ErrorNote, Field, Loading, Pill, TextInput } from "@/components/ui";

export default function ProgramsPage() {
  const [creating, setCreating] = useState(false);

  const programs = useQuery({
    queryKey: ["programs"],
    queryFn: () => api.get<{ programs: Program[] }>("/programs"),
  });

  return (
    <AppShell
      title="Programs"
      action={
        <Button variant={creating ? "secondary" : "primary"} onClick={() => setCreating((open) => !open)}>
          {creating ? "Cancel" : "New"}
        </Button>
      }
    >
      <div className="flex flex-col gap-4">
        {creating && <NewProgramForm onDone={() => setCreating(false)} />}

        {programs.isPending && <Loading />}

        {programs.isError && (
          <ErrorNote
            message={programs.error instanceof ApiError ? programs.error.message : "Could not load your programs."}
          />
        )}

        {programs.isSuccess && programs.data.programs.length === 0 && !creating && (
          <Empty
            title="No programs yet"
            body="A program is a named, ordered list of prescriptions — what to do, how much, and how hard."
            action={<Button onClick={() => setCreating(true)}>Create one</Button>}
          />
        )}

        <ul className="flex flex-col gap-2">
          {programs.data?.programs.map((program) => (
            <li key={program.id}>
              <Link href={`/programs/${program.id}`}>
                <Card>
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                      <p className="font-semibold">{program.name}</p>
                      {program.description && (
                        <p className="mt-0.5 text-sm" style={{ color: "var(--ink-muted)" }}>
                          {program.description}
                        </p>
                      )}
                      <p className="mt-1 text-xs" style={{ color: "var(--ink-muted)" }}>
                        {program.exercises.length} {program.exercises.length === 1 ? "exercise" : "exercises"}
                      </p>
                    </div>
                    <Pill tone={program.is_active ? "accent" : "muted"}>
                      {program.is_active ? "Active" : "Paused"}
                    </Pill>
                  </div>
                </Card>
              </Link>
            </li>
          ))}
        </ul>
      </div>
    </AppShell>
  );
}

function NewProgramForm({ onDone }: { onDone: () => void }) {
  const queryClient = useQueryClient();
  const [errors, setErrors] = useState<Record<string, string>>({});

  const create = useMutation({
    mutationFn: (input: unknown) => api.post<{ program: Program }>("/programs", input),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["programs"] });
      onDone();
    },
  });

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const parsed = programSchema.safeParse({
      name: form.get("name"),
      description: (form.get("description") as string) || undefined,
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
      <h2 className="font-semibold">New program</h2>

      {apiError && !apiError.isValidation && (
        <div className="mt-3">
          <ErrorNote message={apiError.message} correlationId={apiError.correlationId} />
        </div>
      )}

      <form onSubmit={onSubmit} className="mt-3 flex flex-col gap-3" noValidate>
        <Field label="Name" error={errors.name ?? apiError?.fieldError("name")}>
          <TextInput name="name" placeholder="Upper A" required />
        </Field>

        <Field label="Description" error={errors.description}>
          <TextInput name="description" placeholder="Upper body, strength emphasis" />
        </Field>

        <div className="flex gap-2">
          <Button type="submit" loading={create.isPending}>
            Create
          </Button>
          <Button type="button" variant="secondary" onClick={onDone}>
            Cancel
          </Button>
        </div>
      </form>
    </Card>
  );
}

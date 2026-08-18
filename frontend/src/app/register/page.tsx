"use client";

import Link from "next/link";
import { useState, type FormEvent } from "react";
import { ApiError } from "@/lib/api";
import { useRegister } from "@/lib/auth";
import { fieldErrorsFrom, registerSchema } from "@/lib/schemas";
import { Button, ErrorNote, Field, TextInput } from "@/components/ui";

export default function RegisterPage() {
  const register = useRegister();
  const [errors, setErrors] = useState<Record<string, string>>({});

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const parsed = registerSchema.safeParse({
      name: form.get("name"),
      email: form.get("email"),
      password: form.get("password"),
      password_confirmation: form.get("password_confirmation"),
    });

    if (!parsed.success) {
      setErrors(fieldErrorsFrom(parsed.error));
      return;
    }

    setErrors({});
    register.mutate(parsed.data);
  }

  const apiError = register.error instanceof ApiError ? register.error : null;

  return (
    <main className="mx-auto flex min-h-dvh max-w-md flex-col justify-center gap-6 p-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Create your account</h1>
        <p className="mt-1 text-sm" style={{ color: "var(--ink-muted)" }}>
          One account, one training history.
        </p>
      </div>

      {apiError && !apiError.isValidation && (
        <ErrorNote message={apiError.message} correlationId={apiError.correlationId} />
      )}

      <form onSubmit={onSubmit} className="flex flex-col gap-4" noValidate>
        <Field label="Name" error={errors.name ?? apiError?.fieldError("name")}>
          <TextInput name="name" autoComplete="name" required />
        </Field>

        <Field label="Email" error={errors.email ?? apiError?.fieldError("email")}>
          <TextInput name="email" type="email" autoComplete="email" inputMode="email" required />
        </Field>

        <Field
          label="Password"
          hint="At least 8 characters."
          error={errors.password ?? apiError?.fieldError("password")}
        >
          <TextInput name="password" type="password" autoComplete="new-password" required />
        </Field>

        <Field label="Confirm password" error={errors.password_confirmation}>
          <TextInput name="password_confirmation" type="password" autoComplete="new-password" required />
        </Field>

        <Button type="submit" loading={register.isPending}>
          Create account
        </Button>
      </form>

      <p className="text-center text-sm" style={{ color: "var(--ink-muted)" }}>
        Already have one?{" "}
        <Link href="/login" style={{ color: "var(--accent-ink)" }}>
          Sign in
        </Link>
      </p>
    </main>
  );
}

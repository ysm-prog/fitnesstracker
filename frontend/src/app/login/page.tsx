"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState, type FormEvent } from "react";
import { ApiError } from "@/lib/api";
import { useCurrentUser, useLogin } from "@/lib/auth";
import { fieldErrorsFrom, loginSchema } from "@/lib/schemas";
import { Button, ErrorNote, Field, TextInput } from "@/components/ui";

export default function LoginPage() {
  const login = useLogin();
  const router = useRouter();
  const { data: user } = useCurrentUser();
  const [errors, setErrors] = useState<Record<string, string>>({});

  useEffect(() => {
    if (user) router.replace("/");
  }, [user, router]);

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const parsed = loginSchema.safeParse({
      email: form.get("email"),
      password: form.get("password"),
    });

    if (!parsed.success) {
      setErrors(fieldErrorsFrom(parsed.error));
      return;
    }

    setErrors({});
    login.mutate(parsed.data);
  }

  const apiError = login.error instanceof ApiError ? login.error : null;

  return (
    <main className="mx-auto flex min-h-dvh max-w-md flex-col justify-center gap-6 p-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Adaptive Fitness Coach</h1>
        <p className="mt-1 text-sm" style={{ color: "var(--ink-muted)" }}>
          Sign in to pick up where you left off.
        </p>
      </div>

      {apiError && !apiError.isValidation && (
        <ErrorNote message={apiError.message} correlationId={apiError.correlationId} />
      )}

      <form onSubmit={onSubmit} className="flex flex-col gap-4" noValidate>
        <Field label="Email" error={errors.email ?? apiError?.fieldError("email")}>
          <TextInput name="email" type="email" autoComplete="email" inputMode="email" required />
        </Field>

        <Field label="Password" error={errors.password ?? apiError?.fieldError("password")}>
          <TextInput name="password" type="password" autoComplete="current-password" required />
        </Field>

        <Button type="submit" loading={login.isPending}>
          Sign in
        </Button>
      </form>

      <p className="text-center text-sm" style={{ color: "var(--ink-muted)" }}>
        No account yet?{" "}
        <Link href="/register" style={{ color: "var(--accent-ink)" }}>
          Create one
        </Link>
      </p>
    </main>
  );
}

"use client";

import { cloneElement, isValidElement, useId, type ButtonHTMLAttributes, type InputHTMLAttributes, type ReactElement, type ReactNode, type SelectHTMLAttributes } from "react";

export function Card({ children, className = "" }: { children: ReactNode; className?: string }) {
  return (
    <div
      className={`rounded-xl border p-4 ${className}`}
      style={{ background: "var(--surface)", borderColor: "var(--line)" }}
    >
      {children}
    </div>
  );
}

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: "primary" | "secondary" | "danger" | "ghost";
  loading?: boolean;
};

export function Button({ variant = "primary", loading = false, children, ...props }: ButtonProps) {
  const palette = {
    primary: { background: "var(--accent)", color: "var(--accent-contrast)", borderColor: "var(--accent)" },
    secondary: { background: "var(--surface)", color: "var(--ink)", borderColor: "var(--line)" },
    danger: { background: "var(--danger-soft)", color: "var(--danger)", borderColor: "var(--danger)" },
    ghost: { background: "transparent", color: "var(--accent-ink)", borderColor: "transparent" },
  }[variant];

  return (
    <button
      {...props}
      disabled={props.disabled || loading}
      // 44px minimum: a thumb, not a mouse pointer.
      className={`min-h-11 rounded-lg border px-4 font-medium transition-opacity disabled:opacity-50 ${props.className ?? ""}`}
      style={palette}
    >
      {loading ? "Working…" : children}
    </button>
  );
}

/**
 * A labelled control.
 *
 * The label is associated explicitly, by id, rather than by wrapping the input.
 * Wrapping is shorter, but it makes every word inside the element part of the
 * control's accessible name — so a hint becomes part of the field's name, and
 * an error message renames the field the moment it appears. The hint and the
 * error are attached with aria-describedby instead, which is what they are.
 */
export function Field({
  label,
  error,
  hint,
  children,
}: {
  label: string;
  error?: string;
  hint?: string;
  children: ReactNode;
}) {
  const id = useId();
  const hintId = `${id}-hint`;
  const errorId = `${id}-error`;

  const describedBy =
    [hint && !error ? hintId : null, error ? errorId : null].filter(Boolean).join(" ") || undefined;

  const control = isValidElement(children)
    ? cloneElement(children as ReactElement<Record<string, unknown>>, {
        id,
        "aria-describedby": describedBy,
        "aria-invalid": error ? true : undefined,
      })
    : children;

  return (
    <div className="flex flex-col gap-1.5">
      <label htmlFor={id} className="text-sm font-medium" style={{ color: "var(--ink-mid)" }}>
        {label}
      </label>
      {control}
      {hint && !error && (
        <p id={hintId} className="text-xs" style={{ color: "var(--ink-muted)" }}>
          {hint}
        </p>
      )}
      {error && (
        <p id={errorId} className="text-xs font-medium" role="alert" style={{ color: "var(--danger)" }}>
          {error}
        </p>
      )}
    </div>
  );
}

export function TextInput(props: InputHTMLAttributes<HTMLInputElement>) {
  return (
    <input
      {...props}
      className={`min-h-11 rounded-lg border px-3 ${props.className ?? ""}`}
      style={{ background: "var(--surface)", borderColor: "var(--line)", color: "var(--ink)" }}
    />
  );
}

export function Select(props: SelectHTMLAttributes<HTMLSelectElement>) {
  return (
    <select
      {...props}
      className={`min-h-11 rounded-lg border px-3 ${props.className ?? ""}`}
      style={{ background: "var(--surface)", borderColor: "var(--line)", color: "var(--ink)" }}
    />
  );
}

export function Pill({ children, tone = "neutral" }: { children: ReactNode; tone?: "neutral" | "accent" | "muted" }) {
  const palette = {
    neutral: { background: "var(--surface-2)", color: "var(--ink-mid)" },
    accent: { background: "var(--surface-2)", color: "var(--accent-ink)" },
    muted: { background: "transparent", color: "var(--ink-muted)" },
  }[tone];

  return (
    <span className="rounded-md px-2 py-0.5 text-xs font-medium whitespace-nowrap" style={palette}>
      {children}
    </span>
  );
}

/**
 * Every list has three states beyond "here is the data", and saying which one
 * you are in is the difference between a considered screen and a blank one.
 */
export function Empty({ title, body, action }: { title: string; body: string; action?: ReactNode }) {
  return (
    <Card className="text-center">
      <p className="font-semibold">{title}</p>
      <p className="mt-1 text-sm" style={{ color: "var(--ink-muted)" }}>
        {body}
      </p>
      {action && <div className="mt-4 flex justify-center">{action}</div>}
    </Card>
  );
}

export function Loading({ label = "Loading…" }: { label?: string }) {
  return (
    <p className="py-8 text-center text-sm" role="status" style={{ color: "var(--ink-muted)" }}>
      {label}
    </p>
  );
}

export function ErrorNote({ message, correlationId }: { message: string; correlationId?: string }) {
  return (
    <div
      className="rounded-lg border p-3 text-sm"
      role="alert"
      style={{ background: "var(--danger-soft)", borderColor: "var(--danger)", color: "var(--danger)" }}
    >
      <p className="font-medium">{message}</p>
      {correlationId && (
        <p className="mt-1 text-xs opacity-80">Reference: {correlationId}</p>
      )}
    </div>
  );
}

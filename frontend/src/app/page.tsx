"use client";

import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import { api } from "@/lib/api";
import { useCurrentUser } from "@/lib/auth";
import type { Program } from "@/lib/types";
import { AppShell } from "@/components/app-shell";
import { Card, Empty, Loading, Pill } from "@/components/ui";

export default function DashboardPage() {
  const { data: user } = useCurrentUser();
  const programs = useQuery({
    queryKey: ["programs"],
    queryFn: () => api.get<{ programs: Program[] }>("/programs"),
  });

  const active = programs.data?.programs.filter((program) => program.is_active) ?? [];

  return (
    <AppShell title="Today">
      <div className="flex flex-col gap-4">
        <p style={{ color: "var(--ink-mid)" }}>
          {user ? `Welcome back, ${user.name.split(" ")[0]}.` : ""}
        </p>

        <section className="flex flex-col gap-3">
          <h2 className="text-sm font-semibold tracking-wide uppercase" style={{ color: "var(--ink-muted)" }}>
            Active programs
          </h2>

          {programs.isPending && <Loading />}

          {programs.isSuccess && active.length === 0 && (
            <Empty
              title="No active program yet"
              body="Build one from the exercise library and it will show up here."
              action={
                <Link
                  href="/programs"
                  className="min-h-11 rounded-lg border px-4 py-2.5 font-medium"
                  style={{ background: "var(--accent)", color: "var(--accent-contrast)", borderColor: "var(--accent)" }}
                >
                  Create a program
                </Link>
              }
            />
          )}

          {active.map((program) => (
            <Link key={program.id} href={`/programs/${program.id}`}>
              <Card>
                <div className="flex items-start justify-between gap-3">
                  <div>
                    <p className="font-semibold">{program.name}</p>
                    <p className="mt-0.5 text-sm" style={{ color: "var(--ink-muted)" }}>
                      {program.exercises.length}{" "}
                      {program.exercises.length === 1 ? "exercise" : "exercises"}
                    </p>
                  </div>
                  <Pill tone="accent">Active</Pill>
                </div>
              </Card>
            </Link>
          ))}
        </section>

        <Card>
          <p className="text-sm font-semibold">Training Analysis</p>
          <p className="mt-1 text-sm" style={{ color: "var(--ink-muted)" }}>
            Once you log a session, the coach works out whether to add weight, hold, or back off — from
            your recorded sets, by fixed rules. Workout logging arrives in the next milestone.
          </p>
        </Card>
      </div>
    </AppShell>
  );
}

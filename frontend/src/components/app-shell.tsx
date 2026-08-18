"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect, type ReactNode } from "react";
import { useCurrentUser, useLogout } from "@/lib/auth";
import { Loading } from "./ui";

const NAV = [
  { href: "/", label: "Today" },
  { href: "/programs", label: "Programs" },
  { href: "/exercises", label: "Exercises" },
];

/**
 * The signed-in frame.
 *
 * Navigation sits at the bottom because this is used one-handed while standing
 * up, and the top of a phone is the part a thumb cannot reach.
 */
export function AppShell({ title, action, children }: { title: string; action?: ReactNode; children: ReactNode }) {
  const { data: user, isPending, isError } = useCurrentUser();
  const router = useRouter();
  const pathname = usePathname();
  const logout = useLogout();

  useEffect(() => {
    if (!isPending && user === null) {
      router.replace("/login");
    }
  }, [isPending, user, router]);

  if (isPending) return <Loading />;

  if (isError) {
    return (
      <main className="mx-auto max-w-2xl p-4">
        <p className="text-sm" style={{ color: "var(--ink-muted)" }}>
          Could not reach the API. Check that it is running and that this origin is allowed.
        </p>
      </main>
    );
  }

  if (!user) return <Loading label="Redirecting to sign in…" />;

  return (
    <div className="min-h-dvh pb-20">
      <header
        className="sticky top-0 z-10 border-b px-4 py-3"
        style={{ background: "var(--surface)", borderColor: "var(--line)" }}
      >
        <div className="mx-auto flex max-w-2xl items-center justify-between gap-3">
          <h1 className="text-lg font-semibold tracking-tight">{title}</h1>
          <div className="flex items-center gap-2">
            {action}
            <button
              onClick={() => logout.mutate()}
              className="min-h-11 px-2 text-sm"
              style={{ color: "var(--ink-muted)" }}
            >
              Sign out
            </button>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-2xl p-4">{children}</main>

      <nav
        className="fixed inset-x-0 bottom-0 border-t"
        style={{ background: "var(--surface)", borderColor: "var(--line)" }}
      >
        <ul className="mx-auto flex max-w-2xl">
          {NAV.map((item) => {
            const active = item.href === "/" ? pathname === "/" : pathname.startsWith(item.href);

            return (
              <li key={item.href} className="flex-1">
                <Link
                  href={item.href}
                  aria-current={active ? "page" : undefined}
                  className="flex min-h-14 items-center justify-center text-sm font-medium"
                  style={{ color: active ? "var(--accent-ink)" : "var(--ink-muted)" }}
                >
                  {item.label}
                </Link>
              </li>
            );
          })}
        </ul>
      </nav>
    </div>
  );
}

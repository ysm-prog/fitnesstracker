import type { ApiErrorBody } from "./types";

/**
 * The API lives on a different host from this application — Laravel Cloud
 * rather than Vercel — so every request is cross-origin and must carry
 * credentials explicitly. Putting both under one registrable domain is what
 * keeps the session cookie same-site.
 */
export const API_URL = (
  process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000"
).replace(/\/$/, "");

/**
 * A failed request, carrying the server's own error vocabulary.
 *
 * The correlation ID is kept because it is the one thing that ties a screen a
 * user is looking at to a line in the API's logs.
 */
export class ApiError extends Error {
  readonly status: number;
  readonly code: string;
  readonly correlationId: string;
  readonly fieldErrors: Record<string, string[]>;

  constructor(status: number, body: Partial<ApiErrorBody>) {
    super(body.message ?? "Something went wrong.");
    this.name = "ApiError";
    this.status = status;
    this.code = body.error_code ?? "server_error";
    this.correlationId = body.correlation_id ?? "";
    this.fieldErrors = body.errors ?? {};
  }

  /** The first message for a field, for rendering next to the input. */
  fieldError(field: string): string | undefined {
    return this.fieldErrors[field]?.[0];
  }

  get isUnauthenticated(): boolean {
    return this.status === 401;
  }

  get isValidation(): boolean {
    return this.status === 422;
  }
}

function readCookie(name: string): string | undefined {
  if (typeof document === "undefined") return undefined;

  return document.cookie
    .split("; ")
    .find((row) => row.startsWith(`${name}=`))
    ?.split("=")
    .slice(1)
    .join("=");
}

let csrfReady: Promise<void> | null = null;

/**
 * Sanctum issues the CSRF cookie from its own endpoint, and every unsafe
 * request must echo it back as a header.
 *
 * Fetched once per page load and shared: without the guard, six components
 * mounting at once would each fire their own request for the same cookie.
 */
async function ensureCsrfCookie(): Promise<void> {
  if (readCookie("XSRF-TOKEN")) return;

  csrfReady ??= fetch(`${API_URL}/sanctum/csrf-cookie`, {
    credentials: "include",
    headers: { Accept: "application/json" },
  })
    .then(() => undefined)
    .finally(() => {
      csrfReady = null;
    });

  await csrfReady;
}

type RequestOptions = {
  method?: "GET" | "POST" | "PUT" | "PATCH" | "DELETE";
  body?: unknown;
  signal?: AbortSignal;
};

export async function apiRequest<T>(
  path: string,
  { method = "GET", body, signal }: RequestOptions = {},
): Promise<T> {
  const isUnsafe = method !== "GET";

  if (isUnsafe) {
    await ensureCsrfCookie();
  }

  const headers: Record<string, string> = {
    Accept: "application/json",
  };

  if (body !== undefined) {
    headers["Content-Type"] = "application/json";
  }

  if (isUnsafe) {
    const token = readCookie("XSRF-TOKEN");
    if (token) {
      // Laravel expects the decoded value; the cookie itself is URL-encoded.
      headers["X-XSRF-TOKEN"] = decodeURIComponent(token);
    }
  }

  const response = await fetch(`${API_URL}/api/v1${path}`, {
    method,
    headers,
    credentials: "include",
    body: body === undefined ? undefined : JSON.stringify(body),
    signal,
  });

  if (response.status === 204) {
    return undefined as T;
  }

  const text = await response.text();
  let payload: unknown = undefined;

  if (text) {
    try {
      payload = JSON.parse(text);
    } catch {
      // A response that is not JSON is a failure of the API contract, not
      // something to hand to a component half-parsed.
      throw new ApiError(response.status, {
        message: "The server returned an unexpected response.",
      });
    }
  }

  if (!response.ok) {
    throw new ApiError(response.status, (payload ?? {}) as Partial<ApiErrorBody>);
  }

  return payload as T;
}

export const api = {
  get: <T>(path: string, signal?: AbortSignal) => apiRequest<T>(path, { signal }),
  post: <T>(path: string, body?: unknown) => apiRequest<T>(path, { method: "POST", body }),
  put: <T>(path: string, body?: unknown) => apiRequest<T>(path, { method: "PUT", body }),
  patch: <T>(path: string, body?: unknown) => apiRequest<T>(path, { method: "PATCH", body }),
  delete: <T>(path: string, body?: unknown) => apiRequest<T>(path, { method: "DELETE", body }),
};

import { afterEach, describe, expect, it, vi } from "vitest";
import { ApiError, apiRequest } from "../api";

/** Await a request that is expected to fail, and get the error back typed. */
async function failureOf(request: Promise<unknown>): Promise<ApiError> {
  try {
    await request;
  } catch (error) {
    if (error instanceof ApiError) return error;
    throw error;
  }

  throw new Error("Expected the request to fail, but it succeeded.");
}

function jsonResponse(body: unknown, status = 200) {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

afterEach(() => {
  vi.restoreAllMocks();
  document.cookie = "XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/";
});

describe("apiRequest", () => {
  it("sends credentials so the session cookie travels cross-origin", async () => {
    const fetchMock = vi.spyOn(globalThis, "fetch").mockResolvedValue(jsonResponse({ ok: true }));

    await apiRequest("/profile");

    expect(fetchMock).toHaveBeenCalledOnce();
    const [, init] = fetchMock.mock.calls[0];
    expect(init?.credentials).toBe("include");
  });

  /**
   * The CSRF cookie is fetched before an unsafe request, not before every
   * request: a GET that triggered a round trip to Sanctum would double the
   * cost of loading any screen.
   */
  it("fetches the CSRF cookie before an unsafe request only", async () => {
    const fetchMock = vi.spyOn(globalThis, "fetch").mockImplementation((input) => {
      const url = String(input);
      if (url.includes("csrf-cookie")) {
        document.cookie = "XSRF-TOKEN=token-value; path=/";
        return Promise.resolve(new Response(null, { status: 204 }));
      }
      return Promise.resolve(jsonResponse({ ok: true }));
    });

    await apiRequest("/exercises");
    expect(fetchMock.mock.calls.filter(([url]) => String(url).includes("csrf-cookie"))).toHaveLength(0);

    await apiRequest("/exercises", { method: "POST", body: { name: "Squat" } });
    expect(fetchMock.mock.calls.filter(([url]) => String(url).includes("csrf-cookie"))).toHaveLength(1);
  });

  it("echoes the CSRF token back, url-decoded", async () => {
    document.cookie = "XSRF-TOKEN=abc%3D%3D; path=/";
    const fetchMock = vi.spyOn(globalThis, "fetch").mockResolvedValue(jsonResponse({ ok: true }));

    await apiRequest("/programs", { method: "POST", body: {} });

    const [, init] = fetchMock.mock.calls[0];
    expect((init?.headers as Record<string, string>)["X-XSRF-TOKEN"]).toBe("abc==");
  });

  it("turns the error envelope into a typed error with field messages", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(
      jsonResponse(
        {
          error_code: "validation_failed",
          message: "The submitted data is not valid.",
          correlation_id: "0195f3c2-1c8a-7f4d-9b3e-2a5c6d7e8f90",
          errors: { min_reps: ["The minimum repetitions must not exceed the maximum."] },
        },
        422,
      ),
    );

    const error = await failureOf(apiRequest("/programs/1/exercises", { method: "POST", body: {} }));

    expect(error.isValidation).toBe(true);
    expect(error.code).toBe("validation_failed");
    expect(error.correlationId).toBe("0195f3c2-1c8a-7f4d-9b3e-2a5c6d7e8f90");
    expect(error.fieldError("min_reps")).toBe("The minimum repetitions must not exceed the maximum.");
    expect(error.fieldError("target_sets")).toBeUndefined();
  });

  it("marks a 401 as unauthenticated rather than a generic failure", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(
      jsonResponse({ error_code: "unauthenticated", message: "Sign in to continue." }, 401),
    );

    const error = await failureOf(apiRequest("/profile"));

    expect(error.isUnauthenticated).toBe(true);
  });

  /** A non-JSON body is a broken contract, not something to hand on half-parsed. */
  it("refuses a response that is not JSON", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue(
      new Response("<html>502 Bad Gateway</html>", { status: 502 }),
    );

    const error = await failureOf(apiRequest("/profile"));

    expect(error.message).toBe("The server returned an unexpected response.");
  });
});

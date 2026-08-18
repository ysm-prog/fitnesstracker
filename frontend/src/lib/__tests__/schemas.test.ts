import { describe, expect, it } from "vitest";
import { fieldErrorsFrom, prescriptionSchema, registerSchema } from "../schemas";

const validPrescription = {
  exercise_id: 1,
  target_sets: 3,
  min_reps: 8,
  max_reps: 10,
  target_rir: 2,
  rest_seconds: 180,
  is_optional: false,
};

describe("prescriptionSchema", () => {
  it("accepts a normal prescription", () => {
    expect(prescriptionSchema.safeParse(validPrescription).success).toBe(true);
  });

  it("accepts a fixed rep target where minimum equals maximum", () => {
    expect(prescriptionSchema.safeParse({ ...validPrescription, min_reps: 5, max_reps: 5 }).success).toBe(true);
  });

  /** The mirror of the backend rule, and the one a rep range actually breaks. */
  it("rejects a minimum above the maximum, and says so on min_reps", () => {
    const result = prescriptionSchema.safeParse({ ...validPrescription, min_reps: 12, max_reps: 8 });

    expect(result.success).toBe(false);
    if (!result.success) {
      expect(fieldErrorsFrom(result.error).min_reps).toBe("The minimum must not exceed the maximum.");
    }
  });

  it.each([
    ["zero sets", { target_sets: 0 }],
    ["twenty-one sets", { target_sets: 21 }],
    ["zero reps", { min_reps: 0 }],
    ["one hundred and one reps", { max_reps: 101 }],
    ["RIR above five", { target_rir: 6 }],
    ["rest beyond fifteen minutes", { rest_seconds: 901 }],
    ["negative rest", { rest_seconds: -1 }],
  ])("rejects %s", (_label, override) => {
    expect(prescriptionSchema.safeParse({ ...validPrescription, ...override }).success).toBe(false);
  });

  it("coerces the strings a form actually submits", () => {
    const result = prescriptionSchema.safeParse({
      ...validPrescription,
      target_sets: "4",
      min_reps: "6",
      max_reps: "8",
    });

    expect(result.success).toBe(true);
    if (result.success) expect(result.data.target_sets).toBe(4);
  });
});

describe("registerSchema", () => {
  it("reports a password mismatch against the confirmation field", () => {
    const result = registerSchema.safeParse({
      name: "Sam",
      email: "sam@example.com",
      password: "correct-horse",
      password_confirmation: "something-else",
    });

    expect(result.success).toBe(false);
    if (!result.success) {
      expect(fieldErrorsFrom(result.error).password_confirmation).toBe("The passwords do not match.");
    }
  });
});

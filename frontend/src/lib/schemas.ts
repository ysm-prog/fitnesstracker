import { z } from "zod";

/**
 * Client-side validation, mirroring the Laravel Form Requests.
 *
 * These exist to give immediate feedback, not to decide anything. The backend
 * is authoritative: every rule here is enforced again server-side, and a
 * disagreement between the two is a bug in this file.
 */

export const loginSchema = z.object({
  email: z.string().trim().min(1, "Enter your email address.").email("That does not look like an email address."),
  password: z.string().min(1, "Enter your password."),
});

export const registerSchema = z
  .object({
    name: z.string().trim().min(1, "Enter your name.").max(255),
    email: z.string().trim().min(1, "Enter your email address.").email("That does not look like an email address."),
    password: z.string().min(8, "Use at least 8 characters."),
    password_confirmation: z.string(),
  })
  .refine((values) => values.password === values.password_confirmation, {
    message: "The passwords do not match.",
    path: ["password_confirmation"],
  });

export const exerciseSchema = z.object({
  name: z.string().trim().min(1, "Give the exercise a name.").max(255),
  primary_muscle: z.string().min(1, "Choose the primary muscle."),
  equipment: z.string().min(1, "Choose the equipment."),
  loading_type: z.string().min(1),
  default_weight_increment_kg: z.coerce
    .number()
    .gt(0, "The increment must be greater than zero.")
    .max(50, "That increment is implausibly large."),
  default_rest_seconds: z.coerce
    .number()
    .int()
    .min(0)
    .max(900, "Rest cannot exceed 900 seconds."),
  is_unilateral: z.boolean(),
  is_bodyweight: z.boolean(),
  instructions: z.string().max(5000).optional(),
});

export const programSchema = z.object({
  name: z.string().trim().min(1, "Give the program a name.").max(255),
  description: z.string().max(2000).optional(),
});

/**
 * The prescription ranges the coaching engine depends on. `min_reps` is checked
 * against `max_reps` rather than in isolation, because a minimum above the
 * maximum is the error a rep range actually suffers from.
 */
export const prescriptionSchema = z
  .object({
    exercise_id: z.coerce.number().int().positive("Choose an exercise."),
    target_sets: z.coerce.number().int().min(1, "At least one set.").max(20, "At most 20 sets."),
    min_reps: z.coerce.number().int().min(1, "At least one rep.").max(100),
    max_reps: z.coerce.number().int().min(1).max(100, "At most 100 reps."),
    target_rir: z.coerce.number().int().min(0).max(5, "RIR runs from 0 to 5.").nullable(),
    rest_seconds: z.coerce.number().int().min(0).max(900, "Rest cannot exceed 900 seconds."),
    is_optional: z.boolean(),
    notes: z.string().max(2000).optional(),
  })
  .refine((values) => values.min_reps <= values.max_reps, {
    message: "The minimum must not exceed the maximum.",
    path: ["min_reps"],
  });

export type LoginInput = z.infer<typeof loginSchema>;
export type RegisterInput = z.infer<typeof registerSchema>;
export type ExerciseInput = z.infer<typeof exerciseSchema>;
export type ProgramInput = z.infer<typeof programSchema>;
export type PrescriptionInput = z.infer<typeof prescriptionSchema>;

/** Flatten a Zod issue list into the same shape the API uses for field errors. */
export function fieldErrorsFrom(error: z.ZodError): Record<string, string> {
  const result: Record<string, string> = {};

  for (const issue of error.issues) {
    const key = issue.path.join(".");
    result[key] ??= issue.message;
  }

  return result;
}

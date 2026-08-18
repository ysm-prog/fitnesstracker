/**
 * The API's response shapes.
 *
 * These mirror the Laravel API Resources. The backend is authoritative: if the
 * two ever disagree, this file is wrong.
 */

export type LoadingType =
  | "external_weight"
  | "bodyweight"
  | "assisted_bodyweight"
  | "time"
  | "distance";

export type MuscleGroup =
  | "chest" | "back" | "lats" | "traps" | "shoulders" | "biceps" | "triceps"
  | "forearms" | "core" | "quads" | "hamstrings" | "glutes" | "calves"
  | "adductors" | "abductors" | "full_body";

export type Equipment =
  | "barbell" | "dumbbell" | "machine" | "cable" | "smith_machine"
  | "kettlebell" | "band" | "bodyweight" | "other";

export interface User {
  id: number;
  name: string;
  email: string;
  email_verified: boolean;
  created_at: string | null;
}

export interface Exercise {
  id: number;
  name: string;
  primary_muscle: MuscleGroup;
  secondary_muscles: MuscleGroup[];
  equipment: Equipment;
  instructions: string | null;
  loading_type: LoadingType;
  default_weight_increment_kg: number;
  is_unilateral: boolean;
  is_bodyweight: boolean;
  default_rest_seconds: number;
  is_system: boolean;
  is_archived: boolean;
  archived_at: string | null;
}

export interface Prescription {
  target_sets: number;
  min_reps: number;
  max_reps: number;
  target_rir: number | null;
  rest_seconds: number;
}

export interface TemplateExercise {
  id: number;
  position: number;
  prescription: Prescription;
  is_optional: boolean;
  notes: string | null;
  exercise: Exercise;
}

export interface Program {
  id: number;
  name: string;
  description: string | null;
  is_active: boolean;
  is_archived: boolean;
  archived_at: string | null;
  exercises: TemplateExercise[];
  updated_at: string | null;
}

export interface Paginated<T> {
  exercises: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

/** The one error shape every failure takes. See docs/api.md. */
export interface ApiErrorBody {
  error_code: string;
  message: string;
  correlation_id: string;
  errors?: Record<string, string[]>;
}

import type { Equipment, LoadingType, MuscleGroup } from "./types";

/**
 * Stored values are snake_case because they are a database contract. People
 * are not, so nothing snake_case ever reaches the screen.
 */

export const MUSCLE_LABELS: Record<MuscleGroup, string> = {
  chest: "Chest",
  back: "Back",
  lats: "Lats",
  traps: "Traps",
  shoulders: "Shoulders",
  biceps: "Biceps",
  triceps: "Triceps",
  forearms: "Forearms",
  core: "Core",
  quads: "Quads",
  hamstrings: "Hamstrings",
  glutes: "Glutes",
  calves: "Calves",
  adductors: "Adductors",
  abductors: "Abductors",
  full_body: "Full body",
};

export const EQUIPMENT_LABELS: Record<Equipment, string> = {
  barbell: "Barbell",
  dumbbell: "Dumbbell",
  machine: "Machine",
  cable: "Cable",
  smith_machine: "Smith machine",
  kettlebell: "Kettlebell",
  band: "Band",
  bodyweight: "Bodyweight",
  other: "Other",
};

export const LOADING_LABELS: Record<LoadingType, string> = {
  external_weight: "External weight",
  bodyweight: "Bodyweight",
  assisted_bodyweight: "Assisted",
  time: "Time",
  distance: "Distance",
};

/** How this movement gets harder — the thing the coach will act on later. */
export const LOADING_PROGRESSION: Record<LoadingType, string> = {
  external_weight: "Progresses by adding weight",
  bodyweight: "Progresses by adding reps",
  assisted_bodyweight: "Progresses by reducing assistance",
  time: "Progresses by holding longer",
  distance: "Progresses by covering more distance",
};

export function restLabel(seconds: number): string {
  if (seconds < 60) return `${seconds}s rest`;

  const minutes = Math.floor(seconds / 60);
  const remainder = seconds % 60;

  return remainder === 0 ? `${minutes}m rest` : `${minutes}m ${remainder}s rest`;
}

export function repRangeLabel(min: number, max: number): string {
  return min === max ? `${min}` : `${min}–${max}`;
}

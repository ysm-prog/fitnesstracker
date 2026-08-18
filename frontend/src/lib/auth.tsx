"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { api, ApiError } from "./api";
import type { User } from "./types";

interface ProfileResponse {
  user: User;
  fitness_profile: unknown;
}

/**
 * Who is signed in, according to the API.
 *
 * There is deliberately no client-side "am I logged in" flag. The session
 * cookie is the only truth, and it lives somewhere JavaScript cannot read it —
 * so the answer comes from asking the server, not from remembering.
 */
export function useCurrentUser() {
  return useQuery({
    queryKey: ["current-user"],
    queryFn: async () => {
      try {
        const response = await api.get<ProfileResponse>("/profile");
        return response.user;
      } catch (error) {
        // Not signed in is an answer, not a failure. Anything else is a real
        // error and should surface as one.
        if (error instanceof ApiError && error.isUnauthenticated) return null;
        throw error;
      }
    },
    retry: false,
    staleTime: 30_000,
  });
}

export function useLogin() {
  const queryClient = useQueryClient();
  const router = useRouter();

  return useMutation({
    mutationFn: (credentials: { email: string; password: string }) =>
      api.post<{ user: User }>("/auth/login", credentials),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["current-user"] });
      router.push("/");
    },
  });
}

export function useRegister() {
  const queryClient = useQueryClient();
  const router = useRouter();

  return useMutation({
    mutationFn: (input: {
      name: string;
      email: string;
      password: string;
      password_confirmation: string;
    }) => api.post<{ user: User }>("/auth/register", input),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["current-user"] });
      router.push("/");
    },
  });
}

export function useLogout() {
  const queryClient = useQueryClient();
  const router = useRouter();

  return useMutation({
    mutationFn: () => api.post<{ message: string }>("/auth/logout"),
    onSuccess: () => {
      // Clear everything, not just the user: the cache holds one person's
      // exercises and programs, and the next person to sign in on this device
      // must not see them.
      queryClient.clear();
      router.push("/login");
    },
  });
}

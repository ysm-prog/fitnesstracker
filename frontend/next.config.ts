import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // The repository root CLAUDE.md is this project's source of truth. Next
  // regenerates a generic nested one on every dev boot unless told not to,
  // and a second set of instructions competing with the real one is worse
  // than none.
  agentRules: false,
};

export default nextConfig;

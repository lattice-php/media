import react from "@vitejs/plugin-react";
import { defineConfig } from "vitest/config";

import { latticeDeepImports } from "./vite.lattice-deep-imports";

export default defineConfig({
  plugins: [react()],
  resolve: { alias: latticeDeepImports },
  test: {
    environment: "jsdom",
    include: ["resources/js/**/*.test.{ts,tsx}"],
    setupFiles: ["resources/js/test-setup.ts"],
  },
});

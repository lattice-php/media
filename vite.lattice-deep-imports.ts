import { fileURLToPath } from "node:url";

const dist = fileURLToPath(new URL("./node_modules/@lattice-php/lattice/dist/", import.meta.url));

/**
 * The published core's exports map only lists barrel entries (`./core`, `./ui`, …),
 * but this package imports the per-module files its preserveModules build emits
 * (`core/api`, `ui/button`, …). Resolve those nested specifiers against dist/
 * directly; single-segment specifiers keep going through the exports map so
 * `/css`, `/vite`, and the client type references stay intact.
 */
export const latticeDeepImports = [
  {
    find: /^@lattice-php\/lattice\/(.+\/.+)$/,
    replacement: `${dist}$1`,
  },
];

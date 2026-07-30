# Local Development

- This is a first-party companion package for [Lattice](https://github.com/lattice-php/lattice). It ships the media
  library — the PHP models, component, field, actions, and table plus its React renderer as **source** (no separate npm
  package); the consumer's build compiles it via Lattice's `lattice()` Vite plugin.
- The package is developed with Orchestra Testbench, not a full Laravel app. `artisan` at the repo root is a symlink to
  `vendor/bin/testbench`, so `php artisan <command>` boots the Testbench skeleton with Lattice's and this package's
  service providers.
- Run the PHP suite with `composer test` (the Feature suite; the Browser suite is excluded on purpose).
- Run the JavaScript (renderer) suite with `npm test` (Vitest). The tests exercise the renderer against the **published**
  `@lattice-php/lattice`, so `npm install` before running them.
- Run the browser suite with `composer test:browser`. It rebuilds the workbench bundle first (`npm run build`) so it
  can never test stale assets — do not bypass that by invoking Pest's Browser suite directly after renderer changes.
- Serve the workbench demo app with `composer serve` (starts at `/media`; `/media-picker` exercises the field inside a
  form — `testbench.yaml` holds the env/migrations/build wiring).
- The AI tooling overrides for Boost live in `workbench/app/Support/` and are wired in
  `Workbench\App\Providers\WorkbenchServiceProvider`. They point Boost at the package root instead of the Testbench
  skeleton.
- `CLAUDE.md` and `AGENTS.md` are generated (git-ignored). They regenerate automatically after `composer install`; run
  `php artisan boost:update` (or `composer boost:refresh`) by hand after editing files in `.ai/guidelines/`.

## Verification

- Before finishing a change, run the gate that matches what you touched:
  - PHP change → `composer check` (Pint, PHPStan, Pest).
  - Renderer change → `npm run typecheck` and `npm test`.
  - Anything touching the workbench app, an endpoint, or interactive behavior → additionally `composer test:browser`.
- Never report green without having run the gate. CI runs all three.

## Comments

- Code must be self-explanatory: reach for clear names, small functions, and types before a comment.
- Do not add comments. A comment is a last resort and explains only *why* something is done, never *what* the code does.
- When you encounter an obsolete, redundant, or "what" comment, delete it.
- Keep PHPDoc/JSDoc only when it carries type information, public API intent, static-analysis value, or a non-obvious
  constraint.
- Keep comments that explain framework quirks, ordering requirements, browser/test timing, or other constraints that are
  hard to infer from the code alone.

## Testing

- Prefer feature tests for backend behavior — serialize a component and assert its wire shape, drive an action or form
  through its endpoint, or assert the pivot after `syncMedia()`, rather than isolating internals.
- Endpoint behavior (refs, authorization, context round-trip) is feature-tested through HTTP with core's shipped
  `InteractsWithLatticeComponents` trait (`latticeRef()`, `callAction()`, `callBulkAction()`, `submitForm()`).
- For renderer behavior — grid rendering, selection, search, the detail slideout, the picker's hidden inputs — use the
  Vitest suite in `resources/js`, with the local `test-support.ts` fixtures.
- For real-browser coverage (Playwright via Pest 4) use `tests/Browser`; `BrowserTestCase` guards against a missing or
  stale workbench build, and the `assert*Eventually` helpers in `tests/Support/Browser.php` absorb async UI settling.
- It is acceptable to add stable `data-test` attributes when they make assertions clearer or less brittle.

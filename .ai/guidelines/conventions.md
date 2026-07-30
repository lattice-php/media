# Media Package Conventions

- **Namespaces mirror core's shape.** `Lattice\Media\Models\{Media,Attachment}`,
  `Lattice\Media\Models\Concerns\HasMedia`, `Lattice\Media\Components\MediaLibrary`,
  `Lattice\Media\Forms\Components\MediaPicker`, `Lattice\Media\Tables\MediaTable`,
  `Lattice\Media\Actions\*`, `Lattice\Media\Policies\MediaPolicy`, `Lattice\Media\Rules\AttachableMedia`.
- **Three consumer touchpoints.** `MediaLibrary::make()` (the standalone library component), `MediaPicker::make($name)`
  (the form field, which carries a library as its child schema), and the `HasMedia` trait (per-collection
  `syncMedia()`/`media()`/`firstMediaUrl()` over the polymorphic `media_attachments` pivot).
- **Composer-only distribution.** There is no npm package. The React renderer (`resources/js/**`) ships as source and is
  compiled into the consumer's bundle through Lattice's `lattice()` Vite plugin, wired by the two `extra.lattice` keys in
  `composer.json` (`plugin` → `virtual:lattice/plugins`, `discover` → PHP component discovery).
- **Deep core imports resolve straight from the published package.** The renderer imports per-module core paths
  (`@lattice-php/lattice/core/api`, `.../ui/button`, …); core's `exports` map has covered every deep subpath this
  package uses since 0.29.0, so there is no local alias or `tsconfig.json` `paths` bridge.
- **Vitest inlines the core package.** `vitest.config.ts` sets `server.deps.inline: ["@lattice-php/lattice"]` so
  `vi.mock` on a core specifier (e.g. `@lattice-php/lattice/core/api`) also intercepts that module's own internal
  imports (e.g. `core/upload`'s call into `core/api`) — without it, Vitest treats the installed package as an
  external native-ESM import and mocks stop propagating past the first module boundary.
- **Translations.** Strings live under the `media` i18next namespace with inline English defaults at the call site.
  `MediaServiceProvider` registers the namespace directly on the translation loader (not `loadTranslationsFrom` — the
  i18next JSON route resolves only the loader). The i18next loader prefixes every key with its PHP lang group, so the
  JS keys carry a `media.` prefix. Keep `lang/en` and `lang/de` in sync; `TranslationParityTest` enforces it.
- **Uploads have two paths.** Multipart uploads go through `UploadMediaAction` and land on `config('media.disk')`;
  with `media.signed_uploads` enabled the action instead finalizes an already-uploaded temp key out of the `tmp/`
  prefix. Accepted types come from `config('media.accepted_types')` (mime patterns, `image/*` wildcards included).
- **Version coupling.** The package requires `lattice-php/lattice` `^0.29`; features that depend on newer core APIs must
  bump that constraint and wait for the corresponding core release.

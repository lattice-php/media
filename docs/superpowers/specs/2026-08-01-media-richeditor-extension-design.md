# Media rich-editor extension — design

Date: 2026-08-01
Status: approved

## Goal

Let users insert images from the media library into Lattice's `RichEditor`. The stored
document carries media **ids only**; URLs are resolved server-side on every render and
prefill, so temporary/signed disk URLs and disk migrations just work. Referenced media
can be linked to the owning model through the existing attachment machinery.

Built on lattice's server-side rich-editor extension API
(`EditorExtension` + `registerRichEditorExtension`, docs:
`docs/content/docs/forms/fields/rich-editor.mdx` in lattice core).

## Scope

- Images only. Non-image media, video playback, captions, alignment/width, and
  drag-drop upload into the editor are explicitly out of scope (clean follow-ups on
  the same node).
- Per-node options in v1: **alt override** (falls back to the media's library alt) and
  **conversion choice** (which conversion the node renders; original when unset or
  missing).

## Architecture

One extension, both halves living in this package:

### PHP: `Lattice\Media\Forms\RichEditor\MediaImage`

`#[AsEditorExtension('media-image')]`, extends `EditorExtension`. Registered app-wide
in `MediaServiceProvider` via `EditorExtensionRegistry` so bare
`RichContent::make($doc)->toHtml()` renders media images everywhere (index pages,
emails, API). Editors opt in per field:

```php
RichEditor::make('body')->withExtensions(MediaImage::make()->conversions('hero'));
```

Wire props (public typed properties):

- `conversions` — `list<string>`, conversion names offered in the node's size
  dropdown. Empty (default) = original only. Fluent: `->conversions('hero', 'wide')`.
- `library` — a `MediaLibrary::make('rich-editor-media-library')->picker()` component
  instance, built in `make()`. `Wire::map` materializes nested `JsonSerializable`
  eagerly, so it serializes to a `media.library` wire node the client renders with the
  existing `LibraryView` — upload-inside-picker included, no new endpoint.

Document node `mediaImage`:

- Canonical attrs (stored): `id` (int), `alt` (string|null override), `conversion`
  (string|null).
- Ephemeral attrs (outbound only): `url`, `width`, `height`, `mediaAlt`.

The five `EditorExtension` seams:

- `serverExtensions()` — returns `MediaImageNode` (tiptap-php `Node`,
  `$name = 'mediaImage'`): renders
  `<img src alt width height loading="lazy" data-media-id data-conversion>`.
  Effective alt = node `alt` ?? ephemeral `mediaAlt` ?? `''`. Deleted media (no
  resolved `url`) renders nothing. No `$serverTypes` entry needed — types covered by
  `serverExtensions()` are exempt.
- `prepareDocument()` — collects ids via
  `RichContent::make($document, extensions: [$this])->nodes('mediaImage')`, one
  `Media::modelQuery()->findMany()`, injects `url` (via `Media::url($conversion)`,
  which already falls back to the original), `width`, `height`, `mediaAlt`.
- `ephemeralAttributes()` — `['mediaImage' => ['url', 'width', 'height', 'mediaAlt']]`
  so the canonical storage form stays `{id, alt, conversion}`.
- `configureSanitizer()` — allow `img` with exactly the attrs `renderHTML()` emits.
- `validateDocument()` — batch: one query for all referenced ids, then per media the
  same semantics as the existing `AttachableMedia` rule (exists +
  `Gate::allows('attach', $media)`); a message per failing id.

Helper for model linking:

```php
/** @return list<int> */
public static function idsIn(array $document): array
```

Thin wrapper over `RichContent::make($document)->nodes('mediaImage')` (bare works —
the extension is registry-registered), returning unique ids.

### Client: `resources/js/rich-editor/`

Registered in `plugin.ts` via
`registerRichEditorExtension("media-image", { extensions, toolbar })`:

- **Tiptap node** `mediaImage`: atom block, attrs mirroring canonical + ephemeral.
  React NodeView renders `<img>` from the ephemeral `url` attr. When the user picks
  media in-session, the insert seeds ephemeral attrs client-side from the library row
  (`url`/`preview_url`/`width`/`height`/`alt`) — no round-trip; the server scrubs them
  at storage anyway. Selected node shows inline controls: alt text input, conversion
  select (options from `props.conversions`, plus "Original").
- **Toolbar**: one image-icon button opening a `Dialog` with `LibraryView` rendered
  from `props.library` (same reuse as `media-picker.tsx`); confirming inserts one
  `mediaImage` node per picked row.

### Model linking (explicit, app-side)

```php
$post->update(['body' => $data['body']]);
$post->syncMedia(MediaImage::idsIn($data['body']), 'content');
```

No new sync machinery. `syncMedia()` already dispatches
`GenerateMediaConversions` per collection, so an app declaring
`mediaConversions('content')` with the same names it passes to `->conversions()` gets
its editor sizes generated automatically. This pairing is documented (README), not
automated.

## Error handling

- Deleted media in a stored document: `prepareDocument()` resolves nothing →
  `renderHTML()` emits no element; editor NodeView shows a "missing media"
  placeholder.
- Submitted ids that don't exist or fail the `attach` gate: field errors from
  `validateDocument()`.
- Unknown conversion name on a node: `Media::url()` falls back to the original —
  never an error.

## Testing

- **Pest (Feature)**: prepareDocument resolves urls/dimensions/alt in one query and
  respects `conversion`; ephemeral attrs are scrubbed from the canonical form via the
  `RichEditor` field cast; `validateDocument` rejects missing and gate-denied ids;
  `toHtml()` renders the img with sanitizer-approved attrs; deleted media renders
  nothing; `idsIn()` returns unique ids.
- **Vitest**: node insert/attr editing, toolbar button opens library and inserts,
  missing-media placeholder.
- **Browser (Pest)**: pick → insert → save → reload round trip shows the image.

## Dependencies

Requires the lattice release containing the extension API (currently
`feat/rich-editor-server-extensions` in lattice core). Bump the composer constraint
to that minor when it ships; until then develop against a path repo / dev branch.

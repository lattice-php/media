# Media Rich-Editor Extension Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A `media-image` rich-editor extension: pick images from the media library inside Lattice's `RichEditor`, store only media ids in the document, resolve URLs server-side on every render/prefill.

**Architecture:** One `EditorExtension` subclass (`MediaImage`) + one tiptap-php node (`MediaImageNode`) on the PHP side, registered app-wide in `MediaServiceProvider`; one Tiptap node with a React NodeView + toolbar dialog on the client, registered via `registerRichEditorExtension`. The toolbar dialog reuses the existing `LibraryView`; the picker UI arrives as a `MediaLibrary` component serialized into the extension's wire props. Spec: `docs/superpowers/specs/2026-08-01-media-richeditor-extension-design.md`.

**Tech Stack:** PHP 8.4, Laravel/testbench, lattice-php/lattice ≥0.34 (rich-editor extension API), ueberdosis/tiptap-php (transitive via lattice), Symfony HtmlSanitizer, React 19, Tiptap 3 (`@tiptap/core`, `@tiptap/react`), Pest 4, Vitest 4.

## Global Constraints

- Every PHP file starts with `<?php` + `declare(strict_types=1);` (match existing files).
- Composer constraint: `"lattice-php/lattice": ">=0.34.0 <1.0.0"`. npm: `"@lattice-php/lattice": "^0.34.0"`, `"@tiptap/core": "^3.26.0"`, `"@tiptap/react": "^3.26.0"`, `"@tiptap/starter-kit": "^3.26.0"`.
- Exact strings everywhere: wire type `media-image`, tiptap node/schema name `mediaImage`, ephemeral attrs `url`, `width`, `height`, `mediaAlt`, canonical attrs `id`, `alt`, `conversion`.
- Every new `lang/en/media.php` key needs a `lang/de/media.php` counterpart (`TranslationParityTest` enforces this).
- Quality gates, run from `/Users/bambamboole/Projects/lattice/media`: `composer lint`, `composer analyse`, `composer test` (PHP) and `npm run typecheck`, `npm run test` (JS). Browser suite: `composer test:browser`.
- Larastan is strict: if a generic annotation in this plan doesn't satisfy `composer analyse`, adjust the docblock, not the runtime code.

---

### Task 1: Dependency bumps

**Files:**
- Modify: `composer.json` (require.lattice-php/lattice)
- Modify: `package.json` (devDependencies)

**Interfaces:**
- Produces: `Lattice\Lattice\Forms\RichEditor\EditorExtension`, `EditorExtensionRegistry`, `RichContent::nodes()` available in vendor; `@lattice-php/lattice/form/rich-editor` (`registerRichEditorExtension`, `ToolbarIconButton`) and `@tiptap/*` available in node_modules.

- [ ] **Step 1: Bump the composer floor**

In `composer.json` change:

```json
"lattice-php/lattice": ">=0.34.0 <1.0.0"
```

- [ ] **Step 2: Bump/add npm deps**

In `package.json` devDependencies change `"@lattice-php/lattice": "^0.34.0"` and add:

```json
"@tiptap/core": "^3.26.0",
"@tiptap/react": "^3.26.0",
"@tiptap/starter-kit": "^3.26.0",
```

- [ ] **Step 3: Install**

Run: `composer update lattice-php/lattice --with-all-dependencies && npm install`

- [ ] **Step 4: Verify the new API is present**

Run: `ls vendor/lattice-php/lattice/src/Forms/RichEditor/EditorExtension.php && grep -l "registerRichEditorExtension" node_modules/@lattice-php/lattice/dist -r | head -1`
Expected: both commands print a path. Then run `composer test` and `npm run test` — everything green before any new code.

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock package.json package-lock.json
git commit -m "chore: require lattice 0.34 for the rich-editor extension API"
```

---

### Task 2: `MediaImageNode` (tiptap-php node)

**Files:**
- Create: `src/Forms/RichEditor/MediaImageNode.php`
- Test: `tests/Feature/MediaImageNodeTest.php`

**Interfaces:**
- Produces: `Lattice\Media\Forms\RichEditor\MediaImageNode` — tiptap-php `Node`, `$name = 'mediaImage'`, attrs `id/alt/conversion/url/width/height/mediaAlt` (all default null). `renderHTML()` emits `<img>` when `url` is a non-empty string, `<span></span>` otherwise.

- [ ] **Step 1: Write the failing test**

```php
<?php
declare(strict_types=1);

use Lattice\Media\Forms\RichEditor\MediaImageNode;
use Tiptap\Editor;
use Tiptap\Nodes\Document;
use Tiptap\Nodes\Paragraph;
use Tiptap\Nodes\Text;

/**
 * @param  array<string, mixed>  $attrs
 */
function mediaImageNodeHtml(array $attrs): string
{
    return new Editor(['extensions' => [new Document, new Paragraph, new Text, new MediaImageNode]])
        ->setContent(['type' => 'doc', 'content' => [['type' => 'mediaImage', 'attrs' => $attrs]]])
        ->getHTML();
}

test('renderHTML emits an img with the resolved url and metadata attrs', function (): void {
    $html = mediaImageNodeHtml([
        'id' => 7,
        'alt' => 'A lamp',
        'conversion' => 'hero',
        'url' => 'https://cdn.test/a.jpg',
        'width' => 800,
        'height' => 600,
    ]);

    expect($html)->toContain('<img')
        ->toContain('src="https://cdn.test/a.jpg"')
        ->toContain('alt="A lamp"')
        ->toContain('width="800"')
        ->toContain('height="600"')
        ->toContain('loading="lazy"')
        ->toContain('data-media-id="7"')
        ->toContain('data-conversion="hero"');
});

test('the alt attr falls back to the media library alt', function (): void {
    $html = mediaImageNodeHtml(['id' => 7, 'url' => 'https://cdn.test/a.jpg', 'mediaAlt' => 'Library alt']);

    expect($html)->toContain('alt="Library alt"');
});

test('a node without a resolved url renders an empty span instead of a broken img', function (): void {
    expect(mediaImageNodeHtml(['id' => 999]))->not->toContain('<img');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/MediaImageNodeTest.php`
Expected: FAIL — `Class "Lattice\Media\Forms\RichEditor\MediaImageNode" not found`

- [ ] **Step 3: Write the implementation**

```php
<?php
declare(strict_types=1);

namespace Lattice\Media\Forms\RichEditor;

use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

/**
 * The server-side schema for the `mediaImage` document node. The canonical
 * attrs are `{id, alt, conversion}`; `url`, `width`, `height` and `mediaAlt`
 * are ephemeral — injected by MediaImage::prepareDocument() on the way out and
 * never stored.
 */
final class MediaImageNode extends Node
{
    public static $name = 'mediaImage';

    /**
     * @return array<string, array{default: null}>
     */
    public function addAttributes(): array
    {
        return [
            'id' => ['default' => null],
            'alt' => ['default' => null],
            'conversion' => ['default' => null],
            'url' => ['default' => null],
            'width' => ['default' => null],
            'height' => ['default' => null],
            'mediaAlt' => ['default' => null],
        ];
    }

    /**
     * @param  \stdClass  $node
     * @param  array<string, mixed>  $HTMLAttributes
     * @return array<int, mixed>
     */
    public function renderHTML($node, $HTMLAttributes = []): array
    {
        $url = $node->attrs->url ?? null;

        if (! is_string($url) || $url === '') {
            // Deleted media: render nothing visible rather than a broken img.
            return ['span', []];
        }

        return ['img', HTML::mergeAttributes($HTMLAttributes, array_filter([
            'src' => $url,
            'alt' => $node->attrs->alt ?? $node->attrs->mediaAlt ?? '',
            'width' => $node->attrs->width ?? null,
            'height' => $node->attrs->height ?? null,
            'loading' => 'lazy',
            'data-media-id' => isset($node->attrs->id) ? (string) $node->attrs->id : null,
            'data-conversion' => $node->attrs->conversion ?? null,
        ], static fn (mixed $value): bool => $value !== null))];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/MediaImageNodeTest.php`
Expected: PASS (3 tests)

- [ ] **Step 5: Lint, analyse, commit**

```bash
composer lint && composer analyse
git add src/Forms/RichEditor/MediaImageNode.php tests/Feature/MediaImageNodeTest.php
git commit -m "feat: tiptap-php node for rich-editor media images"
```

---

### Task 3: `MediaImage` extension — wire shape, ephemeral scrub, sanitizer

**Files:**
- Create: `src/Forms/RichEditor/MediaImage.php`
- Test: `tests/Feature/MediaImageExtensionTest.php`

**Interfaces:**
- Consumes: `MediaImageNode` (Task 2), `Lattice\Media\Components\MediaLibrary`, `Lattice\Lattice\Forms\RichEditor\EditorExtension`.
- Produces: `Lattice\Media\Forms\RichEditor\MediaImage` — `make()` (builds the `library` prop), `conversions(string ...)`, `serverExtensions()`, `ephemeralAttributes()`, `configureSanitizer()`. Wire: `{type: "media-image", props: {conversions, library}}`. Later tasks add `prepareDocument()`, `validateDocument()`, `idsIn()` to this class.

- [ ] **Step 1: Write the failing test**

```php
<?php
declare(strict_types=1);

use Lattice\Lattice\Forms\RichContent;
use Lattice\Media\Forms\RichEditor\MediaImage;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(workbenchTestUser());
});

/**
 * @param  array<string, mixed>  $attrs
 * @return array<string, mixed>
 */
function mediaImageDoc(array $attrs): array
{
    return ['type' => 'doc', 'content' => [['type' => 'mediaImage', 'attrs' => $attrs]]];
}

test('the extension wires its conversions and the picker library component', function (): void {
    $node = wire(MediaImage::make()->conversions('hero', 'wide'));

    expect($node['type'])->toBe('media-image')
        ->and($node['props']['conversions'])->toBe(['hero', 'wide'])
        ->and($node['props']['library']['type'])->toBe('media.library')
        ->and($node['props']['library']['props']['picker'])->toBeTrue()
        ->and(array_column($node['props']['library']['schema'], 'type'))->toContain('table');
});

test('ephemeral attrs are scrubbed from the canonical document', function (): void {
    $document = mediaImageDoc([
        'id' => 7,
        'alt' => 'Override',
        'conversion' => 'hero',
        'url' => 'https://cdn.test/a.jpg',
        'width' => 800,
        'height' => 600,
        'mediaAlt' => 'Library alt',
    ]);

    $canonical = RichContent::make($document, extensions: [MediaImage::make()])->toArray();
    $attrs = $canonical['content'][0]['attrs'];

    expect($canonical['content'][0]['type'])->toBe('mediaImage')
        ->and($attrs['id'])->toBe(7)
        ->and($attrs['alt'])->toBe('Override')
        ->and($attrs['conversion'])->toBe('hero')
        ->and($attrs)->not->toHaveKey('url')
        ->and($attrs)->not->toHaveKey('width')
        ->and($attrs)->not->toHaveKey('height')
        ->and($attrs)->not->toHaveKey('mediaAlt');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/MediaImageExtensionTest.php`
Expected: FAIL — `Class "Lattice\Media\Forms\RichEditor\MediaImage" not found`

- [ ] **Step 3: Write the implementation**

```php
<?php
declare(strict_types=1);

namespace Lattice\Media\Forms\RichEditor;

use Lattice\Lattice\Forms\RichEditor\Attributes\AsEditorExtension;
use Lattice\Lattice\Forms\RichEditor\EditorExtension;
use Lattice\Media\Components\MediaLibrary;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Rich-editor extension for inserting library media as images. The document
 * stores `{id, alt, conversion}` per node; URLs are resolved on the way out
 * (prepareDocument) and never persisted, so temporary/signed disk URLs work.
 */
#[AsEditorExtension('media-image')]
class MediaImage extends EditorExtension
{
    /**
     * Conversion names offered in the node's size picker; empty means the
     * original only. Unknown names fall back to the original at render time.
     *
     * @var list<string>
     */
    public array $conversions = [];

    /** The picker dialog the toolbar button renders, as a wire component. */
    public ?MediaLibrary $library = null;

    #[\Override]
    public static function make(): static
    {
        $extension = parent::make();
        $extension->library = MediaLibrary::make('rich-editor-media-library')->picker();

        return $extension;
    }

    public function conversions(string ...$conversions): static
    {
        $this->conversions = array_values($conversions);

        return $this;
    }

    #[\Override]
    public function serverExtensions(): array
    {
        return [new MediaImageNode];
    }

    #[\Override]
    public function ephemeralAttributes(): array
    {
        return ['mediaImage' => ['url', 'width', 'height', 'mediaAlt']];
    }

    #[\Override]
    public function configureSanitizer(HtmlSanitizerConfig $config): HtmlSanitizerConfig
    {
        return $config
            ->allowElement('img', ['src', 'alt', 'width', 'height', 'loading', 'data-media-id', 'data-conversion'])
            ->allowMediaSchemes(['https', 'http'])
            ->allowRelativeMedias();
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/MediaImageExtensionTest.php`
Expected: PASS (2 tests). If the `library` assertion fails on shape, inspect `wire(MediaImage::make())` output — the fix belongs in the assertion path only if the component serializes children under a different key; the `media.library` type and `picker: true` must hold.

- [ ] **Step 5: Lint, analyse, commit**

```bash
composer lint && composer analyse
git add src/Forms/RichEditor/MediaImage.php tests/Feature/MediaImageExtensionTest.php
git commit -m "feat: media-image editor extension with wire props and ephemeral attrs"
```

---

### Task 4: `prepareDocument()` — URL resolution and HTML rendering

**Files:**
- Modify: `src/Forms/RichEditor/MediaImage.php`
- Test: `tests/Feature/MediaImageExtensionTest.php` (append)

**Interfaces:**
- Consumes: `Media::modelQuery()`, `Media::url(?string)`, `Media::conversions()`, accessors `width/height/alt` (all existing).
- Produces: `MediaImage::prepareDocument(array $document): array` and private `idsFromNodes()`/`resolve()` helpers reused by Task 5.

- [ ] **Step 1: Write the failing tests (append to the test file)**

```php
use Illuminate\Support\Facades\Storage;
use Lattice\Media\Models\Media;

test('prepareDocument resolves media into ephemeral url, dimensions and alt', function (): void {
    Storage::fake('public');
    $media = Media::factory()->create(['meta' => ['width' => 800, 'height' => 600, 'alt' => 'A lamp']]);

    $prepared = RichContent::make(mediaImageDoc(['id' => $media->getKey()]), extensions: [MediaImage::make()])
        ->toPreparedArray();
    $attrs = $prepared['content'][0]['attrs'];

    expect($attrs['url'])->toContain($media->path)
        ->and($attrs['width'])->toBe(800)
        ->and($attrs['height'])->toBe(600)
        ->and($attrs['mediaAlt'])->toBe('A lamp');
});

test('a node conversion resolves to the conversion url and dimensions', function (): void {
    Storage::fake('public');
    $media = Media::factory()->create(['meta' => [
        'width' => 800,
        'height' => 600,
        'conversions' => ['hero' => ['path' => 'media/conversions/hero.webp', 'width' => 1200, 'height' => 800]],
    ]]);

    $prepared = RichContent::make(
        mediaImageDoc(['id' => $media->getKey(), 'conversion' => 'hero']),
        extensions: [MediaImage::make()->conversions('hero')],
    )->toPreparedArray();
    $attrs = $prepared['content'][0]['attrs'];

    expect($attrs['url'])->toContain('hero.webp')
        ->and($attrs['width'])->toBe(1200)
        ->and($attrs['height'])->toBe(800);
});

test('an ungenerated conversion falls back to the original url and dimensions', function (): void {
    Storage::fake('public');
    $media = Media::factory()->create(['meta' => ['width' => 800, 'height' => 600]]);

    $prepared = RichContent::make(
        mediaImageDoc(['id' => $media->getKey(), 'conversion' => 'missing']),
        extensions: [MediaImage::make()],
    )->toPreparedArray();
    $attrs = $prepared['content'][0]['attrs'];

    expect($attrs['url'])->toContain($media->path)
        ->and($attrs['width'])->toBe(800);
});

test('toHtml renders the img with a sanitizer-approved relative url', function (): void {
    Storage::fake('public');
    $media = Media::factory()->create(['meta' => ['alt' => 'A lamp']]);

    $html = RichContent::make(mediaImageDoc(['id' => $media->getKey()]), extensions: [MediaImage::make()])->toHtml();

    expect($html)->toContain('<img')
        ->toContain($media->path)
        ->toContain('alt="A lamp"')
        ->toContain('data-media-id="'.$media->getKey().'"');
});

test('a node alt override beats the library alt in rendered html', function (): void {
    Storage::fake('public');
    $media = Media::factory()->create(['meta' => ['alt' => 'Library alt']]);

    $html = RichContent::make(
        mediaImageDoc(['id' => $media->getKey(), 'alt' => 'Override']),
        extensions: [MediaImage::make()],
    )->toHtml();

    expect($html)->toContain('alt="Override"')->not->toContain('Library alt');
});

test('deleted media renders no img', function (): void {
    $html = RichContent::make(mediaImageDoc(['id' => 424242]), extensions: [MediaImage::make()])->toHtml();

    expect($html)->not->toContain('<img');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Feature/MediaImageExtensionTest.php`
Expected: the new tests FAIL — `url` attr missing (prepareDocument is still the no-op default).

- [ ] **Step 3: Implement `prepareDocument()`**

Add to `MediaImage` (new imports: `Illuminate\Database\Eloquent\Collection`, `Lattice\Lattice\Forms\RichContent`, `Lattice\Media\Models\Media`):

```php
    #[\Override]
    public function prepareDocument(array $document): array
    {
        $ids = self::idsFromNodes(RichContent::make($document, extensions: [$this])->nodes('mediaImage'));

        if ($ids === []) {
            return $document;
        }

        $media = Media::modelQuery()->findMany($ids)
            ->keyBy(static fn (Media $item): int => (int) $item->getKey());

        return $this->resolve($document, $media);
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<int>
     */
    private static function idsFromNodes(array $nodes): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (array $node): int => (int) ($node['attrs']['id'] ?? 0),
            $nodes,
        ))));
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  Collection<array-key, Media>  $media
     * @return array<string, mixed>
     */
    private function resolve(array $node, Collection $media): array
    {
        if (($node['type'] ?? null) === 'mediaImage') {
            $item = $media->get((int) ($node['attrs']['id'] ?? 0));

            if ($item instanceof Media) {
                $conversion = $node['attrs']['conversion'] ?? null;
                $conversion = is_string($conversion) ? $conversion : null;
                $dimensions = $conversion !== null ? $item->conversions()[$conversion] ?? null : null;

                $node['attrs']['url'] = $item->url($conversion);
                $node['attrs']['width'] = $dimensions['width'] ?? $item->width;
                $node['attrs']['height'] = $dimensions['height'] ?? $item->height;
                $node['attrs']['mediaAlt'] = $item->alt;
            }
        }

        if (isset($node['content']) && is_array($node['content'])) {
            $node['content'] = array_values(array_map(
                fn (array $child): array => $this->resolve($child, $media),
                array_filter($node['content'], is_array(...)),
            ));
        }

        return $node;
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Feature/MediaImageExtensionTest.php`
Expected: PASS (8 tests total)

- [ ] **Step 5: Lint, analyse, commit**

```bash
composer lint && composer analyse
git add src/Forms/RichEditor/MediaImage.php tests/Feature/MediaImageExtensionTest.php
git commit -m "feat: resolve media urls into ephemeral attrs on document preparation"
```

---

### Task 5: `validateDocument()`, `idsIn()`, app-wide registration

**Files:**
- Modify: `src/Forms/RichEditor/MediaImage.php`
- Modify: `src/MediaServiceProvider.php`
- Test: `tests/Feature/MediaImageExtensionTest.php` (append)

**Interfaces:**
- Consumes: `Gate` + existing `MediaPolicy::attach()`, translation key `media::media.validation.not-attachable`, `EditorExtensionRegistry` (lattice).
- Produces: `MediaImage::validateDocument(array): list<string>`; `MediaImage::idsIn(array|string|null $document): list<int>` (the sync helper for `HasMedia::syncMedia()`); `media-image` registered app-wide so bare `RichContent::make($doc)` renders it.

- [ ] **Step 1: Write the failing tests (append)**

```php
use Illuminate\Support\Facades\Auth;
use Lattice\Lattice\Forms\RichEditor\EditorExtensionRegistry;

test('validateDocument rejects ids that do not exist', function (): void {
    $errors = MediaImage::make()->validateDocument(mediaImageDoc(['id' => 424242]));

    expect($errors)->toBe([__('media::media.validation.not-attachable')]);
});

test('validateDocument rejects media the user may not attach', function (): void {
    $media = Media::factory()->create();
    Auth::logout();

    expect(MediaImage::make()->validateDocument(mediaImageDoc(['id' => $media->getKey()])))
        ->toHaveCount(1);
});

test('validateDocument accepts attachable media', function (): void {
    $media = Media::factory()->create();

    expect(MediaImage::make()->validateDocument(mediaImageDoc(['id' => $media->getKey()])))->toBe([]);
});

test('the extension is registered app-wide', function (): void {
    expect(app(EditorExtensionRegistry::class)->all())->toHaveKey('media-image');
});

test('idsIn collects unique media ids through the bare registry path', function (): void {
    $document = ['type' => 'doc', 'content' => [
        ['type' => 'mediaImage', 'attrs' => ['id' => 7]],
        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'between']]],
        ['type' => 'mediaImage', 'attrs' => ['id' => 7]],
        ['type' => 'mediaImage', 'attrs' => ['id' => 9]],
    ]];

    expect(MediaImage::idsIn($document))->toBe([7, 9])
        ->and(MediaImage::idsIn(null))->toBe([]);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Feature/MediaImageExtensionTest.php`
Expected: FAIL — `validateDocument` returns `[]` (no-op default), registry has no `media-image` key, `idsIn` undefined.

- [ ] **Step 3: Implement**

Add to `MediaImage` (new import: `Illuminate\Support\Facades\Gate`):

```php
    #[\Override]
    public function validateDocument(array $document): array
    {
        $ids = self::idsFromNodes(RichContent::make($document, extensions: [$this])->nodes('mediaImage'));

        if ($ids === []) {
            return [];
        }

        $media = Media::modelQuery()->findMany($ids)
            ->keyBy(static fn (Media $item): int => (int) $item->getKey());
        $errors = [];

        foreach ($ids as $id) {
            $item = $media->get($id);

            if (! $item instanceof Media || Gate::denies('attach', $item)) {
                $errors[] = __('media::media.validation.not-attachable');
            }
        }

        return $errors;
    }

    /**
     * Media ids referenced by a stored document — feed for
     * `HasMedia::syncMedia()` after persisting the document. Uses the
     * app-wide registry, so it works on bare stored values.
     *
     * @param  array<string, mixed>|string|null  $document
     * @return list<int>
     */
    public static function idsIn(array|string|null $document): array
    {
        return self::idsFromNodes(RichContent::make($document)->nodes('mediaImage'));
    }
```

In `MediaServiceProvider::boot()` add (imports: `Lattice\Lattice\Forms\RichEditor\EditorExtensionRegistry`, `Lattice\Media\Forms\RichEditor\MediaImage`):

```php
        $this->app->make(EditorExtensionRegistry::class)->register(MediaImage::class);
```

- [ ] **Step 4: Run the whole Feature suite**

Run: `composer test`
Expected: PASS — including all pre-existing tests (registration must not break bare `RichContent` rendering elsewhere).

- [ ] **Step 5: Lint, analyse, commit**

```bash
composer lint && composer analyse
git add src/Forms/RichEditor/MediaImage.php src/MediaServiceProvider.php tests/Feature/MediaImageExtensionTest.php
git commit -m "feat: validate media references and register the extension app-wide"
```

---

### Task 6: Client Tiptap node with NodeView

**Files:**
- Create: `resources/js/rich-editor/media-image.tsx`
- Test: `resources/js/rich-editor/media-image.test.tsx`

**Interfaces:**
- Consumes: `@tiptap/core` `Node`, `@tiptap/react` (`ReactNodeViewRenderer`, `NodeViewWrapper`), lattice UI `Input`, `NativeSelect`, `cn`, `useT`.
- Produces: exported `MediaImageNode` (Tiptap node, name `mediaImage`, option `conversions: string[]`) and `MediaImageView` (NodeView component). Task 7 adds the toolbar + `registerMediaImage()` to this same file.

- [ ] **Step 1: Write the failing test**

```tsx
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { EditorContent, useEditor, type Editor } from "@tiptap/react";
import { StarterKit } from "@tiptap/starter-kit";
import { describe, expect, it } from "vitest";
import { MediaImageNode } from "./media-image";

function Harness({
  attrs,
  conversions = [],
  onEditor,
}: {
  attrs: Record<string, unknown>;
  conversions?: string[];
  onEditor: (editor: Editor) => void;
}) {
  const editor = useEditor({
    content: { type: "doc", content: [{ type: "mediaImage", attrs }] },
    extensions: [StarterKit, MediaImageNode.configure({ conversions })],
    immediatelyRender: true,
  });

  if (editor) {
    onEditor(editor);
  }

  return editor ? <EditorContent editor={editor} /> : null;
}

async function mountNode(attrs: Record<string, unknown>, conversions: string[] = []) {
  let editor: Editor | undefined;
  render(<Harness attrs={attrs} conversions={conversions} onEditor={(e) => (editor = e)} />);
  await waitFor(() => expect(document.querySelector("[data-test=editor-media-image]")).not.toBeNull());

  return editor!;
}

describe("MediaImageNode", () => {
  it("renders the image from the ephemeral url attr", async () => {
    await mountNode({ id: 7, url: "https://cdn.test/a.jpg", mediaAlt: "Lamp" });

    const img = document.querySelector("[data-test=editor-media-image] img")!;
    expect(img.getAttribute("src")).toBe("https://cdn.test/a.jpg");
    expect(img.getAttribute("alt")).toBe("Lamp");
  });

  it("shows a placeholder when the media no longer resolves", async () => {
    await mountNode({ id: 999 });

    expect(document.querySelector("[data-test=editor-media-image-missing]")).not.toBeNull();
  });

  it("edits the alt override into the node attrs when selected", async () => {
    const editor = await mountNode({ id: 7, url: "https://cdn.test/a.jpg" });
    editor.commands.setNodeSelection(0);

    await waitFor(() =>
      expect(document.querySelector("[data-test=editor-media-image-controls]")).not.toBeNull(),
    );
    await userEvent.type(screen.getByLabelText("Alt text"), "Better alt");

    await waitFor(() => {
      const node = editor.getJSON().content?.find((child) => child.type === "mediaImage");
      expect(node?.attrs?.alt).toBe("Better alt");
    });
  });

  it("offers the configured conversions plus the original", async () => {
    const editor = await mountNode({ id: 7, url: "https://cdn.test/a.jpg" }, ["hero"]);
    editor.commands.setNodeSelection(0);

    await waitFor(() => expect(screen.getByLabelText("Size")).not.toBeNull());
    await userEvent.selectOptions(screen.getByLabelText("Size"), "hero");

    await waitFor(() => {
      const node = editor.getJSON().content?.find((child) => child.type === "mediaImage");
      expect(node?.attrs?.conversion).toBe("hero");
    });
  });
});
```

Note: `@testing-library/user-event` may not be installed — check `package.json`; if absent, `npm i -D @testing-library/user-event` and add it to the Task 6 commit. If `userEvent.type` fights Tiptap's contenteditable focus handling, use `fireEvent.change(input, { target: { value: "Better alt" } })` instead — the component uses a plain controlled input, so `fireEvent` is sufficient.

- [ ] **Step 2: Run test to verify it fails**

Run: `npm run test -- rich-editor`
Expected: FAIL — cannot resolve `./media-image`

- [ ] **Step 3: Write the implementation**

```tsx
import { mergeAttributes, Node } from "@tiptap/core";
import { NodeViewWrapper, ReactNodeViewRenderer, type NodeViewProps } from "@tiptap/react";
import { useT } from "@lattice-php/lattice/i18n";
import { cn } from "@lattice-php/lattice/lib/utils";
import { Input } from "@lattice-php/lattice/ui/input";
import { NativeSelect } from "@lattice-php/lattice/ui/native-select";

type MediaImageOptions = { conversions: string[] };

export function MediaImageView({ editor, extension, node, selected, updateAttributes }: NodeViewProps) {
  const { t } = useT("media");
  const conversions = (extension.options as MediaImageOptions).conversions;
  const url = node.attrs.url as string | null;
  const alt = (node.attrs.alt ?? node.attrs.mediaAlt ?? "") as string;

  return (
    <NodeViewWrapper className="flex flex-col gap-2" data-test="editor-media-image">
      {url ? (
        <img
          alt={alt}
          className={cn("max-w-full rounded-lt-sm", selected && "ring-2 ring-lt-ring")}
          src={url}
        />
      ) : (
        <div
          className="rounded-lt-sm border border-dashed border-lt-border px-3 py-2 text-sm text-lt-fg-muted"
          data-test="editor-media-image-missing"
        >
          {t("media.editor.missing", "Missing media")}
        </div>
      )}
      {selected && editor.isEditable && (
        <div className="flex items-center gap-2" data-test="editor-media-image-controls">
          <Input
            aria-label={t("media.editor.alt", "Alt text")}
            onChange={(event) =>
              updateAttributes({ alt: event.target.value === "" ? null : event.target.value })
            }
            placeholder={t("media.editor.alt", "Alt text")}
            value={(node.attrs.alt ?? "") as string}
          />
          {conversions.length > 0 && (
            <NativeSelect
              aria-label={t("media.editor.size", "Size")}
              onChange={(event) =>
                updateAttributes({ conversion: event.target.value === "" ? null : event.target.value })
              }
              value={(node.attrs.conversion ?? "") as string}
            >
              <option value="">{t("media.editor.original", "Original")}</option>
              {conversions.map((name) => (
                <option key={name} value={name}>
                  {name}
                </option>
              ))}
            </NativeSelect>
          )}
        </div>
      )}
    </NodeViewWrapper>
  );
}

export const MediaImageNode = Node.create<MediaImageOptions>({
  name: "mediaImage",
  group: "block",
  atom: true,
  draggable: true,

  addOptions() {
    return { conversions: [] };
  },

  addAttributes() {
    return {
      id: { default: null },
      alt: { default: null },
      conversion: { default: null },
      url: { default: null },
      width: { default: null },
      height: { default: null },
      mediaAlt: { default: null },
    };
  },

  parseHTML() {
    return [{ tag: "img[data-media-id]" }];
  },

  renderHTML({ node, HTMLAttributes }) {
    return [
      "img",
      mergeAttributes(HTMLAttributes, {
        src: node.attrs.url,
        alt: (node.attrs.alt ?? node.attrs.mediaAlt ?? "") as string,
        "data-media-id": node.attrs.id,
      }),
    ];
  },

  addNodeView() {
    return ReactNodeViewRenderer(MediaImageView);
  },
});
```

Check `NativeSelect`'s actual prop names against `node_modules/@lattice-php/lattice` (it is used in `resources/js/components/library-view.tsx` — mirror that usage).

- [ ] **Step 4: Run test to verify it passes**

Run: `npm run test -- rich-editor` then `npm run typecheck`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add resources/js/rich-editor/ package.json package-lock.json
git commit -m "feat: client tiptap node and node view for media images"
```

---

### Task 7: Toolbar button, plugin registration, types, translations

**Files:**
- Modify: `resources/js/rich-editor/media-image.tsx` (toolbar + registration)
- Modify: `resources/js/plugin.ts`
- Modify: `resources/js/types.d.ts`
- Modify: `lang/en/media.php`, `lang/de/media.php`
- Test: `resources/js/rich-editor/media-image.test.tsx` (append)

**Interfaces:**
- Consumes: `registerRichEditorExtension`, `ToolbarIconButton` from `@lattice-php/lattice/form/rich-editor`; `Dialog`, `DialogContent`, `DialogHeader` from lattice UI; `LibraryView`, `MediaRow` from `../components/library-view`; `translate` from lattice i18n.
- Produces: `registerMediaImage(): void` — registers the `media-image` definition; called at module scope in `plugin.ts`. Types: `EditorExtensionProps["media-image"] = { conversions: string[]; library: Node | null }`.

- [ ] **Step 1: Write the failing test (append)**

```tsx
import { resolveRichEditorExtensions } from "@lattice-php/lattice/form/rich-editor";
import { registerMediaImage } from "./media-image";

describe("registerMediaImage", () => {
  it("registers a definition that yields the node and one toolbar control", () => {
    registerMediaImage();

    const [resolved] = resolveRichEditorExtensions([
      { type: "media-image", props: { conversions: ["hero"], library: null } },
    ]);

    expect(resolved).toBeDefined();
    const extensions = resolved.definition.extensions!(resolved.props);
    expect(extensions[0].name).toBe("mediaImage");
    expect(resolved.definition.toolbar!(resolved.props)).toHaveLength(1);
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm run test -- rich-editor`
Expected: FAIL — `registerMediaImage` is not exported

- [ ] **Step 3: Implement toolbar + registration**

Append to `resources/js/rich-editor/media-image.tsx` (new imports: `useState` from react; `registerRichEditorExtension`, `ToolbarIconButton` from `@lattice-php/lattice/form/rich-editor`; `translate` from `@lattice-php/lattice/i18n`; `Dialog`, `DialogContent`, `DialogHeader` from `@lattice-php/lattice/ui/dialog`; `type Editor` from `@tiptap/core`; `type Node as WireNode` from `@lattice-php/lattice/core/types`; `LibraryView`, `type MediaRow` from `../components/library-view`):

```tsx
function InsertMediaImageControl({ editor, library }: { editor: Editor; library: WireNode | null }) {
  const { t } = useT("media");
  const [open, setOpen] = useState(false);

  if (!library) {
    return null;
  }

  return (
    <>
      <ToolbarIconButton
        icon="image"
        label={t("media.editor.insert", "Insert image")}
        onClick={() => setOpen(true)}
        testId="editor-media-image-insert"
      />
      {open && (
        <Dialog onOpenChange={setOpen} open>
          <DialogContent
            aria-describedby={undefined}
            className="flex flex-col gap-5"
            data-test="editor-media-image-dialog"
            width="3xl"
          >
            <DialogHeader
              closeLabel={translate("lattice", "common.close", "Close")}
              title={t("media.picker.heading", "Choose media")}
            />
            <LibraryView
              node={library}
              pick={{
                multiple: true,
                onConfirm: (items: MediaRow[]) => {
                  editor
                    .chain()
                    .focus()
                    .insertContent(
                      items.map((item) => ({
                        type: "mediaImage",
                        attrs: { id: item.id, url: item.url, mediaAlt: item.alt },
                      })),
                    )
                    .run();
                  setOpen(false);
                },
              }}
            />
          </DialogContent>
        </Dialog>
      )}
    </>
  );
}

export function registerMediaImage(): void {
  registerRichEditorExtension("media-image", {
    extensions: (props) => [MediaImageNode.configure({ conversions: props.conversions ?? [] })],
    toolbar: (props) => [
      {
        key: "media-image",
        component: ({ editor }) => (
          <InsertMediaImageControl editor={editor} library={props.library ?? null} />
        ),
      },
    ],
  });
}
```

In `resources/js/plugin.ts`:

```ts
import { createPlugin, lazyComponent } from "@lattice-php/lattice";
import { registerMediaImage } from "./rich-editor/media-image";

// Must run before the app boots — the editor resolves definitions from the
// registry when it mounts, so this cannot live in a lazy chunk.
// ponytail: pulls @tiptap/core+react into the eager bundle; revisit if lattice
// grows lazy extension registration.
registerMediaImage();

export default createPlugin({
  name: "media",
  components: {
    "media.library": lazyComponent(() => import("./library")),
    "field.media-picker": lazyComponent(() => import("./media-picker")),
  },
  i18n: { namespace: "media" },
});
```

In `resources/js/types.d.ts` add `import type { Node } from "@lattice-php/lattice/core/types";` to the imports and inside the existing `declare module "@lattice-php/lattice"` block add:

```ts
  interface EditorExtensionProps {
    "media-image": {
      conversions: string[];
      library: Node | null;
    };
  }
```

In `lang/en/media.php` add after the `picker` section:

```php
    'editor' => [
        'insert' => 'Insert image',
        'alt' => 'Alt text',
        'size' => 'Size',
        'original' => 'Original',
        'missing' => 'Missing media',
    ],
```

In `lang/de/media.php` add the mirror:

```php
    'editor' => [
        'insert' => 'Bild einfügen',
        'alt' => 'Alternativtext',
        'size' => 'Größe',
        'original' => 'Original',
        'missing' => 'Fehlende Datei',
    ],
```

- [ ] **Step 4: Run everything client-side + translation parity**

Run: `npm run test && npm run typecheck && vendor/bin/pest tests/Feature/TranslationParityTest.php && npm run build`
Expected: all PASS, build succeeds

- [ ] **Step 5: Commit**

```bash
git add resources/js/rich-editor/ resources/js/plugin.ts resources/js/types.d.ts lang/
git commit -m "feat: media image toolbar with library picker and plugin registration"
```

---

### Task 8: Workbench wiring + browser round trip

**Files:**
- Modify: `workbench/database/migrations/0001_01_01_000001_create_products_table.php` (add `body` column)
- Modify: `workbench/app/Models/Product.php`
- Modify: `workbench/app/Forms/ProductMediaForm.php`
- Modify: `workbench/app/Pages/ProductMediaPage.php`
- Modify: `workbench/lang/en/workbench.php`, `workbench/lang/de/workbench.php`
- Test: `tests/Browser/MediaImageEditorTest.php`

**Interfaces:**
- Consumes: `MediaImage::make()`, `MediaImage::idsIn()`, `HasMedia::syncMedia()`, `RichEditor` field (`Lattice\Lattice\Forms\Components\RichEditor`), existing browser helpers (`visitAsWorkbenchUser`, `assertSeeEventually`, `retryUntil`, test ids `@media-card`, `@media-pick-confirm`, `@form-submit`).

- [ ] **Step 1: Wire the workbench**

Migration — add inside `Schema::create('products', ...)`:

```php
            $table->json('body')->nullable();
```

`Product` — add `'body'` to `$fillable` and add:

```php
    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['body' => 'array'];
    }
```

`ProductMediaForm::definition()` — add to the schema array (imports: `Lattice\Lattice\Forms\Components\RichEditor`, `Lattice\Media\Forms\RichEditor\MediaImage`):

```php
            RichEditor::make('body', __('workbench.forms.product-media.fields.body'))
                ->withExtensions(MediaImage::make()),
```

`ProductMediaForm::handle()` — after the existing `syncMedia` call add:

```php
        $this->product()->update(['body' => $validated['body'] ?? null]);
        $this->product()->syncMedia(MediaImage::idsIn($validated['body'] ?? null), 'content');
```

`ProductMediaPage::render()` — extend the existing `->fill([...])` to:

```php
                        ->fill([
                            'gallery' => $product->mediaPickerValue('gallery'),
                            'body' => $product->body,
                        ])
```

Both workbench lang files — add under `forms.product-media.fields`: en `'body' => 'Article body'`, de `'body' => 'Artikeltext'`.

- [ ] **Step 2: Write the browser test**

```php
<?php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Lattice\Media\Models\Media;

it('inserts library media into the rich editor, stores only the id, and prefills on reload', function (): void {
    $media = Media::factory()->create(['name' => 'photo.jpg', 'meta' => ['alt' => 'A photo']]);

    $page = $this->visitAsWorkbenchUser('/media-picker')
        ->click('@editor-media-image-insert');

    assertSeeEventually($page, 'photo.jpg');

    $page->click('@media-card')
        ->click('@media-pick-confirm')
        ->assertPresent('@editor-media-image')
        ->click('@form-submit');

    retryUntil(function () use ($media): void {
        $body = json_decode((string) DB::table('products')->value('body'), true);
        $node = collect($body['content'] ?? [])->firstWhere('type', 'mediaImage');

        expect($node)->not->toBeNull()
            ->and($node['attrs']['id'])->toBe($media->getKey())
            ->and($node['attrs'])->not->toHaveKey('url');
        expect(DB::table('media_attachments')->where('collection', 'content')->count())->toBe(1);
    });

    $page->navigate('/media-picker')
        ->assertPresent('@editor-media-image')
        ->assertNoSmoke();
});
```

- [ ] **Step 3: Run the browser suite**

Run: `composer test:browser` (builds assets first). Debug selector timing with the existing patterns in `tests/Browser/MediaPickerTest.php` — same helpers, same page.
Expected: PASS, including the pre-existing browser tests (the form gained a field; they must still pass).

- [ ] **Step 4: Run the full check**

Run: `composer check && npm run test && npm run typecheck`
Expected: all green.

- [ ] **Step 5: Commit**

```bash
git add workbench/ tests/Browser/MediaImageEditorTest.php
git commit -m "test: browser round trip for rich-editor media images"
```

---

### Task 9: README documentation

**Files:**
- Modify: `README.md`

- [ ] **Step 1: Add a "Rich editor images" section**

After the existing MediaPicker/usage docs, add:

````markdown
## Rich editor images

The package registers a `media-image` rich-editor extension. Activate it per
field and optionally offer conversions as selectable sizes:

```php
use Lattice\Lattice\Forms\Components\RichEditor;
use Lattice\Media\Forms\RichEditor\MediaImage;

RichEditor::make('body')->withExtensions(MediaImage::make()->conversions('hero'));
```

The stored document keeps only `{id, alt, conversion}` per image — URLs are
resolved on every render and prefill, so temporary/signed disk URLs work.
Render stored documents as usual with `RichContent::make($post->body)->toHtml()`.

To track usage (and benefit from per-collection conversions), sync the
referenced media as attachments when you persist the document:

```php
$post->update(['body' => $validated['body']]);
$post->syncMedia(MediaImage::idsIn($validated['body']), 'content');
```

Conversion names passed to `->conversions()` should be generated for that
collection — declare them in the model's `mediaConversions('content')` so the
sync dispatches their generation.
````

- [ ] **Step 2: Commit**

```bash
git add README.md
git commit -m "docs: rich editor images"
```

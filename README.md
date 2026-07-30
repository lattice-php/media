# Lattice Media

First-party media library for [Lattice](https://github.com/lattice-php/lattice) — an uploadable,
searchable file library with a React grid, a detail slideout for renaming and alt text, bulk delete,
and a form field that attaches media to any model through a polymorphic pivot.

## Installation

```bash
composer require lattice-php/media
```

Requires `@lattice-php/lattice` `^0.29`.

On the PHP side there is nothing to wire: the classes, migrations, config (`config/media.php`), and
the default `Media` policy are picked up automatically. The React renderer ships as source and
Lattice's `lattice()` Vite plugin compiles it into your app's bundle via `virtual:lattice/plugins`.

## Usage

The package has three touchpoints.

**The library component** — a standalone page of media:

```php
use Lattice\Media\Components\MediaLibrary;

MediaLibrary::make();
```

**The picker field** — the library inside a form, submitting the selected ids:

```php
use Lattice\Media\Forms\Components\MediaPicker;

MediaPicker::make('gallery')->multiple();
```

**The `HasMedia` trait** — per-collection attachments on any model:

```php
use Lattice\Media\Models\Concerns\HasMedia;

class Product extends Model
{
    use HasMedia;
}

$product->syncMedia($ids, 'gallery');   // replaces the collection, keeps the given order
$product->media('gallery');             // MorphToMany<Media>, ordered by the pivot
$product->firstMediaUrl('gallery');
```

Validate submitted ids with `Lattice\Media\Rules\AttachableMedia`.

## Configuration

`config/media.php` covers the disk (`media.disk`), the upload size cap (`media.max_size`), the
accepted mime patterns (`media.accepted_types`, `image/*` wildcards included, empty accepts
everything), and whether uploads go through signed URLs (`media.signed_uploads`).

A single library overrides the config defaults per instance:

```php
MediaLibrary::make()->signedUpload()->disk('s3')->accept('image/*');
```

## Translations

The components' strings ship with inline English defaults. With
[bambamboole/laravel-i18next](https://github.com/bambamboole/laravel-i18next) enabled, the plugin's
`media` namespace is loaded automatically and serves the bundled `en`/`de` translations (override
them like any Laravel package translation).

## Development

```bash
composer install && npm install
composer check          # pint --test, phpstan, pest (Feature)
npm run typecheck && npm test
composer test:browser   # rebuilds the workbench bundle, then runs the Playwright suite
composer serve          # workbench demo app: /media (library) and /media-picker (field in a form)
```

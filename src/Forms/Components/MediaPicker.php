<?php
declare(strict_types=1);

namespace Lattice\Media\Forms\Components;

use Illuminate\Http\Request;
use Lattice\Lattice\Forms\Attributes\AsField;
use Lattice\Lattice\Forms\Components\Field;
use Lattice\Lattice\Forms\FormData;
use Lattice\Lattice\Ui\Components\Concerns\HasChildSchema;
use Lattice\Media\Components\MediaLibrary;
use Lattice\Media\Models\Media;
use Lattice\Media\Rules\AttachableMedia;

#[AsField(type: 'media-picker')]
final class MediaPicker extends Field
{
    use HasChildSchema;

    public bool $multiple = false;

    public ?int $maxFiles = null;

    /**
     * Existing attachments surfaced on edit as display descriptors.
     *
     * @var list<array{id: int, name: string, url: string|null, mime_type: string}>|null
     */
    public ?array $selected = null;

    #[\Override]
    public static function make(string $name, ?string $label = null): static
    {
        $field = parent::make($name, $label);

        return $field->schema([
            MediaLibrary::make($name.'-library')->picker()->multiple(false),
        ]);
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        $this->library()->multiple($multiple);

        return $this;
    }

    public function maxFiles(int $maxFiles): static
    {
        $this->maxFiles = $maxFiles;

        return $this;
    }

    #[\Override]
    public function hydrateState(mixed $value, ?FormData $form = null, ?Request $request = null): void
    {
        $ids = array_values(array_filter(is_array($value) ? $value : [$value], is_numeric(...)));
        $media = Media::query()->findMany($ids)->keyBy(fn (Media $row): int => (int) $row->getKey());

        $this->selected = collect($ids)
            ->map(fn (mixed $id): ?Media => $media->get((int) $id))
            ->filter()
            ->map(fn (Media $row): array => [
                'id' => (int) $row->getKey(),
                'name' => $row->name,
                'url' => $row->url(),
                'mime_type' => $row->mime_type,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, mixed>
     */
    #[\Override]
    protected function defaultRules(): array
    {
        if (! $this->multiple) {
            return ['nullable', 'integer', new AttachableMedia];
        }

        return $this->maxFiles === null
            ? ['nullable', 'array']
            : ['nullable', 'array', "max:{$this->maxFiles}"];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    #[\Override]
    public function nestedRules(FormData $data, Request $request): array
    {
        if (! $this->multiple) {
            return [];
        }

        return ["{$this->name()}.*" => ['integer', new AttachableMedia]];
    }

    private function library(): MediaLibrary
    {
        $library = $this->resolvedChildren()[0] ?? null;
        assert($library instanceof MediaLibrary);

        return $library;
    }
}

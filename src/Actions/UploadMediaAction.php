<?php
declare(strict_types=1);

namespace Lattice\Media\Actions;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Lattice\Lattice\Actions\ActionResult;
use Lattice\Lattice\Actions\Components\Action;
use Lattice\Lattice\Actions\FormActionDefinition;
use Lattice\Lattice\Attributes\AsAction;
use Lattice\Lattice\Forms\Components\FileUpload;
use Lattice\Lattice\Forms\Components\Form;
use Lattice\Lattice\Ui\Enums\HttpMethod;
use Lattice\Lattice\Ui\Enums\Variant;
use Lattice\Media\Models\Media;

#[AsAction('media.upload')]
final class UploadMediaAction extends FormActionDefinition
{
    public function definition(Action $action): Action
    {
        return $action
            ->label(__('media::media.actions.upload.label'))
            ->method(HttpMethod::Post);
    }

    #[\Override]
    public function authorize(Request $request): bool
    {
        return Gate::allows('create', Media::class);
    }

    public function formSchema(Form $form, Request $request): Form
    {
        return $form->schema([self::field()]);
    }

    public function handle(Request $request): ActionResult
    {
        $data = $this->validate($request);
        $ids = [];

        foreach ($this->storeUploads($data['files'] ?? []) as $media) {
            $ids[] = $media->getKey();
        }

        return ActionResult::success(['media' => $ids])
            ->toast(__('media::media.actions.upload.toast', ['count' => count($ids)]), Variant::Success)
            ->reloadComponent('media.library');
    }

    public static function field(): FileUpload
    {
        $field = FileUpload::make('files', __('media::media.actions.upload.label'))
            ->multiple()
            ->maxSize((int) config('media.max_size'))
            ->disk((string) config('media.disk'));

        if ((bool) config('media.signed_uploads')) {
            $field->signedUpload();
        }

        $acceptedTypes = array_values(array_filter(array_map(
            strval(...),
            (array) config('media.accepted_types', []),
        )));

        if ($acceptedTypes !== []) {
            $field->acceptedFileTypes($acceptedTypes);
        }

        return $field;
    }

    /**
     * @return list<Media>
     */
    private function storeUploads(mixed $value): array
    {
        $values = is_array($value) ? $value : [$value];
        $field = self::field();
        $stored = [];

        $uploadedFiles = array_values(array_filter($values, fn (mixed $item): bool => $item instanceof UploadedFile));
        $signedKeys = array_values(array_filter($values, fn (mixed $item): bool => is_string($item) && $item !== ''));

        foreach ($uploadedFiles as $file) {
            $path = $file->storeAs(
                'media',
                Str::uuid()->toString().'.'.$file->getClientOriginalExtension(),
                $field->resolveDisk(),
            );

            $stored[] = Media::query()->create([
                'disk' => $field->resolveDisk(),
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }

        if ($signedKeys !== []) {
            foreach ($field->finalizeSignedUploads(
                $signedKeys,
                fn (string $key, array $metadata): string => 'media/'.Str::uuid()->toString()
                    .($metadata['extension'] !== '' ? '.'.$metadata['extension'] : ''),
            ) as $upload) {
                $stored[] = Media::query()->create([
                    'disk' => $upload['disk'],
                    'path' => $upload['path'],
                    'name' => $upload['name'],
                    'mime_type' => $upload['mime_type'] ?? 'application/octet-stream',
                    'size' => $upload['size'] ?? 0,
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }

        return $stored;
    }
}

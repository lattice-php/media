<?php
declare(strict_types=1);

namespace Lattice\Media\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Lattice\Lattice\Actions\ActionDefinition;
use Lattice\Lattice\Actions\ActionResult;
use Lattice\Lattice\Actions\Components\Action;
use Lattice\Lattice\Attributes\AsAction;
use Lattice\Lattice\Ui\Enums\HttpMethod;
use Lattice\Lattice\Ui\Enums\Variant;
use Lattice\Media\Models\Media;

#[AsAction('media.delete')]
final class DeleteMediaAction extends ActionDefinition
{
    public function definition(Action $action): Action
    {
        return $action
            ->label(__('media::media.actions.delete.label'))
            ->method(HttpMethod::Delete)
            ->variant(Variant::Danger)
            ->confirm(
                __('media::media.actions.delete.confirm-title'),
                __('media::media.actions.delete.confirm-description'),
            );
    }

    #[\Override]
    public function authorize(Request $request): bool
    {
        if (! $request->filled('media_id')) {
            return Gate::allows('viewAny', Media::class);
        }

        return Gate::allows('delete', Media::query()->findOrFail($request->integer('media_id')));
    }

    public function handle(Request $request): ActionResult
    {
        Media::query()->findOrFail($request->integer('media_id'))->delete();

        return ActionResult::success()
            ->toast(__('media::media.actions.delete.toast'), Variant::Success)
            ->reloadComponent('media.library');
    }
}

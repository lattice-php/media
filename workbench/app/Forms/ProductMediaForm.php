<?php
declare(strict_types=1);

namespace Workbench\App\Forms;

use Illuminate\Http\Request;
use Lattice\Lattice\Attributes\AsForm;
use Lattice\Lattice\Forms\Components\Form as FormComponent;
use Lattice\Lattice\Forms\Components\TextInput;
use Lattice\Lattice\Forms\FormDefinition;
use Lattice\Media\Forms\Components\MediaPicker;
use Symfony\Component\HttpFoundation\Response;
use Workbench\App\Models\Product;

#[AsForm('workbench.products.media')]
class ProductMediaForm extends FormDefinition
{
    public function definition(FormComponent $form, Request $request): FormComponent
    {
        return $form->schema([
            MediaPicker::make('gallery', __('workbench.forms.product-media.fields.gallery'))
                ->multiple()
                ->attachmentFields([
                    TextInput::make('caption', __('workbench.forms.product-media.fields.caption')),
                ])
                ->helperText(__('workbench.forms.product-media.fields.gallery-help-text')),
        ]);
    }

    public function handle(Request $request): Response
    {
        $validated = $this->validate($request);

        $this->product()->syncMedia($validated['gallery'] ?? [], 'gallery');

        return redirect('/media-picker');
    }

    private function product(): Product
    {
        return Product::query()->findOrFail($this->context('product_id'));
    }
}

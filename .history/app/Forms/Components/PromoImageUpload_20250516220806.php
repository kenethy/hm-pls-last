<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class PromoImageUpload extends Field
{
    protected string $view = 'components.forms.promo-image-upload';

    // Store the current image path
    protected $imagePath = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerListeners([
            'promo-image-uploaded' => [
                function (PromoImageUpload $component, array $data): void {
                    // Get the component's state path as a string
                    $componentStatePath = $component->getStatePath();

                    // Only update if this is the target component
                    if ($data['statePath'] === $componentStatePath) {
                        $component->state($data['path']);
                    }
                },
            ],
            'promo-image-removed' => [
                function (PromoImageUpload $component, array $data): void {
                    // Get the component's state path as a string
                    $componentStatePath = $component->getStatePath();

                    // Only update if this is the target component
                    if ($data['statePath'] === $componentStatePath) {
                        $component->state(null);
                    }
                },
            ],
        ]);
    }

    public function imagePath($imagePath): static
    {
        $this->extraAttributes(['imagePath' => $imagePath]);

        return $this;
    }
}

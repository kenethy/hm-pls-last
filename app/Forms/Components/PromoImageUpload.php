<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;
use Illuminate\Support\Str;

class PromoImageUpload extends Field
{
    protected string $view = 'components.forms.promo-image-upload';
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->afterStateHydrated(function (PromoImageUpload $component, $state): void {
            // Pass the current value to the Livewire component
            $component->imagePath = $state;
        });
        
        $this->registerListeners([
            'promo-image-uploaded' => [
                function (PromoImageUpload $component, array $data): void {
                    // Only update if this is the target component
                    if ($data['statePath'] === $component->getStatePath()) {
                        $component->state($data['path']);
                    }
                },
            ],
            'promo-image-removed' => [
                function (PromoImageUpload $component, array $data): void {
                    // Only update if this is the target component
                    if ($data['statePath'] === $component->getStatePath()) {
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

<?php

namespace App\Livewire\Promo;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class PromoImageUploader extends Component
{
    use WithFileUploads;

    public $image;
    public $imagePath = null;
    public $imageUrl = null;
    public $isUploading = false;
    public $uploadComplete = false;

    // For Filament integration
    public $formComponent;
    public $statePath;
    public $statePathString;

    // In Livewire 3, we use the #[On] attribute instead of $listeners
    #[\Livewire\Attributes\On('refreshPromoImage')]
    public function refreshImage()
    {
        // This method can be called to refresh the component state
        if ($this->imagePath) {
            $this->imageUrl = Storage::url($this->imagePath);
        }
    }

    public function mount($statePath = null, $imagePath = null)
    {
        // Handle statePath - could be a Closure or a string
        if ($statePath instanceof \Closure) {
            // Store both the original Closure and the evaluated string
            $this->statePath = $statePath;
            $this->statePathString = $statePath();
        } else {
            $this->statePath = $statePath;
            $this->statePathString = $statePath;
        }

        $this->imagePath = $imagePath;

        if ($this->imagePath) {
            $this->imageUrl = Storage::url($this->imagePath);
        }
    }

    public function updatedImage()
    {
        $this->validate([
            'image' => 'image|max:5120', // 5MB max
        ]);

        $this->uploadImage();
    }

    public function uploadImage()
    {
        if (!$this->image) {
            return;
        }

        $this->isUploading = true;

        try {
            // Generate a unique filename
            $filename = Str::random(20) . '.' . $this->image->getClientOriginalExtension();

            // Store the file in the public disk under the promos folder
            $path = $this->image->storeAs('promos', $filename, 'public');

            // Update component state
            $this->imagePath = $path;
            $this->imageUrl = Storage::url($path);
            $this->uploadComplete = true;

            // Dispatch event to update Filament form
            $this->dispatch('promo-image-uploaded', [
                'path' => $path,
                'statePath' => $this->statePath
            ]);

            // Reset the file input
            $this->image = null;
        } catch (\Exception $e) {
            // Log the error
            logger()->error('Promo image upload error: ' . $e->getMessage());

            // Dispatch error event
            $this->dispatch('promo-upload-error', [
                'message' => 'Failed to upload image: ' . $e->getMessage()
            ]);
        }

        $this->isUploading = false;
    }

    public function removeImage()
    {
        // Don't actually delete the file from storage, just remove it from the form
        $this->imagePath = null;
        $this->imageUrl = null;
        $this->uploadComplete = false;

        // Dispatch event to update Filament form
        $this->dispatch('promo-image-removed', [
            'statePath' => $this->statePath
        ]);
    }

    // This method was moved up with the #[On] attribute

    public function render()
    {
        return view('livewire.promo.promo-image-uploader');
    }
}

<?php

namespace App\Livewire\Promo;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;

class PromoImageUploader extends Component
{
    use WithFileUploads;

    #[Rule('image|max:5120')] // 5MB max
    public $image;

    public $imagePath = null;
    public $imageUrl = null;
    public $uploadSuccessful = false;
    public $isUploading = false;

    // For Filament integration
    public $statePath;
    public $statePathString;

    public function mount($statePath = null, $imagePath = null)
    {
        $this->statePath = $statePath;

        // Convert Closure to string if needed
        if ($statePath instanceof \Closure) {
            $this->statePathString = $statePath();
        } else {
            $this->statePathString = $statePath;
        }

        $this->imagePath = $imagePath;

        if ($this->imagePath) {
            $this->imageUrl = Storage::url($this->imagePath);
        }
    }

    public function uploadImage()
    {
        $this->validate([
            'image' => 'required|image|max:5120', // 5MB max
        ]);

        $this->isUploading = true;

        try {
            // Generate a unique filename
            $filename = Str::random(20) . '.' . $this->image->getClientOriginalExtension();

            // Store the file in the public disk under the promos folder
            $path = $this->image->storeAs('promos', $filename, 'public');

            // Set success state
            $this->uploadSuccessful = true;
            $this->imagePath = $path;
            $this->imageUrl = Storage::url($path);

            // Dispatch browser event for notification
            $this->dispatch('promo-image-uploaded', [
                'path' => $path,
                'url' => $this->imageUrl
            ]);

            // Update Filament form state if statePath is provided
            if ($this->statePath) {
                $this->dispatch('set-file-upload', [
                    'statePath' => $this->statePath,
                    'value' => $path,
                ]);
            }
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
        if ($this->imagePath) {
            // Don't actually delete the file, just remove it from the form
            $this->imagePath = null;
            $this->imageUrl = null;
            $this->uploadSuccessful = false;

            // Update Filament form state if statePath is provided
            if ($this->statePath) {
                $this->dispatch('set-file-upload', [
                    'statePath' => $this->statePath,
                    'value' => null,
                ]);
            }
        }
    }

    /**
     * Get a unique ID for this component instance
     * This is used to create unique IDs for form elements
     */
    public function getId()
    {
        // Use the Livewire component ID if available, or generate a random one
        return $this->id ?? 'promo-image-' . uniqid();
    }

    public function render()
    {
        return view('livewire.promo.promo-image-uploader');
    }
}

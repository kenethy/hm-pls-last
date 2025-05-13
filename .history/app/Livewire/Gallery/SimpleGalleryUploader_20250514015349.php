<?php

namespace App\Livewire\Gallery;

use App\Models\Gallery;
use App\Models\GalleryCategory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;

class SimpleGalleryUploader extends Component
{
    use WithFileUploads;

    // Single upload properties
    #[Rule('image|max:5120')] // 5MB max
    public $image;

    // Multiple upload properties
    #[Rule('array')]
    public $images = [];

    // Form fields
    public $category_id;
    public $title_prefix = '';
    public $is_featured = false;

    // UI state
    public $uploadSuccessful = false;
    public $uploadedImagePath = null;
    public $uploadedImageUrl = null;
    public $isUploading = false;
    public $categories = [];

    /**
     * Initialize the component
     */
    public function mount()
    {
        $this->loadCategories();
    }

    /**
     * Load categories from the database
     */
    public function loadCategories()
    {
        try {
            $this->categories = GalleryCategory::orderBy('order')->pluck('name', 'id')->toArray();
        } catch (\Exception) {
            // Ignore the exception and just set empty categories
            $this->categories = [];
        }
    }

    /**
     * Upload a single image
     */
    public function uploadImage()
    {
        $this->validate([
            'image' => 'required|image|max:5120', // 5MB max
            'category_id' => 'nullable|exists:gallery_categories,id',
        ]);

        $this->isUploading = true;

        try {
            // Generate a unique filename
            $filename = Str::random(20) . '.' . $this->image->getClientOriginalExtension();

            // Store the file in the public disk under the galleries folder
            $path = $this->image->storeAs('galleries', $filename, 'public');

            // Create a title if not provided
            $title = $this->title_prefix ?: 'Gallery Image ' . date('Y-m-d H:i:s');

            // Create a new gallery record
            Gallery::create([
                'title' => $title,
                'slug' => Str::slug($title),
                'image_path' => $path,
                'category_id' => $this->category_id,
                'is_featured' => $this->is_featured,
            ]);

            // Set success state
            $this->uploadSuccessful = true;
            $this->uploadedImagePath = $path;
            $this->uploadedImageUrl = Storage::url($path);

            // Reset form
            $this->reset(['image', 'title_prefix']);

            // Dispatch browser event for notification
            $this->dispatch('gallery-image-uploaded', [
                'path' => $path,
                'url' => Storage::url($path)
            ]);

            // Emit event to refresh gallery manager
            $this->dispatch('refreshGallery');
        } catch (\Exception $e) {
            // Log the error
            logger()->error('Gallery upload error: ' . $e->getMessage());

            // Dispatch error event
            $this->dispatch('gallery-upload-error', [
                'message' => 'Failed to upload image: ' . $e->getMessage()
            ]);
        }

        $this->isUploading = false;
    }

    /**
     * Upload multiple images
     */
    public function uploadMultipleImages()
    {
        $this->validate([
            'images' => 'required|array',
            'images.*' => 'image|max:5120', // 5MB max per image
            'category_id' => 'nullable|exists:gallery_categories,id',
        ]);

        $this->isUploading = true;

        try {
            $count = 0;
            $paths = [];
            $urls = [];

            foreach ($this->images as $index => $image) {
                // Generate a unique filename
                $filename = Str::random(20) . '.' . $image->getClientOriginalExtension();

                // Store the file
                $path = $image->storeAs('galleries', $filename, 'public');

                // Create a title
                $title = $this->title_prefix
                    ? $this->title_prefix . ' ' . ($index + 1)
                    : 'Gallery Image ' . date('Y-m-d H:i:s') . ' ' . ($index + 1);

                // Create a new gallery record
                Gallery::create([
                    'title' => $title,
                    'slug' => Str::slug($title),
                    'image_path' => $path,
                    'category_id' => $this->category_id,
                    'is_featured' => $this->is_featured,
                ]);

                $paths[] = $path;
                $urls[] = Storage::url($path);
                $count++;
            }

            // Reset form
            $this->reset(['images', 'title_prefix']);

            // Dispatch browser event for notification
            $this->dispatch('gallery-images-uploaded', [
                'count' => $count,
                'paths' => $paths,
                'urls' => $urls
            ]);

            // Emit event to refresh gallery manager
            $this->dispatch('refreshGallery');
        } catch (\Exception $e) {
            // Log the error
            logger()->error('Gallery multiple upload error: ' . $e->getMessage());

            // Dispatch error event
            $this->dispatch('gallery-upload-error', [
                'message' => 'Failed to upload images: ' . $e->getMessage()
            ]);
        }

        $this->isUploading = false;
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.gallery.simple-gallery-uploader');
    }
}

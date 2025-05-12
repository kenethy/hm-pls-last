<?php

namespace App\Livewire\Gallery;

use App\Models\Gallery;
use App\Models\GalleryCategory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;

class GalleryUploader extends Component
{
    use WithFileUploads;

    // Single upload properties
    #[Rule('image|max:5120')] // 5MB max
    public $image;
    
    // Multiple upload properties
    #[Rule('array')]
    #[Rule('image|max:5120', 'images.*')]
    public $images = [];
    
    // Form fields
    public $category_id;
    public $title_prefix = '';
    public $is_featured = false;
    
    // UI state
    public $uploadSuccessful = false;
    public $uploadedImagePath = null;
    public $uploadedImageUrl = null;
    public $uploadedImagePaths = [];
    public $uploadedImageUrls = [];
    public $isUploading = false;
    public $uploadProgress = 0;
    
    public function mount()
    {
        // Get categories for the dropdown
        $this->categories = GalleryCategory::orderBy('order')->pluck('name', 'id')->toArray();
    }
    
    public function render()
    {
        return view('livewire.gallery.gallery-uploader');
    }
    
    /**
     * Handle single image upload
     */
    public function uploadImage()
    {
        $this->validate([
            'image' => 'required|image|max:5120', // 5MB max
        ]);
        
        $this->isUploading = true;
        
        try {
            // Generate a unique filename
            $originalName = pathinfo($this->image->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $this->image->getClientOriginalExtension();
            $filename = Str::slug($originalName) . '-' . Str::random(10) . '.' . $extension;
            
            // Store the file
            $path = $this->image->storeAs('galleries', $filename, 'public');
            
            $this->uploadedImagePath = $path;
            $this->uploadedImageUrl = Storage::url($path);
            $this->uploadSuccessful = true;
            
            // Reset the file input
            $this->reset('image');
            
            $this->dispatch('image-uploaded', [
                'path' => $path,
                'url' => Storage::url($path)
            ]);
        } catch (\Exception $e) {
            $this->addError('image', 'Upload failed: ' . $e->getMessage());
        } finally {
            $this->isUploading = false;
        }
    }
    
    /**
     * Handle multiple image upload
     */
    public function uploadMultipleImages()
    {
        $this->validate([
            'images.*' => 'required|image|max:5120', // 5MB max per file
            'category_id' => 'required|exists:gallery_categories,id',
            'title_prefix' => 'required|string|max:255',
        ]);
        
        $this->isUploading = true;
        $this->uploadProgress = 0;
        
        try {
            $paths = [];
            $urls = [];
            $count = 0;
            $totalFiles = count($this->images);
            
            foreach ($this->images as $image) {
                // Generate a unique filename
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $image->getClientOriginalExtension();
                $filename = Str::slug($originalName) . '-' . Str::random(10) . '.' . $extension;
                
                // Store the file
                $path = $image->storeAs('galleries', $filename, 'public');
                
                $paths[] = $path;
                $urls[] = Storage::url($path);
                
                // Create gallery entry
                Gallery::create([
                    'title' => $this->title_prefix . ' ' . (++$count),
                    'description' => 'Foto ' . $this->title_prefix . ' ' . $count . ' - Kategori: ' . GalleryCategory::find($this->category_id)->name,
                    'image_path' => $path,
                    'category_id' => $this->category_id,
                    'is_featured' => $this->is_featured,
                    'order' => Gallery::where('category_id', $this->category_id)->count() + 1,
                    'slug' => Str::slug($this->title_prefix . ' ' . $count),
                ]);
                
                // Update progress
                $this->uploadProgress = ($count / $totalFiles) * 100;
            }
            
            $this->uploadedImagePaths = $paths;
            $this->uploadedImageUrls = $urls;
            $this->uploadSuccessful = true;
            
            // Reset the form
            $this->reset(['images', 'title_prefix']);
            
            $this->dispatch('images-uploaded', [
                'count' => $count,
                'paths' => $paths,
                'urls' => $urls
            ]);
        } catch (\Exception $e) {
            $this->addError('images', 'Upload failed: ' . $e->getMessage());
        } finally {
            $this->isUploading = false;
            $this->uploadProgress = 100;
        }
    }
}

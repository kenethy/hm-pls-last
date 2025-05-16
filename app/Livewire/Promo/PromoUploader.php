<?php

namespace App\Livewire\Promo;

use App\Models\Promo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;
use Carbon\Carbon;

class PromoUploader extends Component
{
    use WithFileUploads;

    // Single upload properties
    #[Rule('image|max:5120')] // 5MB max
    public $image;
    
    // Form fields
    public $title = '';
    public $description = '';
    public $original_price = null;
    public $promo_price = null;
    public $discount_percentage = null;
    public $start_date = null;
    public $end_date = null;
    public $is_featured = false;
    public $is_active = true;
    public $promo_code = '';
    public $remaining_slots = null;
    
    // UI state
    public $uploadSuccessful = false;
    public $uploadedImagePath = null;
    public $uploadedImageUrl = null;
    public $isUploading = false;
    
    /**
     * Upload a promo image and create a promo record
     */
    public function uploadPromo()
    {
        $this->validate([
            'image' => 'required|image|max:5120', // 5MB max
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'original_price' => 'nullable|numeric|min:0',
            'promo_price' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'promo_code' => 'nullable|string|max:50',
            'remaining_slots' => 'nullable|integer|min:0',
        ]);
        
        $this->isUploading = true;
        
        try {
            // Generate a unique filename
            $filename = Str::random(20) . '.' . $this->image->getClientOriginalExtension();
            
            // Store the file in the public disk under the promos folder
            $path = $this->image->storeAs('promos', $filename, 'public');
            
            // Create a slug from the title
            $slug = Str::slug($this->title);
            
            // Format dates if provided
            $startDate = $this->start_date ? Carbon::parse($this->start_date) : null;
            $endDate = $this->end_date ? Carbon::parse($this->end_date) : null;
            
            // Create a new promo record
            $promo = Promo::create([
                'title' => $this->title,
                'slug' => $slug,
                'description' => $this->description,
                'image_path' => $path,
                'original_price' => $this->original_price,
                'promo_price' => $this->promo_price,
                'discount_percentage' => $this->discount_percentage,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_featured' => $this->is_featured,
                'is_active' => $this->is_active,
                'promo_code' => $this->promo_code,
                'remaining_slots' => $this->remaining_slots,
            ]);
            
            // Set success state
            $this->uploadSuccessful = true;
            $this->uploadedImagePath = $path;
            $this->uploadedImageUrl = Storage::url($path);
            
            // Reset form
            $this->reset([
                'image', 'title', 'description', 'original_price', 
                'promo_price', 'discount_percentage', 'start_date', 
                'end_date', 'is_featured', 'promo_code', 'remaining_slots'
            ]);
            $this->is_active = true;
            
            // Dispatch browser event for notification
            $this->dispatch('promo-uploaded', [
                'path' => $path,
                'url' => Storage::url($path),
                'id' => $promo->id
            ]);
            
            // Emit event to refresh promo list
            $this->dispatch('refreshPromos');
            
        } catch (\Exception $e) {
            // Log the error
            logger()->error('Promo upload error: ' . $e->getMessage());
            
            // Dispatch error event
            $this->dispatch('promo-upload-error', [
                'message' => 'Failed to upload promo: ' . $e->getMessage()
            ]);
        }
        
        $this->isUploading = false;
    }
    
    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.promo.promo-uploader');
    }
}

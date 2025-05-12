<?php

namespace App\Livewire\Gallery;

use App\Models\Gallery;
use App\Models\GalleryCategory;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class GalleryManager extends Component
{
    use WithPagination;
    
    // Filters
    public $search = '';
    public $category = '';
    public $featured = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    
    // Batch actions
    public $selected = [];
    public $selectAll = false;
    
    // Listeners for events from other components
    protected $listeners = [
        'image-uploaded' => 'handleImageUploaded',
        'images-uploaded' => 'handleImagesUploaded',
        'refreshGallery' => '$refresh'
    ];
    
    public function mount()
    {
        // Get categories for the filter dropdown
        $this->categories = GalleryCategory::orderBy('order')->pluck('name', 'id')->toArray();
    }
    
    public function render()
    {
        $query = Gallery::query()
            ->when($this->search, function ($query) {
                return $query->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->when($this->category, function ($query) {
                return $query->where('category_id', $this->category);
            })
            ->when($this->featured !== '', function ($query) {
                return $query->where('is_featured', $this->featured === 'yes');
            })
            ->orderBy($this->sortField, $this->sortDirection);
        
        $galleries = $query->paginate(12);
        
        return view('livewire.gallery.gallery-manager', [
            'galleries' => $galleries
        ]);
    }
    
    /**
     * Sort the gallery items
     */
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    
    /**
     * Reset all filters
     */
    public function resetFilters()
    {
        $this->reset(['search', 'category', 'featured']);
    }
    
    /**
     * Toggle featured status for a gallery item
     */
    public function toggleFeatured($id)
    {
        $gallery = Gallery::findOrFail($id);
        $gallery->is_featured = !$gallery->is_featured;
        $gallery->save();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Status unggulan berhasil diubah'
        ]);
    }
    
    /**
     * Delete a gallery item
     */
    public function delete($id)
    {
        $gallery = Gallery::findOrFail($id);
        
        // Delete the image file if it exists
        if ($gallery->image_path && Storage::disk('public')->exists($gallery->image_path)) {
            Storage::disk('public')->delete($gallery->image_path);
        }
        
        $gallery->delete();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Foto berhasil dihapus'
        ]);
    }
    
    /**
     * Toggle select all items
     */
    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selected = Gallery::pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
    }
    
    /**
     * Delete selected gallery items
     */
    public function deleteSelected()
    {
        $galleries = Gallery::whereIn('id', $this->selected)->get();
        
        foreach ($galleries as $gallery) {
            // Delete the image file if it exists
            if ($gallery->image_path && Storage::disk('public')->exists($gallery->image_path)) {
                Storage::disk('public')->delete($gallery->image_path);
            }
            
            $gallery->delete();
        }
        
        $this->selected = [];
        $this->selectAll = false;
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => count($galleries) . ' foto berhasil dihapus'
        ]);
    }
    
    /**
     * Set featured status for selected gallery items
     */
    public function setFeaturedForSelected($featured)
    {
        Gallery::whereIn('id', $this->selected)->update(['is_featured' => $featured]);
        
        $this->selected = [];
        $this->selectAll = false;
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Status unggulan berhasil diubah untuk ' . count($this->selected) . ' foto'
        ]);
    }
    
    /**
     * Handle single image upload event
     */
    public function handleImageUploaded($data)
    {
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Gambar berhasil diupload'
        ]);
    }
    
    /**
     * Handle multiple images upload event
     */
    public function handleImagesUploaded($data)
    {
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $data['count'] . ' gambar berhasil diupload'
        ]);
    }
}

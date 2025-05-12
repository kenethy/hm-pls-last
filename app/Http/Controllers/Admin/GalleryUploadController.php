<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class GalleryUploadController extends Controller
{
    /**
     * Handle file upload request
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function upload(Request $request)
    {
        // Validasi file
        $request->validate([
            'image' => 'required|image|max:5120', // 5MB max
        ]);
        
        // Simpan file
        $file = $request->file('image');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $filename = Str::slug($originalName) . '-' . Str::random(10) . '.' . $extension;
        
        // Simpan file ke storage/app/public/galleries
        $path = $file->storeAs('galleries', $filename, 'public');
        
        // Kembalikan response dengan path file
        return redirect()->back()->with([
            'upload_success' => true,
            'image_path' => $path,
            'image_url' => Storage::url($path),
        ]);
    }
    
    /**
     * Handle multiple file uploads
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function uploadMultiple(Request $request)
    {
        // Validasi files
        $request->validate([
            'images.*' => 'required|image|max:5120', // 5MB max per file
        ]);

        $paths = [];
        $urls = [];
        
        // Proses setiap file
        foreach ($request->file('images') as $file) {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $filename = Str::slug($originalName) . '-' . Str::random(10) . '.' . $extension;
            
            // Simpan file ke storage/app/public/galleries
            $path = $file->storeAs('galleries', $filename, 'public');
            
            $paths[] = $path;
            $urls[] = Storage::url($path);
        }
        
        // Kembalikan response dengan path files
        return redirect()->back()->with([
            'upload_success' => true,
            'image_paths' => $paths,
            'image_urls' => $urls,
        ]);
    }
}

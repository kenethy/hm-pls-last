<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CustomFileUploadController extends Controller
{
    /**
     * Handle file upload request
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        // Pastikan user sudah login
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Validasi file
        $request->validate([
            'file' => 'required|file|max:12288', // 12MB max
        ]);

        // Simpan file
        $file = $request->file('file');
        $directory = $request->input('directory', 'uploads');
        
        // Gunakan nama asli file dengan tambahan random string untuk menghindari konflik
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $filename = Str::slug($originalName) . '-' . Str::random(10) . '.' . $extension;
        
        // Simpan file ke storage
        $path = $file->storeAs($directory, $filename, 'public');
        
        // Kembalikan path file
        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => Storage::url($path),
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
        ]);
    }
    
    /**
     * Handle multiple file uploads
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadMultiple(Request $request)
    {
        // Pastikan user sudah login
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Validasi files
        $request->validate([
            'files.*' => 'required|file|max:12288', // 12MB max per file
        ]);

        $results = [];
        $directory = $request->input('directory', 'uploads');
        
        // Proses setiap file
        foreach ($request->file('files') as $file) {
            // Gunakan nama asli file dengan tambahan random string
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $filename = Str::slug($originalName) . '-' . Str::random(10) . '.' . $extension;
            
            // Simpan file ke storage
            $path = $file->storeAs($directory, $filename, 'public');
            
            // Tambahkan info file ke hasil
            $results[] = [
                'path' => $path,
                'url' => Storage::url($path),
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ];
        }
        
        // Kembalikan info semua file
        return response()->json([
            'success' => true,
            'files' => $results,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    /**
     * Handle file uploads for Filament forms
     */
    public function upload(Request $request)
    {
        // Validate the request
        $request->validate([
            'file' => 'required|file|max:5120', // 5MB max
            'directory' => 'nullable|string',
        ]);

        try {
            $file = $request->file('file');
            $directory = $request->input('directory', 'uploads');
            
            // Generate a unique filename
            $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
            
            // Store the file
            $path = $file->storeAs($directory, $filename, 'public');
            
            // Return the file information
            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => Storage::url($path),
                'filename' => $filename,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}

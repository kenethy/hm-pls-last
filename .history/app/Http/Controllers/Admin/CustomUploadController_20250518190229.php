<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CustomUploadController extends Controller
{
    /**
     * Handle file uploads redirected from Livewire
     *
     * This controller handles file uploads that are intercepted by custom-upload.js
     * and redirected to the /custom-upload endpoint.
     */
    public function handle(Request $request)
    {
        // Check if this is a Livewire upload
        $isLivewireUpload = $request->hasHeader('X-Livewire') ||
            $request->has('_token') ||
            $request->has('X-Livewire-File');

        if ($isLivewireUpload) {
            return $this->handleLivewireUpload($request);
        }

        // Handle regular file upload
        return $this->handleRegularUpload($request);
    }

    /**
     * Handle Livewire-specific file uploads
     */
    protected function handleLivewireUpload(Request $request)
    {
        try {
            // Log the request for debugging
            Log::info('Livewire upload request', [
                'files' => $request->hasFile('files'),
                'file' => $request->hasFile('file'),
                'allFiles' => array_keys($request->allFiles()),
                'referer' => $request->header('Referer'),
                'content-type' => $request->header('Content-Type'),
            ]);

            // Extract file from the request
            $file = null;

            if ($request->hasFile('files')) {
                $file = $request->file('files');
            } elseif ($request->hasFile('file')) {
                $file = $request->file('file');
            } else {
                // Try to find any uploaded file
                foreach ($request->allFiles() as $key => $uploadedFile) {
                    Log::info('Found file in request', ['key' => $key]);
                    $file = $uploadedFile;
                    break;
                }
            }

            if (!$file) {
                \Log::error('No file found in the request', [
                    'request_keys' => array_keys($request->all()),
                    'files_keys' => $request->hasFile('files') ? array_keys($request->file('files')) : 'No files key',
                ]);

                return response()->json([
                    'error' => 'No file found in the request'
                ], 400);
            }

            // Determine the directory
            $directory = $request->input('directory', 'uploads');

            // For specific uploads, ensure we use the correct directory based on the referer
            $referer = $request->header('Referer', '');

            if (strpos($referer, 'promos') !== false) {
                $directory = 'promos';
            } elseif (strpos($referer, 'galleries') !== false || strpos($referer, 'enhanced-galleries') !== false) {
                $directory = 'galleries';
            } elseif (strpos($referer, 'blog-posts') !== false) {
                $directory = 'blog';
            }

            \Log::info('Using directory for upload', ['directory' => $directory, 'referer' => $referer]);

            // Generate a unique filename
            $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();

            // Store the file
            $path = $file->storeAs($directory, $filename, 'public');

            \Log::info('File stored successfully', ['path' => $path]);

            // Format response in Livewire-compatible format
            return response()->json([
                'path' => $path,
                'url' => Storage::url($path),
                'name' => $filename,
                'size' => $file->getSize(),
                'type' => $file->getMimeType(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle regular file uploads
     */
    protected function handleRegularUpload(Request $request)
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

    /**
     * Handle multiple file uploads
     */
    public function handleMultiple(Request $request)
    {
        // Validate the request
        $request->validate([
            'files.*' => 'required|file|max:5120', // 5MB max
            'directory' => 'nullable|string',
        ]);

        try {
            $files = $request->file('files');
            $directory = $request->input('directory', 'uploads');

            $uploadedFiles = [];

            foreach ($files as $file) {
                // Generate a unique filename
                $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();

                // Store the file
                $path = $file->storeAs($directory, $filename, 'public');

                // Add to uploaded files
                $uploadedFiles[] = [
                    'path' => $path,
                    'url' => Storage::url($path),
                    'filename' => $filename,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }

            // Return the file information
            return response()->json([
                'success' => true,
                'files' => $uploadedFiles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}

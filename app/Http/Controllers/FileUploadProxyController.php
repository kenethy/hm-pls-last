<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\FileUploadController;

class FileUploadProxyController extends Controller
{
    /**
     * Handle file upload request
     */
    public function handle(Request $request)
    {
        // Pastikan user sudah login
        if (!Auth::check()) {
            abort(401, 'Unauthorized');
        }

        // Teruskan request ke controller Livewire asli
        $controller = new FileUploadController();
        return $controller->handle();
    }
}

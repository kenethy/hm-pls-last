<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectLivewireUpload
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Jika ini adalah request ke livewire/upload-file
        if ($request->is('livewire/upload-file')) {
            // Jika ini adalah GET request, redirect ke home
            if ($request->isMethod('get')) {
                return redirect('/');
            }
            
            // Jika ini adalah POST request, redirect ke custom upload
            if ($request->isMethod('post')) {
                return redirect()->route('custom.upload');
            }
        }
        
        return $next($request);
    }
}

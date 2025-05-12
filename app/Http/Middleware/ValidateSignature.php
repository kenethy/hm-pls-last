<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ValidateSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $relative
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Routing\Exceptions\InvalidSignatureException
     */
    public function handle($request, Closure $next, $relative = null)
    {
        // For Livewire file uploads, use our custom signature validation
        if (Str::contains($request->path(), 'livewire/upload-file')) {
            if ($this->hasValidLivewireSignature($request)) {
                return $next($request);
            }
            
            throw new InvalidSignatureException;
        }
        
        // For all other signed URLs, use Laravel's default validation
        if ($relative !== null && $relative !== 'relative') {
            $relative = true;
        } else {
            $relative = $relative === 'relative';
        }

        if ($request->hasValidSignature($relative)) {
            return $next($request);
        }

        throw new InvalidSignatureException;
    }
    
    /**
     * Determine if the given request has a valid signature for Livewire file uploads.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function hasValidLivewireSignature(Request $request)
    {
        // Ignore the signature parameter itself when validating
        $ignoreQuery = ['signature'];
        
        // Get the URL without the signature
        $url = $request->url();
        
        // Get the query string without the signature
        $queryString = collect(explode('&', (string) $request->server->get('QUERY_STRING')))
            ->reject(fn ($parameter) => in_array(Str::before($parameter, '='), $ignoreQuery))
            ->join('&');
        
        // Build the original URL
        $original = rtrim($url.'?'.$queryString, '?');
        
        // Generate the signature using the app key
        $signature = hash_hmac('sha256', $original, config('app.key'));
        
        // Check if the signature matches and hasn't expired
        return hash_equals($signature, (string) $request->query('signature', '')) && 
               !$this->signatureHasExpired($request);
    }
    
    /**
     * Determine if the signature from the given request has expired.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function signatureHasExpired(Request $request)
    {
        $expires = $request->query('expires');
        
        return $expires && now()->getTimestamp() > $expires;
    }
}
